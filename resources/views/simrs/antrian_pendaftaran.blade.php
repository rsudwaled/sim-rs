@extends('adminlte::page')

@section('title', 'Antrian Pendaftaran')

@section('content_header')
    <h1>Antrian Pendaftaran</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Filter Data Antrian" theme="secondary" collapsible>
                <form action="{{ route('antrian.pendaftaran') }}" method="get">
                    <div class="row">
                        <div class="col-md-3">
                            <x-adminlte-input name="user" label="User" readonly value="{{ Auth::user()->name }}" />
                        </div>
                        <div class="col-md-3">
                            @php
                                $config = ['format' => 'YYYY-MM-DD'];
                            @endphp
                            <x-adminlte-input-date name="tanggal" label="Tanggal Antrian" :config="$config"
                                value="{{ \Carbon\Carbon::parse($request->tanggal)->format('Y-m-d') }}">
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-primary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input-date>
                        </div>
                        <div class="col-md-3">
                            <x-adminlte-select name="loket" label="Loket">
                                <x-adminlte-options :options="[
                                    1 => 'Loket 1',
                                    2 => 'Loket 2',
                                    3 => 'Loket 3',
                                    4 => 'Loket 4',
                                    5 => 'Loket 5',
                                ]" :selected="$request->loket ?? 1" />
                            </x-adminlte-select>
                        </div>
                        <div class="col-md-3">
                            <x-adminlte-select name="lantai" label="Lantai">
                                <x-adminlte-options :options="[1 => 'Lantai 1', 2 => 'Lantai 2']" />
                            </x-adminlte-select>
                        </div>
                    </div>
                    <x-adminlte-button type="submit" class="withLoad" theme="primary" label="Submit Antrian" />
                    {{-- <x-adminlte-button type="submit" class="withLoad" theme="primary" label="Tambah Antrian Offline" /> --}}
                </form>
            </x-adminlte-card>
            @if (isset($request->loket) && isset($request->lantai) && isset($request->tanggal))
                <x-adminlte-card
                    title="Antrian Pendaftaran Sudah Checkin ({{ $antrians->where('taskid', 1)->count() }} Orang)"
                    theme="primary" icon="fas fa-info-circle" collapsible>
                    @php
                        $heads = ['No', 'Kode', 'Tanggal', 'NIK / Kartu', 'No RM', 'Jenis', 'Kunjungan', 'Poliklinik', 'Jam Praktek', 'Status', 'Action'];
                    @endphp
                    <x-adminlte-datatable id="table1" :heads="$heads" striped bordered hoverable compressed>
                        @foreach ($antrians->where('taskid', '!=', 0) as $item)
                            <tr>
                                <td>{{ $item->angkaantrean }}</td>
                                <td>{{ $item->kodebooking }}<br>
                                    {{ $item->nomorantrean }}
                                </td>
                                <td>{{ $item->tanggalperiksa }}</td>
                                <td>
                                    {{ $item->nik }}
                                    @isset($item->nomorkartu)
                                        <br>
                                        {{ $item->nomorkartu }}
                                    @endisset
                                </td>
                                <td>{{ $item->norm }}</td>
                                <td>{{ $item->jenispasien }}</td>
                                <td>{{ $item->jeniskunjungan }}</td>
                                <td>{{ $item->namapoli }}</td>
                                <td>{{ $item->jampraktek }}</td>
                                <td>
                                    @if ($item->taskid == 0)
                                        <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                    @endif
                                    @if ($item->taskid == 1)
                                        <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                    @endif
                                    @if ($item->taskid == 2)
                                        <span class="badge bg-success">{{ $item->taskid }}. Proses Admisi</span>
                                    @endif
                                    @if ($item->taskid == 3)
                                        <span class="badge bg-warning">{{ $item->taskid }}. Tunggu Poli</span>
                                    @endif
                                    @if ($item->taskid == 4)
                                        <span class="badge bg-success">{{ $item->taskid }}. Periksa Poli</span>
                                    @endif
                                    @if ($item->taskid == 99)
                                        <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                    @endif
                                    @if ($item->pasienbaru == 1)
                                        <br> <span class="badge bg-primary">{{ $item->pasienbaru }}. Online</span>
                                    @endif
                                    @if ($item->pasienbaru == 2)
                                        <br> <span class="badge bg-danger">{{ $item->pasienbaru }}. Offline</span>
                                    @endif
                                </td>
                                <td>
                                    <x-adminlte-button class="btn-xs" label="Panggil" theme="warning"
                                        icon="fas fa-volume-down" data-toggle="tooltop" title=""
                                        onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                    <x-adminlte-button class="btn-xs" label="Layani" theme="success"
                                        icon="fas fa-hand-holding-medical" data-toggle="tooltop" title=""
                                        onclick="window.location='{{ route('antrian.layanan', $item->kodebooking) }}'" />
                                    <x-adminlte-button class="btn-xs" label="Batal" theme="danger"
                                        icon="fas fa-times" data-toggle="tooltop" title=""
                                        onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                </td>
                            </tr>
                        @endforeach
                    </x-adminlte-datatable>
                </x-adminlte-card>
                <x-adminlte-card
                    title="Antrian Pendaftaran Belum Checkin ({{ $antrians->where('taskid', 0)->count() }} Orang)"
                    theme="secondary" icon="fas fa-info-circle" collapsible="collapsed">
                    @php
                        $heads = ['No', 'Nomor', 'Tanggal', 'NIK / Kartu', 'No RM', 'Jenis', 'Kunjungan', 'Poliklinik', 'Jam Praktek', 'Status'];
                    @endphp
                    <x-adminlte-datatable id="table2" :heads="$heads" striped bordered hoverable compressed>
                        @foreach ($antrians->where('taskid', 0) as $item)
                            <tr>
                                <td>{{ $item->angkaantrean }}</td>
                                <td>{{ $item->nomorantrean }} <br>
                                    {{ $item->kodebooking }}
                                </td>
                                <td>{{ $item->tanggalperiksa }}</td>
                                <td>
                                    {{ $item->nik }}
                                    @isset($item->nomorkartu)
                                        <br>
                                        {{ $item->nomorkartu }}
                                    @endisset
                                </td>
                                <td>{{ $item->norm }}</td>
                                <td>{{ $item->jenispasien }}</td>
                                <td>{{ $item->jeniskunjungan }}</td>
                                <td>{{ $item->namapoli }}</td>
                                <td>{{ $item->jampraktek }}</td>
                                <td>
                                    @if ($item->taskid == 0)
                                        <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                    @endif
                                    @if ($item->taskid == 1)
                                        <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                    @endif
                                    @if ($item->taskid == 99)
                                        <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-adminlte-datatable>
                </x-adminlte-card>
            @endif
        </div>
    </div>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)
