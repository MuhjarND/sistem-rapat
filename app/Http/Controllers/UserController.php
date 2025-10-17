<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Tambahkan 'operator' sebagai role yang bisa dipilih
        // Catatan: role 'approval' tetap tidak dipilih manual — otomatis jika ada tingkatan
        $daftar_role = ['admin', 'operator', 'notulis', 'peserta'];
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
            // izinkan operator dipilih
            'role'      => 'required|in:admin,operator,notulis,peserta',
            'password'  => 'required|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ]);

        // Normalisasi role & tingkatan:
        // - Jika role = operator, paksa tingkatan NULL (operator bukan approver)
        // - Jika role ≠ operator dan tingkatan diisi, override role -> approval
        $roleInput = $request->role;
        $tingkatan = $request->tingkatan;

        if ($roleInput === 'operator') {
            $tingkatan = null;        // operator tidak punya tingkatan
            $role      = 'operator';
        } else {
            $role      = $request->filled('tingkatan') ? 'approval' : $roleInput;
        }

        DB::table('users')->insert([
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'tingkatan'  => $tingkatan, // 1/2/null
            'role'       => $role,
            'hirarki'    => $request->filled('hirarki') ? (int)$request->hirarki : null,
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

        // Tambahkan operator di pilihan role saat edit juga
        $daftar_role = ['admin', 'operator', 'notulis', 'peserta']; // approval tetap tidak dipilih manual
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
            // izinkan operator dipilih
            'role'      => 'required|in:admin,operator,notulis,peserta',
            'password'  => 'nullable|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ]);

        // Normalisasi role & tingkatan (konsisten dengan store)
        $roleInput = $request->role;
        $tingkatan = $request->tingkatan;

        if ($roleInput === 'operator') {
            $tingkatan = null;
            $role      = 'operator';
        } else {
            $role      = $request->filled('tingkatan') ? 'approval' : $roleInput;
        }

        $data = [
            'name'       => $request->name,
            'jabatan'    => $request->jabatan,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'tingkatan'  => $tingkatan,
            'role'       => $role,
            'hirarki'    => $request->filled('hirarki') ? (int)$request->hirarki : null,
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
