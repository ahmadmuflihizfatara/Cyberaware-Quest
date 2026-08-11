@if (session('sukses'))
    <div class="card card-pad mb-4 border-emerald-200 bg-emerald-50 text-emerald-800 text-sm font-medium">
        {{ session('sukses') }}
    </div>
@endif

@if ($errors->any())
    <div class="card card-pad mb-4 border-red-200 bg-red-50 text-red-800 text-sm">
        <p class="font-semibold mb-1">Periksa kembali:</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif
