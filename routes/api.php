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
use App\Http\Controllers\PlanMantenimientoController;

// Auth
Route::post('/login', [AuthController::class, 'login']);
// Public file proxy for assignment files (no auth required) to avoid CORS preflight issues
Route::get('/files/asignaciones/{filename}', [\App\Http\Controllers\FileController::class, 'serveAsignacion']);
// Public file proxy for maintenance files (no auth required)
Route::get('/files/mantenimientos/{filename}', [\App\Http\Controllers\FileController::class, 'showMantenimiento']);
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
    // (Proxy route defined publicly above)
    Route::post('/equipos/{id}/pre-baja', [EquipoController::class, 'marcarParaBaja']);

    // Email settings
    Route::get('/settings/email', [\App\Http\Controllers\EmailSettingsController::class, 'get']);
    Route::post('/settings/email', [\App\Http\Controllers\EmailSettingsController::class, 'store']);
    Route::post('/settings/email/test', [\App\Http\Controllers\EmailSettingsController::class, 'test']);

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
    // Endpoints de planificación de mantenimiento (URLs en español)
    Route::get('/planes-mantenimiento', [PlanMantenimientoController::class, 'index']);
    Route::get('/planes-mantenimiento/{id}', [PlanMantenimientoController::class, 'show']);
    Route::post('/planes-mantenimiento', [PlanMantenimientoController::class, 'store']);
    Route::post('/planes-mantenimiento/propuesta', [PlanMantenimientoController::class, 'generateProposal']);
    Route::put('/detalles-planes-mantenimiento/{id}/mes', [PlanMantenimientoController::class, 'updateDetailMonth']);
    Route::post('/detalles-planes-mantenimiento/{id}/iniciar', [PlanMantenimientoController::class, 'startFromPlan']);
    Route::post('/ejecuciones-mantenimiento/{id}', [PlanMantenimientoController::class, 'registerExecution']);
    Route::get('/ejecuciones-mantenimiento/{id}', [PlanMantenimientoController::class, 'getExecutions']);
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
