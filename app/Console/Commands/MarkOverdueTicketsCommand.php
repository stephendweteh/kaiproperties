<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;

class MarkOverdueTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark tickets as overdue when expected completion date has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = Ticket::query()
            ->whereNotNull('etd')
            ->where('etd', '<', now())
            ->whereIn('status', ['logged', 'assigned', 'in_progress'])
            ->update(['status' => 'overdue']);

        $this->info("Marked {$updated} ticket(s) as overdue.");

        return self::SUCCESS;
    }
}
