<?php

namespace Tests;

use App\Services\Network\HostResolver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase; // , DatabaseMigrations;

    public const PUBLIC_IP = '93.184.216.34';

    /**
     * 1x1 transparent PNG.
     */
    public const PNG_IMAGE_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Tests must not depend on real DNS
        $this->fakeHostResolver([self::PUBLIC_IP]);
    }

    /**
     * @param  list<string>|array<string, list<string>>  $ips  IPs for every host, or IPs by host
     */
    protected function fakeHostResolver(array $ips): void
    {
        $this->app->instance(HostResolver::class, new class($ips) extends HostResolver
        {
            public function __construct(private readonly array $ips) {}

            public function resolve(string $host): array
            {
                return array_is_list($this->ips) ? $this->ips : ($this->ips[$host] ?? []);
            }
        });
    }

    protected function pngImage(): string
    {
        return base64_decode(self::PNG_IMAGE_BASE64);
    }
}
