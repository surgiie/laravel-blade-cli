<?php

namespace App\Support;

use App\Exceptions\Commands\ExitException;
use Illuminate\Console\Contracts\NewLineAware;
use Illuminate\Console\View\Components\Factory as ConsoleViewFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;
use function Termwind\renderUsing;

abstract class BaseCommand extends Command
{
    /**
     * The options that are not defined on the command.
     */
    protected Collection $arbitraryOptions;

    /**
     * Constuct a new Command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->arbitraryOptions = collect();

        // Ignore validation errors for arbitrary options support.
        $this->ignoreValidationErrors();
    }

    /**
     * Print a custom message to the console.
     */
    public function message(string $title, string $content, string $bg = 'gray', string $fg = 'white')
    {
        renderUsing($this->output);

        $path = base_path('resources/views/message.php');

        $currentViewsConfig = config('view.paths');

        config([
            'view.paths' => [base_path('resources/views')],
        ]);

        $view = View::make($path, [
            'bgColor' => $bg,
            'marginTop' => ($this->output instanceof NewLineAware && $this->output->newLineWritten()) ? 0 : 1,
            'fgColor' => $fg,
            'title' => $title,
            'content' => $content,
        ]);

        render((string) $view);

        @unlink($view->getEngine()->getCompiler()->getCompiledPath($path));

        config(['view.paths' => $currentViewsConfig]);

    }

    /**
     * Check if the command is running on windows.
     */
    public function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Expand a file path to its full path if it contains a tilde.
     */
    protected function expandTilde(?string $path): ?string
    {
        $env = $this->isWindows() ? 'USERPROFILE' : 'HOME';

        return str_replace(['~/', '~/'], [getenv($env).'/', getenv($env).'/'], $path);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->components = $this->laravel->make(ConsoleViewFactory::class, ['output' => $this->output]);

        try {
            $status = 0;
            $method = method_exists($this, 'handle') ? 'handle' : '__invoke';
            $status = (int) $this->laravel->call([$this, $method]);
        } catch (ExitException $e) {
            $level = $e->getLevel();

            $message = $e->getMessage();

            if ($message) {
                $this->components->$level($message);
            }

            $status = $e->getStatus();
        }

        return $status;
    }

    /**
     * Initialize the command input/ouput objects.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // parse arbitrary options for variable data.
        $tokens = $input instanceof ArrayInput ? invade($input)->parameters : invade($input)->tokens;
        $parser = new CommandOptionsParser($tokens);

        $definition = $this->getDefinition();

        foreach ($parser->parse() as $name => $data) {
            if (! $definition->hasOption($name)) {
                $this->arbitraryOptions->put($name, $data['value']);
                $this->addOption($name, mode: $data['mode']);
            }
        }
        //rebind input definition
        $input->bind($definition);
    }

    /**
     * Throw an exception to exit the command.
     */
    protected function exit(string $error = '', int $code = 1, string $level = 'error'): void
    {
        throw new ExitException($error, $code, $level);
    }

    /**
     * Normalize a file path from unix style to windows style, if needed.
     */
    public function normalizeFilePath(string $path)
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $path;
    }
}
