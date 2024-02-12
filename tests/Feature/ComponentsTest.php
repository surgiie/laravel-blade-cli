<?php

use Illuminate\Support\Str;

it('can render @component', function () {
    $componentFileName = Str::random(10);
    write_test_workspace_file($componentFileName, <<<'EOL'
    data: {{ $data }}
    EOL);

    $mainFileName = Str::random(10);
    $path = write_test_workspace_file($mainFileName, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @component('$componentFileName', ['data'=>'foobar'])
    @endcomponent
    favorite_numbers:
    @php(\$count = 0)
    @while (\$count < 3)
        - '{{ \$count }}'
        @php(\$count ++)
    @endwhile
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--cache-path' => test_workspace_path('cache'),
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    data: foobar
    favorite_numbers:
        - '0'
        - '1'
        - '2'
    EOL);
});

it('can render nested @component', function () {
    $componentFileName = Str::random(10);
    write_test_workspace_file($componentFileName, <<<'EOL'
    data: {{ $data }}
    nested: true
    EOL);

    $mainFileName = Str::random(10);
    $path = write_test_workspace_file($mainFileName, <<<"EOL"
    name: {{ \$name }}
        favorite_food: {{ \$favoriteFood }}
        @component('$componentFileName', ['data'=>'foobar'])
        @endcomponent
    favorite_numbers:
    @php(\$count = 0)
    @while (\$count < 3)
        - '{{ \$count }}'
        @php(\$count ++)
    @endwhile
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
    name: Bob
        favorite_food: Pizza
        data: foobar
        nested: true
    favorite_numbers:
        - '0'
        - '1'
        - '2'
    EOL);
});

it('can render component @slot', function () {
    $mainFileName = Str::random(10);
    $componentFileName = Str::random(10);

    write_test_workspace_file($componentFileName, <<<'EOL'
    data: {{ $data }}
    {{ $format ?? 'format: yaml' }}
    EOL);

    $path = write_test_workspace_file($mainFileName, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @component('$componentFileName', ['data'=>'foobar'])
    @slot('format')
    format: json
    @endslot
    @endcomponent
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    data: foobar
    format: json
    EOL);
});

it('does not indent rendered nested @slot.', function () {
    $mainFileName = Str::random(10);
    $componentFileName = Str::random(10);

    write_test_workspace_file($componentFileName, <<<'EOL'
    data: {{ $data }}
    other:
        {{ $format ?? 'format: yaml' }}
    EOL);

    $path = write_test_workspace_file($mainFileName, <<<"EOL"
    name: {{ \$name }}
    favorite_food: {{ \$favoriteFood }}
    @component('$componentFileName', ['data'=>'foobar'])
        @slot('format')
            format: json
        @endslot
    @endcomponent
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--cache-path' => test_workspace_path('cache'),
        '--favorite-food' => 'Pizza',
    ]);
    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    data: foobar
    other:
        format: json
    EOL);
});

it('can render blade x anonymous components', function () {
    $mainFileName = Str::random(10);
    $componentName = Str::random(10);
    write_test_workspace_file($componentName, <<<'EOL'
    name: {{ $name }}
    EOL);

    @mkdir(test_workspace_path('components'));

    $path = write_test_workspace_file($mainFileName, <<<"EOL"
    <x-$componentName :name='\$name' />
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
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
        '--cache-path' => test_workspace_path('cache'),
        '--component-path' => [test_workspace_path('components')],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
    name: Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('can render nested blade x anonymous components', function () {
    $mainFileName = Str::random(10);
    $componentName = Str::random(10);
    write_test_workspace_file($componentName, <<<'EOL'
    name: {{ $name }}
    EOL);

    @mkdir(test_workspace_path('components'));

    $path = write_test_workspace_file($mainFileName, <<<"EOL"
        <x-$componentName :name='\$name' />
        <x-$componentName name='Not Bob' />
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
    EOL);

    $this->artisan('render', [
        'path' => $path,
        '--name' => 'Bob',
        '--favorite-food' => 'Pizza',
        '--oldest' => true,
        '--cache-path' => test_workspace_path('cache'),
        '--component-path' => [test_workspace_path('components')],
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$mainFileName.rendered")))->toBe(<<<'EOL'
        name: Bob
        name: Not Bob
    favorite_food: Pizza
    family_info:
        oldest_child: true
    EOL);
});

it('can render blade x class components via --require', function () {
    $templateName = Str::random(10);
    $requireFileName = Str::random(10);
    $componentName = Str::random(10);
    $componentTemplateName = Str::random(10);
    $view = write_test_workspace_file($componentTemplateName, <<<'EOL'
    {{ $type }}: {{ $message }}
    EOL);

    $class = <<<"EOL"
<?php
namespace App\Views\Components;

class Alert extends \Illuminate\View\Component
{
    public \$type;
    public \$message;
    public function __construct(\$type, \$message)
    {
        \$this->type = \$type;
        \$this->message = \$message;
    }
    public function render()
    {
        return view("$view", [
            'type' => \$this->type,
            'message' => \$this->message,
        ]);
    }
}
EOL;

    write_test_workspace_file($componentName, $class);

    $path = write_test_workspace_file($templateName, <<<"EOL"
    <x-$componentName :type='\$type' :message='\$message' />
    EOL);

    $requireFile = <<<"EOL"
    <?php

    require_once __DIR__ . '/$componentName';
    use Illuminate\Support\Facades\Blade;
    use App\Views\Components\Alert;

    Blade::component('$componentName', Alert::class);
    EOL;

    $requireFile = write_test_workspace_file($requireFileName, $requireFile);
    $this->artisan('render', [
        'path' => $path,
        '--cache-path' => test_workspace_path('cache'),
        '--require' => [$requireFile],
        '--message' => 'Something went wrong!',
        '--type' => 'error',
    ])->assertExitCode(0);

    expect(file_get_contents(test_workspace_path("$templateName.rendered")))->toBe(<<<'EOL'
    error: Something went wrong!
    EOL);
});
