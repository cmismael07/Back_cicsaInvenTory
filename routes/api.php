<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\TipoLicenciaController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\MigrationController;

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Rutas Protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Organización
    Route::apiResource('departamentos', DepartamentoController::class);
    Route::apiResource('puestos', PuestoController::class);

    // Usuarios
    Route::apiResource('users', UserController::class);

    // Equipos
    Route::apiResource('tipos-equipo', TipoEquipoController::class);
    Route::apiResource('equipos', EquipoController::class);

    // Acciones Específicas de Equipos
    Route::post('/equipos/{id}/asignar', [EquipoController::class, 'asignar']);
    Route::post('/equipos/{id}/recepcionar', [EquipoController::class, 'recepcionar']);
    Route::post('/equipos/{id}/baja', [EquipoController::class, 'darBaja']);
    Route::post('/equipos/{id}/mantenimiento', [EquipoController::class, 'enviarMantenimiento']);
    Route::post('/equipos/{id}/finalizar-mantenimiento', [EquipoController::class, 'finalizarMantenimiento']);
    Route::post('/asignaciones/{id}/archivo', [EquipoController::class, 'subirArchivoAsignacion']);
    Route::post('/equipos/{id}/pre-baja', [EquipoController::class, 'marcarParaBaja']);

    // Licencias
    Route::apiResource('tipos-licencia', TipoLicenciaController::class);
    Route::apiResource('licencias', LicenciaController::class);
    Route::post('/tipos-licencia/{id}/add-stock', [TipoLicenciaController::class, 'addStock']);
    // Route::post('/licencias/stock', [LicenciaController::class, 'addStock']);
    Route::post('/licencias/{id}/asignar', [LicenciaController::class, 'asignar']);
    Route::post('/licencias/{id}/liberar', [LicenciaController::class, 'liberar']);

    // Reportes y Stats
    Route::get('/stats/dashboard', [ReportController::class, 'dashboardStats']);
    Route::get('/stats/garantias', [ReportController::class, 'warrantyReport']);
    Route::get('/stats/reemplazos', [ReportController::class, 'replacementCandidates']);
    Route::get('/historial/movimientos', [ReportController::class, 'movementHistory']);
    Route::get('/historial/asignaciones', [ReportController::class, 'assignmentHistory']);
    Route::get('/historial/mantenimientos', [ReportController::class, 'maintenanceHistory']);
    Route::get('/notificaciones', [NotificationController::class, 'index']);

    // Catálogos auxiliares usados por frontend
    Route::get('/ciudades', [CatalogController::class, 'ciudades']);
    Route::post('/ciudades', [CatalogController::class, 'storeCiudad']);
    Route::put('/ciudades/{id}', [CatalogController::class, 'updateCiudad']);
    Route::delete('/ciudades/{id}', [CatalogController::class, 'deleteCiudad']);

    Route::get('/paises', [CatalogController::class, 'paises']);
    Route::post('/paises', [CatalogController::class, 'storePais']);
    Route::put('/paises/{id}', [CatalogController::class, 'updatePais']);
    Route::delete('/paises/{id}', [CatalogController::class, 'deletePais']);

    // Bulk migration endpoints used by frontend migration module
    Route::post('/migrations/equipos', [MigrationController::class, 'equipos']);
    Route::post('/migrations/usuarios', [MigrationController::class, 'usuarios']);
    Route::post('/migrations/licencias', [MigrationController::class, 'licencias']);
    Route::post('/migrations/departamentos', [MigrationController::class, 'departamentos']);
    Route::post('/migrations/puestos', [MigrationController::class, 'puestos']);
    Route::post('/migrations/asignaciones', [MigrationController::class, 'asignaciones']);
});
