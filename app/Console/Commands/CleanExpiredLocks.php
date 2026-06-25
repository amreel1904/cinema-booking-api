<?php

namespace App\Console\Commands;

use App\Events\SeatStatusChanged;
use App\Models\SeatLock;
use Illuminate\Console\Command;

class CleanExpiredLocks extends Command
{
    protected $signature = 'seats:clean-expired';
    protected $description = 'Remove expired seat locks and notify clients';

    public function handle(): void
    {
        // Load expired locks with seat info before deleting so we can broadcast
        $expired = SeatLock::where('expires_at', '<', now())
            ->with('seat')
            ->get();

        foreach ($expired as $lock) {
            SeatStatusChanged::dispatch(
                $lock->showtime_id,
                $lock->seat_id,
                $lock->seat->row,
                $lock->seat->number,
                'available'
            );
        }

        $count = SeatLock::where('expires_at', '<', now())->delete();

        $this->info("Removed {$count} expired seat lock(s).");
    }
}
