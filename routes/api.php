<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Facturacion\Facturas\FacturasElectronicasController;
use App\Http\Controllers\Api\Facturacion\NotasCredito\NotasCreditoController;
use App\Http\Controllers\Api\Facturacion\NotasCredito\NotasDebitoController;
use App\Http\Controllers\Api\Monitoreo\MonitoreoController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TicketsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Aquí es donde puedes registrar las rutas API para tu aplicación.
| Estas rutas se cargan por el RouteServiceProvider y todas se asignan
| al grupo de middleware "api". ¡Haz algo genial!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Grupo de rutas protegidas
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/roles/getroles', 'getRoles')->name('roles.getroles');
        Route::get('/users/pagination', 'indexPagination')->name('user.pagination');
        Route::group(['middleware' => ['role:Administrador']], function () {
            // Route::get('/users/pagination', 'indexPagination')->name('user.pagination');
            // Route::post('/user/register', 'StoreUser')->name('user.store');
            // Route::get('/user/{id}', 'getUser')->name('user.get');
            // Route::put('/user/{id}', 'updateUser')->name('user.update');
            // Route::delete('/user/delete/{id}', 'deleteUser')->name('user.delete');
        });
    });

    Route::controller(TicketsController::class)->group(function () {
        Route::get('/tickets/pagination', 'indexPagination')->name('tickets.pagination');
        Route::get('/tickets/estados', 'getEstadosTicket')->name('tickets.getEstados');
        Route::get('/tickets/companias', 'getCompaniaTicket')->name('tickets.getCompaniaTicket');
        Route::get('/tickets/responsables', 'getResponsablesTicket')->name('tickets.getResponsablesTicket');

        // detail REST
        Route::get('/tickets/detail/{numero}', 'DetailTicket')->name('tickets.detail');
        Route::get('/tickets/atenciones/{idTicket}', 'DetailAtencionesTicket')->name('tickets.DetailAtencionesTicket');
        // Route::get('/tickets/detail/{numero}', 'DetailTicket')->name('tickets.detail');
        // Route::group(['middleware' => ['role:Administrador']], function () {
        //     Route::get('/tickets/pagination', 'indexPagination')->name('tickets.pagination');
        //     Route::get('/tickets/detail/{numero}', 'DetailTicket')->name('tickets.detail');

        // });
    });

    Route::controller(ReportsController::class)->group(function () {
        Route::group(['middleware' => ['role:Administrador']], function () {
            Route::get('/reports/pagination', 'indexPagination')->name('reports.pagination');
            // Route::get('/tickets/detail/{numero}', 'DetailTicket')->name('tickets.detail');

        });
    });
    //   Dashboard Monitoreo
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/companies-nodo', 'getCompaniaNodo')->name('getCompaniaNodo.index');
        Route::get('/companies-disponibilidad-nodo', 'getDisponibilidadNodo')->name('getDisponibilidadNodo.index');
        Route::get('/verificacion-conexion-nodos', 'verificarConexion')->name('verificarConexion.index');
        Route::get('/verificacion-conexion-monitoreos', 'getVerificacionMonitoreo')->name('getVerificacionMonitoreo.index');
        Route::get('/monitoreo-dashboard', 'getListMonitoreo')->name('getListMonitoreo.index');
        Route::get('/monitoreo-revision', 'getListMonitoreoRevision')->name('getListMonitoreoRevision.index');
        Route::get('/monitoreo-revision-correo', 'getListMonitoreoRevisionCorreo')->name('getListMonitoreoRevisionCorreo.index');

        // Route::group(['middleware' => ['role:Administrador']], function () {
        //     Route::get('/reports/pagination', 'indexPagination')->name('reports.pagination');
        //     // Route::get('/tickets/detail/{numero}', 'DetailTicket')->name('tickets.detail');

        // });
    });
});

// Facturación Electronica
Route::controller(NotasCreditoController::class)->group(function () {
    // Route::group(['middleware' => ['role:Administrador']], function () {
    Route::get('/facturacion/notas-credito/pagination', 'indexPagination')->name('nc.pagination');
    Route::get('/facturacion/search-factura/', 'searchFactura')->name('nc.search');
    Route::post('/facturacion/nota-credito/enviar', 'enviarNotaCredito')->name('nc.post');
    Route::get('/facturacion/nota-credito/estados', 'EstadoPagoAll')->name('nc.estados');
    Route::put('/facturacion/nota-credito/enviar-estado', 'EnviarEstadoNC')->name('nc.estados');




    // });
});

Route::controller(NotasDebitoController::class)->group(function () {
    // Route::group(['middleware' => ['role:Administrador']], function () {
    Route::get('/facturacion/notas-debito/pagination', 'indexPagination')->name('nd.pagination');
    Route::get('/facturacion/search-factura-debito/', 'searchFacturaNd')->name('nd.search');
    Route::post('/facturacion/nota-debito/enviar', 'enviarNotaDebito')->name('nd.post');



    // });
});

Route::controller(FacturasElectronicasController::class)->group(function () {
    // Route::group(['middleware' => ['role:Administrador']], function () {
    Route::get('/facturacion/facturas/pagination', 'indexPagination')->name('fe.pagination');
    Route::get('/facturacion/factura/{id}/detraccion', 'GetDetraccion')->name('fe.GetDetraccion');
    Route::get('/facturacion/factura/{id}/cuotas', 'GetCuotas')->name('fe.GetCuotas');
    Route::get('/facturacion/lista-clientes', 'GetClientes')->name('fe.GetClientes');
    Route::post('/facturacion/factura/enviar', 'enviarFactura')->name('enviarFactura.post');
    Route::get('/facturacion/metodos-pago', 'MetodosPagoAll')->name('FE.metodos');
    Route::get('/facturacion/tipos-servicios', 'ServiciosTipoAll')->name('FE.ServiciosTipoAll');
    Route::get('/facturacion/servicios', 'ServiciosAll')->name('FE.ServiciosAll');
    Route::get('/facturacion/reporte', 'exportFacturas')->name('FE.exportFacturas');

    // Route::get('/facturacion/search-factura/', 'searchFactura')->name('nc.search');
    // Route::post('/facturacion/nota-credito/enviar', 'enviarNotaCredito')->name('nc.post');
    // Route::get('/facturacion/nota-credito/estados', 'EstadoPagoAll')->name('nc.estados');
    // Route::put('/facturacion/nota-credito/enviar-estado', 'EnviarEstadoNC')->name('nc.estados');



    // });
});

Route::controller(MonitoreoController::class)->group(function () {
    Route::get('/monitoreo-servicios', 'getListMonitoreoServicios')->name('getListMonitoreoServicios');
     Route::get('/monitoreo-conectividad', 'getListMonitoreoConectividad')->name('getListMonitoreoConectividad');
     Route::get('/lista-equipos', 'GetEquipos')->name('getEquipos');
});

// Grupo de rutas de autenticación
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::get('/login', [AuthController::class, 'login'])->name('login'); // Cambié GET a POST
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
});
