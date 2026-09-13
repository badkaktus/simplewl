<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_currencies_are_synced_daily_once_across_containers(): void
    {
        $events = collect(resolve(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'currency:sync'));

        $this->assertCount(1, $events);

        /** @var Event $event */
        $event = $events->first();
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->onOneServer);
    }
}
