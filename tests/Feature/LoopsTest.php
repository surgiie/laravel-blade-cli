<?php

use Illuminate\Support\Str;

it('can render @foreach', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
        @foreach($dogs as $dog)
        - {{ $dog }}
        @endforeach
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
        '--dogs' => ['Rex', 'Charlie'],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
        - Rex
        - Charlie
    EOL);
});

it('can render nested @foreach', function () {

    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
            @foreach($dogs as $dog)
            - {{ $dog }}
            @endforeach
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
        '--dogs' => ['Rex', 'Charlie'],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
            - Rex
            - Charlie
    EOL);
});

it('can render @forelse', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
        @forelse($dogs as $dog)
        - {{ $dog }}
        @empty
        - 'I have no dogs'
        @endforelse
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
        '--dogs' => ['Rex', 'Charlie'],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
        - Rex
        - Charlie
    EOL);
});

it('can render nested @forelse', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    pets:
            @forelse($dogs as $dog)
            - {{ $dog }}
            @empty
            - 'I have no dogs'
            @endforelse
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
        '--dogs' => ['Rex', 'Charlie'],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    pets:
            - Rex
            - Charlie
    EOL);
});

it('can render @for', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    favorite_numbers:
    @for ($i = 0; $i < 3; $i++)
        - '{{ $i }}'
    @endfor
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    favorite_numbers:
        - '0'
        - '1'
        - '2'
    EOL);
});

it('can render nested @for', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    favorite_numbers:
        @for ($i = 0; $i < 3; $i++)
            - '{{ $i }}'
        @endfor
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);
    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    favorite_numbers:
            - '0'
            - '1'
            - '2'
    EOL);
});

it('can render @while', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    favorite_numbers:
    @php($count = 0)
    @while ($count < 3)
        - '{{ $count }}'
        @php($count ++)
    @endwhile
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);
    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    favorite_numbers:
        - '0'
        - '1'
        - '2'
    EOL);
});

it('can render nested @while', function () {
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
    favorite_numbers:
    @php($count = 0)
        @while ($count < 3)
            - '{{ $count }}'
            @php($count ++)
        @endwhile
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    favorite_numbers:
            - '0'
            - '1'
            - '2'
    EOL);
});
