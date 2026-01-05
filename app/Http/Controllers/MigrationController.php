<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Departamento;
use App\Models\Ubicacion;
use App\Models\TipoEquipo;
use App\Models\TipoLicencia;
use App\Models\Equipo;
use App\Models\User;

class MigrationController extends Controller
{
    private function parseExcelDate(mixed $value)
    {
        // If null/empty
        if ($value === null || $value === '') return null;
        // If numeric, treat as Excel serial date (days since 1899-12-30)
        if (is_numeric($value)) {
            // Excel stores dates as number of days since 1899-12-30 (with the 1900 leap bug);
            // common conversion uses 25569 offset to Unix epoch.
            try {
                $serial = (float) $value;
                $timestamp = ($serial - 25569) * 86400;
                $date = gmdate('Y-m-d', intval($timestamp));
                return $date;
            } catch (\Throwable $e) {
                return null;
            }
        }
        // If already a string date, try to normalize
        $ts = strtotime((string) $value);
        if ($ts === false) return null;
        return date('Y-m-d', $ts);
    }
    /**
     * Normalize common boolean-like values from Excel/CSV to PHP bool.
     * Accepts: 'si', 'sí', 's', '1', 'true', 'yes' (case-insensitive), and numeric 1.
     */
    private function parseBoolean(mixed $value): bool
    {
        if ($value === null || $value === '') return false;
        if (is_bool($value)) return $value;
        // numeric 1
        if (is_numeric($value)) return intval($value) === 1;
        $s = (string) $value;
        $s = trim(mb_strtolower($s));
        // remove common accents
        $map = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'];
        $s = strtr($s, $map);
        $trueVals = ['si', 's', '1', 'true', 't', 'yes', 'y'];
        return in_array($s, $trueVals, true);
    }
    public function equipos(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::equipos received rows count: ' . count($rows));
        Log::info('MigrationController::equipos sample: ' . json_encode(array_slice($rows, 0, 2)));
        $count = 0;
        foreach ($rows as $row) {
            try {
                // Normalize keys (lowercase, replace non-alnum with underscore)
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }

                $codigo = $norm['codigo_activo'] ?? $norm['codigoactivo'] ?? $norm['codigo'] ?? $norm['codigo_act'] ?? null;
                if (! $codigo) continue;

                $tipoNombre = $norm['tipo_equipo'] ?? $norm['tipoequipo'] ?? $norm['tipo'] ?? $norm['tipo_equipo_name'] ?? null;
                $tipoId = null;
                if ($tipoNombre) {
                    $tipo = DB::table('tipos_equipos')->whereRaw('LOWER(nombre) = ?', [Str::lower($tipoNombre)])->first();
                    if (! $tipo) {
                        // The `tipos_equipos` table does not have a `frecuencia_anual` column
                        // Insert only the columns that exist to avoid SQL errors.
                        $tipoId = DB::table('tipos_equipos')->insertGetId([
                            'nombre' => $tipoNombre,
                            'descripcion' => null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $tipoId = $tipo->id;
                    }
                } else {
                    // fallback to first tipo
                    $tipo = DB::table('tipos_equipos')->first();
                    $tipoId = $tipo?->id;
                }

                // Parse fecha_compra (accept Excel serials or strings)
                $fechaCompra = null;
                if (isset($norm['fecha_compra']) && $norm['fecha_compra'] !== '') {
                    $fechaCompra = $this->parseExcelDate($norm['fecha_compra']);
                    if ($fechaCompra) Log::info('MigrationController::equipos parsed fecha_compra: ' . $fechaCompra);
                } elseif (isset($norm['fecha']) && $norm['fecha'] !== '') {
                    $fechaCompra = $this->parseExcelDate($norm['fecha']);
                    if ($fechaCompra) Log::info('MigrationController::equipos parsed fecha: ' . $fechaCompra);
                }

                $insert = [
                    'tipo_equipo_id' => $tipoId,
                    'codigo_activo' => $codigo,
                    'marca' => $norm['marca'] ?? $norm['brand'] ?? null,
                    'modelo' => $norm['modelo'] ?? $norm['model'] ?? null,
                    'serial' => $norm['serie'] ?? $norm['serial'] ?? $norm['numero_serie'] ?? null,
                    'fecha_compra' => $fechaCompra,
                    'valor_compra' => isset($norm['valor']) ? floatval(str_replace([',',' '], ['','.'], $norm['valor']) ) : null,
                    // Defaults for migrated equipos: estado => 'disponible', ubicacion_id => 1
                    'estado' => 'disponible',
                    'ubicacion_id' => 1,
                    'created_at' => now(), 'updated_at' => now()
                ];

                Log::info('MigrationController::equipos prepared insert: ' . json_encode($insert));
                DB::table('equipos')->insert($insert);
                $count++;
            } catch (\Throwable $e) {
                Log::error('MigrationController::equipos error: ' . $e->getMessage());
                Log::error($e->getTraceAsString());
                // skip row on error
                continue;
            }
        }
        return response()->json(['count' => $count]);
    }

    public function usuarios(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::usuarios received rows count: ' . count($rows));
        Log::info('MigrationController::usuarios sample: ' . json_encode(array_slice($rows, 0, 2)));
        $count = 0;
        foreach ($rows as $row) {
            try {
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }
                $email = $norm['email'] ?? $norm['correo'] ?? $norm['e_mail'] ?? null;
                if (! $email) continue;
                $nombre = $norm['nombres'] ?? $norm['nombre'] ?? '';
                $apellidos = $norm['apellidos'] ?? $norm['apellido'] ?? '';
                $usuario = $norm['usuario'] ?? $norm['nombre_usuario'] ?? null;

                // Map to actual users table columns: name, username, email, password, activo, numero_empleado
                $insert = [
                    'name' => trim($nombre . ' ' . $apellidos),
                    'username' => $usuario ?? explode('@', $email)[0],
                    'email' => $email,
                    'password' => bcrypt('123'),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (! empty($norm['numero_empleado'])) $insert['numero_empleado'] = (string)$norm['numero_empleado'];

                // Skip if email already exists
                $exists = DB::table('users')->whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists();
                if ($exists) {
                    Log::info('MigrationController::usuarios skip existing email: ' . $email);
                    continue;
                }

                Log::info('MigrationController::usuarios prepared insert: ' . json_encode($insert));
                DB::table('users')->insert($insert);
                $count++;
            } catch (\Throwable $e) { continue; }
        }
        return response()->json(['count' => $count]);
    }

    public function licencias(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::licencias received rows count: ' . count($rows));
        Log::info('MigrationController::licencias sample: ' . json_encode(array_slice($rows, 0, 2)));
        $count = 0;
        foreach ($rows as $row) {
            try {
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }
                $tipoNombre = $norm['tipo_software'] ?? $norm['tipo'] ?? $norm['tipo_licencia'] ?? null;
                $tipoId = null;
                if ($tipoNombre) {
                    $tipo = DB::table('tipos_licencias')->whereRaw('LOWER(nombre) = ?', [Str::lower($tipoNombre)])->first();
                    if (! $tipo) {
                        $tipoId = DB::table('tipos_licencias')->insertGetId(['nombre' => $tipoNombre, 'proveedor' => $norm['proveedor'] ?? null, 'descripcion' => null, 'created_at' => now(), 'updated_at' => now()]);
                    } else {
                        $tipoId = $tipo->id;
                    }
                }
                if (! $tipoId) continue;

                $fechaCompraLic = null;
                if (isset($norm['fecha_compra']) && $norm['fecha_compra'] !== '') {
                    $fechaCompraLic = $this->parseExcelDate($norm['fecha_compra']);
                    if ($fechaCompraLic) Log::info('MigrationController::licencias parsed fecha_compra: ' . $fechaCompraLic);
                }
                $fechaVenc = null;
                if (isset($norm['fecha_vencimiento']) && $norm['fecha_vencimiento'] !== '') {
                    $fechaVenc = $this->parseExcelDate($norm['fecha_vencimiento']);
                    if ($fechaVenc) Log::info('MigrationController::licencias parsed fecha_vencimiento: ' . $fechaVenc);
                }

                $insert = [
                    'tipo_id' => $tipoId,
                    'clave' => $norm['clave'] ?? null,
                    'fecha_compra' => $fechaCompraLic ?? now(),
                    'fecha_vencimiento' => $fechaVenc,
                    'created_at' => now(), 'updated_at' => now()
                ];
                DB::table('licencias')->insert($insert);
                $count++;
            } catch (\Throwable $e) { continue; }
        }
        return response()->json(['count' => $count]);
    }

    public function departamentos(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::departamentos received rows count: ' . count($rows));
        Log::info('MigrationController::departamentos sample: ' . json_encode(array_slice($rows, 0, 2)));
        $count = 0;
        foreach ($rows as $row) {
            try {
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }
                $nombre = $norm['nombre_departamento'] ?? $norm['nombre'] ?? $norm['department'] ?? null;
                if (! $nombre) continue;
                $ciudadName = $norm['ciudad'] ?? $norm['city'] ?? null;
                $ciudadId = null;
                if ($ciudadName) {
                    $ci = DB::table('ciudades')->whereRaw('LOWER(nombre) = ?', [Str::lower($ciudadName)])->first();
                    $ciudadId = $ci?->id;
                }
                $esBodega = false;
                // Try to find any normalized key that contains 'es_bodega' (handles 'es_bodega_si_no', 'es_bodega', etc.)
                $esKey = null;
                foreach (array_keys($norm) as $k) {
                    if (strpos($k, 'es_bodega') !== false) { $esKey = $k; break; }
                }
                if ($esKey !== null) {
                    $esBodega = $this->parseBoolean($norm[$esKey]);
                    Log::info('MigrationController::departamentos parsed es_bodega key: ' . $esKey . ' value: ' . json_encode($norm[$esKey]) . ' parsed: ' . ($esBodega ? 'true' : 'false'));
                }

                $dataInsert = ['nombre' => $nombre, 'es_bodega' => $esBodega, 'ciudad_id' => $ciudadId];
                Log::info('MigrationController::departamentos prepared insert: ' . json_encode($dataInsert));
                $d = Departamento::create($dataInsert);

                // If departamento is a bodega, create or find an auto-created Ubicacion and link it
                if ($esBodega) {
                    $marker = "AUTO_BODEGA_DEPARTAMENTO_{$d->id}";
                    $exists = Ubicacion::where('descripcion', 'like', "%{$marker}%")->first();
                        if (! $exists) {
                        try {
                            $u = Ubicacion::create([
                                'nombre' => $d->nombre,
                                'descripcion' => "Funciona como bodega IT | {$marker}",
                                'ciudad_id' => $d->ciudad_id ?? null,
                            ]);
                            $d->bodega_ubicacion_id = $u->id;
                            $d->save();
                            Log::info('MigrationController::departamentos created ubicacion id: ' . $u->id);
                        } catch (\Throwable $e) {
                            Log::error('MigrationController::departamentos error creating ubicacion: ' . $e->getMessage());
                        }
                    } else {
                        if (empty($d->bodega_ubicacion_id)) {
                            $d->bodega_ubicacion_id = $exists->id;
                            $d->save();
                        }
                    }
                }
                $count++;
            } catch (\Throwable $e) { continue; }
        }
        return response()->json(['count' => $count]);
    }

    public function puestos(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::puestos received rows count: ' . count($rows));
        $count = 0;
        foreach ($rows as $row) {
            try {
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }
                $nombre = $norm['nombre_cargo'] ?? $norm['nombre'] ?? $norm['puesto'] ?? null;
                if (! $nombre) continue;
                DB::table('puestos')->insert(['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()]);
                $count++;
            } catch (\Throwable $e) { continue; }
        }
        return response()->json(['count' => $count]);
    }

    public function asignaciones(Request $request)
    {
        $rows = $request->all();
        Log::info('MigrationController::asignaciones received rows count: ' . count($rows));
        $count = 0;
        foreach ($rows as $row) {
            try {
                $norm = [];
                foreach ($row as $k => $v) {
                    $nk = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string)$k)));
                    $norm[$nk] = $v;
                }
                $codigo = $norm['codigo_activo'] ?? $norm['codigo'] ?? $norm['codigo_act'] ?? $norm['codigoactivo'] ?? null;
                // Accept several column name variants for the user identifier (email or username)
                $correo = $norm['correo_usuario'] ?? $norm['email'] ?? $norm['correo'] ?? $norm['usuario'] ?? $norm['usuario_responsable'] ?? $norm['nombre_usuario'] ?? null;

                // Log the normalized row for debugging when uploads return 0
                Log::info('MigrationController::asignaciones normalized row: ' . json_encode($norm));

                if (! $codigo || ! $correo) {
                    Log::info('MigrationController::asignaciones skipping row - missing codigo or correo/usuario. codigo=' . ($codigo ?? 'null') . ' correo/usuario=' . ($correo ?? 'null'));
                    continue;
                }

                $correoNorm = Str::lower(trim((string)$correo));
                // Try common email column names: `email`, `correo`. Also try username as fallback.
                $user = DB::table('users')->whereRaw('LOWER(email) = ?', [$correoNorm])->first();
                if (! $user) {
                    $user = DB::table('users')->whereRaw('LOWER(correo) = ?', [$correoNorm])->first();
                }
                if (! $user) {
                    $user = DB::table('users')->whereRaw('LOWER(username) = ?', [$correoNorm])->first();
                }
                Log::info('MigrationController::asignaciones lookup user for: ' . $correoNorm . ' found_id: ' . ($user?->id ?? 'null'));
                $equipo = DB::table('equipos')->where('codigo_activo', $codigo)->first();
                if ($user && $equipo) {
                    DB::table('equipos')->where('id', $equipo->id)->update(['responsable_id' => $user->id, 'estado' => 'activo', 'updated_at' => now()]);
                    // prepare historial_movimientos entry
                    $usuarioResponsable = $user->name ?? $user->nombre_completo ?? $user->email ?? $user->correo ?? $user->username ?? ($user->id ?? 'user_'.$codigo);
                    // Map to actual historial_movimientos table columns: equipo_id, nota, responsable_id, fecha
                    $hist = [
                        'equipo_id' => $equipo->id,
                        'nota' => 'Asignado por migración',
                        'tipo_accion' => 'ASIGNACION',
                        'responsable_id' => $user->id ?? null,
                        'fecha' => isset($norm['fecha_asignacion']) && $norm['fecha_asignacion'] ? $this->parseExcelDate($norm['fecha_asignacion']) . ' 00:00:00' : now()->toDateTimeString(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    Log::info('MigrationController::asignaciones prepared historial: ' . json_encode($hist));
                    try {
                        DB::table('historial_movimientos')->insert($hist);
                        Log::info('MigrationController::asignaciones inserted historial for equipo_id: ' . $equipo->id);
                    } catch (\Throwable $e) {
                        Log::error('MigrationController::asignaciones error inserting historial: ' . $e->getMessage());
                        Log::error($e->getTraceAsString());
                        // don't increment count if historial insert failed
                        continue;
                    }
                    $count++;
                } else {
                    if (! $user) Log::info('MigrationController::asignaciones skipping row - user not found for: ' . $correoNorm);
                    if (! $equipo) Log::info('MigrationController::asignaciones skipping row - equipo not found for codigo: ' . $codigo);
                }
            } catch (\Throwable $e) { continue; }
        }
        return response()->json(['count' => $count]);
    }
}
