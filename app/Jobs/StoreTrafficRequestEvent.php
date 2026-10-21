<?php

namespace App\Jobs;

use App\Models\TrafficRequestEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreTrafficRequestEvent implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(private readonly array $event)
    {
    }

    public function handle(): void
    {
        TrafficRequestEvent::create($this->event);
    }
}
