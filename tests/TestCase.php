<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $systemKey = (string) config('services.system_api_key', '');
        if ($systemKey === '') {
            $systemKey = 'system-test-key';
            config(['services.system_api_key' => $systemKey]);
        }

        $this->withHeader('X-Api-Key', $systemKey);
    }
}
