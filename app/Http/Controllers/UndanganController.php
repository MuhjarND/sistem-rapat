<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UndanganController extends Controller
{
    // Admin: Tampilkan semua undangan
    public function index()
    {
        $undangan = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->join('rapat', 'undangan.id_rapat', '=', 'rapat.id')
            ->select('undangan.*', 'users.name as nama_peserta', 'rapat.judul as judul_rapat')
            ->orderBy('undangan.created_at', 'desc')
            ->get();

        return view('undangan.index', compact('undangan'));
    }

    // Admin: Form buat undangan
    public function create()
    {
        $peserta = DB::table('users')->where('role', 'peserta')->get();
        $rapat = DB::table('rapat')->orderBy('tanggal', 'desc')->get();
        return view('undangan.create', compact('peserta', 'rapat'));
    }

    // Admin: Simpan undangan baru
    public function store(Request $request)
    {
        $request->validate([
            'id_rapat' => 'required|exists:rapat,id',
            'id_user' => 'required|exists:users,id',
        ]);

        // Cegah duplikat undangan
        $ada = DB::table('undangan')
            ->where('id_rapat', $request->id_rapat)
            ->where('id_user', $request->id_user)
            ->exists();

        if ($ada) {
            return redirect()->back()->with('error', 'Peserta sudah diundang ke rapat ini.');
        }

        DB::table('undangan')->insert([
            'id_rapat' => $request->id_rapat,
            'id_user' => $request->id_user,
            'status' => 'terkirim',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('undangan.index')->with('success', 'Undangan berhasil dikirim!');
    }

    // Admin: Form edit undangan (opsional, biasanya tidak dipakai)
    public function edit($id)
    {
        $undangan = DB::table('undangan')->where('id', $id)->first();
        $peserta = DB::table('users')->where('role', 'peserta')->get();
        $rapat = DB::table('rapat')->orderBy('tanggal', 'desc')->get();
        return view('undangan.edit', compact('undangan', 'peserta', 'rapat'));
    }

    // Admin: Update undangan (opsional)
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_rapat' => 'required|exists:rapat,id',
            'id_user' => 'required|exists:users,id',
            'status' => 'required|in:terkirim,diterima,dibaca'
        ]);

        DB::table('undangan')->where('id', $id)->update([
            'id_rapat' => $request->id_rapat,
            'id_user' => $request->id_user,
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return redirect()->route('undangan.index')->with('success', 'Undangan berhasil diubah.');
    }

    // Admin: Hapus undangan
    public function destroy($id)
    {
        DB::table('undangan')->where('id', $id)->delete();
        return redirect()->route('undangan.index')->with('success', 'Undangan berhasil dihapus.');
    }

    // Peserta: Lihat undangan milik sendiri
    public function undanganSaya()
    {
        $undangan = DB::table('undangan')
            ->join('rapat', 'undangan.id_rapat', '=', 'rapat.id')
            ->where('undangan.id_user', Auth::id())
            ->select('undangan.*', 'rapat.judul', 'rapat.tanggal', 'rapat.tempat')
            ->orderBy('undangan.created_at', 'desc')
            ->get();

        return view('undangan.saya', compact('undangan'));
    }
}
