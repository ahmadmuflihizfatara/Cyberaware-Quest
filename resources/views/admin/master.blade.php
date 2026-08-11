@extends('layouts.app')
@section('judul', $def['judul'])

@php
    $pk = $def['pk'];
    $aksi = $edit
        ? route('admin.master.update', [$resource, $edit->{$pk}])
        : route('admin.master.store', $resource);

    /** Nilai terpilih untuk field multiselect (relasi M:N). */
    $terpilih = function (string $nama) use ($edit, $def) {
        if (! $edit) return [];
        $kunci = $def['field'][$nama]['sumber'][1];
        return $edit->{$nama}->pluck($kunci)->all();
    };
@endphp

@section('isi')
<div class="grid gap-5 xl:grid-cols-[1.4fr_1fr] xl:items-start">
    <div>
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-2xl font-extrabold">{{ $def['judul'] }}</h2>
            <span class="text-sm text-slate-500">{{ $baris->total() }} baris</span>
        </div>

        <div class="card mt-4 tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th>
                        @foreach ($def['kolom'] as $label)<th>{{ $label }}</th>@endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($baris as $b)
                    <tr>
                        <td class="text-slate-400">{{ $b->{$pk} }}</td>
                        @foreach ($def['kolom'] as $jalur => $label)
                            <td>
                                @php $nilai = \App\Http\Controllers\Admin\MasterController::nilaiKolom($b, $jalur); @endphp
                                @if (is_bool($nilai))
                                    <span class="chip {{ $nilai ? 'chip-ok' : 'chip-off' }}">{{ $nilai ? 'ya' : 'tidak' }}</span>
                                @else
                                    {{ $nilai ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="whitespace-nowrap text-right">
                            @if (! empty($def['tautan']))
                                <a href="{{ route($def['tautan']['route'], $b->{$pk}) }}" class="btn btn-ghost btn-sm">{{ $def['tautan']['label'] }}</a>
                            @endif
                            <a href="{{ route('admin.master.index', [$resource, 'edit' => $b->{$pk}]) }}" class="btn btn-ghost btn-sm">Ubah</a>
                            <form method="POST" action="{{ route('admin.master.destroy', [$resource, $b->{$pk}]) }}" class="inline"
                                  onsubmit="return confirm('Hapus data ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['kolom']) + 2 }}" class="text-sm text-slate-500">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $baris->links() }}</div>
    </div>

    <form method="POST" action="{{ $aksi }}" class="card card-pad space-y-3">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">{{ $edit ? 'Ubah' : 'Tambah' }} {{ $def['judul'] }}</h3>
            @if ($edit)
                <a href="{{ route('admin.master.index', $resource) }}" class="btn btn-ghost btn-sm">Batal</a>
            @endif
        </div>

        @foreach ($def['field'] as $nama => $f)
            @php $nilai = old($nama, $edit && ! ($f['tipe'] === 'multiselect') ? $edit->{$nama} ?? null : null); @endphp
            <div>
                <label class="label" for="f_{{ $nama }}">{{ $f['label'] }}</label>

                @switch($f['tipe'])
                    @case('textarea')
                        <textarea class="textarea" id="f_{{ $nama }}" name="{{ $nama }}">{{ $nilai }}</textarea>
                        @break

                    @case('select')
                        <select class="select" id="f_{{ $nama }}" name="{{ $nama }}">
                            @if (! empty($f['kosong']) || str_contains($f['rules'] ?? '', 'nullable'))
                                <option value="">{{ $f['kosong'] ?? '— pilih —' }}</option>
                            @endif
                            @foreach ($opsi[$nama] ?? collect($f['opsi'] ?? [])->map(fn ($o) => (object) ['id' => $o, 'label' => $o]) as $o)
                                <option value="{{ $o->id }}" @selected((string) $nilai === (string) $o->id)>{{ $o->label }}</option>
                            @endforeach
                        </select>
                        @break

                    @case('multiselect')
                        @php $dipilih = old($nama, $terpilih($nama)); @endphp
                        <div class="grid gap-1.5 sm:grid-cols-2">
                            @foreach ($opsi[$nama] ?? [] as $o)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="{{ $nama }}[]" value="{{ $o->id }}"
                                           @checked(in_array($o->id, (array) $dipilih))>
                                    {{ $o->label }}
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case('checkbox')
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="{{ $nama }}" value="0">
                            <input type="checkbox" name="{{ $nama }}" value="1" @checked($edit ? $edit->{$nama} : true)>
                            Aktif
                        </label>
                        @break

                    @case('password')
                        <input class="input" id="f_{{ $nama }}" type="password" name="{{ $nama }}"
                               placeholder="{{ $edit ? 'kosongkan bila tidak diubah' : 'minimal 8 karakter' }}">
                        @break

                    @default
                        <input class="input" id="f_{{ $nama }}" type="{{ $f['tipe'] === 'number' ? 'number' : ($f['tipe'] === 'date' ? 'date' : 'text') }}"
                               name="{{ $nama }}" value="{{ $f['tipe'] === 'date' && $nilai ? \Illuminate\Support\Carbon::parse($nilai)->format('Y-m-d') : $nilai }}">
                @endswitch
            </div>
        @endforeach

        <button class="btn btn-primary w-full">{{ $edit ? 'Simpan perubahan' : 'Tambah' }}</button>
    </form>
</div>
@endsection
