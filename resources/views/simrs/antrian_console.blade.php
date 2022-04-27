@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@section('title', 'Antrian QR Code')
@section('body')
    <div class="wrapper">
        <div class="row p-3">
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
            <div class="col-md-8">
                <x-adminlte-card title="Ambil Antrian Ofline RSUD Waled" theme="primary" icon="fas fa-qrcode">
                    <div class="text-center">
                        <h6>Pilih Antrian Poliklinik</h6>
                        <div class="row">
                            @foreach ($poliklinik as $poli)
                                <div class="col-md-3">
                                    {{-- <a class="withLoad"
                                        href="{{ route('antrian.tambah_offline', $poli->kodesubspesialis) }}"> --}}
                                    <x-adminlte-info-box
                                        text="{{ $poli->antrians->where('tanggalperiksa', \Carbon\Carbon::now()->format('Y-m-d'))->count() }} / {{ $poli->jadwals->where('hari', \Carbon\Carbon::now()->dayOfWeek)->sum('kapasitaspasien') }}"
                                        title="POLI {{ $poli->namasubspesialis }} "
                                        class="tombolPoli" data-id="{{ $poli->kodesubspesialis }}" theme="success" />
                                    {{-- </a> --}}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>
    {{-- Themed --}}
    <x-adminlte-modal id="modalDokter" size="lg" title="Pilih Dokter Poliklinik" theme="success" icon="fas fa-user-md">
        <div id="btnDokter">
        </div>
    </x-adminlte-modal>
    {{-- Example button to open modal --}}
@stop

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
                    if (data.success == 'true') {
                        $('#status').html(data.metadata.message);
                    } else
                        $('#status').html(data.metadata.message);
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
    {{-- btn poli --}}
    <script>
        $(function() {
            $('.tombolPoli').click(function() {
                $.LoadingOverlay("show");
                var kodepoli = $(this).data('id');
                var tanggalperiksa = "{{ \Carbon\Carbon::now()->format('Y-m-d') }}";
                var url =
                    "http://127.0.0.1:8000/api/antrian/ref/jadwal?kodepoli=" + kodepoli +
                    "&tanggalperiksa=" + tanggalperiksa;
                $.get(url, function(data) {
                    console.log(data);
                    $.LoadingOverlay("hide", true);
                    $('#modalDokter').modal('show');
                    $('.btnPilihDokter').remove();

                    $.each(data.response, function(value) {
                        console.log(data.response[value].namadokter);
                        $('#btnDokter').append(
                            "<a href='#' class='btn btn-lg bg-success m-2 btnPilihDokter'>" +
                            data
                            .response[value].jadwal + " " + data
                            .response[value].namadokter + " (" + data
                            .response[value].kapasitaspasien + ") </a>");
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
    </script>
    @include('sweetalert::alert')
@stop
