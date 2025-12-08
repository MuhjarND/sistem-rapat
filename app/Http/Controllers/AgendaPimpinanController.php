<?php

namespace App\Http\Controllers;

use App\Helpers\FonnteWa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AgendaPimpinanController extends Controller
{
    public function index()
    {
        $select = ['id','name','jabatan'];
        if (Schema::hasColumn('users','no_hp')) $select[] = 'no_hp';
        if (Schema::hasColumn('users','wa'))    $select[] = 'wa';
        if (Schema::hasColumn('users','phone')) $select[] = 'phone';

        $daftar_penerima = DB::table('users')
            ->select($select)
            ->when(Schema::hasColumn('users','role'), function($q){
                $q->whereNotIn('role',['admin','superadmin']);
            })
            ->orderBy('name')
            ->get();

        $agenda = DB::table('agenda_pimpinan as a')
            ->leftJoin('users as u', 'a.pimpinan_user_id', '=', 'u.id')
            ->select(
                'a.*',
                'u.name as pimpinan_nama',
                'u.jabatan as pimpinan_jabatan'
            )
            ->orderBy('a.created_at', 'desc')
            ->limit(20)
            ->get();

        return view('protokoler.agenda.index', compact('daftar_penerima', 'agenda'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'         => 'required|date',
            'waktu'           => 'required',
            'nomor_naskah'    => 'nullable|string|max:200',
            'judul'           => 'required|string|max:200',
            'tempat'          => 'required|string|max:200',
            'seragam'         => 'nullable|string|max:120',
            'lampiran_url'    => 'nullable|url|max:500',
            'penerima_ids'    => 'required|array|min:1',
            'penerima_ids.*'  => 'integer|exists:users,id',
        ]);

        $penerimaIds = array_values(array_unique(array_map('intval', $request->penerima_ids)));
        $sel = ['id','name'];
        if (Schema::hasColumn('users','no_hp')) $sel[] = 'no_hp';
        if (Schema::hasColumn('users','wa'))    $sel[] = 'wa';
        if (Schema::hasColumn('users','phone')) $sel[] = 'phone';

        $penerimaRows = DB::table('users')->whereIn('id', $penerimaIds)->select($sel)->get();

        $primaryRow = $penerimaRows->first();
        $pimpinanId = $primaryRow->id ?? ($penerimaIds[0] ?? null);
        if (!$pimpinanId) {
            return back()->withErrors('Gagal menentukan penerima utama.')->withInput();
        }
        $yangMenghadiri = $penerimaRows->pluck('name')->implode(', ');

        DB::table('agenda_pimpinan')->insert([
            'pimpinan_user_id'=> $pimpinanId,
            'penerima_json'   => json_encode($penerimaIds),
            'tanggal'         => $request->tanggal,
            'waktu'           => $request->waktu,
            'nomor_naskah'    => $request->nomor_naskah,
            'judul'           => $request->judul,
            'tempat'          => $request->tempat,
            'yang_menghadiri' => $yangMenghadiri,
            'seragam'         => $request->seragam,
            'lampiran_path'   => $request->lampiran_url, // simpan URL sebagai path
            'lampiran_nama'   => null,
            'lampiran_size'   => null,
            'dibuat_oleh'     => Auth::id(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $lampiranUrl  = $request->lampiran_url ?: '-';
        $nomorNaskah  = $request->input('nomor_naskah', '-');
        $tglFormatted = Carbon::parse($request->tanggal)->translatedFormat('l, d F Y');
        $seragam      = $request->seragam ?: '-';

        $buildMessage = function (string $nama) use ($nomorNaskah, $request, $tglFormatted, $seragam, $lampiranUrl) {
            return "Assalamu'alaikum warahmatullahi wabarakatuh,\n\n"
                . "Yth. Bapak/Ibu *{$nama}*,\n\n"
                . "Dengan hormat, kami sampaikan kepada Bapak/Ibu untuk dapat menjalankan sebagaimana surat/undangan pada detail berikut:\n\n"
                . "* Nomor Naskah Dinas: {$nomorNaskah}\n"
                . "* Kegiatan: *{$request->judul}*\n"
                . "* Hari/Tanggal: *{$tglFormatted}*\n"
                . "* Waktu: *{$request->waktu}* WIT\n"
                . "* Tempat: {$request->tempat}\n"
                . "* Pakaian: {$seragam}\n\n"
                . "Silakan meninjau detail kegiatan melalui tautan berikut:\n"
                . "Undangan (PDF): {$lampiranUrl}\n\n"
                . "Atas perhatian dan kehadirannya kami ucapkan terima kasih.\n"
                . "Wassalamu'alaikum warahmatullahi wabarakatuh.";
        };

        $sentMsisdn = [];

        // Kirim ke penerima utama
        $waTarget = null;
        if ($primaryRow) {
            $waTarget = $primaryRow->no_hp ?? null;
            if (!$waTarget && property_exists($primaryRow, 'wa')) {
                $waTarget = $primaryRow->wa;
            }
            if (!$waTarget && property_exists($primaryRow, 'phone')) {
                $waTarget = $primaryRow->phone;
            }
        }
        if ($waTarget) {
            $primaryName = $primaryRow->name ?? 'Bapak/Ibu';
            $msisdn = method_exists(FonnteWa::class, 'normalizeNumber')
                ? FonnteWa::normalizeNumber($waTarget)
                : preg_replace('/^0/', '62', preg_replace('/\D+/', '', $waTarget));
            try {
                FonnteWa::send($msisdn, $buildMessage($primaryName));
                $sentMsisdn[$msisdn] = true;
            } catch (\Throwable $e) {
                report($e);
                return redirect()
                    ->route('agenda-pimpinan.index')
                    ->with('warning', 'Agenda tersimpan, tetapi WA gagal dikirim ke penerima utama.');
            }
        }

        // Kirim ke semua penerima (hindari duplikat)
        foreach ($penerimaRows as $row) {
            $phone = $row->no_hp ?? null;
            if (!$phone && property_exists($row, 'wa')) {
                $phone = $row->wa;
            }
            if (!$phone && property_exists($row, 'phone')) {
                $phone = $row->phone;
            }
            if (!$phone) continue;

            $msisdn = method_exists(FonnteWa::class, 'normalizeNumber')
                ? FonnteWa::normalizeNumber($phone)
                : preg_replace('/^0/', '62', preg_replace('/\D+/', '', $phone));
            if (isset($sentMsisdn[$msisdn])) continue;

            try {
                FonnteWa::send($msisdn, $buildMessage($row->name ?? 'Bapak/Ibu'));
                $sentMsisdn[$msisdn] = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('agenda-pimpinan.index')
            ->with('success', 'Agenda tersimpan dan notifikasi WA telah dikirim.');
    }
}
