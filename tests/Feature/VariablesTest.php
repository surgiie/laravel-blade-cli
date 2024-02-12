<?php

use Pest\Support\Str;

it('renders variables', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    {{$relationship}}
    {{$name}}
    something: {{ $something }}
    foo:
        bar: {{ $bar }}
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--relationship' => 'Uncle',
        '--bar' => 'baz',
        '--something' => 'foo',
        '--name' => 'Bob',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    Uncle
    Bob
    something: foo
    foo:
        bar: baz
    EOL);
});

it('renders nested variables', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    {{$relationship}}
        {{$name}}
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--relationship' => 'Uncle',
        '--name' => 'Bob',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    Uncle
        Bob
    EOL);
});
