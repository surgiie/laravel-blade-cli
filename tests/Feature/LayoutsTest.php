<?php

use Illuminate\Support\Str;

it('compiles basic layout', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('compiles nested @yield', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    name: {{ $name }}
    favorite_food: {{ $favoriteFood }}
        @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
        family_info:
            oldest_child: true
    EOL);
});

it('compiles nested @section', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        family_info:
            @switch(\$oldest)
            @case(1)
            oldest_child: true
                @break
            @case(2)
            oldest_child: false
                @break
            @endswitch
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);
    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
        name: Bob
        favorite_food: Pizza
        family_info:
            oldest_child: true
    EOL);
});

it('compiles @push', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
    @push('head')
    title: About Me
    @endpush
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    head:
        @stack('head')
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    head:
        title: About Me
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('compiles nested @push', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
        @push('head')
        title: About Me
        @endpush
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    head:
    @stack('head')
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    head:
        title: About Me
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('compiles @pushIf', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
    @pushIf(true, 'head')
    title: About Me
    @endPushIf
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    head:
        @stack('head')
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    head:
        title: About Me
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('compiles nested @pushIf', function () {
    $name = Str::random(10);
    $layoutName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    @extends("$layoutName")
    @section("content")
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    family_info:
        @switch(\$oldest)
        @case(1)
        oldest_child: true
            @break
        @case(2)
        oldest_child: false
            @break
        @endswitch
        @pushIf(true, 'head')
        title: About Me
        @endPushIf
    @endsection
    EOL);

    write_test_workspace_file($layoutName, <<<'EOL'
    head:
    @stack('head')
    @yield("content")
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
    ])->assertExitCode(0);
    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    head:
        title: About Me
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});
