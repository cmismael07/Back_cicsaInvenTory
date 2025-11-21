<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DevSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:seed {--skip-users : Do not run user seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reseed development data without removing the admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting dev reseed...');

        $classes = [
            \Database\Seeders\DepartamentoSeeder::class,
            \Database\Seeders\PuestoSeeder::class,
            \Database\Seeders\TipoEquipoSeeder::class,
            \Database\Seeders\TipoLicenciaSeeder::class,
        ];

        // Optionally run UserSeeder only if not skipped
        if (! $this->option('skip-users')) {
            $classes[] = \Database\Seeders\UserSeeder::class;
        }

        // Seed equipos and licencias after types and users exist
        $classes = array_merge($classes, [
            \Database\Seeders\EquipoSeeder::class,
            \Database\Seeders\LicenciaSeeder::class,
        ]);

        foreach ($classes as $class) {
            $this->line("Seeding: $class");
            Artisan::call('db:seed', ['--class' => $class]);
            $this->line(Artisan::output());
        }

        $this->info('Dev reseed finished.');
        return 0;
    }
}
