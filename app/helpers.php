<?php

if (! function_exists('get_cached_path')) {
    /**
     * Get the path to the compiled views.
     */
    function get_cached_path(): string
    {
        return \Phar::running() ? rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.laravel-blade-cli' : env(
            'LARAVEL_BLADE_CLI_COMPILED_PATH',
            realpath(storage_path('framework/compiled'))
        );
    }
}

if (! function_exists('indent_lines')) {
    /**
     * Indent the given content by the number of given spaces
     */
    function indent_lines(string $content, int $spaces): string
    {
        $result = [];
        $spacing = str_repeat(' ', $spaces);
        if ($content == '') {
            return '';
        }
        foreach (explode(PHP_EOL, $content) as $line) {
            $result[] = $spacing.$line;
        }

        return implode(PHP_EOL, $result);
    }
}
