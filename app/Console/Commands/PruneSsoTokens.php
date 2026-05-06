<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SsoToken;
use Illuminate\Console\Command;

final class PruneSsoTokens extends Command
{
    protected $signature = 'sso:prune-tokens {--hours=24 : Delete tokens expired more than this many hours ago}';

    protected $description = 'Delete expired SSO tokens from the database';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $count = SsoToken::query()
            ->where('expires_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$count} expired SSO token(s).");

        return self::SUCCESS;
    }
}
