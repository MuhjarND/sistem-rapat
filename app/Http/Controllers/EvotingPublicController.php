<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvotingPublicController extends Controller
{
    public function show($token)
    {
        $evoting = DB::table('evotings')->where('public_token', $token)->first();
        if (!$evoting) {
            abort(404);
        }

        $mode = 'open';
        if ($evoting->status !== 'open') {
            $mode = 'closed';
        }

        $items = DB::table('evoting_items')
            ->where('evoting_id', $evoting->id)
            ->orderBy('urut')
            ->get();

        $itemIds = $items->pluck('id')->all();
        $candidates = DB::table('evoting_candidates')
            ->whereIn('item_id', $itemIds ?: [0])
            ->orderBy('urut')
            ->get()
            ->groupBy('item_id');

        $voters = DB::table('evoting_voters as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->select('v.id', 'v.user_id', 'v.voted_at', 'u.name', 'u.jabatan', 'u.unit', 'u.hirarki')
            ->where('v.evoting_id', $evoting->id)
            ->orderByRaw('COALESCE(u.hirarki, 9999) ASC')
            ->orderBy('u.name')
            ->get();

        return view('evoting.public', compact('evoting', 'mode', 'items', 'candidates', 'token', 'voters'));
    }

    public function results($token)
    {
        $evoting = DB::table('evotings')->where('public_token', $token)->first();
        if (!$evoting) {
            abort(404);
        }

        return view('evoting.public_results', compact('evoting', 'token'));
    }

    public function resultsData($token)
    {
        $evoting = DB::table('evotings')->where('public_token', $token)->first();
        if (!$evoting) {
            abort(404);
        }

        return response()->json($this->buildResultsPayload($evoting->id));
    }

    public function submit(Request $request, $token)
    {
        $evoting = DB::table('evotings')->where('public_token', $token)->first();
        if (!$evoting) {
            abort(404);
        }
        if ($evoting->status !== 'open') {
            return redirect()->route('evoting.public', $token)->withErrors('Voting sudah ditutup.');
        }

        $request->validate([
            'user_id' => 'required|integer',
        ], [
            'user_id.required' => 'Nama peserta wajib dipilih.',
        ]);

        $voter = DB::table('evoting_voters')
            ->where('evoting_id', $evoting->id)
            ->where('user_id', $request->user_id)
            ->first();
        if (!$voter) {
            return redirect()->route('evoting.public', $token)->withErrors('Peserta tidak terdaftar.');
        }
        if (!empty($voter->voted_at)) {
            return redirect()->route('evoting.public', $token)->withErrors('Anda sudah melakukan voting.');
        }

        $items = DB::table('evoting_items')
            ->where('evoting_id', $evoting->id)
            ->orderBy('urut')
            ->get();

        $itemIds = $items->pluck('id')->all();
        $candidates = DB::table('evoting_candidates')
            ->whereIn('item_id', $itemIds ?: [0])
            ->get()
            ->groupBy('item_id');

        $votesInput = $request->input('vote', []);
        $errors = [];
        $insertRows = [];

        foreach ($items as $item) {
            $candidateId = $votesInput[$item->id] ?? null;
            if (!$candidateId) {
                $errors[] = 'Pilihan untuk "' . $item->judul . '" wajib diisi.';
                continue;
            }

            $validCandidates = $candidates->get($item->id, collect())->pluck('id')->all();
            if (!in_array((int) $candidateId, $validCandidates, true)) {
                $errors[] = 'Pilihan untuk "' . $item->judul . '" tidak valid.';
                continue;
            }

            $insertRows[] = [
                'evoting_id' => $evoting->id,
                'item_id' => $item->id,
                'candidate_id' => (int) $candidateId,
                'voter_id' => $voter->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($errors) > 0) {
            return redirect()->route('evoting.public', $token)->withErrors($errors)->withInput();
        }

        DB::beginTransaction();
        try {
            DB::table('evoting_votes')->insert($insertRows);
            DB::table('evoting_voters')->where('id', $voter->id)->update([
                'voted_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('evoting.public', $token)->withErrors('Gagal menyimpan voting. Coba lagi.');
        }

        return redirect()->route('evoting.public', $token)->with('success', 'Terima kasih, suara Anda berhasil direkam.');
    }

    private function buildResultsPayload($evotingId)
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
        $payload = [];

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

            $payload[] = [
                'id' => $item->id,
                'title' => $item->judul,
                'total' => $total,
                'candidates' => $rows,
            ];
        }

        $voters = DB::table('evoting_voters')
            ->select('id', 'voted_at')
            ->where('evoting_id', $evotingId)
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'voted' => !empty($v->voted_at),
                ];
            });

        return [
            'evoting_id' => $evotingId,
            'items' => $payload,
            'voters' => $voters,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
