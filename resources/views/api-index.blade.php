<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API InvenTory - Indice</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #111827;
            --text: #e5e7eb;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --border: #1f2937;
            --chip: #0ea5e9;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #0b1220 0%, #0f172a 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }
        header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .badge {
            background: var(--chip);
            color: #00111a;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
        }
        h1 {
            font-size: 28px;
            margin: 0;
        }
        p {
            color: var(--muted);
            line-height: 1.5;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            margin-top: 16px;
        }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .code {
            background: #0b1020;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 8px;
            font-family: Consolas, "Courier New", monospace;
            color: #cbd5f5;
            font-size: 12px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        th {
            color: var(--muted);
            font-weight: 600;
        }
        .method {
            display: inline-block;
            background: #0b2538;
            color: var(--accent);
            border: 1px solid #0b3b54;
            border-radius: 6px;
            padding: 2px 8px;
            margin-right: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .section-title {
            font-size: 16px;
            margin: 0 0 8px;
        }
        .note {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <span class="badge">API</span>
            <div>
                <h1>Indice del Backend InvenTory</h1>
                <p>Esta pagina describe como consumir el API, sus endpoints y el flujo basico de autenticacion.</p>
            </div>
        </header>

        <div class="panel">
            <h2 class="section-title">Base URL</h2>
            <div class="code">{{ $baseUrl }}</div>
            <p class="note">Todas las rutas mostradas abajo se anteponen con <strong>/api</strong>.</p>
        </div>

        <div class="row">
            <div class="panel">
                <h2 class="section-title">Autenticacion</h2>
                <p>Usa <strong>POST /api/login</strong> con JSON para obtener el token. Luego envia el header:</p>
                <div class="code">Authorization: Bearer &lt;token&gt;</div>
                <p class="note">Las rutas marcadas como protegidas requieren <em>auth:sanctum</em>.</p>
            </div>
            <div class="panel">
                <h2 class="section-title">Ejemplo rapido</h2>
                <div class="code">curl -X POST {{ $baseUrl }}/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@demo.com","password":"secret"}'</div>
            </div>
        </div>

        <div class="panel">
            <h2 class="section-title">Endpoints Publicos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metodo</th>
                        <th>Ruta</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($publicRoutes as $r)
                    <tr>
                        <td>
                            @foreach($r['methods'] as $m)
                                <span class="method">{{ $m }}</span>
                            @endforeach
                        </td>
                        <td>{{ $r['uri'] }}</td>
                        <td>{{ $r['name'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No hay rutas publicas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2 class="section-title">Endpoints Protegidos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metodo</th>
                        <th>Ruta</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($protectedRoutes as $r)
                    <tr>
                        <td>
                            @foreach($r['methods'] as $m)
                                <span class="method">{{ $m }}</span>
                            @endforeach
                        </td>
                        <td>{{ $r['uri'] }}</td>
                        <td>{{ $r['name'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No hay rutas protegidas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">Ultima actualizacion dinamica al cargar esta pagina.</div>
    </div>
</body>
</html>
