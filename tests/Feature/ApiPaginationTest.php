<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Equipo;
use App\Models\Licencia;

class ApiPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipos_index_returns_paginated_structure()
    {
        User::factory()->create();
        Equipo::factory()->count(30)->create();

        $user = User::first();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/equipos');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta',
                 ]);
    }

    public function test_users_index_returns_paginated_structure()
    {
        User::factory()->count(25)->create();
        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta',
                 ]);
    }

    public function test_licencias_index_returns_paginated_structure()
    {
        User::factory()->create();
        Licencia::factory()->count(40)->create();
        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/licencias');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta',
                 ]);
    }
}
