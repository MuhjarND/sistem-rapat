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
    // Ambil semua kategori untuk filter
    $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

    // Query join kategori
    $query = DB::table('rapat')
        ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
        ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
        ->select('rapat.*', 'kategori_rapat.nama as nama_kategori', 'pimpinan_rapat.nama as nama_pimpinan', 'pimpinan_rapat.jabatan as jabatan_pimpinan');

    // Jika filter kategori aktif
    if ($request->kategori) {
        $query->where('rapat.id_kategori', $request->kategori);
    }

    $daftar_rapat = $query->orderBy('tanggal', 'desc')->get();

    foreach ($daftar_rapat as $rapat) {
        $rapat->status_label = $this->getStatusRapat($rapat);

        $rapat->peserta_terpilih = DB::table('undangan')
        ->where('id_rapat', $rapat->id)
        ->pluck('id_user')
        ->toArray();
    }

        $daftar_pimpinan = DB::table('pimpinan_rapat')->get();
        $daftar_peserta = DB::table('users')->where('role', 'peserta')->get();

        return view('rapat.index', compact('daftar_rapat', 'daftar_kategori', 'daftar_pimpinan', 'daftar_peserta'));
    }

    // Form tambah rapat
    public function create()
    {
        $daftar_peserta = DB::table('users')->where('role', 'peserta')->get();
        $daftar_pimpinan = DB::table('pimpinan_rapat')->get();
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('rapat.create', compact('daftar_peserta', 'daftar_pimpinan', 'daftar_kategori'));
    }

    // Proses simpan rapat & undangan
public function store(Request $request)
{
    $request->validate([
        'nomor_undangan' => 'required|unique:rapat,nomor_undangan',
        'judul' => 'required',
        'deskripsi' => 'nullable',
        'tanggal' => 'required|date',
        'waktu_mulai' => 'required',
        'tempat' => 'required',
        'id_pimpinan' => 'required|exists:pimpinan_rapat,id',
        'peserta' => 'required|array|min:1',
        'id_kategori' => 'required|exists:kategori_rapat,id'
    ]);

    // Simpan rapat
    $id_rapat = DB::table('rapat')->insertGetId([
        'nomor_undangan' => $request->nomor_undangan,
        'judul' => $request->judul,
        'deskripsi' => $request->deskripsi,
        'tanggal' => $request->tanggal,
        'waktu_mulai' => $request->waktu_mulai,
        'tempat' => $request->tempat,
        'dibuat_oleh' => Auth::id(),
        'id_pimpinan' => $request->id_pimpinan,
        'id_kategori' => $request->id_kategori,
        'token_qr' => Str::random(32    ),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Ambil data rapat & pimpinan untuk lampiran
    $rapat = DB::table('rapat')->where('id', $id_rapat)->first();
    $pimpinan = DB::table('pimpinan_rapat')->where('id', $rapat->id_pimpinan)->first();

foreach ($request->peserta as $id_peserta) {
    // Simpan undangan
    DB::table('undangan')->insert([
        'id_rapat' => $id_rapat,
        'id_user' => $id_peserta,
        'status' => 'terkirim',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $peserta = DB::table('users')->where('id', $id_peserta)->first();

    // --- Kirim WA via Fonnte ---
    if ($peserta && $peserta->no_hp) {
        // Pastikan format nomor HP: 628xxxxxx
        $wa = preg_replace('/^0/', '62', $peserta->no_hp); // dari 0812xxx ke 62812xxx

        $message = "Assalamualaikum wr. wb.\n\n"
            . "*[Undangan Rapat]*\n"
            . "Halo $peserta->name,\n"
            . "Anda diundang pada rapat: _{$rapat->judul}_\n"
            . "Tanggal: {$rapat->tanggal} {$rapat->waktu_mulai}\n"
            . "Tempat: {$rapat->tempat}\n\n"
            . "Silakan login ke aplikasi *Sistem Rapat* untuk melihat detail rapat dan download file undangan PDF Anda.\n"
            . "Terima kasih atas perhatian Bapak/Ibu.\n"
            . "Wassalamualaikum wr. wb.";


        FonnteWa::send($wa, $message);
    }
        // Generate PDF langsung ke memory
        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat' => $rapat,
            'daftar_peserta' => [$peserta], // Bisa diubah jika ingin semua peserta di lampiran
            'pimpinan' => $pimpinan,
            'kop_path' => public_path('Screenshot 2025-08-23 121254.jpeg')
        ])->setPaper('A4', 'portrait');
        $pdfData = $pdf->output();

        // Kirim email (lampiran PDF dari memory, tanpa file disimpan)
        // Mail::to($peserta->email)->queue(
        //     new UndanganRapatMail($rapat->id, $peserta->id)
        // );
    }

    return redirect()->route('rapat.index')->with('success', 'Rapat & Undangan berhasil dibuat. Notifikasi WA sudah dikirim!');
}


    // Detail rapat
public function show($id)
{
    $rapat = DB::table('rapat')
        ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
        ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
        ->select(
            'rapat.*',
            'pimpinan_rapat.nama as nama_pimpinan',
            'pimpinan_rapat.jabatan as jabatan_pimpinan',
            'kategori_rapat.nama as nama_kategori'
        )
        ->where('rapat.id', $id)
        ->first();

    if (!$rapat) abort(404);

    // Ambil peserta undangan rapat ini (opsional, jika dipakai di view)
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

        $daftar_peserta = DB::table('users')->where('role', 'peserta')->get();
        $daftar_pimpinan = DB::table('pimpinan_rapat')->get();
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();
        $peserta_terpilih = DB::table('undangan')->where('id_rapat', $id)->pluck('id_user')->toArray();

        return view('rapat.edit', compact('rapat', 'daftar_peserta', 'peserta_terpilih', 'daftar_pimpinan','daftar_kategori'));
    }

    // Update rapat & undangan
    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor_undangan' => 'required|unique:rapat,nomor_undangan,' . $id,
            'judul' => 'required',
            'deskripsi' => 'nullable',
            'tanggal' => 'required|date',
            'waktu_mulai' => 'required',
            'tempat' => 'required',
            'id_pimpinan' => 'required|exists:pimpinan_rapat,id',
            'peserta' => 'required|array|min:1',
            'id_kategori' => 'required|exists:kategori_rapat,id'
        ]);

        // Update data rapat
        DB::table('rapat')->where('id', $id)->update([
            'nomor_undangan' => $request->nomor_undangan,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'tanggal' => $request->tanggal,
            'waktu_mulai' => $request->waktu_mulai,
            'tempat' => $request->tempat,
            'id_pimpinan' => $request->id_pimpinan,
            'id_kategori' => $request->id_kategori,
            'updated_at' => now(),
        ]);

        // Update undangan peserta:
        DB::table('undangan')->where('id_rapat', $id)->delete();
        foreach ($request->peserta as $id_peserta) {
            DB::table('undangan')->insert([
                'id_rapat' => $id,
                'id_user' => $id_peserta,
                'status' => 'terkirim',
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

    // Export undangan PDF (dengan kop surat gambar & pimpinan otomatis)
    public function undanganPdf($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email','users.jabatan')
            ->get();

        $pimpinan = DB::table('pimpinan_rapat')->where('id', $rapat->id_pimpinan)->first();

        $kop_path = public_path('Screenshot 2025-08-23 121254.jpeg'); // pastikan ada file ini di folder public

        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat' => $rapat,
            'daftar_peserta' => $daftar_peserta,
            'pimpinan' => $pimpinan,
            'kop_path' => $kop_path
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
        $selesai = $mulai->copy()->addHours(2); // Anggap default rapat 2 jam

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
