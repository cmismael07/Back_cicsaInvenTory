<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Serve a maintenance file from storage/app/public/mantenimientos
     * URL: GET /api/files/mantenimientos/{filename}
     */
    public function showMantenimiento(Request $request, $filename)
    {
        // sanitize filename to avoid directory traversal
        $filename = ltrim($filename, '/');
        $filename = str_replace('..', '', $filename);

        // If caller included 'storage/' or a leading 'mantenimientos/' segment, normalize
        if (str_starts_with($filename, 'storage/')) {
            $filename = substr($filename, strlen('storage/'));
        }
        if (str_starts_with($filename, 'mantenimientos/')) {
            // keep as-is (will map to storage/app/public/mantenimientos/..)
        }

        // Build path relative to storage/app/public
        $path = storage_path('app/public/' . $filename);

        if (! file_exists($path)) {
            // log for debugging
            \Illuminate\Support\Facades\Log::warning('FileController::showMantenimiento not found', ['requested' => $filename, 'path' => $path]);
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        // Let PHP/Laravel stream the file with appropriate headers
        return response()->file($path, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ]);
    }
}
