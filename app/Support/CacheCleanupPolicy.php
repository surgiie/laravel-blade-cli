<?php

namespace App\Support;

use Spatie\DirectoryCleanup\Policies\CleanupPolicy;
use Symfony\Component\Finder\SplFileInfo;

class CacheCleanupPolicy implements CleanupPolicy
{
    public function shouldDelete(SplFileInfo $file): bool
    {
        return ! in_array($file->getFilename(), ['.gitignore', '.gitkeep']);
    }
}
