<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailSetting;
use App\Mail\GenericNotification;

class EmailSettingsController extends Controller
{
    public function get()
    {
        $row = EmailSetting::first();
        if (! $row) {
            // Return default shape expected by frontend to avoid null crashes
            return response()->json([
                'remitente' => '',
                'correos_copia' => [],
                'notificar_asignacion' => true,
                'notificar_mantenimiento' => true,
                'dias_anticipacion_alerta' => 15,
                'smtp_host' => null,
                'smtp_port' => null,
                'smtp_username' => null,
                'smtp_encryption' => null,
            ], 200);
        }

        // Do not expose smtp_password
        $data = $row->toArray();
        unset($data['smtp_password']);
        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'remitente' => 'nullable|string',
            'correos_copia' => 'nullable|array',
            'notificar_asignacion' => 'required|boolean',
            'notificar_mantenimiento' => 'required|boolean',
            'dias_anticipacion_alerta' => 'required|integer|min:0',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string'
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $payload = $v->validated();
        if (!empty($payload['smtp_password'])) {
            $payload['smtp_password'] = Crypt::encryptString($payload['smtp_password']);
        }

        $row = EmailSetting::first();
        if ($row) {
            $row->update($payload);
        } else {
            $row = EmailSetting::create($payload);
        }

        return response()->json(['ok' => true, 'data' => $row], 200);
    }

    public function test(Request $request)
    {
        $to = $request->input('to');
        if (! $to) return response()->json(['message' => 'Provide a `to` email address'], 422);

        $row = EmailSetting::first();
        if (! $row) return response()->json(['message' => 'Email settings not configured'], 400);

        // Build dynamic mailer config
        $password = null;
        try { $password = $row->smtp_password ? Crypt::decryptString($row->smtp_password) : null; } catch (\Throwable $e) { $password = null; }

        $mailerConfig = [
            'transport' => 'smtp',
            'host' => $row->smtp_host,
            'port' => $row->smtp_port ?: 587,
            'encryption' => $row->smtp_encryption ?: null,
            'username' => $row->smtp_username,
            'password' => $password,
        ];

        config(['mail.mailers.dynamic' => $mailerConfig]);

        try {
            Mail::mailer('dynamic')->to($to)->send(new GenericNotification('Prueba de correo', 'Este es un correo de prueba desde la configuración dinámica.'));
            return response()->json(['ok' => true], 200);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
