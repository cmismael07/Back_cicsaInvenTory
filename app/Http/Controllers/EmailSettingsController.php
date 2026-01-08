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
    private function mapEncryptionToStorage($value): ?string
    {
        if ($value === null) return null;
        $s = trim((string) $value);
        if ($s === '') return null;
        $u = strtoupper($s);
        if ($u === 'NONE' || $u === 'NO' || $u === 'NULL') return null;
        if ($u === 'TLS' || $u === 'STARTTLS') return 'tls';
        if ($u === 'SSL') return 'ssl';

        // Allow already-normalized values
        $l = strtolower($s);
        if (in_array($l, ['tls', 'ssl'], true)) return $l;
        return $l;
    }

    private function mapEncryptionToFrontend($value): string
    {
        if ($value === null) return 'NONE';
        $s = trim((string) $value);
        if ($s === '') return 'NONE';
        $u = strtoupper($s);
        if ($u === 'TLS' || $u === 'STARTTLS') return 'TLS';
        if ($u === 'SSL') return 'SSL';
        if ($u === 'NONE' || $u === 'NO') return 'NONE';
        // stored values like 'tls'/'ssl'
        if (strtolower($s) === 'tls') return 'TLS';
        if (strtolower($s) === 'ssl') return 'SSL';
        return $u;
    }

    private function toFrontendShape(?EmailSetting $row): array
    {
        return [
            'remitente' => $row?->remitente ?? '',
            'correos_copia' => $row?->correos_copia ?? [],
            'notificar_asignacion' => $row?->notificar_asignacion ?? true,
            'notificar_mantenimiento' => $row?->notificar_mantenimiento ?? true,
            'notificar_alerta_mantenimiento' => $row?->notificar_alerta_mantenimiento ?? true,
            'dias_anticipacion_alerta' => $row?->dias_anticipacion_alerta ?? 15,
            'smtp_host' => $row?->smtp_host ?? '',
            // frontend lo maneja como string
            'smtp_port' => $row && $row->smtp_port !== null ? (string) $row->smtp_port : '',
            // frontend usa smtp_user
            'smtp_user' => $row?->smtp_username ?? '',
            // nunca exponer password real
            'smtp_pass' => '',
            // frontend espera TLS/SSL/NONE
            'smtp_encryption' => $this->mapEncryptionToFrontend($row?->smtp_encryption ?? null),
        ];
    }

    public function get()
    {
        $row = EmailSetting::first();
        return response()->json($this->toFrontendShape($row), 200);
    }

    public function store(Request $request)
    {
        // Normalizar entrada a lo que el frontend manda (Settings.tsx)
        $input = $request->all();

        // compat: aceptar smtp_user/smtp_pass además de smtp_username/smtp_password
        if (array_key_exists('smtp_user', $input) && !array_key_exists('smtp_username', $input)) {
            $input['smtp_username'] = $input['smtp_user'];
        }
        if (array_key_exists('smtp_pass', $input) && !array_key_exists('smtp_password', $input)) {
            $input['smtp_password'] = $input['smtp_pass'];
        }

        // compat: correos_copia también podría venir como string "a@b.com,c@d.com"
        if (isset($input['correos_copia']) && is_string($input['correos_copia'])) {
            $parts = array_values(array_filter(array_map('trim', preg_split('/[;,\s]+/', $input['correos_copia']))));
            $input['correos_copia'] = $parts;
        }

        // smtp_port viene como string en el frontend
        if (isset($input['smtp_port']) && is_string($input['smtp_port'])) {
            $p = trim($input['smtp_port']);
            $input['smtp_port'] = $p === '' ? null : (ctype_digit($p) ? (int) $p : $p);
        }

        // encryption viene como TLS/SSL/NONE
        if (array_key_exists('smtp_encryption', $input)) {
            $input['smtp_encryption'] = $this->mapEncryptionToStorage($input['smtp_encryption']);
        }

        $v = Validator::make($input, [
            'remitente' => 'nullable|string',
            'correos_copia' => 'nullable|array',
            'notificar_asignacion' => 'required|boolean',
            'notificar_mantenimiento' => 'required|boolean',
            'notificar_alerta_mantenimiento' => 'required|boolean',
            'dias_anticipacion_alerta' => 'required|integer|min:0',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string'
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $payload = $v->validated();

        // smtp_port: si no es int, invalidar
        if (array_key_exists('smtp_port', $payload) && $payload['smtp_port'] !== null && $payload['smtp_port'] !== '') {
            if (!is_int($payload['smtp_port'])) {
                return response()->json(['errors' => ['smtp_port' => ['smtp_port debe ser numérico']]], 422);
            }
        } else {
            $payload['smtp_port'] = null;
        }

        // No sobreescribir password si viene vacío
        $rawPass = $payload['smtp_password'] ?? null;
        if ($rawPass === null || trim((string) $rawPass) === '') {
            unset($payload['smtp_password']);
        } else {
            $payload['smtp_password'] = Crypt::encryptString((string) $rawPass);
        }

        $row = EmailSetting::first();
        if ($row) {
            $row->update($payload);
        } else {
            $row = EmailSetting::create($payload);
        }

        return response()->json(['ok' => true, 'data' => $this->toFrontendShape($row)], 200);
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
