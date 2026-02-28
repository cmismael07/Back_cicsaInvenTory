<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

header('Content-Type: text/html; charset=utf-8');

$providedKey = trim((string) ($_GET['key'] ?? $_GET['token'] ?? ''));
$expectedKey = trim((string) env('MAINTENANCE_WEB_KEY', ''));

if (empty($expectedKey)) {
    http_response_code(500);
    echo '<h3>Error</h3><p>Define MAINTENANCE_WEB_KEY en .env antes de usar este script.</p>';
    exit;
}

if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    echo '<h3>403</h3><p>Key inválida.</p>';
    echo '<p>Verifica que MAINTENANCE_WEB_KEY esté definida en .env, sin espacios ni comillas extras, y que la URL use el mismo valor.</p>';
    exit;
}

$action = $_GET['action'] ?? 'diag';
$ciudadId = isset($_GET['ciudad_id']) ? (int) $_GET['ciudad_id'] : null;

$result = [
    'action' => $action,
    'timestamp' => date('c'),
    'commands' => [],
    'diagnostics' => [],
    'errors' => [],
];

try {
    if ($action === 'clear') {
        $kernel->call('config:clear');
        $result['commands'][] = 'config:clear';

        $kernel->call('cache:clear');
        $result['commands'][] = 'cache:clear';

        $kernel->call('route:clear');
        $result['commands'][] = 'route:clear';

        $kernel->call('view:clear');
        $result['commands'][] = 'view:clear';

        $kernel->call('optimize:clear');
        $result['commands'][] = 'optimize:clear';
    }

    if ($action === 'migrate') {
        $kernel->call('migrate', ['--force' => true]);
        $result['commands'][] = 'migrate --force';
    }
} catch (\Throwable $e) {
    $result['errors'][] = 'Error ejecutando comandos: ' . $e->getMessage();
}

try {
    $tipoTable = null;
    if (Schema::hasTable('tipo_equipos')) {
        $tipoTable = 'tipo_equipos';
    } elseif (Schema::hasTable('tipos_equipos')) {
        $tipoTable = 'tipos_equipos';
    }

    $diag = [];
    $diag['db_name'] = DB::connection()->getDatabaseName();
    $diag['tables'] = [
        'equipos' => Schema::hasTable('equipos'),
        'ubicaciones' => Schema::hasTable('ubicaciones'),
        'tipo_equipos' => Schema::hasTable('tipo_equipos'),
        'tipos_equipos' => Schema::hasTable('tipos_equipos'),
    ];
    $diag['columnas'] = [
        'ubicaciones.ciudad_id' => Schema::hasTable('ubicaciones') ? Schema::hasColumn('ubicaciones', 'ciudad_id') : false,
        'tipo_equipos.considerar_recambio' => Schema::hasTable('tipo_equipos') ? Schema::hasColumn('tipo_equipos', 'considerar_recambio') : false,
    ];

    if (Schema::hasTable('equipos')) {
        $diag['totales'] = [
            'equipos' => DB::table('equipos')->count(),
            'equipos_no_baja' => DB::table('equipos')
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhereRaw('LOWER(estado) NOT LIKE ?', ['%baja%']);
                })
                ->count(),
        ];
    }

    if ($tipoTable && Schema::hasTable('equipos')) {
        $diag['integridad_relaciones'] = [
            'equipos_sin_tipo_valido' => DB::table('equipos as e')
                ->leftJoin($tipoTable . ' as t', 't.id', '=', 'e.tipo_equipo_id')
                ->whereNull('t.id')
                ->count(),
        ];

        if (Schema::hasColumn($tipoTable, 'considerar_recambio')) {
            $baseConsiderados = DB::table('equipos as e')
                ->join($tipoTable . ' as t', 't.id', '=', 'e.tipo_equipo_id')
                ->where(function ($q) {
                    $q->whereNull('e.estado')->orWhereRaw('LOWER(e.estado) NOT LIKE ?', ['%baja%']);
                })
                ->where('t.considerar_recambio', 1)
                ->count();

            $elegiblesRecambio = DB::table('equipos as e')
                ->join($tipoTable . ' as t', 't.id', '=', 'e.tipo_equipo_id')
                ->where(function ($q) {
                    $q->whereNull('e.estado')->orWhereRaw('LOWER(e.estado) NOT LIKE ?', ['%baja%']);
                })
                ->where('t.considerar_recambio', 1)
                ->whereNull('e.plan_recambio_id')
                ->where(function ($q) {
                    $q->whereNull('e.pi_recambio')->orWhere('e.pi_recambio', '');
                })
                ->whereNotNull('e.fecha_compra')
                ->whereRaw('TIMESTAMPDIFF(YEAR, e.fecha_compra, CURDATE()) >= 4')
                ->count();

            $target = $baseConsiderados > 0 ? max(1, (int) ceil($baseConsiderados * 0.2)) : 0;

            $diag['recambio'] = [
                'base_total_considerados' => $baseConsiderados,
                'target_20_percent' => $target,
                'elegibles' => $elegiblesRecambio,
                'retornables' => min($target, $elegiblesRecambio),
            ];
        }
    }

    if (Schema::hasTable('equipos') && Schema::hasTable('ubicaciones')) {
        $diag['mantenimiento'] = [
            'equipos_sin_ubicacion' => DB::table('equipos')->whereNull('ubicacion_id')->count(),
            'ubicaciones_sin_ciudad' => Schema::hasColumn('ubicaciones', 'ciudad_id')
                ? DB::table('ubicaciones')->whereNull('ciudad_id')->count()
                : null,
        ];

        if (!empty($ciudadId) && Schema::hasColumn('ubicaciones', 'ciudad_id')) {
            $diag['mantenimiento']['equipos_para_ciudad_id'] = DB::table('equipos as e')
                ->join('ubicaciones as u', 'u.id', '=', 'e.ubicacion_id')
                ->where('u.ciudad_id', $ciudadId)
                ->count();
        }
    }

    $result['diagnostics'] = $diag;
} catch (\Throwable $e) {
    $result['errors'][] = 'Error en diagnóstico: ' . $e->getMessage();
}

echo '<h2>Diagnóstico Backend Hosting</h2>';
echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
echo '<p>Cuando termines, elimina este archivo por seguridad.</p>';
