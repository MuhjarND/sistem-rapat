<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class OperatorController extends Controller
{
    public function index()
    {
        // beberapa ringkasan untuk card
        $today = now()->toDateString();

        $totalRapat  = DB::table('rapat')->count();
        $rapatHariIni = DB::table('rapat')->whereDate('tanggal', $today)->count();

        $totalAbsensi = DB::table('absensi')->count();
        $totalLaporan = DB::table('laporan_files')->where('is_archived', 0)->count();
        $totalArsip   = DB::table('laporan_files')->where('is_archived', 1)->count();

        // contoh: pending approval undangan/absensi
        $pendingApproval = DB::table('approval_requests')
            ->whereIn('doc_type', ['undangan','absensi'])
            ->where('status','pending')
            ->count();

        return view('operator.index', compact(
            'totalRapat','rapatHariIni',
            'totalAbsensi','totalLaporan','totalArsip',
            'pendingApproval'
        ));
    }
}
