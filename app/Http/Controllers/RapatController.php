<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Mail\UndanganRapatMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Helpers\FonnteWa;
use iio\libmergepdf\Merger;

class RapatController extends Controller
{
    // Tampilkan daftar rapat
    public function index(Request $request)
    {
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        $query = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->leftJoin('users as appr1', 'rapat.approval1_user_id', '=', 'appr1.id')
            ->leftJoin('users as appr2', 'rapat.approval2_user_id', '=', 'appr2.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pembuat.name as nama_pembuat',
                'appr1.name as approval1_nama',
                'appr2.name as approval2_nama'
            );

        if ($request->kategori) {
            $query->where('rapat.id_kategori', $request->kategori);
        }
        if ($request->tanggal) {
            $query->where('rapat.tanggal', $request->tanggal);
        }
        if ($request->keyword) {
            $query->where(function($q) use ($request) {
                $q->where('rapat.judul', 'like', '%' . $request->keyword . '%')
                  ->orWhere('rapat.nomor_undangan', 'like', '%' . $request->keyword . '%');
            });
        }

        $daftar_rapat = $query
            ->orderBy('tanggal', 'desc')
            ->paginate(6)
            ->appends($request->all());

        foreach ($daftar_rapat as $rapat) {
            $rapat->status_label = $this->getStatusRapat($rapat);
        }

        // Daftar peserta (tetap)
        $daftar_peserta  = DB::table('users')->where('role', 'peserta')->get();

        // List approval
        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->orderBy('name')->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->where('tingkatan', 2)
            ->orderBy('name')->get();

        return view('rapat.index', compact(
            'daftar_rapat',
            'daftar_kategori',
            'daftar_peserta',
            'approval1_list',
            'approval2_list'
        ));
    }

    // Form tambah rapat (kalau pakai halaman terpisah)
    public function create()
    {
        $daftar_peserta = DB::table('users')->where('role', 'peserta')->get();
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderBy('name')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderBy('name')
            ->get();

        return view('rapat.create', compact('daftar_peserta', 'daftar_kategori', 'approval1_list', 'approval2_list'));
    }

    // Proses simpan rapat & undangan
    public function store(Request $request)
    {
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan',
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'approval1_user_id' => 'required|exists:users,id',
            'approval2_user_id' => 'nullable|exists:users,id',
            'peserta'           => 'required|array|min:1',
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        // Simpan rapat
        $id_rapat = DB::table('rapat')->insertGetId([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'dibuat_oleh'       => Auth::id(),
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval2_user_id' => $request->approval2_user_id,
            'token_qr'          => Str::random(32),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Ambil data rapat & approver utk notifikasi/PDF
        $rapat = DB::table('rapat')->where('id', $id_rapat)->first();
        $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approval2 = $rapat->approval2_user_id ? DB::table('users')->where('id', $rapat->approval2_user_id)->first() : null;

        // Insert undangan + kirim notifikasi
        foreach ($request->peserta as $id_peserta) {
            DB::table('undangan')->insert([
                'id_rapat'   => $id_rapat,
                'id_user'    => $id_peserta,
                'status'     => 'terkirim',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $peserta = DB::table('users')->where('id', $id_peserta)->first();

            // WA via Fonnte (jika ada no_hp)
            if ($peserta && $peserta->no_hp) {
                $wa = preg_replace('/^0/', '62', $peserta->no_hp);

                $message = "Assalamualaikum wr. wb.\n\n"
                    . "*[Undangan Rapat]*\n"
                    . "Halo {$peserta->name},\n"
                    . "Anda diundang pada rapat: _{$rapat->judul}_\n"
                    . "Tanggal: {$rapat->tanggal} {$rapat->waktu_mulai}\n"
                    . "Tempat: {$rapat->tempat}\n\n"
                    . "Approval 1: " . ($approval1->name ?? '-') . "\n"
                    . "Approval 2: " . ($approval2->name ?? '-') . "\n\n"
                    . "Silakan login ke aplikasi *Sistem Rapat* untuk melihat detail rapat dan undangan PDF.\n"
                    . "Terima kasih.\n"
                    . "Wassalamualaikum wr. wb.";

                FonnteWa::send($wa, $message);
            }
        }

        // === Generate PDF Undangan ===
        // Ambil seluruh peserta rapat untuk ditampilkan (atau lampiran)
        $daftar_peserta = DB::table('undangan')
            ->join('users','undangan.id_user','=','users.id')
            ->where('undangan.id_rapat', $id_rapat)
            ->select('users.name','users.email','users.jabatan')
            ->get();

        // Aturan tampilan lampiran/daftar
        $tampilkan_lampiran = $daftar_peserta->count() > 5;     // lampiran hanya jika > 5
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;      // daftar di badan surat jika ≤ 5

        // (Jika kamu perlu menyimpan/attach PDF, di sini variabel $pdfData sudah siap)
        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat'                      => $rapat,
            'daftar_peserta'             => $daftar_peserta,
            'approval1'                  => $approval1,
            'approval2'                  => $approval2,
            'kop_path'                   => public_path('Screenshot 2025-08-23 121254.jpeg'),
            'tampilkan_lampiran'         => $tampilkan_lampiran,
            'tampilkan_daftar_di_surat'  => $tampilkan_daftar_di_surat,
        ])->setPaper('A4', 'portrait');
        $pdfData = $pdf->output();
        // Mail::to(...)->queue(new UndanganRapatMail(...)) // jika ingin email

        return redirect()->route('rapat.index')->with('success', 'Rapat & Undangan berhasil dibuat. Notifikasi WA sudah dikirim!');
    }

    // Detail rapat
    public function show($id)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('users as a1', 'rapat.approval1_user_id', '=', 'a1.id')
            ->leftJoin('users as a2', 'rapat.approval2_user_id', '=', 'a2.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'a1.name as approval1_nama',
                'a2.name as approval2_nama'
            )
            ->where('rapat.id', $id)
            ->first();

        if (!$rapat) abort(404);

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email', 'users.jabatan')
            ->get();

        return view('rapat.show', compact('rapat', 'daftar_peserta'));
    }

    // Form edit rapat
    public function edit($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        $daftar_peserta   = DB::table('users')->where('role', 'peserta')->get();
        $daftar_kategori  = DB::table('kategori_rapat')->orderBy('nama')->get();
        $peserta_terpilih = DB::table('undangan')->where('id_rapat', $id)->pluck('id_user')->toArray();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderBy('name')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderBy('name')
            ->get();

        return view('rapat.edit', compact(
            'rapat', 'daftar_peserta', 'peserta_terpilih',
            'daftar_kategori', 'approval1_list', 'approval2_list'
        ));
    }

    // Update rapat & undangan
    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan,' . $id,
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'approval1_user_id' => 'required|exists:users,id',
            'approval2_user_id' => 'nullable|exists:users,id',
            'peserta'           => 'required|array|min:1',
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        DB::table('rapat')->where('id', $id)->update([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval2_user_id' => $request->approval2_user_id,
            'updated_at'        => now(),
        ]);

        // Reset & simpan ulang undangan peserta:
        DB::table('undangan')->where('id_rapat', $id)->delete();
        foreach ($request->peserta as $id_peserta) {
            DB::table('undangan')->insert([
                'id_rapat'   => $id,
                'id_user'    => $id_peserta,
                'status'     => 'terkirim',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('rapat.index')->with('success', 'Rapat dan undangan berhasil diupdate!');
    }

    // Hapus rapat & undangan terkait
    public function destroy($id)
    {
        DB::table('undangan')->where('id_rapat', $id)->delete();
        DB::table('rapat')->where('id', $id)->delete();
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dihapus!');
    }

    // Export undangan PDF (pakai approval)
    public function undanganPdf($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email','users.jabatan')
            ->get();

        $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approval2 = $rapat->approval2_user_id ? DB::table('users')->where('id', $rapat->approval2_user_id)->first() : null;

        $kop_path = public_path('Screenshot 2025-08-23 121254.jpeg');

        // Flag tampilan
        $tampilkan_lampiran = $daftar_peserta->count() > 5;    // lampiran hanya jika > 5
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;     // daftar di badan surat jika ≤ 5

        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat'                      => $rapat,
            'daftar_peserta'             => $daftar_peserta,
            'approval1'                  => $approval1,
            'approval2'                  => $approval2,
            'kop_path'                   => $kop_path,
            'tampilkan_lampiran'         => $tampilkan_lampiran,
            'tampilkan_daftar_di_surat'  => $tampilkan_daftar_di_surat,
        ])->setPaper('A4', 'portrait');

        $filename = 'Undangan-Rapat-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
        return $pdf->download($filename);
    }

    private function getStatusRapat($rapat)
    {
        if ($rapat->status === 'dibatalkan') {
            return 'Dibatalkan';
        }

        $now = Carbon::now('Asia/Jayapura');
        $mulai = Carbon::parse($rapat->tanggal . ' ' . $rapat->waktu_mulai, 'Asia/Jayapura');
        $selesai = $mulai->copy()->addHours(2); // default 2 jam

        if ($now->lt($mulai)) {
            return 'Akan Datang';
        } elseif ($now->between($mulai, $selesai)) {
            return 'Berlangsung';
        } elseif ($now->gt($selesai)) {
            return 'Selesai';
        }
        return 'Akan Datang';
    }

    public function batalkan($id)
    {
        DB::table('rapat')->where('id', $id)->update(['status' => 'dibatalkan', 'updated_at' => now()]);
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dibatalkan!');
    }
}
