<?php

namespace Tests\Feature;

use App\Support\FirstRunSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_screen_boots(): void
    {
        $this->withoutVite();
        $owner = app(FirstRunSetup::class)->complete($this->firstRunPayload());

        $response = $this->actingAs($owner)->get('/status');

        $response
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee(config('pharmacy.version'));
    }

    public function test_sqlite_connection_is_available(): void
    {
        $result = DB::selectOne('select 1 as ok');

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(1, (int) $result->ok);
    }
}
