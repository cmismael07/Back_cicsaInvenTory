<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistorialMovimiento;

class CheckHistorialActions extends Command
{
    protected $signature = 'historial:check-actions {--limit=10}';
    protected $description = 'Show counts of tipo_accion and list possible mismatches between tipo_accion and equipo.estado';

    public function handle()
    {
        $this->info('Counting tipo_accion values...');
        $counts = HistorialMovimiento::selectRaw('COALESCE(tipo_accion, "EDICION") as tipo, count(*) as cnt')->groupBy('tipo')->pluck('cnt','tipo');
        foreach ($counts as $tipo => $cnt) {
            $this->line(" - $tipo: $cnt");
        }

        $limit = (int) $this->option('limit');

        $this->info('\nListing movimientos where tipo_accion = RECEPCION but equipo.estado indicates PARA_BAJA (possible mismatch):');
        $rows = HistorialMovimiento::where('tipo_accion', 'RECEPCION')->with('equipo')->take($limit)->get();
        $found = 0;
        foreach ($rows as $r) {
            $estado = $r->equipo?->estado ?? null;
            if ($estado && stripos($estado, 'para') !== false && stripos($estado, 'baja') !== false) {
                $this->line("#{$r->id} equipo_id={$r->equipo_id} fecha={$r->fecha} tipo={$r->tipo_accion} equipo.estado={$estado} nota={$r->nota}");
                $found++;
            }
        }
        if ($found === 0) $this->line(' (none found in sample)');

        $this->info('\nListing movimientos where tipo_accion = PRE_BAJA but equipo.estado is not PARA_BAJA:');
        $rows2 = HistorialMovimiento::where('tipo_accion', 'PRE_BAJA')->with('equipo')->take($limit)->get();
        $found2 = 0;
        foreach ($rows2 as $r) {
            $estado = $r->equipo?->estado ?? null;
            if (!($estado && stripos($estado, 'para') !== false && stripos($estado, 'baja') !== false)) {
                $this->line("#{$r->id} equipo_id={$r->equipo_id} fecha={$r->fecha} tipo={$r->tipo_accion} equipo.estado={$estado} nota={$r->nota}");
                $found2++;
            }
        }
        if ($found2 === 0) $this->line(' (none found in sample)');

        $this->info('\nDone.');
        return 0;
    }
}
