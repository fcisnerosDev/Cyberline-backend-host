<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Documento-Electrónico</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">

    <style>
        .clearfix:after {
            content: " ";
            display: block;
            height: 0;
            clear: both;
        }

        .clearfix {
            overflow: auto;
        }

        #contenedor {
            display: table;
            border: 0.1px solid #5A5858;
            width: 500px;
            text-align: center;
            margin: 0 auto;
        }

        #contenidos {
            display: table-row;
        }

        #columna1,
        #columna2,
        #columna3 {
            display: table-cell;
            border: 0.1px solid #5A5858;
            vertical-align: middle;
            padding: 1px;
            font-size: 11px;
            width: 50px
        }

        .row {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
            margin-right: -12px;
            margin-left: -12px;
        }

        /* .col-lg-6 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 50%;
            flex: 0 0 50%;
            max-width: 52%;
            position: relative;
            left: 2%;
            padding-inline: 17px;
        } */

        .col-lg-12 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 100%;
            flex: 0 0 95%;
            max-width: 100%;
            position: relative;
            left: 2%;
            padding-inline: 0;
        }

        .col-lg-9 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 50%;
            flex: 0 0 70%;
            max-width: 64%;
            position: relative;
            left: 2%;

            padding-inline-end: 34px;
        }

        .col-lg-90 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 50%;
            flex: 0 0 70%;
            max-width: 64%;
            position: relative;
            left: 2%;

            padding-inline-end: 70px;
        }

        .col-lg-3 {
            /* -webkit-box-flex: 0; */
            -ms-flex: 0 0 50%;
            flex: 0 0 25%;
            max-width: 25%;
            position: relative;
            left: 2%;
            /* padding-inline: 17px; */
        }

        .card {
            margin-bottom: 24px;
            -webkit-box-shadow: 0 .75rem 6rem rgba(56, 65, 74, .03);
            box-shadow: 0 .75rem 6rem rgba(56, 65, 74, .03);
        }

        .card-body {
            -webkit-box-flex: 1;
            -ms-flex: 1 1 auto;
            flex: 1 1 auto;
            padding: 0.3rem;
        }

        .card {
            position: relative;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 1px solid #c1c1c1;
            border-radius: 0px;
        }

        .card2 {
            position: relative;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: none;
            background-clip: border-box;
        }

        .card.card-body {
            background: #d3d2d270;
        }

        p {
            font-family: 'Roboto', sans-serif;
            margin-left: 10px;
            /* margin-top: 10px; */
            /* margin-bottom: 5px; */
            font-size: 13px;
        }

        img {
            width: 198px;
        }

        p.text-factura {
            font-size: 13px;
        }

        .card2.card-white {
            position: relative;
            left: 62px;
            top: -12px;
        }

        .card3.card-white {
            position: relative;

        }

        .table {
            width: 100%;
            margin-bottom: 1.5rem;
            color: #6c757d
        }

        .table td,
        .table th {
            padding: .85rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6
        }

        .table tbody+tbody {
            border-top: 2px solid #dee2e6
        }

        .table-sm td,
        .table-sm th {
            padding: .5rem
        }

        .table-bordered {
            border: 1px solid #dee2e6
        }

        .table-bordered td,
        .table-bordered th {
            border: 1px solid #dee2e6
        }

        .table-bordered thead td,
        .table-bordered thead th {
            border-bottom-width: 2px
        }

        .table-borderless tbody+tbody,
        .table-borderless td,
        .table-borderless th,
        .table-borderless thead th {
            border: 0
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f1f5f7
        }

        .table-hover tbody tr:hover {
            color: #6c757d;
            background-color: #f1f5f7
        }

        .table-primary,
        .table-primary>td,
        .table-primary>th {
            background-color: #cedef6
        }

        .table-primary tbody+tbody,
        .table-primary td,
        .table-primary th,
        .table-primary thead th {
            border-color: #a4c2ee
        }

        .table thead th {
            font-size: 11px;
            font-family: 'Roboto', sans-serif;
            vertical-align: bottom;
            border-bottom: 0;
        }

        .table td,
        .table th {
            font-size: 12px;
            font-family: 'Roboto', sans-serif;
            padding: .85rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        thead {
            background: #d3d2d270;
            border: none;
        }

        .table {
            width: 100%;
            margin-bottom: 1.5rem;
            color: #000000;
        }

        .col-lg-4 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 33.33333%;
            flex: 0 0 33.33333%;
            max-width: 33.33333%;
        }

        .qr-code {
            position: relative;
            top: -14px;
            left: 37px;
            width: auto;
        }
    </style>
</head>

<body>
    <?php
    $border = '#000000';
    $bgColor = '#FFFFFF';
    $bgBoxColor = '#EEEEEE';
    $bgBoxHeaderColor = '#004D91';
    $bgBoxTotalBg = '#DDDDDD';
    ?>
    <div id="documento_borde"
        style="
     position:relative;
     margin-top: 0px;
     width: 1000px;
     border: solid 1px <?php echo $border; ?>;
     background-color: <?php echo $bgColor; ?>;
     color: black;">

        <div class="clearfix"
            style="
     padding: 20px 20px;
     overflow: auto;
     position: relative;
     margin-top: 5px;
     ">

            <div style="
float: left;
width: 240px; ">
                <img style=" margin-left:20px; margin-bottom: 10px"
                    src="https://cybernet.cyberline.com.pe/img/logos/LogoCyberline_1.png" />
                <p id="boleto_header_1"
                    style="font-family: 'Roboto', sans-serif;
  text-align: center;
  margin-top: 5px;
  margin-bottom: 0px;
  font-size: 10px; ">
                    CYBERLINE SRL
                </p>
                <p
                    style="
  font-family: 'Roboto', sans-serif;
  margin-top: 5px;
  margin-bottom: 0px;
  font-size: 10px;
  text-align: center;
  color: #0000CC">
                    Av. del Pinar Nro. 152 Dpto. 708
                </p>
                <p
                    style="
font-family: 'Roboto', sans-serif;
margin-top: 5px;
margin-bottom: 0px;
font-size: 10px;
text-align: center;
color: #0000CC">
                    Urb. Chacarilla del Estanque - Santiago de Surco
                </p>
                <p
                    style="
font-family: 'Roboto', sans-serif;
margin-top: 5px;
margin-bottom: 0px;
font-size: 10px;
text-align: center;
color: #0000CC">
                    Lima - Lima - Per&uacute;
                </p>
                <p
                    style="
  font-family: 'Roboto', sans-serif;
  text-align: center;
  margin-top: 5px;
  margin-bottom: 0px;
  font-size: 10px;
  color: #0000CC">
                    Telf.: (51-1) 630-9595
                </p>
                <p
                    style="
  font-family: 'Roboto', sans-serif;
  text-align: center;
  margin-top: 5px;
  margin-bottom: 0px;
  font-size: 10px;
  color: #0000CC">
                    comercial@cyberline.com.pe - www.cyberline.com.pe
                </p>
            </div>

            <div
                style="
                        width: 180px;
                        float: right;
                        text-align: center;
                        border: solid 1px <?php echo $border; ?>;">

                <p id="boleto_header_5"
                    style="font-family: 'Roboto', sans-serif; margin-left: 10px; margin-top: 10px; margin-bottom: 5px; font-size: 14px;">
                    R.U.C. Nº 20125546481</p>

                <p id="boleto_header_6"
                    style="font-family: 'Roboto', sans-serif; margin-left: 10px; margin-top: 10px; margin-bottom: 5px; font-size: 13px;">
                    NOTA DE CRÉDITO<br> ELECTR&Oacute;NICA
                </p>
                <p id="boleto_header_7"
                    style="font-family: 'Roboto', sans-serif; margin-left: 10px; margin-top: 10px; margin-bottom: 10px; font-size: 16px;">
                    {{ $notaCredito->serie }} - {{ $notaCredito->correlativo }}
                </p>
            </div>
        </div>
        {{--  --}}

        <div class="row">
            <div class="col-lg-9">
                <div class="card card-body">
                    <p style="text-factura">

                        RAZON SOCIAL :
                        {{ $notaCredito->client->rzn_social ?? 'No disponible' }}
                    </p>

                    <p style="text-factura">

                        RUC :
                        {{ $notaCredito->client->num_doc ?? 'No disponible' }}
                    </p>

                    <p style="text-factura">

                        DIRECCIÓN:
                        {{ $notaCredito->client->direccion ?? 'No disponible' }}
                    </p>
                </div>
            </div>


            <div class="col-lg-3">
                <div class="card2 card-white">
                    <p class="text-factura">

                        FECHA EMISI&Oacute;N :
                        {{ \Carbon\Carbon::parse($notaCredito->fecha_emision)->format('d/m/Y') }}
                    </p>


                    <p class="text-factura">

                        CONDICIÓN DE PAGO :
                        {{ $notaCredito->forma_pago_tipo }}
                    </p>

                    <p class="text-factura">

                        MONEDA :
                        {{ $notaCredito->tipo_moneda == 'PEN' ? 'NUEVOS SOLES' : ($notaCredito->tipo_moneda == 'USD' ? 'DÓLARES AMERICANOS' : $notaCredito->tipo_moneda) }}

                    </p>
                </div>
            </div>
        </div>

        {{-- Referencia --}}

        <div class="row">
            <div class="col-lg-12">
                <div class="card card-body">
                    <p style="text-factura">

                        DOCUMENTO AFECTADO :
                        {{ $notaCredito->num_doc_afectado ?? 'No disponible' }}
                    </p>

                    <p style="text-factura">

                        CODIGO DE MOTIVO :
                        {{ $notaCredito->cod_motivo ?? 'No disponible' }}
                    </p>

                    <p style="text-factura">

                        MOTIVO :
                        {{ $notaCredito->des_motivo ?? 'No disponible' }}
                    </p>



                </div>
            </div>



        </div>


        <div class="row">
            <div class="col-lg-12">
                @if ($notaCredito->invoiceDetails && $notaCredito->invoiceDetails->count())
                    <table class="table mt-4 table-centered">
                        <thead>
                            <tr>
                                <th>CANT</th>
                                <th style="width: 90%">DESCRIPCIÓN</th>
                                <th style="width: 40%">V. UNITARIO</th>
                                <th style="width: 40%" class="text-right">IMPORTE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notaCredito->invoiceDetails as $detalle)
                                <tr>
                                    <td>{{ $detalle->cantidad }}</td>
                                    <td>{{ $detalle->descripcion }}</td>
                                    <td>{{ number_format($detalle->mto_valor_unitario, decimals: 2) }}</td>
                                    <td class="text-right">{{ number_format($detalle->mto_valor_unitario, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>


                    <div class="row">
                        <div class="col-lg-90">
                            <div class="card3 card-white">
                                <p style="text-factura">
                                    @foreach ($notaCredito->invoiceDetails as $detalle)
                                        SON: {{ $detalle->precio_unitario_letras }}<br><br>
                                    @endforeach
                                </p>



                            </div>
                        </div>


                        <div class="col-lg-3">
                            <div class="card2 card-white">
                                <p class="text-factura">
                                    @foreach ($notaCredito->invoiceDetails as $detalle)
                                        SUBTOTAL :
                                        {{ $notaCredito->tipo_moneda == 'PEN' ? 'S/.' : ($notaCredito->tipo_moneda == 'USD' ? '$' : $notaCredito->tipo_moneda) }}
                                        {{ number_format($detalle->mto_valor_unitario, decimals: 2) }}
                                    @endforeach
                                </p>


                                <p class="text-factura">

                                    IGV :
                                    @foreach ($notaCredito->invoiceDetails as $detalle)
                                        {{ $notaCredito->tipo_moneda == 'PEN' ? 'S/.' : ($notaCredito->tipo_moneda == 'USD' ? '$' : $notaCredito->tipo_moneda) }}
                                        {{ number_format($detalle->igv, 2) }}
                                    @endforeach
                                </p>

                                <p class="text-factura">

                                    TOTAL :
                                    @foreach ($notaCredito->invoiceDetails as $detalle)
                                        {{ $notaCredito->tipo_moneda == 'PEN' ? 'S/.' : ($notaCredito->tipo_moneda == 'USD' ? '$' : $notaCredito->tipo_moneda) }}
                                        {{ number_format($detalle->mto_precio_unitario, decimals: 2) }}
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <p>No hay detalles disponibles.</p>
                @endif

            </div>



        </div>

        {{-- detalles extra --}}

        <div class="row">
            <div class="col-lg-4">
                <div class="qr-code">
                    <img src="{{ $qrCode }}" style="width: 90px;">
                </div>

            </div>

            <div class="col-lg-4">
                <p> Representación impresa de
                    NOTA DE CREDITO ELECTRÓNICA,
                    Esta puede ser consultada en : <br>
                    https://cybernet2.cyberline.com.pe/</p>

            </div>

            <div class="col-lg-4">

            </div>
        </div>
    </div>

</body>

</html>
