<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\HistorialMovimiento;

class BackfillHistorialTipoAccion extends Command
{
    protected $signature = 'historial:backfill-tipo-accion {--dry-run}';
    protected $description = 'Backfill historial_movimientos.tipo_accion based on nota keywords when missing.';

    public function handle()
    {
        $dry = $this->option('dry-run');
        $this->info('Backfill started. Dry run: ' . ($dry ? 'yes' : 'no'));

        $query = HistorialMovimiento::whereNull('tipo_accion')->orWhere('tipo_accion', '');
        $count = $query->count();
        $this->info("Candidates: $count");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunkById(200, function($rows) use (&$bar, $dry) {
            foreach ($rows as $r) {
                $nota = strtolower((string) ($r->nota ?? ''));
                $new = null;
                if (strpos($nota, 'pre_baja') !== false || strpos($nota, 'pre-baja') !== false || strpos($nota, 'para baja') !== false || strpos($nota, 'marcado para baja') !== false) {
                    $new = 'PRE_BAJA';
                } elseif (strpos($nota, 'dar de baja') !== false || strpos($nota, 'dado de baja') !== false || strpos($nota, 'baja') !== false) {
                    $new = 'BAJA';
                } elseif (strpos($nota, 'recep') !== false || strpos($nota, 'recib') !== false || strpos($nota, 'recepcion') !== false) {
                    $new = 'RECEPCION';
                } elseif (strpos($nota, 'asign') !== false || strpos($nota, 'entreg') !== false) {
                    $new = 'ASIGNACION';
                } elseif (strpos($nota, 'manten') !== false) {
                    $new = 'MANTENIMIENTO';
                } elseif (strpos($nota, 'creaci') !== false || strpos($nota, 'creado') !== false) {
                    $new = 'CREACION';
                }

                if ($new) {
                    if ($dry) {
                        $this->line("[DRY] #{$r->id} => $new");
                    } else {
                        $r->tipo_accion = $new;
                        $r->save();
                    }
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info(PHP_EOL . 'Done.');
        return 0;
    }
}
