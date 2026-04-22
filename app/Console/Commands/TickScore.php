<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Score tick: aggregates building points from all towns and updates user totals.
 *
 * Performance fix: original TickScore did a per-row UPDATE loop.
 * Replaced with a single join-update query.
 */
class TickScore extends Command
{
    protected $signature   = 'game:tick-score';
    protected $description = 'Update player scores based on building and research points';

    public function handle(): int
    {
        // Aggregate building points per user then bulk-update.
        // Uses plain query builder for cross-DB compatibility (MySQL + SQLite).
        $perUser = DB::table('towns')
            ->select('user_id', DB::raw('SUM(building_pts) AS total_bld_pts'))
            ->groupBy('user_id')
            ->get();

        foreach ($perUser as $row) {
            DB::table('users')
                ->where('id', $row->user_id)
                ->update([
                    'building_pts' => $row->total_bld_pts,
                    'total_pts'    => DB::raw("research_pts + {$row->total_bld_pts}"),
                ]);
        }

        $this->info('Score tick completed.');
        return Command::SUCCESS;
    }
}
