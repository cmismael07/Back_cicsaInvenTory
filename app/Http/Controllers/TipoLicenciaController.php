<?php

namespace App\Http\Controllers;

use App\Models\TipoLicencia;
use App\Models\Licencia; // Importar el modelo Licencia
use Illuminate\Http\Request;
use App\Http\Resources\TipoLicenciaResource;
use Illuminate\Support\Facades\DB; // Importar DB para usar transacciones
use Illuminate\Support\Str;

class TipoLicenciaController extends Controller
{
    public function index()
    {
        $tipos = TipoLicencia::withCount([
                    'licencias as total', // total de licencias
                    'licencias as disponibles' => function ($query) { // licencias disponibles
                        $query->whereNull('user_id');
                    }
                ])->get();

        return TipoLicenciaResource::collection($tipos);    
    }

   public function store(Request $request)
    {
        $payload = $request->validate([
            'nombre' => 'required|string|max:255',
            'proveedor' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        $tipoLicencia = DB::transaction(function () use ($payload) {
            // Determine intended stock explicitly from payload to avoid relying on defaults
            $cantidad = isset($payload['stock']) ? (int) $payload['stock'] : 0;

            // Create the tipo and ensure stock field is set to the requested amount
            $tipo = TipoLicencia::create(array_merge($payload, ['stock' => $cantidad]));

            if ($cantidad > 0) {
                $licenciasParaCrear = [];
                for ($i = 0; $i < $cantidad; $i++) {
                    $licenciasParaCrear[] = [
                        'tipo_licencia_id' => $tipo->id,
                        'clave' => 'LIC-' . strtoupper(Str::random(10)) . '-' . uniqid(),
                        'fecha_vencimiento' => $payload['fecha_vencimiento'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Licencia::insert($licenciasParaCrear);
            }

            return $tipo;
        });

        $tipoLicencia->loadCount([
            'licencias as total',
            'licencias as disponibles' => fn($q) => $q->whereNull('user_id')
        ]);

        // Build a plain JSON payload that includes the created resource fields
        // (so frontends that expect `res.json().id` will work) and also
        // return a Location header for convenience.
        $resourceArray = (new TipoLicenciaResource($tipoLicencia))->toArray(request());
        $location = url('/api/tipos-licencia/' . $tipoLicencia->id);

        return response()->json(array_merge($resourceArray, ['location' => $location]), 201)
            ->header('Location', $location);
    }

   public function addStock(Request $request, $id)
    {
        // Defensive: validate id to avoid routes called with 'undefined'
        if (!is_numeric($id) || intval($id) <= 0) {
            return response()->json(['message' => 'Invalid TipoLicencia id provided'], 400);
        }

        $payload = $request->validate([
            'cantidad' => 'required|integer|min:1',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        $tipoLicencia = TipoLicencia::findOrFail($id);
        $cantidadAAgregar = $payload['cantidad'];

        // Actualizamos el stock total en el tipo
        $tipoLicencia->stock += $cantidadAAgregar;
        $tipoLicencia->save();

        // Creamos las nuevas licencias individuales con su fecha de vencimiento
        $licenciasParaCrear = [];
        for ($i = 0; $i < $cantidadAAgregar; $i++) {
            $licenciasParaCrear[] = [
                'tipo_licencia_id' => $tipoLicencia->id,
                'clave' => 'LIC-' . strtoupper(Str::random(10)) . '-' . uniqid(), // *** Generar clave única ***
                'fecha_vencimiento' => $payload['fecha_vencimiento'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Licencia::insert($licenciasParaCrear);

        $tipoLicencia->loadCount([
            'licencias as total',
            'licencias as disponibles' => fn($q) => $q->whereNull('user_id')
        ]);

        return new TipoLicenciaResource($tipoLicencia);
    }

    
        public function update(Request $request, $id)
    {
        $tipoLicencia = TipoLicencia::findOrFail($id);
        $payload = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'proveedor' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            // El stock se ajusta solo desde addStock o en la creación
            // Aquí no permitimos reducir el stock por debajo de las asignaciones actuales.
            'stock' => 'sometimes|integer|min:' . $tipoLicencia->licencias()->count(),
        ]);
        $tipoLicencia->update($payload);
        
        // Si el stock fue cambiado, necesitamos añadir/eliminar licencias.
        // Esto es una mejora: en el `update`, si el stock se incrementa, añadimos licencias.
        // Si se disminuye, idealmente solo se podrían eliminar las no asignadas.
        if (isset($payload['stock']) && $payload['stock'] > $tipoLicencia->licencias()->count()) {
            $diff = $payload['stock'] - $tipoLicencia->licencias()->count();
            $licenciasParaCrear = [];
            for ($i = 0; $i < $diff; $i++) {
                $licenciasParaCrear[] = [
                    'tipo_licencia_id' => $tipoLicencia->id,
                    'clave' => 'LIC-' . strtoupper(Str::random(10)) . '-' . uniqid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($licenciasParaCrear)) Licencia::insert($licenciasParaCrear);
        } elseif (isset($payload['stock']) && $payload['stock'] < $tipoLicencia->licencias()->count()) {
             // Reducir stock: eliminamos licencias no asignadas hasta el nuevo stock
             $diff = $tipoLicencia->licencias()->count() - $payload['stock'];
             $tipoLicencia->licencias()->whereNull('user_id')->limit($diff)->delete();
        }

        $tipoLicencia->loadCount([
            'licencias as total',
            'licencias as disponibles' => fn($q) => $q->whereNull('user_id')
        ]);
        return new TipoLicenciaResource($tipoLicencia);
    }

    public function show($id)
    {
        // Defensive: if id is not a positive integer, return 400 to avoid model NotFoundExceptions with 'undefined'
        if (!is_numeric($id) || intval($id) <= 0) {
            return response()->json(['message' => 'Invalid TipoLicencia id provided'], 400);
        }
        return new TipoLicenciaResource(TipoLicencia::with('licencias')->findOrFail($id));
    }


    public function destroy($id)
    {
        // Se eliminarán las licencias en cascada si la BBDD está bien configurada
        TipoLicencia::destroy($id);
        return response()->noContent();
    }
}