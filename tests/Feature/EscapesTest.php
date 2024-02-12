<?php

use Pest\Support\Str;

it('respects escaped directives', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    {{$name}}
    @@if(true)
        example
    @@endif

        @@if(true)
            example2
        @@endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    Bob
    @if(true)
        example
    @endif

        @if(true)
            example2
        @endif
    EOL);
});

it('escapes html', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    {{$html}}
    EOL);
    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--html' => '<script>alert("foo")</script>',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    &lt;script&gt;alert(&quot;foo&quot;)&lt;/script&gt;
    EOL);
});
