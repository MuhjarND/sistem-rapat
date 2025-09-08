<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use iio\libmergepdf\Merger;
use Illuminate\Support\Str;

class NotulensiController extends Controller
{
    /**
     * Opsional: arahkan index ke daftar "belum" (atau bisa ke "sudah" sesuai preferensi)
     */
    public function index()
    {
        return redirect()->route('notulensi.belum');
    }

    /**
     * Helper: base query rapat + join (dipakai oleh belum() & sudah()).
     */
    protected function baseRapatQuery()
    {
        return DB::table('rapat')
            ->leftJoin('notulensi', 'notulensi.id_rapat', '=', 'rapat.id')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'notulensi.id as id_notulensi',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            );
    }

    /**
     * Helper: terapkan filter (kategori, tanggal, keyword)
     */
    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('kategori')) {
            $query->where('rapat.id_kategori', $request->kategori);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('rapat.tanggal', $request->tanggal);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('rapat.judul', 'like', "%{$keyword}%")
                  ->orWhere('rapat.nomor_undangan', 'like', "%{$keyword}%")
                  ->orWhere('rapat.tempat', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    /**
     * Rapat yang BELUM memiliki notulensi.
     */
    public function belum(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_belum = $query->orderBy('rapat.tanggal', 'desc')
            ->paginate(6);

        // Laravel 7: pakai appends agar query filter ikut di pagination
        $rapat_belum->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.belum', compact('rapat_belum', 'daftar_kategori'));
    }

    /**
     * Rapat yang SUDAH memiliki notulensi.
     */
    public function sudah(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNotNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_sudah = $query->orderBy('rapat.tanggal', 'desc')
            ->paginate(6);

        // Laravel 7: pakai appends agar query filter ikut di pagination
        $rapat_sudah->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.sudah', compact('rapat_sudah', 'daftar_kategori'));
    }

    // ===== CREATE: form create notulensi untuk rapat tertentu =====
    public function create($id_rapat)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'pimpinan_rapat.nama as nama_pimpinan'
            )
            ->where('rapat.id', $id_rapat)
            ->first();

        if (!$rapat) abort(404);

        // Cegah duplikasi
        if (DB::table('notulensi')->where('id_rapat', $id_rapat)->exists()) {
            return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah dibuat.');
        }

        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $id_rapat)->count();

        Carbon::setLocale('id');
        $hari_tanggal = Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $jam = $rapat->waktu_mulai;

        return view('notulensi.create', compact('rapat','jumlah_peserta','hari_tanggal','jam'));
    }

    // ===== STORE: simpan notulensi + detail baris + dokumentasi =====
    public function store(Request $request)
    {
        $request->validate([
            'id_rapat'                      => 'required|exists:rapat,id',

            // Section 2 (detail pembahasan)
            'baris'                         => 'required|array|min:1',
            'baris.*.hasil_pembahasan'      => 'required|string',
            'baris.*.rekomendasi'           => 'nullable|string',
            'baris.*.penanggung_jawab'      => 'nullable|string|max:150',
            'baris.*.tgl_penyelesaian'      => 'nullable|date',

            // Section 3 (foto dokumentasi)
            'dokumentasi'                   => 'required',
            'dokumentasi.*'                 => 'image|max:10240', // 10MB
        ], [
            'dokumentasi.required' => 'Minimal unggah 3 foto dokumentasi.',
        ]);

        // Satu notulensi per rapat
        if (DB::table('notulensi')->where('id_rapat', $request->id_rapat)->exists()) {
            return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah ada.');
        }

        // simpan header notulensi (pakai id_user pembuat)
        $id_notulensi = DB::table('notulensi')->insertGetId([
            'id_rapat'   => $request->id_rapat,
            'id_user'    => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // simpan detail baris
        $urut = 1; $rows = [];
        foreach ($request->baris as $r) {
            $rows[] = [
                'id_notulensi'     => $id_notulensi,
                'urut'             => $urut++,
                'hasil_pembahasan' => $r['hasil_pembahasan'],
                'rekomendasi'      => $r['rekomendasi'] ?? null,
                'penanggung_jawab' => $r['penanggung_jawab'] ?? null,
                'tgl_penyelesaian' => $r['tgl_penyelesaian'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }
        if (!empty($rows)) {
            DB::table('notulensi_detail')->insert($rows);
        }

        // simpan dokumentasi (multiple files)
        if ($request->hasFile('dokumentasi')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) {
                mkdir($dest, 0775, true);
            }

            foreach ($request->file('dokumentasi') as $file) {
                if (!$file || !$file->isValid()) continue;

                $ext      = strtolower($file->getClientOriginalExtension());
                $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $slugBase = preg_replace('/[^a-z0-9\-]+/i', '-', $basename);
                $name     = $slugBase.'-'.uniqid().'.'.$ext;

                $file->move($dest, $name);
                $relPath = 'uploads/notulensi/'.$name;

                DB::table('notulensi_dokumentasi')->insert([
                    'id_notulensi' => $id_notulensi,
                    'file_path'    => $relPath,
                    'caption'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return redirect()->route('notulensi.show', $id_notulensi)->with('success', 'Notulensi berhasil dibuat.');
    }

    // ===== SHOW: detail notulensi =====
    public function show($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first();
        if (!$notulensi) abort(404);

        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id', $notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi', $id)
            ->orderBy('urut')
            ->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi', $id)
            ->get();

        return view('notulensi.show', compact('notulensi','rapat','detail','dokumentasi'));
    }

    // ===== EDIT =====
    public function edit($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first();
        if (!$notulensi) abort(404);

        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat', 'rapat.id_pimpinan', '=', 'pimpinan_rapat.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id', $notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi', $id)
            ->orderBy('urut')
            ->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi', $id)
            ->get();

        return view('notulensi.edit', compact('notulensi','rapat','detail','dokumentasi'));
    }

    // ===== UPDATE =====
    public function update(Request $request, $id)
    {
        $request->validate([
            'baris'                         => 'nullable|array',
            'baris.*.hasil_pembahasan'      => 'required_with:baris|string',
            'baris.*.rekomendasi'           => 'nullable|string',
            'baris.*.penanggung_jawab'      => 'nullable|string|max:150',
            'baris.*.tgl_penyelesaian'      => 'nullable|date',

            'hapus_dok'                     => 'nullable|array',
            'hapus_dok.*'                   => 'integer',
            'dokumentasi_baru.*'            => 'nullable|image|max:10240',
        ]);

        DB::table('notulensi')->where('id', $id)->update(['updated_at' => now()]);

        if ($request->filled('baris')) {
            DB::table('notulensi_detail')->where('id_notulensi', $id)->delete();

            $urut = 1; $rows = [];
            foreach ($request->baris as $r) {
                if (!isset($r['hasil_pembahasan']) || $r['hasil_pembahasan'] === '') continue;
                $rows[] = [
                    'id_notulensi'     => $id,
                    'urut'             => $urut++,
                    'hasil_pembahasan' => $r['hasil_pembahasan'],
                    'rekomendasi'      => $r['rekomendasi'] ?? null,
                    'penanggung_jawab' => $r['penanggung_jawab'] ?? null,
                    'tgl_penyelesaian' => $r['tgl_penyelesaian'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
            if ($rows) DB::table('notulensi_detail')->insert($rows);
        }

        if ($request->filled('hapus_dok')) {
            $hapusIds = $request->hapus_dok;
            $lama = DB::table('notulensi_dokumentasi')->whereIn('id', $hapusIds)->get();

            foreach ($lama as $item) {
                $path = public_path($item->file_path);
                if (is_file($path)) @unlink($path);
            }
            DB::table('notulensi_dokumentasi')->whereIn('id', $hapusIds)->delete();
        }

        if ($request->hasFile('dokumentasi_baru')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) mkdir($dest, 0775, true);

            foreach ($request->file('dokumentasi_baru') as $file) {
                if (!$file || !$file->isValid()) continue;

                $ext      = strtolower($file->getClientOriginalExtension());
                $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $slugBase = preg_replace('/[^a-z0-9\-]+/i', '-', $basename);
                $name     = $slugBase.'-'.uniqid().'.'.$ext;

                $file->move($dest, $name);
                $relPath = 'uploads/notulensi/'.$name;

                DB::table('notulensi_dokumentasi')->insert([
                    'id_notulensi' => $id,
                    'file_path'    => $relPath,
                    'caption'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return redirect()->route('notulensi.show', $id)->with('success', 'Notulensi berhasil diperbarui.');
    }

    /**
     * Cetak gabungan (Header P, Pembahasan L, Dokumentasi P)
     */
    public function cetakGabung($id)
    {
        $notulensi = DB::table('notulensi')->where('id', $id)->first() ?? abort(404);

        $rapat = DB::table('rapat')
            ->leftJoin('pimpinan_rapat','rapat.id_pimpinan','=','pimpinan_rapat.id')
            ->leftJoin('kategori_rapat','rapat.id_kategori','=','kategori_rapat.id')
            ->select(
                'rapat.*',
                'pimpinan_rapat.nama as nama_pimpinan',
                'pimpinan_rapat.jabatan as jabatan_pimpinan',
                'kategori_rapat.nama as nama_kategori'
            )
            ->where('rapat.id',$notulensi->id_rapat)
            ->first();

        $detail = DB::table('notulensi_detail')
            ->where('id_notulensi',$id)
            ->orderBy('urut')->get();

        $dokumentasi = DB::table('notulensi_dokumentasi')
            ->where('id_notulensi',$id)
            ->get();

        $creator = DB::table('users')->where('id',$notulensi->id_user)->first();

        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $notulensi->id_rapat)->count();

        $data = compact('notulensi','rapat','detail','dokumentasi','creator','jumlah_peserta');

        $tmpDir = storage_path('app');
        $f1 = $tmpDir.'/header-'.Str::random(8).'.pdf';
        $f2 = $tmpDir.'/pembahasan-'.Str::random(8).'.pdf';
        $f3 = $tmpDir.'/dokumentasi-'.Str::random(8).'.pdf';

        Pdf::loadView('notulensi.cetak_p1', $data)
            ->setPaper('a4','portrait')->save($f1);

        Pdf::loadView('notulensi.cetak_p2', $data)
            ->setPaper('a4','landscape')->save($f2);

        Pdf::loadView('notulensi.cetak_p3', $data)
            ->setPaper('a4','portrait')->save($f3);

        $merger = new Merger();
        $merger->addFile($f1);
        $merger->addFile($f2);
        $merger->addFile($f3);
        $mergedPdf = $merger->merge();

        @unlink($f1); @unlink($f2); @unlink($f3);

        $filename = 'Notulensi-'.Str::slug($rapat->judul).'-'.date('d-m-Y', strtotime($notulensi->created_at)).'.pdf';
        return response($mergedPdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
