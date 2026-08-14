<?php

namespace Tests\Unit;

use App\Support\SystemStatus;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_default_application_data_path_is_not_empty(): void
    {
        $status = new SystemStatus;

        $this->assertNotSame('', $status->applicationDataPath());
        $this->assertTrue(
            str_contains($status->applicationDataPath(), 'MedStore')
            || str_contains($status->applicationDataPath(), 'pharmacy-data')
        );
    }
}
