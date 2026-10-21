<?php

namespace Tests\Unit;

use App\Models\TrafficRequestEvent;
use PHPUnit\Framework\TestCase;

class TrafficRequestEventTest extends TestCase
{
    public function test_it_masks_ipv4_addresses(): void
    {
        $event = new TrafficRequestEvent(['client_ip' => '203.0.113.42']);

        $this->assertSame('203.***.***.***', $event->maskedIp());
    }

    public function test_it_masks_ipv6_addresses(): void
    {
        $event = new TrafficRequestEvent(['client_ip' => '2001:db8:85a3::8a2e:370:7334']);

        $this->assertStringStartsWith('2001:db8:', $event->maskedIp());
        $this->assertStringContainsString('****', $event->maskedIp());
    }
}
