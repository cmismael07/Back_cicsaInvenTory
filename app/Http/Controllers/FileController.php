<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    // Serve assignment files from storage with CORS headers
    public function serveAsignacion(Request $request, $filename)
    {
        $filename = ltrim($filename, '/');
        $path = storage_path('app/public/asignaciones/' . $filename);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        $response = response()->file($path);
        $origin = $request->headers->get('origin') ?? '*';
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        return $response;
    }

    /**
     * Serve maintenance or other files under storage/app/public
     * URL: GET /api/files/mantenimientos/{filename} or any file path
     */
    public function showMantenimiento(Request $request, $filename)
    {
        // sanitize and normalize
        $filename = ltrim($filename, '/');
        $filename = str_replace('..', '', $filename);
        if (str_starts_with($filename, 'storage/')) {
            $filename = substr($filename, strlen('storage/'));
        }

        // Prefer files under the 'mantenimientos' folder by default
        $path = storage_path('app/public/mantenimientos/' . $filename);
        // Fallback: allow requesting a full relative path under public storage
        if (! file_exists($path)) {
            $path = storage_path('app/public/' . $filename);
        }
        if (! file_exists($path)) {
            \Illuminate\Support\Facades\Log::warning('FileController::showMantenimiento not found', ['requested' => $filename, 'path' => $path]);
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        $response = response()->file($path, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ]);
        $origin = $request->headers->get('origin') ?? '*';
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        return $response;
    }
}
