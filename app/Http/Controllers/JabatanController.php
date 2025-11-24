<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JabatanController extends Controller
{
    /**
     * Tampilkan daftar jabatan + pencarian.
     */
    public function index(Request $r)
    {
        $q       = trim($r->get('q', ''));
        $perPage = (int) ($r->get('per_page', 12) ?: 12);

        $rows = DB::table('jabatan as j')
            ->select([
                'j.id',
                'j.nama',
                'j.kategori',
                'j.keterangan',
                'j.is_active',
                DB::raw('(SELECT COUNT(*) FROM users u WHERE u.jabatan_id = j.id) as users_count'),
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('j.nama', 'like', "%{$q}%")
                      ->orWhere('j.kategori', 'like', "%{$q}%")
                      ->orWhere('j.keterangan', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('j.is_active')
            ->orderBy('j.nama', 'asc')
            ->paginate($perPage)
            ->appends($r->all());

        return view('jabatan.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    /**
     * Simpan jabatan baru.
     */
    public function store(Request $r)
    {
        $r->validate([
            'nama'       => ['required', 'string', 'max:150', 'unique:jabatan,nama'],
            'kategori'   => ['nullable', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['required', Rule::in([0,1])],
        ]);

        DB::table('jabatan')->insert([
            'nama'        => $r->nama,
            'kategori'    => $r->kategori ?: null,
            'keterangan'  => $r->keterangan ?: null,
            'is_active'   => (int) $r->is_active,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    /**
     * Update jabatan.
     */
    public function update(Request $r, $id)
    {
        $row = DB::table('jabatan')->where('id', $id)->first();
        if (!$row) {
            return back()->with('error', 'Jabatan tidak ditemukan.');
        }

        $r->validate([
            'nama'       => ['required', 'string', 'max:150', Rule::unique('jabatan','nama')->ignore($id)],
            'kategori'   => ['nullable', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['required', Rule::in([0,1])],
        ]);

        DB::table('jabatan')->where('id', $id)->update([
            'nama'        => $r->nama,
            'kategori'    => $r->kategori ?: null,
            'keterangan'  => $r->keterangan ?: null,
            'is_active'   => (int) $r->is_active,
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Hapus jabatan jika tidak dipakai user.
     */
    public function destroy($id)
    {
        $row = DB::table('jabatan')->where('id', $id)->first();
        if (!$row) {
            return back()->with('error', 'Jabatan tidak ditemukan.');
        }

        $used = (int) DB::table('users')->where('jabatan_id', $id)->count();
        if ($used > 0) {
            return back()->with('error', "Tidak dapat menghapus: jabatan dipakai oleh {$used} user.");
        }

        DB::table('jabatan')->where('id', $id)->delete();
        return back()->with('success', 'Jabatan berhasil dihapus.');
    }
}
