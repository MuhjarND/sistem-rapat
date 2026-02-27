<?php

namespace App\Http\Controllers;

use App\Helpers\FonnteWa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EvotingController extends Controller
{
    public function index()
    {
        $evotings = DB::table('evotings as e')
            ->leftJoin('users as u', 'u.id', '=', 'e.created_by')
            ->select(
                'e.*',
                'u.name as creator_name',
                DB::raw('(select count(*) from evoting_items where evoting_id = e.id) as item_count'),
                DB::raw('(select count(*) from evoting_voters where evoting_id = e.id) as voter_count'),
                DB::raw('(select count(*) from evoting_voters where evoting_id = e.id and voted_at is not null) as voted_count')
            )
            ->orderByDesc('e.created_at')
            ->get();

        return view('evoting.index', compact('evotings'));
    }

    public function create()
    {
        $qry = DB::table('users')
            ->select('id', 'name', 'jabatan', 'unit', 'role')
            ->orderByRaw('COALESCE(hirarki, 9999) ASC')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'role')) {
            $qry->whereNotIn('role', ['admin', 'superadmin']);
        }

        $users = $qry->get();

        return view('evoting.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'peserta' => 'required|array|min:1',
            'peserta.*' => 'integer|exists:users,id',
            'send_link' => 'nullable|in:1',
        ]);

        $validator->after(function ($v) use ($request) {
            $items = $request->input('items', []);
            if (!is_array($items) || count($items) === 0) {
                $v->errors()->add('items', 'Minimal 1 item voting harus diisi.');
                return;
            }

            foreach ($items as $idx => $item) {
                $judul = trim((string) ($item['title'] ?? ''));
                $cands = $item['candidates'] ?? [];
                $cands = array_values(array_filter(is_array($cands) ? $cands : []));

                if ($judul === '') {
                    $v->errors()->add("items.{$idx}.title", 'Judul item voting wajib diisi.');
                }
                if (count($cands) === 0) {
                    $v->errors()->add("items.{$idx}.candidates", 'Setiap item minimal punya 1 kandidat.');
                } else {
                    $unique = array_values(array_unique($cands));
                    $exists = DB::table('users')->whereIn('id', $unique)->count();
                    if ($exists !== count($unique)) {
                        $v->errors()->add("items.{$idx}.candidates", 'Ada kandidat yang tidak valid.');
                    }
                }
            }
        });

        $validator->validate();

        $itemsInput = $request->input('items', []);
        $items = [];
        $allCandidateIds = [];
        foreach ($itemsInput as $item) {
            $judul = trim((string) ($item['title'] ?? ''));
            $cands = $item['candidates'] ?? [];
            $cands = array_values(array_filter(is_array($cands) ? $cands : []));
            if ($judul !== '' && count($cands) > 0) {
                $items[] = [
                    'judul' => $judul,
                    'candidates' => $cands,
                ];
                $allCandidateIds = array_merge($allCandidateIds, $cands);
            }
        }

        if (count($items) === 0) {
            return back()->withErrors('Item voting belum lengkap.')->withInput();
        }

        $pesertaIds = array_values(array_unique(array_filter($request->input('peserta', []))));
        if (count($pesertaIds) === 0) {
            return back()->withErrors('Peserta voting belum dipilih.')->withInput();
        }

        $candidateMap = DB::table('users')
            ->whereIn('id', array_values(array_unique($allCandidateIds ?: [0])))
            ->pluck('name', 'id');

        DB::beginTransaction();
        try {
            $publicToken = Str::random(40);
            $evotingId = DB::table('evotings')->insertGetId([
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'status' => 'open',
                'public_token' => $publicToken,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $idx => $item) {
                $itemId = DB::table('evoting_items')->insertGetId([
                    'evoting_id' => $evotingId,
                    'judul' => $item['judul'],
                    'urut' => $idx + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($item['candidates'] as $cIdx => $candidateId) {
                    $candidateId = (int) $candidateId;
                    $candidateName = $candidateMap[$candidateId] ?? null;
                    if (!$candidateName) {
                        continue;
                    }
                    DB::table('evoting_candidates')->insert([
                        'item_id' => $itemId,
                        'nama' => $candidateName,
                        'user_id' => $candidateId,
                        'urut' => $cIdx + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($pesertaIds as $userId) {
                DB::table('evoting_voters')->insert([
                    'evoting_id' => $evotingId,
                    'user_id' => $userId,
                    'token' => Str::random(40),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors('Gagal membuat e-voting.')->withInput();
        }

        if ($request->input('send_link') === '1') {
            $this->sendVotingLinks($evotingId);
        }

        return redirect()->route('evoting.show', $evotingId)->with('success', 'E-voting berhasil dibuat.');
    }

    public function show($id)
    {
        $evoting = DB::table('evotings')->where('id', $id)->first();
        if (!$evoting) {
            abort(404);
        }
        if (empty($evoting->public_token)) {
            $token = Str::random(40);
            DB::table('evotings')->where('id', $id)->update([
                'public_token' => $token,
                'updated_at' => now(),
            ]);
            $evoting->public_token = $token;
        }

        $items = DB::table('evoting_items')
            ->where('evoting_id', $id)
            ->orderBy('urut')
            ->get();

        $itemIds = $items->pluck('id')->all();
        $candidates = DB::table('evoting_candidates')
            ->whereIn('item_id', $itemIds ?: [0])
            ->orderBy('urut')
            ->get();

        $voteCounts = DB::table('evoting_votes')
            ->select('candidate_id', DB::raw('count(*) as total'))
            ->where('evoting_id', $id)
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $voters = DB::table('evoting_voters as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->select('v.*', 'u.name', 'u.no_hp', 'u.jabatan', 'u.unit', 'u.hirarki')
            ->where('v.evoting_id', $id)
            ->orderByRaw('COALESCE(u.hirarki, 9999) ASC')
            ->orderBy('u.name')
            ->get();

        $candidatesByItem = $candidates->groupBy('item_id');

        return view('evoting.show', compact('evoting', 'items', 'candidatesByItem', 'voteCounts', 'voters'));
    }

    public function sendLinks(Request $request, $id)
    {
        $evoting = DB::table('evotings')->where('id', $id)->first();
        if (!$evoting) {
            abort(404);
        }
        if (empty($evoting->public_token)) {
            $token = Str::random(40);
            DB::table('evotings')->where('id', $id)->update([
                'public_token' => $token,
                'updated_at' => now(),
            ]);
            $evoting->public_token = $token;
        }

        $sent = $this->sendVotingLinks($id);

        return back()->with('success', 'Link voting dikirim ke ' . $sent . ' peserta.');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->input('status');
        if (!in_array($status, ['open', 'closed'], true)) {
            return back()->withErrors('Status e-voting tidak valid.');
        }

        DB::table('evotings')->where('id', $id)->update([
            'status' => $status,
            'closed_at' => $status === 'closed' ? now() : null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Status e-voting diperbarui.');
    }

    public function results($id)
    {
        $evoting = DB::table('evotings')->where('id', $id)->first();
        if (!$evoting) {
            abort(404);
        }

        $result = $this->buildResultsPayload($id);

        return response()->json([
            'evoting_id' => $evoting->id,
            'items' => $result['items'],
            'voters' => $result['voters_simple'],
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    public function exportPdf($id)
    {
        $evoting = DB::table('evotings')->where('id', $id)->first();
        if (!$evoting) {
            abort(404);
        }

        if (empty($evoting->public_token)) {
            $token = Str::random(40);
            DB::table('evotings')->where('id', $id)->update([
                'public_token' => $token,
                'updated_at' => now(),
            ]);
            $evoting->public_token = $token;
        }

        $result = $this->buildResultsPayload($id);

        $creator = null;
        if (!empty($evoting->created_by)) {
            $creator = DB::table('users')->where('id', $evoting->created_by)->value('name');
        }

        $kop = null;
        $kopCandidates = [
            public_path('Screenshot 2025-08-23 121254.jpeg'),
            public_path('kop_laporan.jpg'),
            public_path('kop_absen.jpg'),
        ];
        foreach ($kopCandidates as $path) {
            if (is_file($path)) {
                $kop = $path;
                break;
            }
        }

        $pdf = Pdf::loadView('evoting.laporan_pdf', [
            'evoting' => $evoting,
            'items' => $result['items'],
            'voters' => $result['voters_detailed'],
            'totalVoters' => $result['total_voters'],
            'votedCount' => $result['voted_count'],
            'creatorName' => $creator,
            'kop' => $kop,
            'publicLink' => route('evoting.public', $evoting->public_token),
            'generatedAt' => now('Asia/Jayapura'),
        ])->setPaper('A4', 'portrait');

        $filename = 'Laporan-E-Voting-' . Str::slug($evoting->judul ?: ('evoting-' . $evoting->id)) . '.pdf';

        return $pdf->download($filename);
    }

    private function sendVotingLinks($evotingId)
    {
        $evoting = DB::table('evotings')->where('id', $evotingId)->first();
        if (!$evoting) {
            return 0;
        }
        if (empty($evoting->public_token)) {
            $token = Str::random(40);
            DB::table('evotings')->where('id', $evotingId)->update([
                'public_token' => $token,
                'updated_at' => now(),
            ]);
            $evoting->public_token = $token;
        }

        $voters = DB::table('evoting_voters as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->select('v.token', 'u.name', 'u.no_hp')
            ->where('v.evoting_id', $evotingId)
            ->get();

        $sent = 0;
        foreach ($voters as $voter) {
            $phone = FonnteWa::normalizeNumber($voter->no_hp ?? null);
            if (!$phone) {
                continue;
            }
            $link = route('evoting.public', $evoting->public_token);
            $message = "Assalamu'alaikum warahmatullahi wabarakatuh,\n\n"
                . "Yth. Bapak/Ibu {$voter->name},\n"
                . "Mohon berkenan mengikuti e-voting melalui tautan berikut:\n"
                . $link . "\n\n"
                . "Gunakan nama Anda pada form. Setiap peserta hanya dapat voting satu kali.\n\n"
                . "Terima kasih.";
            FonnteWa::send($phone, $message);
            $sent++;
        }

        return $sent;
    }

    private function buildResultsPayload($evotingId): array
    {
        $items = DB::table('evoting_items')
            ->where('evoting_id', $evotingId)
            ->orderBy('urut')
            ->get();

        $itemIds = $items->pluck('id')->all();
        $candidates = DB::table('evoting_candidates')
            ->whereIn('item_id', $itemIds ?: [0])
            ->orderBy('urut')
            ->get();

        $counts = DB::table('evoting_votes')
            ->select('candidate_id', DB::raw('count(*) as total'))
            ->where('evoting_id', $evotingId)
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $candidatesByItem = $candidates->groupBy('item_id');
        $itemsPayload = [];
        foreach ($items as $item) {
            $rows = [];
            $total = 0;
            foreach ($candidatesByItem->get($item->id, collect()) as $cand) {
                $count = (int) ($counts[$cand->id] ?? 0);
                $total += $count;
                $rows[] = [
                    'id' => $cand->id,
                    'name' => $cand->nama,
                    'total' => $count,
                ];
            }

            $rows = array_map(function ($row) use ($total) {
                $row['percent'] = $total > 0 ? round(($row['total'] / $total) * 100, 1) : 0;
                return $row;
            }, $rows);

            $itemsPayload[] = [
                'id' => $item->id,
                'title' => $item->judul,
                'total' => $total,
                'candidates' => $rows,
            ];
        }

        $voters = DB::table('evoting_voters as v')
            ->leftJoin('users as u', 'u.id', '=', 'v.user_id')
            ->where('v.evoting_id', $evotingId)
            ->select('v.id', 'v.voted_at', 'u.name', 'u.hirarki')
            ->orderByRaw('COALESCE(u.hirarki, 9999) ASC')
            ->orderBy('u.name')
            ->get();

        $votersSimple = $voters->map(function ($v) {
            return [
                'id' => $v->id,
                'voted' => !empty($v->voted_at),
            ];
        })->values();

        $votersDetailed = $voters->map(function ($v) {
            return [
                'id' => $v->id,
                'name' => trim((string) ($v->name ?? '')) !== '' ? $v->name : ('Peserta #' . $v->id),
                'voted' => !empty($v->voted_at),
                'voted_at' => $v->voted_at,
            ];
        })->values();

        $votedCount = $votersSimple->where('voted', true)->count();

        return [
            'items' => $itemsPayload,
            'voters_simple' => $votersSimple,
            'voters_detailed' => $votersDetailed,
            'total_voters' => $votersSimple->count(),
            'voted_count' => $votedCount,
        ];
    }
}
