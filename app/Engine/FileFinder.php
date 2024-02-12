<?php

namespace App\Engine;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

class FileFinder extends FileViewFinder
{
    protected $extensions = [];

    /**
     * Get the possible view files for the given name.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        if (empty($this->extensions)) {
            return [$name, str_replace('.', '/', $name), str_replace('.', '/', $name).'.blade.php'];
        }

        return array_map(fn ($extension) => str_replace('.', '/', $name).'.'.$extension, $this->extensions);
    }

    /**
     * Find the view in registered paths.
     *
     * @param  string  $name
     * @param  array  $paths
     * @return void
     */
    protected function findInPaths($name, $paths)
    {
        try {
            return parent::findInPaths($name, $paths);
        } catch (InvalidArgumentException) {
            if (file_exists($name)) {
                return $name;
            }

            throw new InvalidArgumentException("File [{$name}] not found.");
        }
    }
}
