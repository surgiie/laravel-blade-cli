<?php

use Illuminate\Support\Str;

// General tests that dont fit into a specific category or focus area.
it('throws error when file doesnt exist', function () {
    $this->artisan('render', ['path' => '/i-dont-exist'])
        ->expectsOutputToContain("The file or directory '/i-dont-exist' does not exist.")
        ->assertExitCode(1);
});

it('can use custom save path for rendered files', function () {
    $name = Str::random(10);

    $templatePath = write_test_workspace_file($name, <<<'EOL'
    Hello {{ $name }}
    EOL);

    $this->artisan('render', [
        'path' => $templatePath,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--save-to' => test_workspace_path('custom-name.txt'),
    ])->assertExitCode(0);

    expect(is_file(test_workspace_path("$name.rendered")))->toBeFalse();
    expect(file_get_contents(test_workspace_path('custom-name.txt')))->toBe(<<<'EOL'
    Hello Bob
    EOL);
});

it('will error out if rendered file already exists.', function () {
    $name = Str::random(10);

    $template = write_test_workspace_file($name, <<<'EOL'
    Hello {{ $name }}
    EOL);

    write_test_workspace_file($name.'.rendered', <<<'EOL'
    Hello Bob
    EOL);

    $this->artisan('render', [
        'path' => $template,
        '--name' => 'Bob',
    ])->expectsOutputToContain("The file 'tests/workspace/$name.rendered' already exists. Use --force to overwrite.")
        ->assertExitCode(1);
});

it('can use overwrite vars with --require file', function () {
    $name = Str::random(10);

    $templatePath = write_test_workspace_file($name, <<<'EOL'
    Hello {{ $name }}
    EOL);

    $requireFile = write_test_workspace_file($name.'-require.php', <<<'EOL'
    <?php

    return [
        "name" => "Not Bob"
    ];
    EOL);

    $this->artisan('render', [
        'path' => $templatePath,
        '--name' => 'Bob',
        '--require' => [$requireFile],
        '--cache-path' => test_workspace_path('cache'),
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    Hello Not Bob
    EOL);
});

it('uses default save name when no save path is provided', function () {
    // file with no extension
    $name = Str::random(10);

    $templatePath = write_test_workspace_file($name, <<<'EOL'
    Hello {{ $name }}
    EOL);

    $this->artisan('render', [
        'path' => $templatePath,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
    ])->assertExitCode(0);

    expect(is_file(test_workspace_path("$name.rendered")))->toBeTrue();

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    Hello Bob
    EOL);

    // file with extension:
    $templatePath = write_test_workspace_file($name.'.txt', <<<'EOL'
    Hello {{ $name }}
    EOL);
    $this->artisan('render', [
        'path' => $templatePath,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
    ])->assertExitCode(0);

    expect(is_file(test_workspace_path("$name.rendered.txt")))->toBeTrue();

    expect(file_get_contents(test_workspace_path("$name.rendered.txt")))->toBe(<<<'EOL'
    Hello Bob
    EOL);
});

it('renders a complex file as expected', function () {
    $name = Str::random(10);
    $layout = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layout")
    @section("content")
        @if(\$nodeAffinity)
        affinity:
            nodeAffinity:
            requiredDuringSchedulingIgnoredDuringExecution:
                nodeSelectorTerms:
            —matchExpressions:
                —key: disktype
                    operator: In
                    values:
                —ssd
        @endif
        containers:
        —name: nginx
            image: nginx
            ports:
            —containerPort: 80
    @endsection
    EOL);

    write_test_workspace_file('strategy', <<<'EOL'
    strategy:
        type: RollingUpdate
    EOL);

    write_test_workspace_file($layout, <<<'EOL'
    apiVersion: {{ $apiVersion }}
    kind: Deployment
    metadata:
    name: {{ $name }}
    labels:
        @foreach($labels as $label)
        {{ $label }}
        @endforeach
    spec:
    selector:
        matchLabels:
        @foreach($labels as $label)
        {{ $label }}
        @endforeach
    replicas: {{ $replicas }}
    @include('strategy')
    template:
        metadata:
        labels:
            @foreach($labels as $label)
            {{ $label }}
            @endforeach
        spec:
        @yield('content')
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--api-version' => 'apps/v1',
        '--name' => 'nginx-deployment',
        '--replicas' => 3,
        '--node-affinity' => true,
        '--labels' => [
            'app: web',
            'backend: api',
        ],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    apiVersion: apps/v1
    kind: Deployment
    metadata:
    name: nginx-deployment
    labels:
        app: web
        backend: api
    spec:
    selector:
        matchLabels:
        app: web
        backend: api
    replicas: 3
    strategy:
        type: RollingUpdate
    template:
        metadata:
        labels:
            app: web
            backend: api
        spec:
            affinity:
                nodeAffinity:
                requiredDuringSchedulingIgnoredDuringExecution:
                    nodeSelectorTerms:
                —matchExpressions:
                    —key: disktype
                        operator: In
                        values:
                    —ssd
            containers:
            —name: nginx
                image: nginx
                ports:
                —containerPort: 80
    EOL);
});

it('validates save directory is required when rendering directory', function () {
    write_test_workspace_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);
    $this->artisan('render', [
        'path' => test_workspace_path('directory'),
        '--favorite-food' => 'Pizza',
        '--force' => true,
    ])->assertExitCode(1)
        ->expectsOutputToContain('The --save-to directory option is required for rendering a directory of files.');
});

it('validates save directory must not be directory being processed', function () {
    @mkdir(test_workspace_path('directory'));

    write_test_workspace_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);

    $this->artisan('render', [
        'path' => test_workspace_path('directory'),
        '--save-to' => test_workspace_path('directory'),
        '--favorite-food' => 'Pizza',
        '--force' => true,
    ])->expectsOutputToContain('The path being processed is also the --save-to directory, use a different save directory.');
});

it('can render files in directory', function () {
    @mkdir(test_workspace_path('directory'));

    write_test_workspace_file('directory/example.yaml', <<<'EOL'
    favorite_food: {{ $favoriteFood }}
    EOL);

    write_test_workspace_file('directory/nested/example2.yaml', <<<'EOL'
    name: {{ $name }}
    EOL);

    $this->artisan('render', [
        'path' => test_workspace_path('directory'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--force' => true,
        '--save-to' => test_workspace_path('save-dir'),
    ]);
    $path = test_workspace_path('save-dir/example.yaml');
    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toBe(<<<'EOL'
    favorite_food: Pizza
    EOL);

    $path = test_workspace_path('save-dir/nested/example2.yaml');
    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toBe(<<<'EOL'
    name: Bob
    EOL);
});

it('can load variable data from json files', function () {
    $name = Str::random(10);

    $path = write_test_workspace_file("$name.yaml", <<<'EOL'
    name: {{ $name }}
    EOL);

    write_test_workspace_file('vars.json', <<<'EOL'
    {
        "name": "Doug"
    }
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--from-json' => [test_workspace_path('vars.json')],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered.yaml")))->toBe(<<<'EOL'
    name: Doug
    EOL);
});

it('can load variable data from yaml files', function () {
    $name = Str::random(10);

    $path = write_test_workspace_file("$name.txt", <<<'EOL'
    name: {{ $name }}
    last_name: {{ $lastName }}
    EOL);

    write_test_workspace_file('vars.yaml', <<<'EOL'
    "name": "Doug"
    "last_name": "Thompson"
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--from-yaml' => [test_workspace_path('vars.yaml')],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered.txt")))->toBe(<<<'EOL'
    name: Doug
    last_name: Thompson
    EOL);
});

it('can load variable data from env files', function () {
    $name = Str::random(10);

    $path = write_test_workspace_file("$name.yaml", <<<'EOL'
    name: {{ $name }}
    EOL);

    write_test_workspace_file('.env.vars', <<<'EOL'
    NAME=Doug
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--from-env' => [test_workspace_path('.env.vars')],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered.yaml")))->toBe(<<<'EOL'
    name: Doug
    EOL);
});

it('can dry run render', function () {
    $name = Str::random(10);

    $path = write_test_workspace_file("$name.yaml", <<<'EOL'
    name: {{ $name }}
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Doug',
        '--dry-run' => true,
    ])->expectsOutputToContain('DRY RUN')
        ->expectsOutputToContain('name: Doug')
        ->assertExitCode(0);

    expect(is_file(test_workspace_path("$name.rendered.yaml")))->toBeFalse();
});
