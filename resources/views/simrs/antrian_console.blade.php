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
                                    <a class="withLoad"
                                        href="{{ route('antrian.tambah_offline', $poli->kodesubspesialis) }}">
                                        <x-adminlte-info-box text="{{ $poli->antrians->where('tanggalperiksa', \Carbon\Carbon::now()->format('Y-m-d'))->count() }}" title="POLI {{ $poli->namasubspesialis }}" theme="success" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>
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
