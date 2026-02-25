<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-Voting - {{ $evoting->judul }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{
            background:#0f172a;
            color:#e6eefc;
            font-family: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            min-height:100vh;
        }
        .wrap{ max-width: 860px; margin: 36px auto; padding: 0 16px; }
        .card{ background: rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:14px; }
        .card-header{ background: transparent; border-bottom:1px solid rgba(255,255,255,.08); }
        .brand-top{
            display:flex;
            align-items:center;
            gap:14px;
        }
        .brand-logo{
            width:64px;
            height:64px;
            object-fit:contain;
            border-radius:10px;
            background:#ffffff;
            padding:6px;
            box-shadow: 0 8px 22px rgba(0,0,0,.28);
            flex:0 0 auto;
        }
        .badge-soft{ background: rgba(34,197,94,.18); border:1px solid rgba(34,197,94,.4); color:#bbf7d0; }
        .muted{ color:#9fb0cd; }
        .option{ padding:10px 12px; border:1px solid rgba(255,255,255,.08); border-radius:10px; margin-bottom:10px; }
        .option:hover{ border-color: rgba(99,102,241,.5); }
        .btn-primary{ background: #4f46e5; border:none; }
        .btn-outline-soft{
            border:1px solid rgba(122,162,255,.45);
            color:#dbe8ff;
            background: rgba(122,162,255,.12);
        }
        .btn-outline-soft:hover{
            color:#fff;
            background: rgba(122,162,255,.2);
            border-color: rgba(122,162,255,.7);
        }
        .option input[type="radio"]{
            width:18px; height:18px;
            accent-color: #FEE715;
        }
        .option span{ font-weight:600; }
        select.form-control{
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.15);
            color: #fff;
        }
        @media (max-width: 576px){
            .wrap{ margin: 20px auto; }
            .brand-logo{
              width:52px;
              height:52px;
              padding:5px;
            }
            .option{
              flex-direction: row;
              align-items:center;
              gap:10px;
              background: rgba(255,255,255,.06);
              border: 1px solid rgba(255,255,255,.2);
              padding:12px 14px;
            }
            .option input[type="radio"]{
              width:22px; height:22px;
              outline: 2px solid rgba(254,231,21,.6);
              outline-offset: 2px;
            }
            .btn-primary{ width: 100%; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card mb-3">
        <div class="card-body">
            <div class="brand-top mb-2">
                <img src="{{ asset('logo_pta.png') }}" alt="Logo PTA Papua Barat" class="brand-logo">
                <div>
                    <h4 class="mb-1">{{ $evoting->judul }}</h4>
                    <div class="muted">{!! nl2br(e($evoting->deskripsi ?: 'E-voting resmi.')) !!}</div>
                </div>
            </div>
            <div class="mt-2">
                <span class="badge badge-soft">Voting Resmi</span>
                <a href="{{ route('evoting.public.results', $token) }}" class="btn btn-sm btn-outline-soft ml-2">
                    Lihat Hasil
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if($mode !== 'open')
        <div class="card">
            <div class="card-body">
                @if($mode === 'done')
                    <h5>Terima kasih.</h5>
                    <p class="mb-0">Suara Anda sudah tercatat. Anda tidak dapat melakukan voting ulang.</p>
                @else
                    <h5>Voting Ditutup</h5>
                    <p class="mb-0">Voting sudah ditutup. Terima kasih atas partisipasinya.</p>
                @endif
            </div>
        </div>
    @else
        <form action="{{ route('evoting.public.submit', $token) }}" method="POST">
            @csrf
            <div class="card mb-3">
                <div class="card-body">
                    <label for="user_id"><strong>Pilih Nama Anda</strong></label>
                    <select id="user_id" name="user_id" class="form-control" required>
                        <option value="">-- Pilih peserta --</option>
                        @foreach($voters as $voter)
                            <option value="{{ $voter->user_id }}" {{ $voter->voted_at ? 'disabled' : '' }}>
                                {{ $voter->name }}{{ $voter->voted_at ? ' [Sudah voting]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="muted mt-2">Setiap peserta hanya dapat voting satu kali.</div>
                </div>
            </div>
            @foreach($items as $item)
                @php $itemCandidates = ($candidates[$item->id] ?? collect()); @endphp
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>{{ $item->judul }}</strong>
                    </div>
                    <div class="card-body">
                        @if($itemCandidates->count() > 5)
                            <label for="vote_{{ $item->id }}" class="font-weight-bold">Pilih Kandidat</label>
                            <select id="vote_{{ $item->id }}" name="vote[{{ $item->id }}]" class="form-control" required>
                                <option value="">-- Pilih kandidat --</option>
                                @foreach($itemCandidates as $cand)
                                    <option value="{{ $cand->id }}" {{ old('vote.'.$item->id) == $cand->id ? 'selected' : '' }}>
                                        {{ $cand->nama }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="muted mt-2">Daftar kandidat lebih dari 5, gunakan dropdown untuk memilih.</div>
                        @else
                            @foreach($itemCandidates as $cand)
                                <label class="option d-flex align-items-center">
                                    <input type="radio" name="vote[{{ $item->id }}]" value="{{ $cand->id }}" class="mr-2" required {{ old('vote.'.$item->id) == $cand->id ? 'checked' : '' }}>
                                    <span>{{ $cand->nama }}</span>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="muted">Pastikan pilihan sudah benar. Voting hanya bisa 1 kali.</div>
                    <button type="submit" class="btn btn-primary">Kirim Voting</button>
                </div>
            </div>
        </form>
    @endif
</div>
</body>
</html>
