<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * List + filter + paginate.
     */
    public function index(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $status  = $request->get('status', ''); // '', '1', '0'
        $perPage = (int) ($request->get('per_page', 12) ?: 12);

        $qry = DB::table('units as u')
            ->leftJoin('users as usr', 'usr.unit', '=', 'u.nama')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($s) use ($q) {
                    $s->where('u.nama', 'like', "%{$q}%")
                      ->orWhere('u.singkatan', 'like', "%{$q}%")
                      ->orWhere('u.keterangan', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && in_array($status, ['0','1'], true), function ($w) use ($status) {
                $w->where('u.is_active', (int) $status);
            })
            ->select(
                'u.id','u.nama','u.singkatan','u.keterangan','u.is_active','u.created_at','u.updated_at',
                DB::raw('COUNT(usr.id) as jml_pengguna')
            )
            ->groupBy('u.id','u.nama','u.singkatan','u.keterangan','u.is_active','u.created_at','u.updated_at')
            ->orderBy('u.is_active','desc')
            ->orderBy('u.nama','asc');

        $units = $qry->paginate($perPage)->appends($request->all());

        return view('unit.index', compact('units', 'q', 'status', 'perPage'));
    }

    /**
     * Store unit baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama'       => 'required|string|max:120|unique:units,nama',
            'singkatan'  => 'nullable|string|max:20|unique:units,singkatan',
            'keterangan' => 'nullable|string|max:500',
            'is_active'  => 'nullable|boolean',
        ],[
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique'   => 'Nama unit sudah digunakan.',
            'singkatan.unique' => 'Singkatan sudah digunakan.',
        ]);

        DB::table('units')->insert([
            'nama'       => $request->nama,
            'singkatan'  => $request->singkatan ?: null,
            'keterangan' => $request->keterangan ?: null,
            'is_active'  => $request->boolean('is_active') ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kalau mau langsung arahkan ke Users untuk tetapkan:
        if ($request->boolean('go_assign')) {
            $idBaru = DB::getPdo()->lastInsertId();
            return redirect()->route('user.index', ['pick_unit' => $idBaru])
                ->with('success', 'Unit berhasil ditambahkan. Silakan tetapkan ke pengguna.');
        }

        return redirect()->route('units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    /**
     * Update unit.
     */
    public function update(Request $request, $id)
    {
        $unit = DB::table('units')->where('id', $id)->first();
        if (!$unit) {
            return redirect()->route('units.index')->with('error', 'Unit tidak ditemukan.');
        }

        $request->validate([
            'nama'       => 'required|string|max:120|unique:units,nama,' . $id,
            'singkatan'  => 'nullable|string|max:20|unique:units,singkatan,' . $id,
            'keterangan' => 'nullable|string|max:500',
            'is_active'  => 'nullable|boolean',
        ],[
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique'   => 'Nama unit sudah digunakan.',
            'singkatan.unique' => 'Singkatan sudah digunakan.',
        ]);

        DB::table('units')->where('id', $id)->update([
            'nama'       => $request->nama,
            'singkatan'  => $request->singkatan ?: null,
            'keterangan' => $request->keterangan ?: null,
            'is_active'  => $request->boolean('is_active') ? 1 : 0,
            'updated_at' => now(),
        ]);

        // Opsi: kalau klik "Simpan & Tetapkan"
        if ($request->boolean('go_assign')) {
            return redirect()->route('user.index', ['pick_unit' => $id])
                ->with('success', 'Unit diperbarui. Silakan tetapkan ke pengguna.');
        }

        return redirect()->route('units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    /**
     * Hapus unit (dicegah jika sedang dipakai user).
     */
    public function destroy($id)
    {
        $unit = DB::table('units')->where('id', $id)->first();
        if (!$unit) {
            return redirect()->route('units.index')->with('error', 'Unit tidak ditemukan.');
        }

        $dipakai = DB::table('users')->where('unit', $unit->nama)->exists();
        if ($dipakai) {
            return redirect()->route('units.index')->with('error', 'Unit ini tidak dapat dihapus karena masih dipakai oleh pengguna.');
        }

        DB::table('units')->where('id', $id)->delete();
        return redirect()->route('units.index')->with('success', 'Unit berhasil dihapus.');
    }
}
