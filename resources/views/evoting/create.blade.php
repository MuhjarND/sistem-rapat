@extends('layouts.app')

@section('title', 'Buat E-Voting')

@section('content')
<h3 class="mb-3">Buat E-Voting</h3>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
    </div>
@endif

@php
  $candidateOptions = '<option value="">-- Pilih peserta calon kandidat --</option>';
  $candidateUsers = [];
  foreach ($users as $u) {
      $label = e($u->name);
      if (!empty($u->jabatan)) {
          $label .= ' - ' . e($u->jabatan);
      }
      if (!empty($u->unit)) {
          $label .= ' (' . e($u->unit) . ')';
      }
      $candidateOptions .= '<option value="' . $u->id . '">' . $label . '</option>';
      $candidateUsers[] = ['id' => $u->id, 'label' => $label];
  }
@endphp

<form action="{{ route('evoting.store') }}" method="POST">
    @csrf
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group">
                <label>Judul E-Voting</label>
                <input type="text" name="judul" class="form-control" value="{{ old('judul') }}" required maxlength="200">
            </div>
            <div class="form-group">
                <label>Deskripsi (opsional)</label>
                <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
            </div>
            <div class="form-group">
                <label>Peserta Voting</label>
                <select name="peserta[]" class="form-control js-example-basic-multiple" multiple required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ collect(old('peserta', []))->contains($user->id) ? 'selected' : '' }}>
                            {{ $user->name }}{{ $user->jabatan ? ' - '.$user->jabatan : '' }}{{ $user->unit ? ' ('.$user->unit.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" id="selectAllPeserta">
                    <label class="form-check-label" for="selectAllPeserta">Pilih semua peserta</label>
                </div>
                <div class="text-muted mt-1">Pilih peserta yang akan menerima link voting.</div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="send_link" name="send_link" value="1" {{ old('send_link') ? 'checked' : '' }}>
                <label class="form-check-label" for="send_link">Kirim link voting via WhatsApp setelah dibuat</label>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0">Item & Kandidat</h5>
        <button type="button" class="btn btn-outline-light btn-sm ml-auto" id="btnAddItem">
            <i class="fas fa-plus mr-1"></i> Tambah Item
        </button>
    </div>

    <div id="itemsWrap">
        <div class="card mb-3 item-block" data-index="0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h6 class="mb-0">Item Voting #1</h6>
                    <button type="button" class="btn btn-sm btn-danger ml-auto btn-remove-item" disabled>Hapus Item</button>
                </div>
                <div class="form-group">
                    <label>Judul Item</label>
                    <input type="text" name="items[0][title]" class="form-control" required>
                </div>
                <div class="candidates">
                    <div class="form-row candidate-row">
                        <div class="form-group col-md-10">
                            <label>Kandidat</label>
                            <select name="items[0][candidates][]" class="form-control candidate-select" required>
                                {!! $candidateOptions !!}
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-light btn-sm btn-remove-candidate" disabled>Hapus</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-light btn-sm btn-add-candidate">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Kandidat
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="{{ route('evoting.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan E-Voting</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && typeof $.fn.select2 === 'function') {
      $('.js-example-basic-multiple').select2({
        width: '100%',
        placeholder: 'Pilih peserta',
        closeOnSelect: false
      });
    }

    var itemIndex = 0;
    var allUsers = @json($candidateUsers);
    var useSelect2 = window.jQuery && typeof $.fn.select2 === 'function';

    function buildItem(idx){
      return `
        <div class="card mb-3 item-block" data-index="${idx}">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <h6 class="mb-0">Item Voting #${idx + 1}</h6>
              <button type="button" class="btn btn-sm btn-danger ml-auto btn-remove-item">Hapus Item</button>
            </div>
            <div class="form-group">
              <label>Judul Item</label>
              <input type="text" name="items[${idx}][title]" class="form-control" required>
            </div>
            <div class="candidates">
              ${buildCandidate(idx)}
            </div>
            <button type="button" class="btn btn-outline-light btn-sm btn-add-candidate">
              <i class="fas fa-user-plus mr-1"></i> Tambah Kandidat
            </button>
          </div>
        </div>
      `;
    }

    function renderCandidateOptions(selectedIds){
      var options = '<option value="">-- Pilih peserta calon kandidat --</option>';
      var limitIds = Array.isArray(selectedIds) ? selectedIds : [];
      allUsers.forEach(function(user){
        if (limitIds.length === 0 || limitIds.indexOf(String(user.id)) !== -1) {
          options += '<option value="' + user.id + '">' + user.label + '</option>';
        }
      });
      return options;
    }

    function buildCandidate(itemIdx){
      return `
        <div class="form-row candidate-row">
          <div class="form-group col-md-10">
            <label>Kandidat</label>
            <select name="items[${itemIdx}][candidates][]" class="form-control candidate-select" required>
              ${renderCandidateOptions(getSelectedPesertaIds())}
            </select>
          </div>
          <div class="form-group col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-light btn-sm btn-remove-candidate">Hapus</button>
          </div>
        </div>
      `;
    }

    var btnAddItem = document.getElementById('btnAddItem');
    var itemsWrap = document.getElementById('itemsWrap');

    function initCandidateSelect2(scope){
      if (!useSelect2) return;
      var $scope = scope ? $(scope) : $(document);
      $scope.find('.candidate-select').each(function(){
        var $el = $(this);
        if ($el.data('select2')) {
          $el.select2('destroy');
        }
        $el.select2({
          width: '100%',
          placeholder: 'Ketik untuk mencari kandidat',
          allowClear: true
        });
      });
    }

    if (btnAddItem && itemsWrap) {
      btnAddItem.addEventListener('click', function(){
        itemIndex++;
        itemsWrap.insertAdjacentHTML('beforeend', buildItem(itemIndex));
        refreshItemTitles();
        toggleRemoveButtons();
        initCandidateSelect2(itemsWrap);
      });

      itemsWrap.addEventListener('click', function(e){
        if (e.target.classList.contains('btn-add-candidate')) {
          var itemBlock = e.target.closest('.item-block');
          var idx = itemBlock.getAttribute('data-index');
          var candidates = itemBlock.querySelector('.candidates');
          candidates.insertAdjacentHTML('beforeend', buildCandidate(idx));
          toggleRemoveButtons();
          initCandidateSelect2(itemBlock);
        }

        if (e.target.classList.contains('btn-remove-candidate')) {
          var row = e.target.closest('.candidate-row');
          var candidatesWrap = e.target.closest('.candidates');
          if (candidatesWrap.querySelectorAll('.candidate-row').length > 1) {
            row.remove();
          }
          toggleRemoveButtons();
        }

        if (e.target.classList.contains('btn-remove-item')) {
          var block = e.target.closest('.item-block');
          if (document.querySelectorAll('.item-block').length > 1) {
            block.remove();
            refreshItemTitles();
            toggleRemoveButtons();
          }
        }
      });
    }

    function refreshItemTitles(){
      document.querySelectorAll('.item-block').forEach(function(block, idx){
        block.setAttribute('data-index', idx);
        var title = block.querySelector('h6');
        if (title) title.textContent = 'Item Voting #' + (idx + 1);
        var titleInput = block.querySelector('input[name^="items["]');
        if (titleInput) titleInput.setAttribute('name', 'items[' + idx + '][title]');
        block.querySelectorAll('.candidate-row input').forEach(function(inp){
          inp.setAttribute('name', 'items[' + idx + '][candidates][]');
        });
      });
      itemIndex = document.querySelectorAll('.item-block').length - 1;
    }

    function toggleRemoveButtons(){
      document.querySelectorAll('.item-block').forEach(function(block){
        var btnItem = block.querySelector('.btn-remove-item');
        if (btnItem) {
          btnItem.disabled = document.querySelectorAll('.item-block').length <= 1;
        }
        var candRows = block.querySelectorAll('.candidate-row');
        candRows.forEach(function(row){
          var btn = row.querySelector('.btn-remove-candidate');
          if (btn) btn.disabled = candRows.length <= 1;
        });
      });
    }

    toggleRemoveButtons();

    var selectAll = document.getElementById('selectAllPeserta');
    var pesertaSelect = document.querySelector('select[name="peserta[]"]');
    function getSelectedPesertaIds(){
      return Array.prototype.filter.call(pesertaSelect.options, function(opt){ return opt.selected; })
        .map(function(opt){ return String(opt.value); });
    }

    function syncCandidateOptions(){
      var selectedIds = getSelectedPesertaIds();
      document.querySelectorAll('.candidate-select').forEach(function(sel){
        var current = sel.value;
        sel.innerHTML = renderCandidateOptions(selectedIds);
        var exists = Array.prototype.some.call(sel.options, function(opt){ return opt.value === current; });
        if (current && exists) {
          sel.value = current;
        }
      });
      initCandidateSelect2(itemsWrap || document);
    }

    if (selectAll && pesertaSelect) {
      selectAll.addEventListener('change', function(){
        Array.prototype.forEach.call(pesertaSelect.options, function(opt){
          opt.selected = selectAll.checked;
        });
        if (window.jQuery && typeof $.fn.select2 === 'function') {
          $(pesertaSelect).trigger('change');
        }
        syncCandidateOptions();
      });
    }

    if (pesertaSelect) {
      pesertaSelect.addEventListener('change', syncCandidateOptions);
    }

    syncCandidateOptions();
    initCandidateSelect2(itemsWrap || document);
  });
</script>
@endpush
