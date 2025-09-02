<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Daftar user
    public function index()
    {
        $daftar_user = DB::table('users')->orderBy('name')->get();
        return view('user.index', compact('daftar_user'));
    }

    // Form tambah user
    public function create()
    {
        $daftar_role = ['admin', 'notulis', 'peserta'];
        return view('user.create', compact('daftar_role'));
    }

    // Simpan user baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,notulis,peserta',
            'password' => 'required|min:6|confirmed'
        ]);

        DB::table('users')->insert([
            'name' => $request->name,
            'jabatan' => $request->jabatan,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('user.index')->with('success', 'User berhasil ditambah!');
    }

    // Form edit user
    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $daftar_role = ['admin', 'notulis', 'peserta'];
        return view('user.edit', compact('user', 'daftar_role'));
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $request->validate([
            'name' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:admin,notulis,peserta',
            'password' => 'nullable|min:6|confirmed'
        ]);

        $data = [
            'name' => $request->name,
            'jabatan' => $request->jabatan,
            'email' => $request->email,
            'role' => $request->role,
            'updated_at' => now(),
        ];
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('users')->where('id', $id)->update($data);

        return redirect()->route('user.index')->with('success', 'User berhasil diupdate!');
    }

    // Hapus user
    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus!');
    }
}

