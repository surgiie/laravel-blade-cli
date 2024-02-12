<?php

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

uses(TestCase::class)->beforeEach(function () {
    (new Filesystem)->deleteDirectory(test_workspace_path());
})->afterAll(function () {
    (new Filesystem)->deleteDirectory(test_workspace_path());
})->in(__DIR__);

function test_workspace_path(string $path = '')
{
    return rtrim(__DIR__.'/workspace'.'/'.$path);
}

function write_test_workspace_file(string $file, string $contents)
{
    $file = trim($file, '/');

    $path = test_workspace_path().$file;

    @mkdir(dirname($path), recursive: true);

    file_put_contents($path, $contents);

    return $path;
}
