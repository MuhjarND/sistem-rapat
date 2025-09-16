<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Daftar user
    public function index()
    {
        // Tetap konsisten: Query Builder, urut nama
        $daftar_user = DB::table('users')
            ->select('id','name','jabatan','email','no_hp','unit','role','created_at')
            ->orderBy('name')
            ->get();

        return view('user.index', compact('daftar_user'));
    }

    // Form tambah user
    public function create()
    {
        $daftar_role = ['admin', 'notulis', 'peserta'];
        $daftar_unit = ['kepaniteraan', 'kesekretariatan'];

        return view('user.create', compact('daftar_role','daftar_unit'));
    }

    // Simpan user baru
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'jabatan'  => 'nullable|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'no_hp'    => 'nullable|regex:/^0[0-9]{9,13}$/',
            'unit'     => 'required|in:kepaniteraan,kesekretariatan',
            'role'     => 'required|in:admin,notulis,peserta',
            'password' => 'required|min:6|confirmed'
        ]);

        DB::table('users')->insert([
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,          // <— simpan no_hp
            'unit'       => $request->unit,           // <— simpan unit
            'role'       => $request->role,
            'password'   => Hash::make($request->password),
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
        $daftar_unit = ['kepaniteraan', 'kesekretariatan'];

        return view('user.edit', compact('user', 'daftar_role','daftar_unit'));
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $request->validate([
            'name'     => 'required|string|max:100',
            'jabatan'  => 'nullable|string|max:100',
            'email'    => 'required|email|unique:users,email,'.$id,
            'no_hp'    => 'nullable|regex:/^0[0-9]{9,13}$/',
            'unit'     => 'required|in:kepaniteraan,kesekretariatan',
            'role'     => 'required|in:admin,notulis,peserta',
            'password' => 'nullable|min:6|confirmed'
        ]);

        $data = [
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,     // <— update no_hp
            'unit'       => $request->unit,      // <— update unit
            'role'       => $request->role,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
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
