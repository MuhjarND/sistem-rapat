<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Urutkan berdasarkan hirarki (kecil di atas), baru nama
        $daftar_user = DB::table('users')
            ->select('id','name','jabatan','email','no_hp','unit','tingkatan','role','hirarki','created_at')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        return view('user.index', compact('daftar_user'));
    }

    public function create()
    {
        // role tetap tampil admin/notulis/peserta; role 'approval' diatur otomatis ketika tingkatan diisi
        $daftar_role = ['admin', 'notulis', 'peserta'];
        $daftar_unit = ['kepaniteraan', 'kesekretariatan'];
        $daftar_tingkatan = [1,2];

        return view('user.create', compact('daftar_role','daftar_unit','daftar_tingkatan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'jabatan'   => 'nullable|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'no_hp'     => 'nullable|regex:/^0[0-9]{9,13}$/',
            'unit'      => 'required|in:kepaniteraan,kesekretariatan',
            'tingkatan' => 'nullable|in:1,2',
            'role'      => 'required|in:admin,notulis,peserta', // input awal
            'password'  => 'required|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',   // <<< tambahan
        ]);

        // Jika tingkatan diisi, override role jadi 'approval'
        $role = $request->filled('tingkatan') ? 'approval' : $request->role;

        DB::table('users')->insert([
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'tingkatan'  => $request->tingkatan, // 1/2/null
            'role'       => $role,
            'hirarki'    => $request->filled('hirarki') ? (int)$request->hirarki : null, // <<< simpan
            'password'   => Hash::make($request->password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('user.index')->with('success', 'User berhasil ditambah!');
    }

    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $daftar_role = ['admin', 'notulis', 'peserta']; // approval tidak dipilih manual
        $daftar_unit = ['kepaniteraan', 'kesekretariatan'];
        $daftar_tingkatan = [1,2];

        return view('user.edit', compact('user','daftar_role','daftar_unit','daftar_tingkatan'));
    }

    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $request->validate([
            'name'      => 'required|string|max:100',
            'jabatan'   => 'nullable|string|max:100',
            'email'     => 'required|email|unique:users,email,'.$id,
            'no_hp'     => 'nullable|regex:/^0[0-9]{9,13}$/',
            'unit'      => 'required|in:kepaniteraan,kesekretariatan',
            'tingkatan' => 'nullable|in:1,2',
            'role'      => 'required|in:admin,notulis,peserta',
            'password'  => 'nullable|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',   // <<< tambahan
        ]);

        // Override role jika tingkatan diisi
        $role = $request->filled('tingkatan') ? 'approval' : $request->role;

        $data = [
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'tingkatan'  => $request->tingkatan,
            'role'       => $role,
            'hirarki'    => $request->filled('hirarki') ? (int)$request->hirarki : null, // <<< simpan
            'updated_at' => now(),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('users')->where('id', $id)->update($data);

        return redirect()->route('user.index')->with('success', 'User berhasil diupdate!');
    }

    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus!');
    }
}
