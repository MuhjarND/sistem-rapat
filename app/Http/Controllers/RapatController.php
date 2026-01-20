<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use iio\libmergepdf\Merger;

class RapatController extends Controller
{
    /**
     * Ambil daftar user yang bisa dipilih sebagai peserta (untuk create/edit):
     * - Sertakan semua user NON-admin/superadmin
     * - (opsional) filter aktif jika kolomnya ada
     * Return kolom: id, name, hirarki, (jabatan), (unit|bagian as unit), (bidang)
     */
    private function getSelectableParticipants()
    {
        $q = DB::table('users');

        // Susun daftar kolom dinamis
        $select = ['id', 'name', 'hirarki'];
        if (Schema::hasColumn('users', 'jabatan')) {
            $select[] = 'jabatan';
        }
        // Unit (atau alias dari 'bagian')
        if (Schema::hasColumn('users', 'unit')) {
            $select[] = 'unit';
        } elseif (Schema::hasColumn('users', 'bagian')) {
            $select[] = DB::raw('bagian as unit');
        }
        // Bidang (opsional)
        if (Schema::hasColumn('users', 'bidang')) {
            $select[] = 'bidang';
        }

        $q->select($select);

        if (Schema::hasColumn('users', 'role')) {
            $q->whereNotIn('role', ['admin', 'superadmin']);
        }
        if (Schema::hasColumn('users', 'is_active')) {
            $q->where('is_active', 1);
        } elseif (Schema::hasColumn('users', 'active')) {
            $q->where('active', 1);
        } elseif (Schema::hasColumn('users', 'status')) {
            $q->where('status', 'aktif');
        }

        return $q
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();
    }

    /** Helper: daftar Unit unik (untuk quick-pick) */
    private function getDistinctUnits(): array
    {
        if (Schema::hasColumn('users', 'unit')) {
            return DB::table('users')
                ->whereNotNull('unit')
                ->where('unit', '!=', '')
                ->distinct()
                ->orderBy('unit')
                ->pluck('unit')
                ->toArray();
        }
        if (Schema::hasColumn('users', 'bagian')) {
            return DB::table('users')
                ->whereNotNull('bagian')
                ->where('bagian', '!=', '')
                ->distinct()
                ->orderBy('bagian')
                ->pluck('bagian')
                ->toArray();
        }
        return [];
    }

    /** Helper: daftar Bidang unik (untuk quick-pick) */
    private function getDistinctBidang(): array
    {
        if (!Schema::hasColumn('users', 'bidang')) return [];
        return DB::table('users')
            ->whereNotNull('bidang')
            ->where('bidang', '!=', '')
            ->distinct()
            ->orderBy('bidang')
            ->pluck('bidang')
            ->toArray();
    }

    // Tampilkan daftar rapat
    public function index(Request $request)
    {
        // === Master data ===
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();
        $pendingScheduledCount = DB::table('rapat')
            ->whereNotNull('schedule_type')
            ->whereNull('approval_enqueued_at')
            ->count();
        $pendingScheduledList = DB::table('rapat')
            ->whereNotNull('schedule_type')
            ->whereNull('approval_enqueued_at')
            ->select('id','judul','nomor_undangan','tanggal','waktu_mulai','schedule_type')
            ->orderBy('tanggal','asc')
            ->limit(5)
            ->get();

        // === Base query daftar rapat ===
        $query = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->leftJoin('users as appr1', 'rapat.approval1_user_id', '=', 'appr1.id')
            ->leftJoin('users as appr2', 'rapat.approval2_user_id', '=', 'appr2.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pembuat.name as nama_pembuat',
                'appr1.name as approval1_nama',
                'appr2.name as approval2_nama'
            );

        // === Filter ===
        if ($request->filled('kategori')) {
            $query->where('rapat.id_kategori', $request->kategori);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('rapat.tanggal', $request->tanggal);
        }
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function($q) use ($kw) {
                $q->where('rapat.judul', 'like', '%'.$kw.'%')
                  ->orWhere('rapat.nomor_undangan', 'like', '%'.$kw.'%')
                  ->orWhere('rapat.tempat', 'like', '%'.$kw.'%');
            });
        }

        // === Paging ===
        $daftar_rapat = $query
            ->orderBy('tanggal', 'desc')
            ->paginate(6)
            ->appends($request->all());

        // === Status label ===
        foreach ($daftar_rapat as $rapat) {
            $rapat->status_label = $this->getStatusRapat($rapat);
        }

        // === PRELOAD: Peserta terpilih (nama saja) GÃ‡Ã¶ urut by hirarki lalu nama
        $rapatIds = $daftar_rapat->pluck('id')->all();
        if (!empty($rapatIds)) {
            $pesertaMap = DB::table('undangan as u')
                ->join('users as usr', 'usr.id', '=', 'u.id_user')
                ->whereIn('u.id_rapat', $rapatIds)
                ->select('u.id_rapat','usr.id as id','usr.name','usr.hirarki')
                ->orderByRaw('COALESCE(usr.hirarki, 9999) ASC')
                ->orderBy('usr.name', 'asc')
                ->get()
                ->groupBy('id_rapat')
                ->map(function ($group) {
                    return $group->map(function ($row) {
                        return ['id' => (int)$row->id, 'text' => $row->name];
                    })->values()->all();
                });

            foreach ($daftar_rapat as $r) {
                $r->peserta_terpilih = $pesertaMap->get($r->id, []);
            }

            // === PRELOAD: Approval map (undangan & absensi) ===
            $apprRows = DB::table('approval_requests as ar')
                ->leftJoin('users as u', 'u.id', '=', 'ar.approver_user_id')
                ->whereIn('ar.rapat_id', $rapatIds)
                ->whereIn('ar.doc_type', ['undangan', 'absensi'])
                ->select(
                    'ar.rapat_id','ar.doc_type','ar.order_index','ar.status',
                    'ar.signed_at','ar.rejected_at','ar.rejection_note','ar.updated_at',
                    'u.name as approver_name'
                )
                ->orderBy('ar.doc_type')->orderBy('ar.order_index')
                ->get();

            $approvalMapByRapat = $apprRows
                ->groupBy('rapat_id')
                ->map(function ($rowsPerRapat) {
                    $priority = ['approved' => 3, 'rejected' => 2, 'pending' => 1, 'blocked' => 0];
                    return $rowsPerRapat->groupBy('doc_type')->map(function ($groupPerType) use ($priority) {
                        return $groupPerType
                            ->groupBy('order_index')
                            ->map(function ($rowsPerOrder) use ($priority) {
                                $picked = collect($rowsPerOrder)
                                    ->sortByDesc(function ($row) use ($priority) {
                                        return $priority[$row->status] ?? 0;
                                    })
                                    ->sortByDesc('updated_at')
                                    ->first();

                                return [
                                    'order'          => (int)$picked->order_index,
                                    'name'           => $picked->approver_name ?: 'Approver',
                                    'status'         => $picked->status,
                                    'signed_at'      => $picked->signed_at,
                                    'rejected_at'    => $picked->rejected_at,
                                    'rejection_note' => $picked->rejection_note,
                                ];
                            })
                            ->values()
                            ->all();
                    })->toArray();
                });

            foreach ($daftar_rapat as $r) {
                $r->approval_map = $approvalMapByRapat->get($r->id, []);
            }
        } else {
            foreach ($daftar_rapat as $r) {
                $r->peserta_terpilih = [];
                $r->approval_map = [];
            }
        }

        // === Master daftar peserta (non-admin; sudah urut) + kolom unit & jabatan & bidang
        $daftar_peserta = $this->getSelectableParticipants();

        // === Daftar Approver ===
        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->where('tingkatan', 2)
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        // === Quick-pick sources ===
        $daftar_unit   = $this->getDistinctUnits();
        $daftar_bidang = $this->getDistinctBidang();

        return view('rapat.index', compact(
            'daftar_rapat',
            'daftar_kategori',
            'daftar_peserta',
            'approval1_list',
            'approval2_list',
            'daftar_unit',
            'daftar_bidang',
            'pendingScheduledCount',
            'pendingScheduledList'
        ));
    }

    // Form tambah rapat
    public function create()
    {
        $daftar_peserta  = $this->getSelectableParticipants();  // berisi unit & jabatan & bidang
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $daftar_unit   = $this->getDistinctUnits();
        $daftar_bidang = $this->getDistinctBidang();

        return view('rapat.create', compact(
            'daftar_peserta',
            'daftar_kategori',
            'approval1_list',
            'approval2_list',
            'daftar_unit',
            'daftar_bidang'
        ));
    }

    // Form jadwal rapat berkala (tidak langsung ke approval)
    public function createSchedule()
    {
        $daftar_peserta  = $this->getSelectableParticipants();
        $daftar_kategori = DB::table('kategori_rapat')->orderBy('nama')->get();
        $pendingScheduledCount = DB::table('rapat')
            ->whereNotNull('schedule_type')
            ->whereNull('approval_enqueued_at')
            ->count();
        $pendingScheduledList = DB::table('rapat')
            ->whereNotNull('schedule_type')
            ->whereNull('approval_enqueued_at')
            ->select('id','judul','nomor_undangan','tanggal','waktu_mulai','schedule_type')
            ->orderBy('tanggal','asc')
            ->limit(5)
            ->get();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role', 'approval')
            ->where('tingkatan', 2)
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $daftar_unit   = $this->getDistinctUnits();
        $daftar_bidang = $this->getDistinctBidang();

        return view('rapat.schedule', compact(
            'daftar_peserta',
            'daftar_kategori',
            'approval1_list',
            'approval2_list',
            'daftar_unit',
            'daftar_bidang',
            'pendingScheduledCount',
            'pendingScheduledList'
        ));
    }

    // Daftar rapat terjadwal (schedule_type tidak null)
    public function scheduleIndex(Request $request)
    {
        $rapatList = DB::table('rapat')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->leftJoin('users as pembuat', 'rapat.dibuat_oleh', '=', 'pembuat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'pembuat.name as nama_pembuat'
            )
            ->whereNotNull('rapat.schedule_type');

        if ($request->filled('schedule_type')) {
            $rapatList->where('rapat.schedule_type', $request->schedule_type);
        }
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $rapatList->where(function($q) use ($kw) {
                $q->where('rapat.judul','like','%'.$kw.'%')
                  ->orWhere('rapat.nomor_undangan','like','%'.$kw.'%')
                  ->orWhere('rapat.schedule_label','like','%'.$kw.'%');
            });
        }

        $rapatList = $rapatList
            ->orderBy('rapat.approval_enqueued_at','asc')
            ->orderBy('rapat.tanggal','asc')
            ->get()
            ->map(function($r){
                $r->status_label = $this->getStatusRapat($r);
                return $r;
            });

        return view('rapat.schedule_index', compact('rapatList'));
    }

    // Proses simpan rapat & undangan
    public function store(Request $request)
    {
        $pesertaRule = $request->boolean('pilih_semua') ? 'nullable|array' : 'required|array|min:1';

        $kategoriNama = $request->filled('id_kategori')
            ? DB::table('kategori_rapat')->where('id', $request->id_kategori)->value('nama')
            : null;
        $isPakta = strtolower(trim((string) $kategoriNama)) === strtolower('Penandatanganan Pakta Integritas dan Komitmen Bersama');

        $isVirtual = $request->boolean('is_virtual');
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan',
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'lampiran_tambahan' => 'nullable|in:0,1',
            'lampiran_tambahan_file' => 'required_if:lampiran_tambahan,1|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            'jenis_pakaian'     => [$isPakta ? 'required' : 'nullable', 'string', 'max:120'],
            'is_virtual'        => 'nullable|boolean',
            'meeting_id'        => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'meeting_passcode'  => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'approval1_user_id' => 'required|exists:users,id',
            'approval1_jabatan_manual' => 'nullable|string|max:150',
            'approval2_user_id' => 'nullable|exists:users,id',
            'peserta'           => $pesertaRule,
            'peserta.*'         => [
                'integer',
                Rule::exists('users','id')->where(function ($q) {
                    if (Schema::hasColumn('users','role')) {
                        $q->whereNotIn('role', ['admin','superadmin']);
                    }
                    if (Schema::hasColumn('users','is_active')) {
                        $q->where('is_active', 1);
                    }
                }),
            ],
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        $lampiranMeta = [];
        if ($request->input('lampiran_tambahan') === '1' && $request->hasFile('lampiran_tambahan_file')) {
            $file = $request->file('lampiran_tambahan_file');
            if ($file && $file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize();

                $dest = public_path('uploads/rapat_lampiran');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0775, true);
                }

                $ext = strtolower($file->getClientOriginalExtension());
                $name = 'lampiran-rapat-'.Str::random(12).'.'.$ext;
                $file->move($dest, $name);
                $relPath = 'uploads/rapat_lampiran/'.$name;

                if (Schema::hasColumn('rapat', 'lampiran_tambahan_path')) {
                    $lampiranMeta['lampiran_tambahan_path'] = $relPath;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_nama')) {
                    $lampiranMeta['lampiran_tambahan_nama'] = $originalName;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_mime')) {
                    $lampiranMeta['lampiran_tambahan_mime'] = $mimeType;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_size')) {
                    $lampiranMeta['lampiran_tambahan_size'] = $fileSize;
                }
            }
        }

        $id_rapat = DB::table('rapat')->insertGetId(array_merge([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'jenis_pakaian'     => Schema::hasColumn('rapat', 'jenis_pakaian') ? ($request->jenis_pakaian ?: null) : null,
            'is_virtual'        => Schema::hasColumn('rapat', 'is_virtual') ? ($isVirtual ? 1 : 0) : null,
            'meeting_id'        => Schema::hasColumn('rapat', 'meeting_id') ? ($isVirtual ? ($request->meeting_id ?: null) : null) : null,
            'meeting_passcode'  => Schema::hasColumn('rapat', 'meeting_passcode') ? ($isVirtual ? ($request->meeting_passcode ?: null) : null) : null,
            'dibuat_oleh'       => Auth::id(),
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval1_jabatan_manual' => $request->approval1_jabatan_manual ?: null,
            'approval2_user_id' => $request->approval2_user_id,
            'token_qr'          => Str::random(32),     // QR internal peserta (login)
            'public_code'       => Str::random(12),     // token publik (absensi publik, tanpa login)
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $lampiranMeta));

        if ($request->boolean('pilih_semua')) {
            $allowedIds = $this->getSelectableParticipants()->pluck('id')->all();
        } else {
            $pesertaIds = array_unique(array_map('intval', $request->peserta ?: []));
            $allowedIds = DB::table('users')
                ->when(Schema::hasColumn('users','role'), function($q){
                    $q->whereNotIn('role',['admin','superadmin']);
                })
                ->whereIn('id', $pesertaIds)
                ->pluck('id')->all();
        }

        $now = now();
        $bulk = [];
        foreach ($allowedIds as $uid) {
            $bulk[] = [
                'id_rapat'   => $id_rapat,
                'id_user'    => $uid,
                'status'     => 'terkirim',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($bulk)) {
            DB::table('undangan')->insert($bulk);
        }

        $this->createApprovalChainForDoc($id_rapat, 'undangan');
        $this->createApprovalChainForDoc($id_rapat, 'absensi');

        DB::table('rapat')->where('id', $id_rapat)->update([
            'approval_enqueued_at' => now(),
            'updated_at'           => now(),
        ]);

        $rapat = DB::table('rapat')->where('id', $id_rapat)->first();
        $this->notifyFirstApprover($id_rapat, 'undangan', $rapat->judul);

        return redirect()->route('rapat.index')->with('success', 'Rapat & Undangan berhasil dibuat. Notifikasi WA sudah dikirim!');
    }

    // Simpan rapat jadwal (tidak langsung enqueue approval)
    public function storeSchedule(Request $request)
    {
        $pesertaRule = $request->boolean('pilih_semua') ? 'nullable|array' : 'required|array|min:1';

        $kategoriNama = $request->filled('id_kategori')
            ? DB::table('kategori_rapat')->where('id', $request->id_kategori)->value('nama')
            : null;
        $isPakta = strtolower(trim((string) $kategoriNama)) === strtolower('Penandatanganan Pakta Integritas dan Komitmen Bersama');

        $isVirtual = $request->boolean('is_virtual');
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan',
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'lampiran_tambahan' => 'nullable|in:0,1',
            'lampiran_tambahan_file' => 'required_if:lampiran_tambahan,1|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            'jenis_pakaian'     => [$isPakta ? 'required' : 'nullable', 'string', 'max:120'],
            'is_virtual'        => 'nullable|boolean',
            'meeting_id'        => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'meeting_passcode'  => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'approval1_user_id' => 'required|exists:users,id',
            'approval1_jabatan_manual' => 'nullable|string|max:150',
            'approval2_user_id' => 'nullable|exists:users,id',
            'schedule_label'    => 'required|string|max:120',
            'schedule_type'     => 'required|in:bulanan,triwulanan,tahunan',
            'peserta'           => $pesertaRule,
            'peserta.*'         => [
                'integer',
                Rule::exists('users','id')->where(function ($q) {
                    if (Schema::hasColumn('users','role')) {
                        $q->whereNotIn('role', ['admin','superadmin']);
                    }
                    if (Schema::hasColumn('users','is_active')) {
                        $q->where('is_active', 1);
                    }
                }),
            ],
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        $lampiranMeta = [];
        if ($request->input('lampiran_tambahan') === '1' && $request->hasFile('lampiran_tambahan_file')) {
            $file = $request->file('lampiran_tambahan_file');
            if ($file && $file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize();

                $dest = public_path('uploads/rapat_lampiran');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0775, true);
                }

                $ext = strtolower($file->getClientOriginalExtension());
                $name = 'lampiran-rapat-'.Str::random(12).'.'.$ext;
                $file->move($dest, $name);
                $relPath = 'uploads/rapat_lampiran/'.$name;

                if (Schema::hasColumn('rapat', 'lampiran_tambahan_path')) {
                    $lampiranMeta['lampiran_tambahan_path'] = $relPath;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_nama')) {
                    $lampiranMeta['lampiran_tambahan_nama'] = $originalName;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_mime')) {
                    $lampiranMeta['lampiran_tambahan_mime'] = $mimeType;
                }
                if (Schema::hasColumn('rapat', 'lampiran_tambahan_size')) {
                    $lampiranMeta['lampiran_tambahan_size'] = $fileSize;
                }
            }
        }

        $id_rapat = DB::table('rapat')->insertGetId(array_merge([
            'nomor_undangan'    => $request->nomor_undangan,
            'judul'             => $request->judul,
            'deskripsi'         => $request->deskripsi,
            'tanggal'           => $request->tanggal,
            'waktu_mulai'       => $request->waktu_mulai,
            'tempat'            => $request->tempat,
            'jenis_pakaian'     => Schema::hasColumn('rapat', 'jenis_pakaian') ? ($request->jenis_pakaian ?: null) : null,
            'is_virtual'        => Schema::hasColumn('rapat', 'is_virtual') ? ($isVirtual ? 1 : 0) : null,
            'meeting_id'        => Schema::hasColumn('rapat', 'meeting_id') ? ($isVirtual ? ($request->meeting_id ?: null) : null) : null,
            'meeting_passcode'  => Schema::hasColumn('rapat', 'meeting_passcode') ? ($isVirtual ? ($request->meeting_passcode ?: null) : null) : null,
            'dibuat_oleh'       => Auth::id(),
            'id_kategori'       => $request->id_kategori,
            'approval1_user_id' => $request->approval1_user_id,
            'approval1_jabatan_manual' => $request->approval1_jabatan_manual ?: null,
            'approval2_user_id' => $request->approval2_user_id,
            'token_qr'          => Str::random(32),
            'public_code'       => Str::random(12),
            'schedule_type'     => $request->schedule_type,
            'schedule_label'    => $request->schedule_label,
            'approval_enqueued_at' => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $lampiranMeta));

        if ($request->boolean('pilih_semua')) {
            $allowedIds = $this->getSelectableParticipants()->pluck('id')->all();
        } else {
            $pesertaIds = array_unique(array_map('intval', $request->peserta ?: []));
            $allowedIds = DB::table('users')
                ->when(Schema::hasColumn('users','role'), function($q){
                    $q->whereNotIn('role',['admin','superadmin']);
                })
                ->whereIn('id', $pesertaIds)
                ->pluck('id')->all();
        }

        $now = now();
        $bulk = [];
        foreach ($allowedIds as $uid) {
            $bulk[] = [
                'id_rapat'   => $id_rapat,
                'id_user'    => $uid,
                'status'     => 'terkirim',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($bulk)) {
            DB::table('undangan')->insert($bulk);
        }

        return redirect()
            ->route('rapat.index')
            ->with('success', 'Jadwal rapat berhasil dibuat. Klik "Kirim ke Approval" dari daftar rapat ketika siap.');
    }

    // Detail rapat
    public function show($id)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('users as a1', 'rapat.approval1_user_id', '=', 'a1.id')
            ->leftJoin('users as a2', 'rapat.approval2_user_id', '=', 'a2.id')
            ->leftJoin('kategori_rapat', 'rapat.id_kategori', '=', 'kategori_rapat.id')
            ->select(
                'rapat.*',
                'kategori_rapat.nama as nama_kategori',
                'a1.name as approval1_nama',
                'a2.name as approval2_nama'
            )
            ->where('rapat.id', $id)
            ->first();

        if (!$rapat) abort(404);

        // Pastikan public_code tersedia (untuk absensi publik/umum)
        if (Schema::hasColumn('rapat','public_code') && empty($rapat->public_code)) {
            $newPublic = Str::random(12);
            DB::table('rapat')->where('id', $id)->update([
                'public_code' => $newPublic,
                'updated_at'  => now(),
            ]);
            $rapat->public_code = $newPublic;
        }

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email', 'users.jabatan', 'users.hirarki')
            ->orderByRaw('COALESCE(users.hirarki, 9999) ASC')
            ->orderBy('users.name','asc')
            ->get();

        // Token tamu lama (tetap dipertahankan)
        if (Schema::hasColumn('rapat', 'guest_token') && empty($rapat->guest_token)) {
            $newToken = Str::random(32);
            DB::table('rapat')->where('id', $id)->update([
                'guest_token' => $newToken,
                'updated_at'  => now(),
            ]);
            $rapat->guest_token = $newToken;
        }

        // ===== URL QR =====
        // QR Peserta (scan internal)
        $qrPesertaUrl = \Illuminate\Support\Facades\Route::has('absensi.scan')
            ? route('absensi.scan', $rapat->token_qr)
            : url('/absensi/scan/'.$rapat->token_qr);

        // QR Tamu (jika fitur tamu aktif di sistemmu)
        $qrTamuUrl = null;
        if (!empty($rapat->guest_token)) {
            $qrTamuUrl = \Illuminate\Support\Facades\Route::has('absensi.guest.form')
                ? route('absensi.guest.form', [$rapat->id, $rapat->guest_token])
                : url('/absensi/guest/'.$rapat->id.'/'.$rapat->guest_token); // perbaiki typo
        }

        // QR Publik (absensi publik tanpa login; pakai public_code)
        $qrPublikUrl = null;
        if (!empty($rapat->public_code)) {
            if (\Illuminate\Support\Facades\Route::has('absensi.publik.show')) {
                $qrPublikUrl = route('absensi.publik.show', $rapat->public_code);
            } else {
                $qrPublikUrl = url('/absensi/publik/'.$rapat->public_code);
            }
        }

        // ===== QR images =====
        $qrPesertaImg = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=' . urlencode($qrPesertaUrl);
        $qrTamuImg    = $qrTamuUrl
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=' . urlencode($qrTamuUrl)
            : null;
        $qrPublikImg  = $qrPublikUrl
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=' . urlencode($qrPublikUrl)
            : null;

        // Preview dokumen (tetap)
        $previewAbsensiUrl = \Illuminate\Support\Facades\Route::has('absensi.export.pdf')
            ? route('absensi.export.pdf', ['id_rapat' => $id, 'preview' => 1])
            : url("/absensi/laporan/{$id}?preview=1");

        $previewUndanganUrl = \Illuminate\Support\Facades\Route::has('rapat.undangan.pdf')
            ? route('rapat.undangan.pdf', $id)
            : url("/rapat/{$id}/undangan");

        return view('rapat.show', compact(
            'rapat',
            'daftar_peserta',
            'qrPesertaUrl',
            'qrPesertaImg',
            'qrTamuUrl',
            'qrTamuImg',
            'qrPublikUrl',
            'qrPublikImg',
            'previewUndanganUrl',
            'previewAbsensiUrl'
        ));
    }

    // Form edit rapat
    public function edit($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        // === Semua user non-admin untuk daftar peserta GÃ‡Ã¶ termasuk jabatan, unit, dan bidang
        $q = DB::table('users');
        if (Schema::hasColumn('users','role')) {
            $q->whereNotIn('role', ['admin','superadmin']);
        }
        $select = ['id','name','hirarki'];
        if (Schema::hasColumn('users','jabatan')) $select[] = 'jabatan';
        if (Schema::hasColumn('users','unit')) {
            $select[] = 'unit';
        } elseif (Schema::hasColumn('users','bagian')) {
            $select[] = DB::raw('bagian as unit');
        }
        if (Schema::hasColumn('users','bidang')) {
            $select[] = 'bidang';
        }

        $daftar_peserta = $q->select($select)
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name','asc')
            ->get();

        // === Peserta yang sudah dipilih di rapat ini
        $peserta_terpilih = DB::table('undangan as u')
            ->join('users as usr', 'usr.id', '=', 'u.id_user')
            ->where('u.id_rapat', $id)
            ->select('usr.id', 'usr.name', 'usr.hirarki')
            ->orderByRaw('COALESCE(usr.hirarki, 9999) ASC')
            ->orderBy('usr.name')
            ->get()
            ->map(function($r){
                return ['id' => (int)$r->id, 'text' => $r->name];
            })->toArray();

        $daftar_kategori  = DB::table('kategori_rapat')->orderBy('nama')->get();

        $approval1_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        $approval2_list = DB::table('users')
            ->select('id','name','tingkatan')
            ->where('role','approval')
            ->where('tingkatan', 2)
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name', 'asc')
            ->get();

        // Quick-pick sources untuk checklist
        $daftar_unit   = $this->getDistinctUnits();
        $daftar_bidang = $this->getDistinctBidang();

        $dropdownParentId = null;
        $pesertaWrapperId = 'peserta-wrapper-edit';

        return view('rapat.edit', compact(
            'rapat',
            'daftar_peserta',
            'peserta_terpilih',
            'daftar_kategori',
            'approval1_list',
            'approval2_list',
            'dropdownParentId',
            'pesertaWrapperId',
            'daftar_unit',
            'daftar_bidang'
        ));
    }

    // Update rapat & undangan
    public function update(Request $request, $id)
    {
        $pesertaRule = $request->boolean('pilih_semua') ? 'nullable|array' : 'required|array|min:1';

        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);
        $isEnqueued = !empty($rapat->approval_enqueued_at);

        $kategoriNama = $request->filled('id_kategori')
            ? DB::table('kategori_rapat')->where('id', $request->id_kategori)->value('nama')
            : null;
        $isPakta = strtolower(trim((string) $kategoriNama)) === strtolower('Penandatanganan Pakta Integritas dan Komitmen Bersama');

        $isVirtual = $request->boolean('is_virtual');
        $request->validate([
            'nomor_undangan'    => 'required|unique:rapat,nomor_undangan,' . $id,
            'judul'             => 'required',
            'deskripsi'         => 'nullable',
            'tanggal'           => 'required|date',
            'waktu_mulai'       => 'required',
            'tempat'            => 'required',
            'jenis_pakaian'     => [$isPakta ? 'required' : 'nullable', 'string', 'max:120'],
            'is_virtual'        => 'nullable|boolean',
            'meeting_id'        => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'meeting_passcode'  => [$isVirtual ? 'required' : 'nullable', 'string', 'max:120'],
            'approval1_user_id' => 'required|exists:users,id',
            'approval1_jabatan_manual' => 'nullable|string|max:150',
            'approval2_user_id' => 'nullable|exists:users,id',
            'peserta'           => $pesertaRule,
            'peserta.*'         => [
                'integer',
                Rule::exists('users','id')->where(function ($q) {
                    if (Schema::hasColumn('users','role')) {
                        $q->whereNotIn('role', ['admin','superadmin']);
                    }
                    if (Schema::hasColumn('users','is_active')) {
                        $q->where('is_active', 1);
                    }
                }),
            ],
            'id_kategori'       => 'required|exists:kategori_rapat,id'
        ]);

        DB::beginTransaction();
        try {
            DB::table('rapat')->where('id', $id)->update([
                'nomor_undangan'    => $request->nomor_undangan,
                'judul'             => $request->judul,
                'deskripsi'         => $request->deskripsi,
                'tanggal'           => $request->tanggal,
                'waktu_mulai'       => $request->waktu_mulai,
                'tempat'            => $request->tempat,
                'jenis_pakaian'     => Schema::hasColumn('rapat', 'jenis_pakaian') ? ($request->jenis_pakaian ?: null) : null,
                'is_virtual'        => Schema::hasColumn('rapat', 'is_virtual') ? ($isVirtual ? 1 : 0) : null,
                'meeting_id'        => Schema::hasColumn('rapat', 'meeting_id') ? ($isVirtual ? ($request->meeting_id ?: null) : null) : null,
                'meeting_passcode'  => Schema::hasColumn('rapat', 'meeting_passcode') ? ($isVirtual ? ($request->meeting_passcode ?: null) : null) : null,
                'id_kategori'       => $request->id_kategori,
                'approval1_user_id' => $request->approval1_user_id,
                'approval1_jabatan_manual' => $request->approval1_jabatan_manual ?: null,
                'approval2_user_id' => $request->approval2_user_id,
                'schedule_type'     => $rapat->schedule_type,
                'updated_at'        => now(),
            ]);

            DB::table('undangan')->where('id_rapat', $id)->delete();

            if ($request->boolean('pilih_semua')) {
                $allowedIds = $this->getSelectableParticipants()->pluck('id')->all();
            } else {
                $pesertaIds = array_unique(array_map('intval', $request->peserta ?: []));
                $allowedIds = DB::table('users')
                    ->when(Schema::hasColumn('users','role'), function($q){
                        $q->whereNotIn('role',['admin','superadmin']);
                    })
                    ->whereIn('id', $pesertaIds)
                    ->pluck('id')->all();
            }

            $now = now();
            $bulk = [];
            foreach ($allowedIds as $uid) {
                $bulk[] = [
                    'id_rapat'   => $id,
                    'id_user'    => $uid,
                    'status'     => 'terkirim',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($bulk)) {
                DB::table('undangan')->insert($bulk);
            }

            if ($isEnqueued) {
                // === Sinkronisasi approval UNDANGAN (versimu)
                $desired = [];
                if (!empty($request->approval2_user_id)) $desired[1] = (int)$request->approval2_user_id;
                if (!empty($request->approval1_user_id)) $desired[2] = (int)$request->approval1_user_id;

                $existing = DB::table('approval_requests')
                    ->where('rapat_id', $id)
                    ->where('doc_type', 'undangan')
                    ->orderBy('order_index')
                    ->get();

                $approvedByOrder = $existing->where('status','approved')->keyBy('order_index');
                $nonApproved     = $existing->where('status','!=','approved');

                foreach ($nonApproved as $row) {
                    if (!isset($desired[$row->order_index]) || (int)$desired[$row->order_index] !== (int)$row->approver_user_id) {
                        DB::table('approval_requests')->where('id', $row->id)->delete();
                    }
                }

                foreach ($desired as $ord => $uid) {
                    $existsOrd = DB::table('approval_requests')
                        ->where('rapat_id', $id)
                        ->where('doc_type', 'undangan')
                        ->where('order_index', $ord)
                        ->first();

                    if ($existsOrd) {
                        if ($existsOrd->status !== 'approved' && (int)$existsOrd->approver_user_id !== (int)$uid) {
                            DB::table('approval_requests')->where('id',$existsOrd->id)->update([
                                'approver_user_id' => $uid,
                                'status'           => 'pending',
                                'sign_token'       => Str::random(32),
                                'updated_at'       => now(),
                            ]);
                        }
                    } else {
                        DB::table('approval_requests')->insert([
                            'rapat_id'         => $id,
                            'doc_type'         => 'undangan',
                            'approver_user_id' => $uid,
                            'order_index'      => $ord,
                            'status'           => 'pending',
                            'sign_token'       => Str::random(32),
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                    }
                }

                $rejected = DB::table('approval_requests')
                    ->where('rapat_id', $id)
                    ->where('doc_type', 'undangan')
                    ->where('status', 'rejected')
                    ->orderBy('order_index','asc')
                    ->first();

                if ($rejected) {
                    $extra = [];
                    if (Schema::hasColumn('approval_requests','resubmitted')) {
                        $extra['resubmitted'] = 1;
                    }
                    if (Schema::hasColumn('approval_requests','resubmitted_at')) {
                        $extra['resubmitted_at'] = now();
                    }

                    DB::table('approval_requests')->where('id', $rejected->id)->update(array_merge([
                        'status'         => 'pending',
                        'rejection_note' => null,
                        'rejected_at'    => null,
                        'sign_token'     => Str::random(32),
                        'updated_at'     => now(),
                    ], $extra));

                    DB::table('approval_requests')
                        ->where('rapat_id', $id)
                        ->where('doc_type', 'undangan')
                        ->where('order_index', '>', $rejected->order_index)
                        ->where('status', 'blocked')
                        ->update([
                            'status'     => 'pending',
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('approval_requests')
                        ->where('rapat_id', $id)
                        ->where('doc_type', 'undangan')
                        ->where('status', 'blocked')
                        ->update(['status' => 'pending', 'updated_at' => now()]);
                }

                if (Schema::hasColumn('rapat','undangan_revised_at')) {
                    DB::table('rapat')->where('id',$id)->update([
                        'undangan_revised_at' => now(),
                        'updated_at'          => now(),
                    ]);
                } else {
                    DB::table('rapat')->where('id',$id)->update(['updated_at'=>now()]);
                }
            } else {
                DB::table('rapat')->where('id',$id)->update(['updated_at'=>now()]);
            }

            DB::commit();

            if ($isEnqueued) {
                app(\App\Http\Controllers\ApprovalController::class)
                    ->notifyFirstPendingApproverOnResubmission((int)$id, 'undangan');

                return redirect()->route('rapat.index')->with('success', 'Undangan berhasil diperbarui & dikirim ulang ke antrean approval (status: sudah diperbaiki).');
            }

            return redirect()->route('rapat.index')->with('success', 'Rapat berhasil diperbarui (belum dikirim ke approval).');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors('Gagal memperbarui undangan.')->withInput();
        }
    }

    // Kirim rapat jadwal ke antrean approval
    public function sendToApproval($id)
    {
        $rapat = DB::table('rapat')->where('id', $id)->first();
        if (!$rapat) abort(404);

        if (!empty($rapat->approval_enqueued_at)) {
            return redirect()->route('rapat.index')->with('success', 'Rapat sudah ada di antrean approval.');
        }

        $existing = DB::table('approval_requests')->where('rapat_id', $id)->count();
        if ($existing === 0) {
            $this->createApprovalChainForDoc($id, 'undangan');
            $this->createApprovalChainForDoc($id, 'absensi');
        }

        DB::table('rapat')->where('id', $id)->update([
            'approval_enqueued_at' => now(),
            'updated_at'           => now(),
        ]);

        $this->notifyFirstApprover($id, 'undangan', $rapat->judul);

        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dikirim ke approval.');
    }

    // Hapus rapat & undangan terkait
    public function destroy($id)
    {
        DB::table('undangan')->where('id_rapat', $id)->delete();
        DB::table('approval_requests')->where('rapat_id', $id)->delete();
        DB::table('rapat')->where('id', $id)->delete();
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dihapus!');
    }

    // Export undangan PDF (tidak diubah)
    public function undanganPdf($id)
    {
        $rapat = DB::table('rapat')
            ->leftJoin('kategori_rapat','kategori_rapat.id','=','rapat.id_kategori')
            ->select('rapat.*','kategori_rapat.nama as nama_kategori')
            ->where('rapat.id', $id)
            ->first();
        if (!$rapat) abort(404);

        $daftar_peserta = DB::table('undangan')
            ->join('users', 'undangan.id_user', '=', 'users.id')
            ->where('undangan.id_rapat', $id)
            ->select('users.name', 'users.email', 'users.jabatan', 'users.hirarki')
            ->orderByRaw('COALESCE(users.hirarki, 9999) ASC')
            ->orderBy('users.name','asc')
            ->get();

        $approval1 = DB::table('users')->where('id', $rapat->approval1_user_id)->first();
        $approval2 = $rapat->approval2_user_id
            ? DB::table('users')->where('id', $rapat->approval2_user_id)->first()
            : null;

        $kop_path = public_path('Screenshot 2025-08-23 121254.jpeg');

        $tampilkan_lampiran        = $daftar_peserta->count() > 1;
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;

        $qrA1 = DB::table('approval_requests')
            ->where('rapat_id', $rapat->id)
            ->where('doc_type', 'undangan')
            ->where('approver_user_id', $rapat->approval1_user_id)
            ->where('status', 'approved')
            ->orderByDesc('signed_at')
            ->value('signature_qr_path');

        $qrA2 = null;
        if (!empty($rapat->approval2_user_id)) {
            $qrA2 = DB::table('approval_requests')
                ->where('rapat_id', $rapat->id)
                ->where('doc_type', 'undangan')
                ->where('approver_user_id', $rapat->approval2_user_id)
                ->where('status', 'approved')
                ->orderByDesc('signed_at')
                ->value('signature_qr_path');
        }

        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat'                      => $rapat,
            'daftar_peserta'             => $daftar_peserta,
            'approval1'                  => $approval1,
            'approval2'                  => $approval2,
            'kop_path'                   => $kop_path,
            'tampilkan_lampiran'         => $tampilkan_lampiran,
            'tampilkan_daftar_di_surat'  => $tampilkan_daftar_di_surat,
            'qrA1'                       => $qrA1,
            'qrA2'                       => $qrA2,
        ])->setPaper('A4', 'portrait');

        $filename = 'Undangan-Rapat-' . str_replace(' ', '-', $rapat->judul) . '.pdf';
        $output   = null;

        $tmpBase = storage_path('app/undangan-'.$rapat->id.'-'.Str::random(8).'.pdf');
        $pdf->save($tmpBase);

        $files = [$tmpBase];

        $lampiranPdf = null;
        if (Schema::hasColumn('rapat', 'lampiran_tambahan_path') && !empty($rapat->lampiran_tambahan_path)) {
            $candidate = public_path($rapat->lampiran_tambahan_path);
            if (is_file($candidate) && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'pdf') {
                $lampiranPdf = $candidate;
            }
        }
        if ($lampiranPdf) {
            $files[] = $lampiranPdf;
        }

        if (count($files) > 1) {
            $merger = new Merger();
            foreach ($files as $f) {
                $merger->addFile($f);
            }
            $output = $merger->merge();
        } else {
            $output = @file_get_contents($tmpBase);
        }

        if (is_file($tmpBase)) {
            @unlink($tmpBase);
        }

        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    private function getStatusRapat($rapat)
    {
        if ($rapat->status === 'dibatalkan') {
            return 'Dibatalkan';
        }

        $now     = Carbon::now('Asia/Jayapura');
        $mulai   = Carbon::parse($rapat->tanggal . ' ' . $rapat->waktu_mulai, 'Asia/Jayapura');
        $selesai = $mulai->copy()->addHours(2);

        if ($now->lt($mulai)) {
            return 'Akan Datang';
        } elseif ($now->between($mulai, $selesai)) {
            return 'Berlangsung';
        } elseif ($now->gt($selesai)) {
            return 'Selesai';
        }
        return 'Akan Datang';
    }

    public function batalkan($id)
    {
        DB::table('rapat')->where('id', $id)->update(['status' => 'dibatalkan', 'updated_at' => now()]);
        return redirect()->route('rapat.index')->with('success', 'Rapat berhasil dibatalkan!');
    }

    private function createApprovalChainForDoc($rapatId, $docType)
    {
        $rapat = DB::table('rapat')->where('id',$rapatId)->first();
        if (!$rapat) return;

        $chain = [];
        if (!empty($rapat->approval2_user_id)) {
            $chain[] = ['user_id' => $rapat->approval2_user_id, 'order' => 1];
            $chain[] = ['user_id' => $rapat->approval1_user_id, 'order' => 2];
        } else {
            $chain[] = ['user_id' => $rapat->approval1_user_id, 'order' => 1];
        }

        foreach ($chain as $c) {
            DB::table('approval_requests')->insert([
                'rapat_id'         => $rapatId,
                'doc_type'         => $docType,
                'approver_user_id' => $c['user_id'],
                'order_index'      => $c['order'],
                'status'           => 'pending',
                'sign_token'       => Str::random(48),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    private function notifyFirstApprover($rapatId, $docType, $judulRapat)
    {
        $firstReq = DB::table('approval_requests')
            ->where('rapat_id',$rapatId)
            ->where('doc_type',$docType)
            ->orderBy('order_index')
            ->first();

        if ($firstReq) {
            $approver = DB::table('users')->where('id', $firstReq->approver_user_id)->first();
            if ($approver && $approver->no_hp) {
                $wa      = preg_replace('/^0/', '62', $approver->no_hp);
                $signUrl = url('/approval/sign/' . $firstReq->sign_token);
                \App\Helpers\FonnteWa::send(
                    $wa,
                    "Assalamu'alaikum Wr. Wb.\n\n" .
                    "Dengan hormat,\n" .
                    "Mohon kesediaan Bapak/Ibu untuk memberikan *Approval* pada dokumen *{$docType}* rapat berikut:\n\n" .
                    "*{$judulRapat}*\n\n" .
                    "Silakan melakukan persetujuan melalui tautan di bawah ini:\n{$signUrl}\n\n" .
                    "Terima kasih atas perhatian dan kerja samanya.\n" .
                    "Wassalamu'alaikum Wr. Wb."
                );
            }
        }
    }
}


