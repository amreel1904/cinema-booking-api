<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SeatStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $showtimeId,
        public int $seatId,
        public string $row,
        public int $number,
        public string $status, // 'locked', 'available', 'booked'
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("showtime.{$this->showtimeId}")];
    }

    public function broadcastAs(): string
    {
        return 'seat.status.changed';
    }
}
