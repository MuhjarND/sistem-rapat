<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PimpinanRapatController extends Controller
{
    // Daftar pimpinan
    public function index()
    {
        $daftar_pimpinan = DB::table('pimpinan_rapat')->orderBy('nama')->get();
        return view('pimpinan.index', compact('daftar_pimpinan'));
    }

    // Form tambah
    public function create()
    {
        return view('pimpinan.create');
    }

    // Simpan baru
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
        ]);

        DB::table('pimpinan_rapat')->insert([
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pimpinan.index')->with('success', 'Data pimpinan berhasil ditambah!');
    }

    // Form edit
    public function edit($id)
    {
        $pimpinan = DB::table('pimpinan_rapat')->where('id', $id)->first();
        if (!$pimpinan) abort(404);

        return view('pimpinan.edit', compact('pimpinan'));
    }

    // Update data
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
        ]);

        DB::table('pimpinan_rapat')->where('id', $id)->update([
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'updated_at' => now(),
        ]);

        return redirect()->route('pimpinan.index')->with('success', 'Data pimpinan berhasil diupdate!');
    }

    // Hapus data
    public function destroy($id)
    {
        DB::table('pimpinan_rapat')->where('id', $id)->delete();
        return redirect()->route('pimpinan.index')->with('success', 'Data pimpinan berhasil dihapus!');
    }
}
    
