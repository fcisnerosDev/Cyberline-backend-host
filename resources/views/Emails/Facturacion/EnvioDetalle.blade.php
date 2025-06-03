<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <table cellspacing='0' cellpadding='0' border='0' align='center'>
        <tbody style=''>
        <tr style=''>
            <td colspan='2' class='texto_correo' style='font-family: arial; font-size: 12px; text-align: center;' width='500'>
                <div class='col-xs-12 vcenter'>
                    <img id='logoCyberline' class='center-block' src='https://cybernet.cyberline.com.pe/img/logos/LogoCyberline_1.png'>
                </div>
                <br style=''>
            </td>
        </tr>
        <tr style=''>
        <td style='' width='500'>
        <table style='' width='500'>
        <tbody style=''>
        <tr style=''>
            <td class='titulo_correo' colspan='2' style='font-family: arial; font-size: 12px; text-align: justify; font-weight: bold; color: rgb(66, 87, 143); padding: 10px 0px;'>
                COMPROBANTES DE PAGO CYBERLINE
            </td>
        </tr>
        <tr style=''>
        <td class='texto_correo' colspan='2' style='font-family: arial; font-size: 12px; text-align: justify;'>
        Estimado Personal administrativo,</td>
        </tr>
        <tr style=''>
        <td class='texto_correo' colspan='2' style='font-family: arial; font-size: 12px; text-align: justify; padding: 10px 0px;'>
        Se  adjunta el siguiente comprobante electrónico:</td>
        </tr>
        <tr style=''>
        <td class='texto_correo' style='font-family: arial; font-size: 12px; text-align: justify;' width='250'>
        Tipo de Documento:</td>
        <td class='texto_tci' style='font-family: arial; font-size: 12px; text-align: justify; border: 1px solid rgb(0, 0, 0);' width='250'>
            {{ $notaCredito->serie }}</td>
        </tr>
        <tr style=''>
        <td class='texto_correo' style='font-family: arial; font-size: 12px; text-align: justify;' width='250'>
        Número de Comprobante:</td>
        <td class='texto_tci' style='font-family: arial; font-size: 12px; text-align: justify; border: 1px solid rgb(0, 0, 0);'width='250'>
            {{ $notaCredito->correlativo }}</td></td>
        </tr>
        <tr style=''>
        <td class='texto_correo' style='font-family: arial; font-size: 12px; text-align: justify;' width='250'>
        Fecha de Emisión</td>
        <td class='texto_tci' style='font-family: arial; font-size: 12px; text-align: justify; border: 1px solid rgb(0, 0, 0);' width='250'>
        <span class='Object' role='link' id='OBJ_PREFIX_DWT5413_com_zimbra_date'><span class='Object' role='link' id='OBJ_PREFIX_DWT5421_com_zimbra_date'>{{ \Carbon\Carbon::parse($notaCredito->fecha_emision)->format('d/m/Y') }}
        </span></span></td>
        </tr>
        <tr style=''>
        <td colspan='2' class='texto_correo' style='font-family: arial; font-size: 12px; text-align: center; padding: 10px 0px;' width='500'>
        Para consultar su comprobante ingrese <span class='Object' role='link' id='OBJ_PREFIX_DWT5414_com_zimbra_url'><span class='Object' role='link' id='OBJ_PREFIX_DWT5422_com_zimbra_url'><a href='https://cybernet2.cyberline.com.pe/' target='_blank' style=''>
        aquí</a></span></span>.</td>
        </tr>
        <tr style=''>
        <td colspan='2' class='texto_correo' style='font-family: arial; font-size: 12px; text-align: center;' width='500'>
        Visítanos <span class='Object' role='link' id='OBJ_PREFIX_DWT5415_com_zimbra_url'><span class='Object' role='link' id='OBJ_PREFIX_DWT5423_com_zimbra_url'><a href='http://cyberline.pe' target='_blank' style=''>
        aquí</a></span></span>:</td>
        </tr>
        </tbody>
        </table>
        </td>
        <td style=''>&nbsp;</td>
        </tr>
        </tbody>
    </table>
</body>
</html>
