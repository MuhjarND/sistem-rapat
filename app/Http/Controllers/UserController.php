<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar user + filter + pagination.
     * Mendukung deep-link dari Units: ?pick_unit=<id>
     */
    public function index(Request $request)
    {
        $q         = trim($request->get('q', ''));
        $role      = $request->get('role', '');         // '', admin, operator, notulis, peserta, approval
        $unitName  = $request->get('unit', '');         // filter by unit name
        $pickUnit  = $request->get('pick_unit');        // id unit dari halaman Units
        $perPage   = (int) ($request->get('per_page', 12) ?: 12);

        // Jika datang dari Units (pick_unit=id), ambil nama unit-nya lalu pakai sebagai filter default
        if ($pickUnit && $unitName === '') {
            $pickedName = DB::table('units')->where('id', $pickUnit)->value('nama');
            if ($pickedName) {
                $unitName = $pickedName;
            }
        }

        // Master daftar unit aktif untuk filter/dropdown di view
        $daftar_unit = DB::table('units')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        $qry = DB::table('users')
            ->select('id','name','jabatan','email','no_hp','unit','tingkatan','role','hirarki','created_at');

        if ($q !== '') {
            $qry->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('jabatan', 'like', "%{$q}%")
                  ->orWhere('unit', 'like', "%{$q}%")
                  ->orWhere('no_hp', 'like', "%{$q}%");
            });
        }

        if ($role !== '' && in_array($role, ['admin','operator','notulis','peserta','approval'], true)) {
            $qry->where('role', $role);
        }

        if ($unitName !== '') {
            $qry->where('unit', $unitName);
        }

        // Urutkan berdasarkan hirarki (kecil di atas), baru nama
        $qry->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc');

        $daftar_user = $qry->paginate($perPage)->appends($request->all());

        return view('user.index', compact('daftar_user', 'q', 'role', 'unitName', 'daftar_unit', 'perPage', 'pickUnit'));
    }

    /**
     * Form create user.
     * - role dipilih dari: admin, operator, notulis, peserta
     * - jika tingkatan diisi (1/2) maka role akan di-set otomatis menjadi approval saat simpan
     * - unit diambil dari tabel units (aktif saja)
     */
    public function create(Request $request)
    {
        $daftar_role      = ['admin', 'operator', 'notulis', 'peserta']; // approval auto dari tingkatan
        $daftar_tingkatan = [1, 2];

        $daftar_unit = DB::table('units')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        // Preselect unit jika datang dari Units (pick_unit=id)
        $pickUnit   = $request->get('pick_unit');
        $pickedName = null;
        if ($pickUnit) {
            $pickedName = DB::table('units')->where('id', $pickUnit)->value('nama');
        }

        return view('user.create', compact('daftar_role', 'daftar_unit', 'daftar_tingkatan', 'pickedName', 'pickUnit'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'jabatan'   => 'nullable|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'no_hp'     => ['nullable','regex:/^0[0-9]{9,13}$/'],
            // unit harus ada di tabel units.nama dan aktif
            'unit'      => [
                'required',
                Rule::exists('units','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'tingkatan' => ['nullable', Rule::in([1,2])],
            'role'      => ['required', Rule::in(['admin','operator','notulis','peserta'])],
            'password'  => 'required|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ],[
            'unit.exists' => 'Unit tidak valid atau non-aktif.',
        ]);

        // Normalisasi role & tingkatan:
        // - role=operator => tingkatan=null (bukan approver)
        // - selain operator, jika tingkatan diisi => role dipaksa 'approval'
        $roleInput = $request->role;
        $tingkatan = $request->tingkatan;

        if ($roleInput === 'operator') {
            $tingkatan = null;
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

    /**
     * Form edit user.
     */
    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $daftar_role      = ['admin', 'operator', 'notulis', 'peserta']; // approval auto dari tingkatan
        $daftar_tingkatan = [1, 2];

        $daftar_unit = DB::table('units')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        return view('user.edit', compact('user','daftar_role','daftar_unit','daftar_tingkatan'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $request->validate([
            'name'      => 'required|string|max:100',
            'jabatan'   => 'nullable|string|max:100',
            'email'     => 'required|email|unique:users,email,'.$id,
            'no_hp'     => ['nullable','regex:/^0[0-9]{9,13}$/'],
            'unit'      => [
                'required',
                Rule::exists('units','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'tingkatan' => ['nullable', Rule::in([1,2])],
            'role'      => ['required', Rule::in(['admin','operator','notulis','peserta'])],
            'password'  => 'nullable|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ],[
            'unit.exists' => 'Unit tidak valid atau non-aktif.',
        ]);

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

    /**
     * Hapus user.
     */
    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus!');
    }
}
