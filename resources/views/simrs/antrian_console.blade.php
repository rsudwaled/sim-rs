@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@section('title', 'Antrian QR Code')
@section('body')

    <div class="wrapper">
        <div class="row p-3">
            {{-- checkin --}}
            <div class="col-md-4">
                <x-adminlte-card title="Checkin Antrian RSUD Waled" theme="primary" icon="fas fa-qrcode">
                    <div class="text-center">
                        <br>
                        <x-adminlte-input name="kodebooking" label="Silahkan scan QR Code Antrian atau masukan Kode Antrian"
                            placeholder="Masukan Kode Antrian untuk Checkin" igroup-size="lg">
                            <x-slot name="appendSlot">
                                <x-adminlte-button name="btn_checkin" id="btn_checkin" theme="success" label="Checkin!" />
                            </x-slot>
                            <x-slot name="prependSlot">
                                <div class="input-group-text text-success">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                            </x-slot>
                        </x-adminlte-input>
                        <i class="fas fa-qrcode fa-10x"></i>
                        <br>
                        <h2>Status = <span id="status">-</span></h2>
                    </div>
                </x-adminlte-card>
            </div>
            {{-- ambil antrian offline --}}
            <div class="col-md-8">
                <x-adminlte-card title="Ambil Antrian Ofline RSUD Waled" theme="primary" icon="fas fa-qrcode">
                    <div class="text-center">
                        <h6>Pilih Antrian Poliklinik</h6>
                        <p hidden>{{ setlocale(LC_ALL, 'IND') }}</p>
                        <h6>{{ \Carbon\Carbon::now()->formatLocalized('%A, %d %B %Y') }}</h6>
                        <div class="row">
                            @foreach ($poliklinik as $poli)
                                <div class="col-md-4">
                                    <x-adminlte-info-box
                                        text="{{ $poli->antrians->where('tanggalperiksa', \Carbon\Carbon::now()->format('Y-m-d'))->count() }} / {{ $poli->jadwals->where('hari', \Carbon\Carbon::now()->dayOfWeek)->where('kodesubspesialis', $poli->kodesubspesialis)->sum('kapasitaspasien') }}"
                                        title="{{ $poli->namasubspesialis }} " class="tombolPoli"
                                        data-id="{{ $poli->kodesubspesialis }}"
                                        theme="{{ $poli->antrians->where('tanggalperiksa', \Carbon\Carbon::now()->format('Y-m-d'))->count() >=$poli->jadwals->where('hari', \Carbon\Carbon::now()->dayOfWeek)->where('kodesubspesialis', $poli->kodesubspesialis)->sum('kapasitaspasien')? 'danger': 'success' }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <x-adminlte-button icon="fas fa-sync" class="withLoad reload" theme="success" label="Reload" />
                </x-adminlte-card>
            </div>
        </div>
    </div>
    {{-- Pilih Dokter --}}
    <x-adminlte-modal id="modalDokter" size="lg" title="Pilih Dokter Poliklinik" theme="success" icon="fas fa-user-md">
        <div id="btnDokter">
        </div>
    </x-adminlte-modal>
@stop
@section('plugins.Sweetalert2', true);
@section('adminlte_js')
    <script src="{{ asset('vendor/loading-overlay/loadingoverlay.min.js') }}"></script>
    <script src="{{ asset('vendor/onscan.js/onscan.min.js') }}"></script>
    {{-- scan --}}
    <script>
        $(function() {
            onScan.attachTo(document, {
                onScan: function(sCode, iQty) {
                    $.LoadingOverlay("show", {
                        text: "Printing..."
                    });
                    var url = "{{ route('antrian.checkin_update') }}";
                    var formData = {
                        kodebooking: sCode,
                    };
                    $('#kodebooking').val(sCode);
                    $.get(url, formData, function(data) {
                        if (data.success == 'true') {
                            $('#status').html(data.metadata.message);
                        } else
                            $('#status').html(data.metadata.message);
                        $.LoadingOverlay("hide");
                    });
                    setTimeout(function() {
                        $.LoadingOverlay("show", {
                            text: "Reload..."
                        });
                        location.reload();
                        $('#status').html('-');

                    }, 2000);
                },
            });
        });
    </script>
    {{-- btn chekin --}}
    <script>
        $(function() {
            $('#btn_checkin').click(function() {
                var kodebooking = $('#kodebooking').val();
                $.LoadingOverlay("show", {
                    text: "Printing..."
                });
                var url = "{{ route('antrian.checkin_update') }}";
                var formData = {
                    kodebooking: kodebooking,
                };
                $('#kodebooking').val(kodebooking);
                $.get(url, formData, function(data) {
                    if (data.metadata.code == 200) {
                        $('#status').html(data.metadata.message);
                        swal.fire(
                            'Sukses...',
                            data.metadata.message,
                            'success'
                        );
                    } else {
                        $('#status').html(data.metadata.message);
                        swal.fire(
                            'Opss Error...',
                            data.metadata.message,
                            'error'
                        );
                    }
                    $.LoadingOverlay("hide");
                    setTimeout(function() {
                        $.LoadingOverlay("show", {
                            text: "Reload..."
                        });
                        location.reload();
                        $('#status').html('-');
                    }, 3000);
                });
            });
        });
    </script>
    {{-- btn poli pilih dokter --}}
    <script>
        $(function() {
            $('.tombolPoli').click(function() {
                $.LoadingOverlay("show");
                var kodepoli = $(this).data('id');
                var tanggalperiksa = "{{ \Carbon\Carbon::now()->format('Y-m-d') }}";
                var url =
                    "{{ route('antrian.index') }}" + "/console_jadwaldokter/" + kodepoli +
                    "/" + tanggalperiksa;
                console.log(url);
                setTimeout(function() {
                    $.LoadingOverlay("hide", true);
                }, 2000);
                $.get(url, function(data) {
                    $('#modalDokter').modal('show');
                    $('.btnPilihDokter').remove();
                    $.each(data, function(value) {
                        $('#btnDokter').append(
                            "<a href='" + "{{ route('antrian.index') }}" +
                            "/tambah_offline/" +
                            data[
                                value].kodesubspesialis +
                            "/" + data[value].kodedokter + "/" + data[value].jadwal +
                            "' class='btn btn-lg bg-success m-2 btnPilihDokter withLoad'>" +
                            data[value].jadwal + " " + data[value].namadokter + " (" +
                            data[value].kapasitaspasien + ") </a>");
                    });
                });
            });
        });
    </script>
    {{-- withLoad --}}
    <script>
        $(function() {
            $(".withLoad").click(function() {
                $.LoadingOverlay("show");
            });
        })
        $('.reload').click(function() {
            location.reload();
        });
    </script>
    @include('sweetalert::alert')
@stop
