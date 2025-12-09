<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    // Devuelve lista de ciudades; si no existe la tabla, retorna array vacío
    public function ciudades(Request $request)
    {
        try {
            if (! Schema::hasTable('ciudades')) {
                return response()->json([], 200);
            }
            $rows = DB::table('ciudades')->select('id', 'nombre', 'pais_id', 'abreviatura')->get();
            return response()->json($rows, 200);
        } catch (\Throwable $ex) {
            return response()->json([], 200);
        }
    }

    // Devuelve lista de paises; si no existe la tabla, retorna array vacío
    public function paises(Request $request)
    {
        try {
            if (! Schema::hasTable('paises')) {
                return response()->json([], 200);
            }
            $rows = DB::table('paises')->select('id', 'nombre', 'abreviatura')->get();
            return response()->json($rows, 200);
        } catch (\Throwable $ex) {
            return response()->json([], 200);
        }
    }

    // Create a new pais
    public function storePais(Request $request)
    {
        // Normalize nombre if frontend sent object/number
        $input = $request->all();
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            } elseif (is_numeric($input['nombre'])) {
                $input['nombre'] = (string) $input['nombre'];
            }
        }
        // Normalize abreviatura if nested/object/number
        if (array_key_exists('abreviatura', $input)) {
            if (is_array($input['abreviatura']) && isset($input['abreviatura']['abreviatura'])) {
                $input['abreviatura'] = $input['abreviatura']['abreviatura'];
            } elseif (is_array($input['abreviatura']) && isset($input['abreviatura']['code'])) {
                $input['abreviatura'] = $input['abreviatura']['code'];
            } elseif (is_object($input['abreviatura'])) {
                $obj = (array) $input['abreviatura'];
                if (isset($obj['abreviatura'])) $input['abreviatura'] = $obj['abreviatura'];
                elseif (isset($obj['code'])) $input['abreviatura'] = $obj['code'];
            } elseif (is_numeric($input['abreviatura'])) {
                $input['abreviatura'] = (string) $input['abreviatura'];
            }
        }

        $request->merge($input);

        $data = $request->validate([
            'nombre' => 'required|string',
            'abreviatura' => 'nullable|string',
        ]);

        Log::info('CatalogController::storePais - payload', $input);
        try {
            $id = DB::table('paises')->insertGetId(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
            $pais = DB::table('paises')->where('id', $id)->first();
            Log::info('CatalogController::storePais - created', ['id' => $id, 'pais' => (array) $pais]);
            return response()->json($pais, 201);
        } catch (\Throwable $ex) {
            Log::error('CatalogController::storePais - error inserting', ['error' => $ex->getMessage(), 'payload' => $input]);
            return response()->json(['error' => 'Error creating pais', 'message' => $ex->getMessage()], 500);
        }
    }

    public function updatePais(Request $request, $id)
    {
        $input = $request->all();
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            } elseif (is_numeric($input['nombre'])) {
                $input['nombre'] = (string) $input['nombre'];
            }
        }
        // Normalize abreviatura similarly
        if (array_key_exists('abreviatura', $input)) {
            if (is_array($input['abreviatura']) && isset($input['abreviatura']['abreviatura'])) {
                $input['abreviatura'] = $input['abreviatura']['abreviatura'];
            } elseif (is_array($input['abreviatura']) && isset($input['abreviatura']['code'])) {
                $input['abreviatura'] = $input['abreviatura']['code'];
            } elseif (is_object($input['abreviatura'])) {
                $obj = (array) $input['abreviatura'];
                if (isset($obj['abreviatura'])) $input['abreviatura'] = $obj['abreviatura'];
                elseif (isset($obj['code'])) $input['abreviatura'] = $obj['code'];
            } elseif (is_numeric($input['abreviatura'])) {
                $input['abreviatura'] = (string) $input['abreviatura'];
            }
        }

        $request->merge($input);

        $data = $request->validate([
            'nombre' => 'sometimes|required|string',
            'abreviatura' => 'nullable|string',
        ]);
        DB::table('paises')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        return response()->json(DB::table('paises')->where('id', $id)->first(), 200);
    }

    public function deletePais($id)
    {
        DB::table('paises')->where('id', $id)->delete();
        return response()->noContent();
    }

    // Create a new ciudad
    public function storeCiudad(Request $request)
    {
        // Normalize nombre similar to pais
        $input = $request->all();
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            } elseif (is_numeric($input['nombre'])) {
                $input['nombre'] = (string) $input['nombre'];
            }
        }
        // Map various ways frontend may send country reference: 'pais', 'pais_id', 'paisId', 'country', 'countryId'
        if (! isset($input['pais_id'])) {
            if (isset($input['pais']) && is_numeric($input['pais'])) {
                $input['pais_id'] = (int) $input['pais'];
            } elseif (isset($input['pais']) && is_array($input['pais']) && isset($input['pais']['id'])) {
                $input['pais_id'] = (int) $input['pais']['id'];
            } elseif (isset($input['pais']) && is_object($input['pais'])) {
                $p = (array) $input['pais'];
                if (isset($p['id'])) $input['pais_id'] = (int) $p['id'];
            } elseif (isset($input['paisId'])) {
                $input['pais_id'] = (int) $input['paisId'];
            } elseif (isset($input['countryId'])) {
                $input['pais_id'] = (int) $input['countryId'];
            } elseif (isset($input['country']) && is_numeric($input['country'])) {
                $input['pais_id'] = (int) $input['country'];
            }
        }

        // Normalize abreviatura if nested/object/number
        if (array_key_exists('abreviatura', $input)) {
            if (is_array($input['abreviatura']) && isset($input['abreviatura']['abreviatura'])) {
                $input['abreviatura'] = $input['abreviatura']['abreviatura'];
            } elseif (is_array($input['abreviatura']) && isset($input['abreviatura']['code'])) {
                $input['abreviatura'] = $input['abreviatura']['code'];
            } elseif (is_object($input['abreviatura'])) {
                $obj = (array) $input['abreviatura'];
                if (isset($obj['abreviatura'])) $input['abreviatura'] = $obj['abreviatura'];
                elseif (isset($obj['code'])) $input['abreviatura'] = $obj['code'];
            } elseif (is_numeric($input['abreviatura'])) {
                $input['abreviatura'] = (string) $input['abreviatura'];
            }
        }

        $request->merge($input);

        $data = $request->validate([
            'nombre' => 'required|string',
            'pais_id' => 'nullable|integer',
            'abreviatura' => 'nullable|string',
        ]);

        Log::info('CatalogController::storeCiudad - payload', $input);
        try {
            $id = DB::table('ciudades')->insertGetId(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
            $ciudad = DB::table('ciudades')->where('id', $id)->first();
            Log::info('CatalogController::storeCiudad - created', ['id' => $id, 'ciudad' => (array) $ciudad]);
            return response()->json($ciudad, 201);
        } catch (\Throwable $ex) {
            Log::error('CatalogController::storeCiudad - error inserting', ['error' => $ex->getMessage(), 'payload' => $input]);
            return response()->json(['error' => 'Error creating ciudad', 'message' => $ex->getMessage()], 500);
        }
    }

    public function updateCiudad(Request $request, $id)
    {
        $input = $request->all();
        if (array_key_exists('nombre', $input)) {
            if (is_array($input['nombre']) && isset($input['nombre']['nombre'])) {
                $input['nombre'] = $input['nombre']['nombre'];
            } elseif (is_array($input['nombre']) && isset($input['nombre']['name'])) {
                $input['nombre'] = $input['nombre']['name'];
            } elseif (is_object($input['nombre'])) {
                $obj = (array) $input['nombre'];
                if (isset($obj['nombre'])) $input['nombre'] = $obj['nombre'];
                elseif (isset($obj['name'])) $input['nombre'] = $obj['name'];
            } elseif (is_numeric($input['nombre'])) {
                $input['nombre'] = (string) $input['nombre'];
            }
        }
        // Map country reference variants
        if (! isset($input['pais_id']) && array_key_exists('pais', $input)) {
            if (is_numeric($input['pais'])) {
                $input['pais_id'] = (int) $input['pais'];
            } elseif (is_array($input['pais']) && isset($input['pais']['id'])) {
                $input['pais_id'] = (int) $input['pais']['id'];
            } elseif (is_object($input['pais'])) {
                $p = (array) $input['pais'];
                if (isset($p['id'])) $input['pais_id'] = (int) $p['id'];
            }
        }

        // Normalize abreviatura
        if (array_key_exists('abreviatura', $input)) {
            if (is_array($input['abreviatura']) && isset($input['abreviatura']['abreviatura'])) {
                $input['abreviatura'] = $input['abreviatura']['abreviatura'];
            } elseif (is_array($input['abreviatura']) && isset($input['abreviatura']['code'])) {
                $input['abreviatura'] = $input['abreviatura']['code'];
            } elseif (is_object($input['abreviatura'])) {
                $obj = (array) $input['abreviatura'];
                if (isset($obj['abreviatura'])) $input['abreviatura'] = $obj['abreviatura'];
                elseif (isset($obj['code'])) $input['abreviatura'] = $obj['code'];
            } elseif (is_numeric($input['abreviatura'])) {
                $input['abreviatura'] = (string) $input['abreviatura'];
            }
        }

        $request->merge($input);

        $data = $request->validate([
            'nombre' => 'sometimes|required|string',
            'pais_id' => 'nullable|integer',
            'abreviatura' => 'nullable|string',
        ]);
        DB::table('ciudades')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        return response()->json(DB::table('ciudades')->where('id', $id)->first(), 200);
    }

    public function deleteCiudad($id)
    {
        DB::table('ciudades')->where('id', $id)->delete();
        return response()->noContent();
    }
}
