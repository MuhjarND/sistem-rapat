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
        $q          = trim($request->get('q', ''));
        $role       = $request->get('role', '');           // '', admin, operator, notulis, peserta, approval
        $unitName   = $request->get('unit', '');           // filter by unit name
        $bidangName = $request->get('bidang', '');         // filter by bidang (nama pada tabel bidang)
        $pickUnit   = $request->get('pick_unit');          // id unit dari halaman Units
        $perPage    = (int) ($request->get('per_page', 12) ?: 12);

        // Jika datang dari Units (pick_unit=id), ambil nama unit-nya lalu pakai sebagai filter default
        if ($pickUnit && $unitName === '') {
            $pickedName = DB::table('units')->where('id', $pickUnit)->value('nama');
            if ($pickedName) {
                $unitName = $pickedName;
            }
        }

        // Master dropdown UNIT (aktif)
        $daftar_unit = DB::table('units')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        // Master dropdown BIDANG (aktif)
        $daftar_bidang = DB::table('bidang')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        $daftar_jabatan = DB::table('jabatan')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->get();

        $qry = DB::table('users as u')
            ->leftJoin('jabatan as j','j.id','=','u.jabatan_id')
            ->select(
                'u.id','u.name','u.jabatan','u.jabatan_id',
                DB::raw('COALESCE(u.jabatan_keterangan, j.keterangan) as jabatan_keterangan'),
                'u.email','u.no_hp','u.unit','u.bidang','u.tingkatan','u.role','u.hirarki','u.created_at',
                'j.nama as jabatan_ref'
            );

        if ($q !== '') {
            $qry->where(function ($w) use ($q) {
                $w->where('u.name', 'like', "%{$q}%")
                  ->orWhere('u.email', 'like', "%{$q}%")
                  ->orWhere('u.jabatan', 'like', "%{$q}%")
                  ->orWhere('u.unit', 'like', "%{$q}%")
                  ->orWhere('u.bidang', 'like', "%{$q}%")
                  ->orWhere('u.no_hp', 'like', "%{$q}%")
                  ->orWhere('j.nama', 'like', "%{$q}%");
            });
        }

        if ($role !== '' && in_array($role, ['admin','operator','notulis','peserta','approval'], true)) {
            $qry->where('role', $role);
        }

        if ($unitName !== '') {
            $qry->where('unit', $unitName);
        }

        if ($bidangName !== '') {
            $qry->where('bidang', $bidangName);
        }

        // Urutkan berdasarkan hirarki (kecil di atas), baru nama
        $qry->orderByRaw('COALESCE(u.hirarki, 9999) ASC')
            ->orderBy('u.name', 'asc');

        $daftar_user = $qry->paginate($perPage)->appends($request->all());

        return view('user.index', [
            'daftar_user'    => $daftar_user,
            'q'              => $q,
            'role'           => $role,
            'unitName'       => $unitName,
            'bidangName'     => $bidangName,
            'daftar_unit'    => $daftar_unit,
            'daftar_bidang'  => $daftar_bidang,
            'daftar_jabatan' => $daftar_jabatan,
            'perPage'        => $perPage,
            'pickUnit'       => $pickUnit,
        ]);
    }

    /**
     * Form create user.
     * - role dipilih dari: admin, operator, notulis, peserta
     * - jika tingkatan diisi (1/2) maka role akan di-set otomatis menjadi approval saat simpan
     * - unit & bidang diambil dari master (aktif saja)
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

        $daftar_bidang = DB::table('bidang')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        $daftar_jabatan = DB::table('jabatan')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->get();

        // Preselect unit jika datang dari Units (pick_unit=id)
        $pickUnit   = $request->get('pick_unit');
        $pickedName = null;
        if ($pickUnit) {
            $pickedName = DB::table('units')->where('id', $pickUnit)->value('nama');
        }

        return view('user.create', compact(
            'daftar_role', 'daftar_unit', 'daftar_tingkatan', 'daftar_bidang', 'daftar_jabatan', 'pickedName', 'pickUnit'
        ));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'jabatan_id'=> ['nullable', Rule::exists('jabatan','id')->where(function($q){ $q->where('is_active',1); })],
            'jabatan_keterangan' => 'nullable|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'no_hp'     => ['nullable','regex:/^0[0-9]{9,13}$/'],
            // unit harus ada di tabel units.nama dan aktif
            'unit'      => [
                'required',
                Rule::exists('units','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            // bidang opsional, tapi jika diisi harus ada di master bidang (aktif)
            'bidang'    => [
                'nullable',
                Rule::exists('bidang','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'tingkatan' => ['nullable', Rule::in([1,2])],
            'role'      => ['required', Rule::in(['admin','operator','notulis','peserta'])],
            'password'  => 'required|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ],[
            'unit.exists'   => 'Unit tidak valid atau non-aktif.',
            'bidang.exists' => 'Bidang tidak valid atau non-aktif.',
        ]);

        // Normalisasi role & tingkatan
        $roleInput = $request->role;
        $tingkatan = $request->tingkatan;

        if ($roleInput === 'operator') {
            $tingkatan = null;
            $role      = 'operator';
        } else {
            $role      = $request->filled('tingkatan') ? 'approval' : $roleInput;
        }

        $jabatanName = null;
        if ($request->filled('jabatan_id')) {
            $jabatanName = DB::table('jabatan')->where('id', $request->jabatan_id)->value('nama');
        }

        DB::table('users')->insert([
            'name'       => $request->name,
            'jabatan'    => $jabatanName,
            'jabatan_id' => $request->jabatan_id ?: null,
            'jabatan_keterangan' => $request->jabatan_keterangan ?: null,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'bidang'     => $request->bidang ?: null,
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

        $daftar_bidang = DB::table('bidang')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->pluck('nama')
            ->toArray();

        $daftar_jabatan = DB::table('jabatan')
            ->where('is_active', 1)
            ->orderBy('nama', 'asc')
            ->get();

        return view('user.edit', compact('user','daftar_role','daftar_unit','daftar_tingkatan','daftar_bidang','daftar_jabatan'));
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
            'jabatan_id'=> ['nullable', Rule::exists('jabatan','id')->where(function($q){ $q->where('is_active',1); })],
            'jabatan_keterangan' => 'nullable|string|max:255',
            'email'     => 'required|email|unique:users,email,'.$id,
            'no_hp'     => ['nullable','regex:/^0[0-9]{9,13}$/'],
            'unit'      => [
                'required',
                Rule::exists('units','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'bidang'    => [
                'nullable',
                Rule::exists('bidang','nama')->where(function ($q) {
                    $q->where('is_active', 1);
                }),
            ],
            'tingkatan' => ['nullable', Rule::in([1,2])],
            'role'      => ['required', Rule::in(['admin','operator','notulis','peserta'])],
            'password'  => 'nullable|min:6|confirmed',
            'hirarki'   => 'nullable|integer|min:0|max:65535',
        ],[
            'unit.exists'   => 'Unit tidak valid atau non-aktif.',
            'bidang.exists' => 'Bidang tidak valid atau non-aktif.',
        ]);

        $roleInput = $request->role;
        $tingkatan = $request->tingkatan;

        if ($roleInput === 'operator') {
            $tingkatan = null;
            $role      = 'operator';
        } else {
            $role      = $request->filled('tingkatan') ? 'approval' : $roleInput;
        }

        $jabatanName = null;
        if ($request->filled('jabatan_id')) {
            $jabatanName = DB::table('jabatan')->where('id', $request->jabatan_id)->value('nama');
        }

        $data = [
            'name'       => $request->name,
            'jabatan'    => $jabatanName,
            'jabatan_id' => $request->jabatan_id ?: null,
            'jabatan_keterangan' => $request->jabatan_keterangan ?: null,
            'email'      => $request->email,
            'no_hp'      => $request->no_hp,
            'unit'       => $request->unit,
            'bidang'     => $request->bidang ?: null,
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
