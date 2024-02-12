<?php

use Illuminate\Support\Str;

it('can render @include', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    write_test_workspace_file($includeName, <<<'EOL'
    "phone": "1234567890",
    @if($includeAddress)
    "street_info": "123 Lane."
    @endif
    EOL);

    $path = write_test_workspace_file($name, <<<"EOL"
    {
        "name": "{{ \$name }}",
        "favorite_food": "{{ \$favoriteFood }}",
        @include('$includeName')
    }
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    {
        "name": "Bob",
        "favorite_food": "Pizza",
        "phone": "1234567890",
        "street_info": "123 Lane."
    }
    EOL);
});

it('can render nested @include', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    {
        "name": "{{ \$name }}",
        "favorite_food": "{{ \$favoriteFood }}",
        "contactInfo": {
            @include('$includeName')
        }
    }
    EOL);
    write_test_workspace_file($includeName, <<<'EOL'
    "phone": "1234567890",
    @if($includeAddress)
    "street_info": "123 Lane."
    @endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    {
        "name": "Bob",
        "favorite_food": "Pizza",
        "contactInfo": {
            "phone": "1234567890",
            "street_info": "123 Lane."
        }
    }
    EOL);
});

it('can render @includeIf', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @includeIf('$includeName')
    EOL);

    write_test_workspace_file($includeName, <<<'EOL'
    contact_info:
        phone: 1234567890
        @if($includeAddress)
        street_info: 123 Lane.
        @endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    contact_info:
        phone: 1234567890
        street_info: 123 Lane.
    EOL);
});

it('can render nested @includeIf', function () {
    $includeName = Str::random(10);
    $name = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        personal_life:
            @includeIf('$includeName')
        EOL);

    write_test_workspace_file($includeName, <<<'EOL'
        contact_info:
            phone: 1234567890
            @if($includeAddress)
            street_info: 123 Lane.
            @endif
        EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
        name: Bob
        favorite_food: Pizza
        personal_life:
            contact_info:
                phone: 1234567890
                street_info: 123 Lane.
        EOL);
});

it('can render @includeWhen', function () {
    $includeName = Str::random(10);
    $name = Str::random(10);
    $path = write_test_workspace_file($name, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @includeWhen(true, '$includeName')
    EOL);
    write_test_workspace_file($includeName, <<<'EOL'
    contact_info:
        phone: 1234567890
        @if($includeAddress)
        street_info: 123 Lane.
        @endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    contact_info:
        phone: 1234567890
        street_info: 123 Lane.
    EOL);
});

it('can render nested @includeWhen', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        personal_life:
            @includeWhen(true, '$includeName')
        EOL);

    write_test_workspace_file($includeName, <<<'EOL'
        contact_info:
            phone: 1234567890
            @if($includeAddress)
            street_info: 123 Lane.
            @endif
        EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
        name: Bob
        favorite_food: Pizza
        personal_life:
            contact_info:
                phone: 1234567890
                street_info: 123 Lane.
        EOL);
});

it('can render @includeUnless', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @includeUnless(false, '$includeName')
    EOL);
    write_test_workspace_file($includeName, <<<'EOL'
    contact_info:
        phone: 1234567890
        @if($includeAddress)
        street_info: 123 Lane.
        @endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    contact_info:
        phone: 1234567890
        street_info: 123 Lane.
    EOL);
});

it('can render nested @includeUnless', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        personal_life:
            @includeUnless(false, '$includeName')
        EOL);

    write_test_workspace_file($includeName, <<<'EOL'
        contact_info:
            phone: 1234567890
            @if($includeAddress)
            street_info: 123 Lane.
            @endif
        EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
        name: Bob
        favorite_food: Pizza
        personal_life:
            contact_info:
                phone: 1234567890
                street_info: 123 Lane.
        EOL);
});

it('can render @includeFirst', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @includeFirst(['$includeName'])
    EOL);
    write_test_workspace_file($includeName, <<<'EOL'
    contact_info:
        phone: 1234567890
        @if($includeAddress)
        street_info: 123 Lane.
        @endif
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    contact_info:
        phone: 1234567890
        street_info: 123 Lane.
    EOL);
});

it('can render nested @includeFirst', function () {
    $name = Str::random(10);
    $includeName = Str::random(10);

    $path = write_test_workspace_file($name, <<<"EOL"
        name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        personal_life:
            @includeFirst(['$includeName'])
        EOL);

    write_test_workspace_file($includeName, <<<'EOL'
        contact_info:
            phone: 1234567890
            @if($includeAddress)
            street_info: 123 Lane.
            @endif
        EOL);

    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--include-address' => true,
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$name.rendered")))->toBe(<<<'EOL'
        name: Bob
        favorite_food: Pizza
        personal_life:
            contact_info:
                phone: 1234567890
                street_info: 123 Lane.
        EOL);
});
