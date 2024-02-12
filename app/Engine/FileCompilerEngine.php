<?php

namespace App\Engine;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\ViewException;
use Throwable;

class FileCompilerEngine extends CompilerEngine
{
    /**
     * Require the file path and return the evaluated contents.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    protected function evaluatePath($path, $data)
    {
        $obLevel = ob_get_level();

        ob_start();

        try {
            $this->files->getRequire($path, $data);
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        // the blade engine uses ltrim on the output buffer
        // this is problematic when rendering a file that
        // has semantic whitespace at the beginning of lines
        // like yaml.
        return rtrim(ob_get_clean());
    }

    /**
     * Handle a file exception during render.
     *
     * @param  int  $obLevel
     * @return void
     */
    protected function handleViewException(Throwable $e, $obLevel)
    {
        $e = new ViewException($this->getExceptionMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

        PhpEngine::handleViewException($e, $obLevel);
    }

    /**
     * Get the exception message for an exception.
     */
    protected function getExceptionMessage(Throwable $e): string
    {
        $msg = $e->getMessage();

        return $msg.' (File: '.realpath(end($this->lastCompiled)).')';
    }
}
