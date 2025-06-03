<?php

use App\Http\Controllers\ActualizarConexionPadreController;
use App\Http\Controllers\ActualizarMonitoreoController;
use App\Http\Controllers\Api\Facturacion\NotasCredito\NotasCreditoController;
use App\Http\Controllers\Api\Facturacion\NotasCredito\NotasDebitoController;
use App\Http\Controllers\BackupMonResumenController;
use App\Http\Controllers\Correo\CorreoMonitoreoController;
use App\Http\Controllers\InformarConexionController;
use App\Http\Controllers\InformarConexionHijoController;
use App\Http\Controllers\MailTestController;
use App\Http\Controllers\ReportMonitoreoCliente;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ReplicarMonitoreoController;
use App\Http\Controllers\SyncCybernetOldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/facturas', [ReportsController::class, 'export']);

// Ruta llamada cron solo para Appliance cyberline
Route::get('/verificar-conexion-nodo-hijo', [InformarConexionController::class, 'sincronizarConHijo']);
// Ruta llamada cron solo para cliente
Route::get('/verificar-conexion-nodo-padre', [InformarConexionController::class, 'sincronizarConPadre']);
// Ruta llamada cron solo para cliente
Route::get('/replicar-data-conexion-padre', [InformarConexionController::class, 'replicarDatosConexion']);

// Cybernet Old v6
Route::get('/update-monitoreo', [SyncCybernetOldController::class, 'UpdateMonitoreoData']);
Route::get('/recover-monitoreo/{idNodo?}', [SyncCybernetOldController::class, 'DataMonitoreos']);


Route::get('/recover-monitoreo-cliente', [SyncCybernetOldController::class, 'obtenerMonitoreosCliente']);

Route::get('/recover-servicios/{idNodo?}', [SyncCybernetOldController::class, 'DataServicios']);

Route::get('/recover-servicios-update', [SyncCybernetOldController::class, 'obtenerServiciosCliente']);

// Reportes Monitoreos desconocidos Excel
Route::get('/reporte-log-monitoreo', [ReportMonitoreoCliente::class, 'exportarReporteExcelMonitoreo']);
// Route::get('/test-mail', [MailTestController::class, 'sendTestEmail']);


// Route::get('/verificarconexion', [InformarConexionHijoController::class, 'verificarConexion']);

// Route::get('/replicar-monitoreo', [ReplicarMonitoreoController::class, 'replicar']);
// Route::get('/replicar-monitoreo-individual', [ReplicarMonitoreoController::class, 'replicaIndividual']);

// Route::get('/primary-client-conect', [ReplicarMonitoreoController::class, 'replicaIndividual']);
// Route::get('/secundary-conexion', [ReplicarMonitoreoController::class, 'replicaIndividual']);

// Cybernet Old v6
//correos
// Route::get('/monitoreo-correos', [CorreoMonitoreoController::class, 'Correos']);


// Facturacion

Route::get('/correo/{correlativo}', [NotasDebitoController::class, 'generateMailNotasDebito']);
Route::get('/correo/notas-credito/{correlativo}', [NotasCreditoController::class, 'generateMailNotasCredito']);
Route::get('/correo-invoice/notas-credito/{correlativo}', [NotasCreditoController::class, 'generateMailInvoiceNotasCredito']);

Route::get('/correo-invoice/notas-debito/{correlativo}', [NotasDebitoController::class, 'generateMailInvoiceNotasDebito']);

// Host New
Route::get('/backup-resumen-auto', [BackupMonResumenController::class, 'Backup']);
