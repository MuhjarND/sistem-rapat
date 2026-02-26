<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KategoriRapatController extends Controller
{
    // Daftar kategori
    public function index()
    {
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();
        return view('kategori.index', compact('daftar_kategori'));
    }

    // Form tambah
    public function create()
    {
        return view('kategori.create');
    }

    // Simpan baru
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_rapat,nama',
        ]);

        $payload = [
            'nama' => $request->nama,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('kategori_rapat', 'butuh_pakaian')) {
            $payload['butuh_pakaian'] = $request->boolean('butuh_pakaian') ? 1 : 0;
        }

        DB::table('kategori_rapat')->insert($payload);

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambah!');
    }

    // Form edit
    public function edit($id)
    {
        $kategori = DB::table('kategori_rapat')->where('id', $id)->first();
        if (!$kategori) abort(404);

        return view('kategori.edit', compact('kategori'));
    }

    // Update data
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_rapat,nama,'.$id,
        ]);

        $payload = [
            'nama' => $request->nama,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('kategori_rapat', 'butuh_pakaian')) {
            $payload['butuh_pakaian'] = $request->boolean('butuh_pakaian') ? 1 : 0;
        }

        DB::table('kategori_rapat')->where('id', $id)->update($payload);

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diupdate!');
    }

    // Hapus data
    public function destroy($id)
    {
        DB::table('kategori_rapat')->where('id', $id)->delete();
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus!');
    }
}
