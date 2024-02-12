<?php

namespace App\Commands;

use App\Concerns\LoadsEnvFiles;
use App\Concerns\LoadsJsonFiles;
use App\Support\BaseCommand;

class CleanCacheCommand extends BaseCommand
{
    use LoadsEnvFiles, LoadsJsonFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cache:clean
                            {--force : Force delete all files in the cache directory regardless of age.}
                            {--expires-minutes=1440 : Custom age of minutes for files that should be deleted. Default: 1440 }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Cleanup the cache directory of compiled files older than the expired age.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $cacheExpiration = $this->option('expires-minutes');

        if ($this->option('force')) {
            $cacheExpiration = -1;
        }

        config([
            'laravel-directory-cleanup.directories.'.config('view.compiled').'.deleteAllOlderThanMinutes' => $cacheExpiration,
        ]);

        $this->call('clean:directories');
    }
}
