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
    public function index()
    {
        return redirect()->route('notulensi.belum');
    }

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

    public function belum(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_belum = $query->orderBy('rapat.tanggal', 'desc')->paginate(6);
        $rapat_belum->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.belum', compact('rapat_belum', 'daftar_kategori'));
    }

    public function sudah(Request $request)
    {
        $query = $this->baseRapatQuery()->whereNotNull('notulensi.id');
        $this->applyFilters($query, $request);

        $rapat_sudah = $query->orderBy('rapat.tanggal', 'desc')->paginate(6);
        $rapat_sudah->appends($request->query());

        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        return view('notulensi.sudah', compact('rapat_sudah', 'daftar_kategori'));
    }

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

        if (DB::table('notulensi')->where('id_rapat', $id_rapat)->exists()) {
            return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah dibuat.');
        }

        $jumlah_peserta = DB::table('undangan')->where('id_rapat', $id_rapat)->count();

        Carbon::setLocale('id');
        $hari_tanggal = Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y');
        $jam = $rapat->waktu_mulai;

        return view('notulensi.create', compact('rapat','jumlah_peserta','hari_tanggal','jam'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_rapat'                 => 'required|exists:rapat,id',
            'baris'                    => 'required|array|min:1',
            'baris.*.hasil_pembahasan' => 'required|string',
            'baris.*.rekomendasi'      => 'nullable|string',
            'baris.*.penanggung_jawab' => 'nullable|string|max:150',
            'baris.*.tgl_penyelesaian' => 'nullable|date',
            'dokumentasi'              => 'required',
            'dokumentasi.*'            => 'image|max:10240',
        ], [
            'dokumentasi.required' => 'Minimal unggah 3 foto dokumentasi.',
        ]);

        if (DB::table('notulensi')->where('id_rapat', $request->id_rapat)->exists()) {
            return redirect()->route('notulensi.sudah')->with('success', 'Notulensi untuk rapat ini sudah ada.');
        }

        // header
        $id_notulensi = DB::table('notulensi')->insertGetId([
            'id_rapat'   => $request->id_rapat,
            'id_user'    => Auth::id(),   // notulis (pembuat)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // detail
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
        if ($rows) DB::table('notulensi_detail')->insert($rows);

        // dokumentasi
        if ($request->hasFile('dokumentasi')) {
            $dest = public_path('uploads/notulensi');
            if (!is_dir($dest)) mkdir($dest, 0775, true);

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

        // === AUTO: QR TTD NOTULIS (order 1, approved) + logo ===
        $this->ensureNotulensiNotulisQr((int)$request->id_rapat, (int)$id_notulensi);

        // === Setup approval chain NOTULENSI: aprv2 (opsional) -> aprv1 ===
        $rapat = DB::table('rapat')->where('id', $request->id_rapat)->first();
        if ($rapat) {
            $order = 2; // setelah notulis: 1

            if (!empty($rapat->approval2_user_id)) {
                $exists2 = DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'notulensi')
                    ->where('approver_user_id', $rapat->approval2_user_id)
                    ->exists();
                if (!$exists2) {
                    DB::table('approval_requests')->insert([
                        'rapat_id'         => $rapat->id,
                        'doc_type'         => 'notulensi',
                        'approver_user_id' => $rapat->approval2_user_id,
                        'order_index'      => $order++,
                        'status'           => 'pending',
                        'sign_token'       => Str::random(32),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                } else {
                    $order++;
                }
            }

            if (!empty($rapat->approval1_user_id)) {
                $exists1 = DB::table('approval_requests')
                    ->where('rapat_id', $rapat->id)
                    ->where('doc_type', 'notulensi')
                    ->where('approver_user_id', $rapat->approval1_user_id)
                    ->exists();
                if (!$exists1) {
                    DB::table('approval_requests')->insert([
                        'rapat_id'         => $rapat->id,
                        'doc_type'         => 'notulensi',
                        'approver_user_id' => $rapat->approval1_user_id,
                        'order_index'      => $order,
                        'status'           => 'pending',
                        'sign_token'       => Str::random(32),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        return redirect()->route('notulensi.show', $id_notulensi)
            ->with('success', 'Notulensi berhasil dibuat. TTD Notulis dibuat otomatis & approval pimpinan disiapkan.');
    }

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'baris'                    => 'nullable|array',
            'baris.*.hasil_pembahasan' => 'required_with:baris|string',
            'baris.*.rekomendasi'      => 'nullable|string',
            'baris.*.penanggung_jawab' => 'nullable|string|max:150',
            'baris.*.tgl_penyelesaian' => 'nullable|date',
            'hapus_dok'                => 'nullable|array',
            'hapus_dok.*'              => 'integer',
            'dokumentasi_baru.*'       => 'nullable|image|max:10240',
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
     * Cetak gabungan (p1, p2, p3). P2 memuat QR Notulis & QR Pimpinan.
     * Di sini kita **force refresh** QR Notulis supaya schema & ECC terbaru.
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

        // pastikan QR Notulis ada & up-to-date (force refresh)
        $this->ensureNotulensiNotulisQr((int)$notulensi->id_rapat, (int)$notulensi->id, true);

        // QR Notulis (approved)
        $qrNotulis = DB::table('approval_requests')
            ->where('rapat_id', $notulensi->id_rapat)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $notulensi->id_user)
            ->where('status', 'approved')
            ->orderBy('order_index')->first();

        $qr_notulis_data = null;
        if ($qrNotulis && $qrNotulis->signature_qr_path) {
            $fs = public_path($qrNotulis->signature_qr_path);
            if (@is_file($fs)) {
                $qr_notulis_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($fs));
            }
        }

        // QR Pimpinan (approved)
        $qrPimpinan = DB::table('approval_requests')
            ->where('rapat_id', $notulensi->id_rapat)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderBy('order_index')->first();

        $qr_pimpinan_data = null;
        if ($qrPimpinan && $qrPimpinan->signature_qr_path) {
            $fs = public_path($qrPimpinan->signature_qr_path);
            if (@is_file($fs)) {
                $qr_pimpinan_data = 'data:image/png;base64,'.base64_encode(@file_get_contents($fs));
            }
        }

        $data = compact('notulensi','rapat','detail','dokumentasi','creator','jumlah_peserta');

        $notulis  = $creator;
        $pimpUser = DB::table('users')->where('id', $rapat->approval1_user_id)->first();

        $dataP2 = array_merge($data, [
            'qr_notulis_data'  => $qr_notulis_data,
            'qr_pimpinan_data' => $qr_pimpinan_data,
            'notulis_nama'     => $notulis->name ?? '-',
            'notulis_jabatan'  => $notulis->jabatan ?? 'Notulis',
            'pimpinan_nama'    => $pimpUser->name ?? ($rapat->nama_pimpinan ?? '-'),
            'pimpinan_jabatan' => $pimpUser->jabatan ?? ($rapat->jabatan_pimpinan ?? 'Pimpinan Rapat'),
            'kop'              => public_path('kop_notulen.jpg'),
        ]);

        $tmpDir = storage_path('app');
        $f1 = $tmpDir.'/header-'.Str::random(8).'.pdf';
        $f2 = $tmpDir.'/pembahasan-'.Str::random(8).'.pdf';
        $f3 = $tmpDir.'/dokumentasi-'.Str::random(8).'.pdf';

        Pdf::loadView('notulensi.cetak_p1', $data)
            ->setPaper('a4','portrait')->save($f1);

        Pdf::loadView('notulensi.cetak_p2', $dataP2)
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

    /**
     * Buat/refresh QR TTD Notulis (doc_type notulensi, approver=notulis) + tempel logo.
     * $forceRefresh: true untuk memaksa regenerate & overwrite (dipakai saat cetak).
     */
    private function ensureNotulensiNotulisQr(int $rapatId, int $notulenId, bool $forceRefresh = false): void
    {
        $notulensi = DB::table('notulensi')->where('id', $notulenId)->first();
        $rapat     = DB::table('rapat')->where('id', $rapatId)->first();
        if (!$notulensi || !$rapat) return;

        $row = DB::table('approval_requests')
            ->where('rapat_id', $rapatId)
            ->where('doc_type', 'notulensi')
            ->where('approver_user_id', $notulensi->id_user) // notulis
            ->first();

        if ($row && $row->status === 'approved' && $row->signature_qr_path && !$forceRefresh) return;

        // ambil data notulis (nama/jabatan opsional)
        $notulisUser = DB::table('users')->where('id', $notulensi->id_user)->first();

        // === payload KONSISTEN: gunakan 'approver' (bukan 'signer') ===
        $payload = [
            'v'          => 1,
            'doc_type'   => 'notulensi',
            'rapat_id'   => $rapatId,
            'notulen_id' => $notulenId,
            'nomor'      => $rapat->nomor_undangan,
            'judul'      => $rapat->judul,
            'tanggal'    => $rapat->tanggal,
            'approver'   => [
                'id'      => $notulensi->id_user,
                'name'    => $notulisUser->name ?? null,
                'jabatan' => $notulisUser->jabatan ?? null,
                'order'   => 1,
            ],
            'issued_at'  => now()->toIso8601String(),
            'nonce'      => Str::random(18),
        ];

        $secret = config('app.key');
        if (is_string($secret) && Str::startsWith($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        $payload['sig'] = hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $secret
        );
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        // QR = URL verifikasi + ECC H (aman untuk logo)
        $qrContent = route('qr.verify', ['d' => base64_encode($payloadJson)]);
        $encoded   = urlencode($qrContent);
        $qrUrl     = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl={$encoded}&chld=H|0";

        $dir = public_path('qr');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $filename     = 'qr_notulensi_notulis_r'.$rapatId.'_n'.$notulenId.'_'.Str::random(6).'.png';
        $relativePath = 'qr/'.$filename;
        $absolutePath = public_path($relativePath);

        $png = @file_get_contents($qrUrl);
        if ($png === false) {
            $alt = "https://api.qrserver.com/v1/create-qr-code/?size=600x600&data={$encoded}&ecc=H&margin=0";
            $png = @file_get_contents($alt);
        }
        if ($png === false) return;

        // Tempel logo transparan (ratio 18% lebar QR)
        $logoPath = public_path('logo_qr.png'); // PNG transparan
        $saved = false;
        if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $qrImg = @imagecreatefromstring($png);
            if ($qrImg !== false) {
                if (is_file($logoPath)) {
                    $logoImg = @imagecreatefrompng($logoPath);
                    if ($logoImg !== false) {
                        imagealphablending($logoImg, true);
                        imagesavealpha($logoImg, true);

                        $qrW = imagesx($qrImg); $qrH = imagesy($qrImg);
                        $lw  = imagesx($logoImg); $lh = imagesy($logoImg);

                        $targetW = (int) round($qrW * 0.18); // 18% width
                        $targetH = (int) round($lh * ($targetW / $lw));

                        $dstX = (int) round(($qrW - $targetW) / 2);
                        $dstY = (int) round(($qrH - $targetH) / 2);

                        $logoResized = imagecreatetruecolor($targetW, $targetH);
                        imagealphablending($logoResized, false);
                        imagesavealpha($logoResized, true);
                        imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $targetW, $targetH, $lw, $lh);

                        imagecopy($qrImg, $logoResized, $dstX, $dstY, 0, 0, $targetW, $targetH);

                        imagepng($qrImg, $absolutePath);
                        imagedestroy($logoResized);
                        imagedestroy($logoImg);
                        imagedestroy($qrImg);
                        $saved = true;
                    }
                }
                if (!$saved) {
                    imagepng($qrImg, $absolutePath);
                    imagedestroy($qrImg);
                    $saved = true;
                }
            }
        }
        if (!$saved) {
            file_put_contents($absolutePath, $png);
        }

        // Upsert/Update approval_requests notulis
        if ($row) {
            DB::table('approval_requests')->where('id', $row->id)->update([
                'status'            => 'approved',
                'order_index'       => 1,
                'signature_qr_path' => $relativePath,
                'signature_payload' => $payloadJson,
                'signed_at'         => now(),
                'updated_at'        => now(),
            ]);
        } else {
            DB::table('approval_requests')->insert([
                'rapat_id'          => $rapatId,
                'doc_type'          => 'notulensi',
                'approver_user_id'  => $notulensi->id_user, // notulis
                'order_index'       => 1,
                'status'            => 'approved',
                'sign_token'        => Str::random(32),
                'signature_qr_path' => $relativePath,
                'signature_payload' => $payloadJson,
                'signed_at'         => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

        public function dashboard()
    {
        // ===== METRICS
        $totalRapat       = DB::table('rapat')->count();
        $totalNotulensi   = DB::table('notulensi')->count();
        $belumAda         = DB::table('rapat')
                                ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                                ->whereNull('notulensi.id')
                                ->count();
        $sudahAda         = $totalNotulensi;

        // ===== PENDING (10 terbaru, rapat tanpa notulensi)
        $pending = DB::table('rapat')
            ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
            ->whereNull('notulensi.id')
            ->select('rapat.id','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat')
            ->orderBy('rapat.tanggal','desc')->orderBy('rapat.waktu_mulai','desc')
            ->limit(10)->get();

        // ===== SUDAH (10 terbaru, rapat dengan notulensi)
        $selesai = DB::table('notulensi')
            ->join('rapat','notulensi.id_rapat','=','rapat.id')
            ->select('notulensi.id as id_notulensi','rapat.id as id_rapat','rapat.judul','rapat.tanggal','rapat.waktu_mulai','rapat.tempat')
            ->orderBy('rapat.tanggal','desc')->orderBy('rapat.waktu_mulai','desc')
            ->limit(10)->get();

        // ===== PRODUKTIVITAS 6 BULAN (opsional untuk ringkas tren)
        $byMonth = DB::table('notulensi')
            ->join('rapat','notulensi.id_rapat','=','rapat.id')
            ->select(DB::raw("DATE_FORMAT(rapat.tanggal,'%Y-%m') as ym"), DB::raw('COUNT(*) as total'))
            ->groupBy('ym')
            ->orderBy('ym','desc')
            ->limit(6)
            ->pluck('total','ym') // ['2025-10'=>5, ...]
            ->reverse();          // urut dari lama ke baru

        return view('notulensi.dashboard', [
            'metrics'  => [
                'totalRapat'     => $totalRapat,
                'totalNotulensi' => $totalNotulensi,
                'belumAda'       => $belumAda,
                'sudahAda'       => $sudahAda,
            ],
            'pending'  => $pending,
            'selesai'  => $selesai,
            'byMonth'  => $byMonth,
        ]);
    }
}
