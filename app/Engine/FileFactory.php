<?php

namespace App\Engine;

use Illuminate\View\Factory;

class FileFactory extends Factory
{
    /**
     * Normalizes the given file name.
     *
     * @param  string  $name
     * @return string
     */
    protected function normalizeName($name)
    {
        // Disable dot notation from file name.
        return $name;
    }

    /**
     * Get the extension used by the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        return pathinfo($path)['extension'] ?? '';
    }

    /**
     * Resolve the engine to use from the given path.
     *
     * @param  string  $path
     * @return \Illuminate\Contracts\View\Engine
     */
    public function getEngineFromPath($path)
    {
        return $this->engines->resolve('blade');
    }
}
