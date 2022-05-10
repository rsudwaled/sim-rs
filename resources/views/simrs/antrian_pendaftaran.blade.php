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
                    <x-adminlte-button class="withLoad" theme="success" label="Tambah Antrian Offline" />
                </form>
            </x-adminlte-card>
            @if (isset($request->loket) && isset($request->lantai) && isset($request->tanggal))
                <x-adminlte-card
                    title="Antrian Pendaftaran Sudah Checkin ({{ $antrians->where('taskid', 1)->count() }} Orang)"
                    theme="primary" icon="fas fa-info-circle" collapsible>
                    @if ($errors->any())
                        <x-adminlte-alert title="Ops Terjadi Masalah !" theme="danger" dismissable>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-adminlte-alert>
                    @endif
                    @php
                        $heads = ['No', 'Kode', 'Tanggal', 'NIK / Kartu', 'No RM', 'Jenis', 'Poliklinik', 'Jam', 'Status', 'Action'];
                        $config['order'] = ['8', 'asc'];
                    @endphp
                    <x-adminlte-datatable id="table1" :heads="$heads" :config="$config" striped bordered hoverable
                        compressed>
                        @foreach ($antrians->where('taskid', '!=', 0) as $item)
                            <tr>
                                <td>{{ $item->angkaantrean }}</td>
                                <td>{{ $item->kodebooking }}<br>
                                    {{ $item->nomorantrean }}
                                </td>
                                <td>{{ $item->tanggalperiksa }}</td>
                                <td>
                                    {{ $item->nik }}
                                    @isset($item->pasien)
                                        <br>
                                        {{ $item->pasien->nama }}
                                    @endisset

                                </td>
                                <td>{{ $item->norm }}</td>
                                <td>
                                    {{ $item->jenispasien }}
                                    @isset($item->nomorkartu)
                                        <br>
                                        {{ $item->nomorkartu }}
                                    @endisset
                                </td>
                                <td>{{ $item->namapoli }}<br>{{ $item->namadokter }} </td>
                                <td>{{ $item->jampraktek }}</td>
                                <td>
                                    @if ($item->taskid == 0)
                                        <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                    @endif
                                    @if ($item->taskid == 1)
                                        <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                    @endif
                                    @if ($item->taskid == 2)
                                        <span class="badge bg-primary">{{ $item->taskid }}. Pembayaran</span>
                                    @endif
                                    @if ($item->taskid == 3)
                                        <span class="badge bg-success">{{ $item->taskid }}. Tunggu Poli</span>
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
                                    @if ($item->taskid == 1)
                                        <x-adminlte-button class="btn-xs" label="Panggil" theme="warning"
                                            icon="fas fa-volume-down" data-toggle="tooltop" title=""
                                            onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                        <x-adminlte-button class="btn-xs btnDaftar" label="Daftar" theme="primary"
                                            icon="fas fa-hand-holding-medical" data-toggle="tooltop" title="Daftar"
                                            data-id="{{ $item->id }}" />
                                        <x-adminlte-button class="btn-xs" theme="success" icon="fas fa-check"
                                            data-toggle="tooltop" title=""
                                            onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                        <x-adminlte-button class="btn-xs" theme="danger" icon="fas fa-times"
                                            data-toggle="tooltop" title=""
                                            onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                    @else
                                        <x-adminlte-button class="btn-xs" label="Print Karcis" theme="warning"
                                            icon="fas fa-print" data-toggle="tooltop" title=""
                                            onclick="window.location='{{ route('antrian.panggil', $item->kodebooking) }}'" />
                                    @endif

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
    <x-adminlte-modal id="modalDaftar" title="Pendaftaran Pasien Offline" size="lg" theme="success" icon="fas fa-user-plus"
        v-centered>
        <form name="formDaftar" id="formDaftar" action="{{ route('antrian.update_offline') }}" method="post">
            @csrf
            <input type="hidden" name="antrianid" id="antrianid" value="">
            <dl class="row">
                <dt class="col-sm-3">Kode Booking</dt>
                <dd class="col-sm-8">: <span id="kodebooking"></span></dd>
                <dt class="col-sm-3">Antrian</dt>
                <dd class="col-sm-8">: <span id="angkaantrean"></span> / <span id="nomorantrean"></span></dd>
                <dt class="col-sm-3">Administrator</dt>
                <dd class="col-sm-8">: {{ Auth::user()->name }}</dd>
            </dl>
            <x-adminlte-card theme="primary" title="Informasi Kunjungan Berobat">
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-input name="nik" id="nik" label="NIK" placeholder="NIK" enable-old-support>
                            <x-slot name="appendSlot">
                                <x-adminlte-button name="cariNIK" id="cariNIK" theme="primary" label="Cari!" />
                            </x-slot>
                            <x-slot name="prependSlot">
                                <div class="input-group-text text-primary">
                                    <i class="fas fa-search"></i>
                                </div>
                            </x-slot>
                            <x-slot name="bottomSlot">
                                <span id="pasienTidakDitemukan" class="text-sm text-danger"></span>
                                <span id="pasienDitemukan" class="text-sm text-success"></span>
                            </x-slot>
                        </x-adminlte-input>
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-input name="norm" label="Nomor RM" placeholder="Nomor RM" readonly enable-old-support />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-input name="statuspasien" label="Status Pasien" placeholder="Status Pasien" readonly
                            enable-old-support />
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="nomorkk" label="Nomor KK" placeholder="Nomor KK" enable-old-support />
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="nama" label="Nama Lengkap" placeholder="Nama Lengkap" enable-old-support />
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="nohp" label="Nomor HP" placeholder="Nomor HP Aktif" enable-old-support />
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-input name="nomorkartu" label="Nomor Kartu BPJS" placeholder="Nomor Kartu BPJS"
                            enable-old-support>
                            <x-slot name="bottomSlot">
                                <span class="text-sm text-danger">
                                    Masukan jika kunjungan anda menggunakan BPJS/JKN
                                </span>
                            </x-slot>
                        </x-adminlte-input>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-input name="nomorreferensi" label="Nomor Rujukan" placeholder="Nomor Rujukan"
                            enable-old-support>
                            <x-slot name="bottomSlot">
                                <span class="text-sm text-danger">
                                    Masukan jika kunjungan anda menggunakan BPJS/JKN
                                </span>
                            </x-slot>
                        </x-adminlte-input>
                    </div>
                    <div class="col-md-4">
                        <input type="hidden" name="kodepoli" id="kodepoli" value="">
                        <x-adminlte-input name="namapoli" label="Poliklinik" placeholder="Nama Poliklinik" readonly
                            enable-old-support />
                    </div>
                    <div class="col-md-8">
                        <input type="hidden" name="kodedokter" id="kodedokter" value="">
                        <x-adminlte-input name="namadokter" label="Dokter" placeholder="Nama Dokter" readonly
                            enable-old-support />
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-select id="jeniskunjungan" name="jeniskunjungan" label="Jenis Kunjungan"
                            enable-old-support>
                            <option disabled selected>PILIH JENIS KUNJUNGAN</option>
                            <option value="1">RUJUKAN FKTP</option>
                            <option value="3">KONTROL</option>
                            <option value="2">RUJUKAN INTERNAL</option>
                            <option value="4">RUJUKAN ANTAR RS</option>
                        </x-adminlte-select>
                    </div>
                    <div class="col-md-4">
                        @php
                            $config = ['format' => 'YYYY-MM-DD'];
                        @endphp
                        <x-adminlte-input-date name="tanggalperiksa" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                            label="Tanggal Periksa" readonly :config="$config" />
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="jampraktek" label="Jadwal Praktek" placeholder="Waktu Jadwal Praktek"
                            readonly enable-old-support />
                    </div>

                </div>
            </x-adminlte-card>
            <x-adminlte-card id="formPasien" theme="primary" title="Informasi Pasien Berobat">
                <div class="row">
                    <div class="col-md-4">
                        <x-adminlte-select id="jeniskelamin" name="jeniskelamin" label="Jenis Kelamin" enable-old-support>
                            <option disabled selected>PILIH JENIS KELAMIN</option>
                            <option value="L">LAKI-LAKI</option>
                            <option value="P">PEREMPUAN</option>
                        </x-adminlte-select>
                    </div>
                    <div class="col-md-4">
                        @php
                            $config = ['format' => 'YYYY-MM-DD'];
                        @endphp
                        <x-adminlte-input-date name="tanggallahir" value="" label="Tanggal Lahir"
                            placeholder="Tanggal Lahir" :config="$config" enable-old-support />
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-8">
                        <x-adminlte-input name="alamat" label="Alamat" placeholder="Alamat" enable-old-support />
                    </div>
                    <div class="col-md-2">
                        <x-adminlte-input name="rt" label="Nomor RT" placeholder="RT" enable-old-support />
                    </div>
                    <div class="col-md-2">
                        <x-adminlte-input name="rw" label="Nomor RW" placeholder="RW" enable-old-support />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-select2 name="kodeprop" id="kodeprop" label="Provonsi">
                            <option value="" disabled selected>PILIH PROVINSI</option>
                            @foreach ($provinsis as $item)
                                <option value="{{ $item->kode }}">{{ $item->nama }}</option>
                            @endforeach
                        </x-adminlte-select2>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-select2 name="kodedati2" id="kodedati2" label="Kota / Kabupaten">
                            <option value="" disabled selected>PILIH PROVINSI</option>
                        </x-adminlte-select2>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-select2 name="kodekec" id="kodekec" label="Kecamatan">
                            <option value="" disabled selected>PILIH PROVINSI</option>
                        </x-adminlte-select2>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-input name="namakel" id="namakel" label="Kelurahan / Desa"
                            placeholder="Kelurahan / Desa" enable-old-support />
                    </div>
                </div>
            </x-adminlte-card>
            <x-slot name="footerSlot">
                <x-adminlte-button label="Daftar" form="formDaftar" class="mr-auto" type="submit" theme="success"
                    icon="fas fa-plus" />
                <x-adminlte-button theme="danger" label="Dismiss" data-dismiss="modal" />
            </x-slot>
        </form>

    </x-adminlte-modal>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)

@section('js')
    <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.btnDaftar').click(function() {
                var antrianid = $(this).data('id');
                $.LoadingOverlay("show");
                $.get("{{ route('antrian.index') }}" + '/' + antrianid + '/edit', function(data) {
                    // console.log($data);
                    $('#kodebooking').html(data.kodebooking);
                    $('#angkaantrean').html(data.angkaantrean);
                    $('#nomorantrean').html(data.nomorantrean);
                    $('#user').html(data.user);
                    $('#antrianid').val(antrianid);
                    $('#namapoli').val(data.namapoli);
                    $('#namadokter').val(data.namadokter);
                    $('#kodepoli').val(data.kodepoli);
                    $('#kodedokter').val(data.kodedokter);
                    $('#jampraktek').val(data.jampraktek);
                    // $('#kodepoli').val(data.kodepoli).trigger('change');
                    $('#modalDaftar').modal('show');

                    $.LoadingOverlay("hide", true);
                })
            });
        });
    </script>
    {{-- js jadwal --}}
    {{-- <script>
        $(function() {
            $("#kodepoli").change(function() {
                var url = 'http://127.0.0.1:8000/api/antrian/ref/jadwal';
                $.LoadingOverlay("show");
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: {
                        kodepoli: $("#kodepoli").val(),
                        tanggalperiksa: $("#tanggalperiksa").val(),
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        if (data.metadata.code != 200) {
                            $("#kodedokter").empty();
                            $.LoadingOverlay("hide", true);
                            alert(
                                "Jadwal Dokter Poliklinik pada tanggal tersebut tidak tersedia"
                            );
                            return false;
                        } else {
                            $("#kodedokter").empty();
                            $.each(data.response, function(item) {
                                $('#kodedokter').append($('<option>', {
                                    value: data.response[item]
                                        .kodedokter,
                                    text: data.response[item].jadwal +
                                        ' - ' + data.response[item]
                                        .namadokter
                                }));
                            })
                        }
                    }
                });
            });
        });
    </script> --}}
    {{-- js pasien baru / lama --}}
    <script>
        $(function() {
            $('#formPasien').hide();

            $('#cariNIK').on('click', function() {
                var nik = $('#nik').val();
                if (nik == '') {
                    alert('NIK tidak boleh kosong');
                } else {
                    $.LoadingOverlay("show");
                    $.get("http://127.0.0.1:8000/antrian/cari_pasien/" + nik, function(data) {
                        console.log(data.metadata.code);
                        if (data.metadata.code == 200) {
                            $('#pasienDitemukan').html(data.metadata.message);
                            $('#pasienTidakDitemukan').html('');
                            $('#nomorkk').val(data.response.nomorkk);
                            $('#nohp').val(data.response.nohp);
                            $('#nama').val(data.response.nama);
                            $('#norm').val(data.response.norm);
                            $('#nomorkartu').val(data.response.nomorkartu);
                            $('#statuspasien').val('LAMA');
                            $('#formPasien').hide();

                        } else {
                            $('#pasienTidakDitemukan').html(data.metadata.message);
                            $('#pasienDitemukan').html('');
                            $('#statuspasien').val('BARU');
                            $('#nomorkk').val('');
                            $('#nohp').val('');
                            $('#nama').val('');
                            $('#nomorkartu').val('');
                            $('#formPasien').show();
                        }
                        $.LoadingOverlay("hide", true);
                    })
                }
            });
        });
    </script>
    {{-- js provinsi --}}
    <script>
        $(function() {
            $('#kodeprop').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: 'http://127.0.0.1:8000/api/vclaim/ref_kabupaten',
                    method: 'POST',
                    data: {
                        provinsi: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodedati2').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodedati2').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
            $('#kodedati2').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: 'http://127.0.0.1:8000/api/vclaim/ref_kecamatan',
                    method: 'POST',
                    data: {
                        kabupaten: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodekec').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodekec').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
        });
    </script>
@endsection
