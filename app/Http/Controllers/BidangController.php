<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BidangController extends Controller
{
    /**
     * Index + pencarian + pagination.
     * Menampilkan jumlah user yang memakai setiap bidang.
     */
    public function index(Request $r)
    {
        $q       = trim($r->get('q', ''));
        $perPage = (int) ($r->get('per_page', 12) ?: 12);

        // Ambil master bidang + hitung pemakaian oleh users (users.bidang = bidang.nama)
        $rows = DB::table('bidang as b')
            ->select([
                'b.id',
                'b.nama',
                'b.singkatan',
                'b.keterangan',
                'b.is_active',
                DB::raw("(SELECT COUNT(*) FROM users u WHERE u.bidang = b.nama) as users_count"),
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('b.nama', 'like', "%{$q}%")
                      ->orWhere('b.singkatan', 'like', "%{$q}%")
                      ->orWhere('b.keterangan', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('b.is_active')
            ->orderBy('b.nama', 'asc')
            ->paginate($perPage)
            ->appends($r->all());

        return view('bidang.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    /**
     * Simpan bidang baru (modal Create).
     */
    public function store(Request $r)
    {
        $r->validate([
            'nama'       => ['required', 'string', 'max:120', 'unique:bidang,nama'],
            'singkatan'  => ['nullable', 'string', 'max:40'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['required', Rule::in([0,1])],
        ]);

        DB::table('bidang')->insert([
            'nama'        => $r->nama,
            'singkatan'   => $r->singkatan ?: null,
            'keterangan'  => $r->keterangan ?: null,
            'is_active'   => (int) $r->is_active,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Bidang berhasil ditambahkan.');
    }

    /**
     * Update bidang (modal Edit).
     * Jika nama berubah, kita cascade-rename ke users.bidang agar konsisten.
     */
    public function update(Request $r, $id)
    {
        $row = DB::table('bidang')->where('id', $id)->first();
        if (!$row) {
            return back()->with('error', 'Bidang tidak ditemukan.');
        }

        $r->validate([
            'nama'       => ['required', 'string', 'max:120', Rule::unique('bidang','nama')->ignore($id)],
            'singkatan'  => ['nullable', 'string', 'max:40'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['required', Rule::in([0,1])],
        ]);

        $oldName = $row->nama;
        $newName = $r->nama;

        DB::beginTransaction();
        try {
            // Update master bidang
            DB::table('bidang')->where('id', $id)->update([
                'nama'        => $newName,
                'singkatan'   => $r->singkatan ?: null,
                'keterangan'  => $r->keterangan ?: null,
                'is_active'   => (int) $r->is_active,
                'updated_at'  => now(),
            ]);

            // Jika nama berubah → cascade ke users.bidang
            $renamed = 0;
            if ($oldName !== $newName) {
                $renamed = DB::table('users')->where('bidang', $oldName)->update([
                    'bidang'     => $newName,
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $msg = 'Bidang berhasil diperbarui.';
            if ($oldName !== $newName) {
                $msg .= " ({$renamed} user diperbarui bidangnya)";
            }
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui bidang: '.$e->getMessage());
        }
    }

    /**
     * Hapus bidang.
     * Safety: blok jika masih dipakai user (agar tidak memutus referensi string).
     * (Kalau ingin memaksa, bisa tambahkan opsi force & set users.bidang = null sebelum delete.)
     */
    public function destroy($id)
    {
        $row = DB::table('bidang')->where('id', $id)->first();
        if (!$row) {
            return back()->with('error', 'Bidang tidak ditemukan.');
        }

        $used = (int) DB::table('users')->where('bidang', $row->nama)->count();
        if ($used > 0) {
            return back()->with('error', "Tidak dapat menghapus: bidang masih dipakai oleh {$used} user.");
        }

        DB::table('bidang')->where('id', $id)->delete();
        return back()->with('success', 'Bidang berhasil dihapus.');
    }
}
