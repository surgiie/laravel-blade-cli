<?php

namespace App\Commands;

use App\Concerns\LoadsEnvFiles;
use App\Concerns\LoadsJsonFiles;
use App\Support\BaseCommand;
use ErrorException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\text;

class RenderCommand extends BaseCommand
{
    use LoadsEnvFiles, LoadsJsonFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'render
                            {path? : The file or directory path to render and save file(s) for. }
                            {--require=* : Require a php file to autoload/execute scripts before render. }
                            {--component-path=* : Specify a directory of where to load x blade components from. }
                            {--cache-path= : The custom directory path to save cached/compiled files to. }
                            {--save-to= : The custom file or directory path to save the rendered file(s) to. }
                            {--from-yaml=* : A yaml file path to load variable data from. }
                            {--from-json=* : A json file path to load variable data from. }
                            {--cache-path= : Custom directory for the compiled/cached files. }
                            {--from-env=* : A .env file to load variable data from. }
                            {--dry-run : Print out rendered file contents only. }
                            {--no-cache : Force recompile file & dont keep compiled file after render. }
                            {--force : Force render or overwrite existing files.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Render a file or directory of files with the laravel blade template engine.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Determine the path to the file or directory of files to render.
        $givenPath = $this->argument('path') ?: text(
            label: 'What is the path to the file or directory of files you want to render?',
            placeholder: '/home/example/template.yaml',
        );

        // use custom cache path if desired.
        if ($this->option('cache-path')) {
            config(['view.compiled' => $this->option('cache-path')]);
        }

        foreach ($this->option('component-path') as $path) {
            try {
                [$prefix, $path] = explode(':', $path, 2);
            } catch (ErrorException) {
                $prefix = null;
            }

            if (! is_dir($path)) {
                $this->exit("The components path directory '$path' does not exist.");
            }
            Blade::anonymousComponentPath(realpath($path), $prefix);
        }

        // load variables for render
        $vars = $this->gatherVariables($this->arbitraryOptions->all());

        // load the required files.
        foreach ($this->option('require') as $file) {
            if (! file_exists($file)) {
                $this->exit("The require file '$file' does not exist.");
            }
            $vars = $this->requireFile($file, $vars);
        }

        if (is_dir($givenPath)) {
            $this->renderDirectoryFiles($givenPath, $vars, $this->option('save-to'));
        } else {
            $this->renderFile(
                $givenPath,
                $vars,
                $this->option('dry-run') ? false : $this->option('save-to'),
            );
        }

        return 0;
    }

    /**
     * Render files in a directory.
     */
    protected function renderDirectoryFiles(string $path, array $variables, ?string $saveDirectory = null)
    {
        // // Ensure the path being processed isn't the same as the save directory
        if ($path === $saveDirectory || rtrim($path, DIRECTORY_SEPARATOR) === rtrim($saveDirectory, DIRECTORY_SEPARATOR)) {
            $this->exit('The path being processed is also the --save-to directory, use a different save directory.');
        }

        if (! $this->option('dry-run') && is_null($saveDirectory)) {
            $this->exit('The --save-to directory option is required for rendering a directory of files.');
        }

        // Check if save directory already exists and confirm overwrite
        if (! $this->option('dry-run') && is_dir($saveDirectory) && $path !== $saveDirectory && ! $this->option('force', false)) {
            $this->exit("The save to directory '$saveDirectory' already exists, use --force to overwrite.");
        }

        (new Filesystem)->deleteDirectory($saveDirectory, preserve: true);

        $saveDirectory = rtrim($saveDirectory, DIRECTORY_SEPARATOR);

        foreach ((new Finder())->in($path)->files() as $file) {
            // compute a save directory that mirrors the directory structure of the file
            $relativePath = Str::after($pathName = $file->getPathName(), $path);
            $fileSaveTo = dirname("$saveDirectory/$relativePath/{$file->getFileName()}");
            $fileSaveTo = str_starts_with($saveDirectory, '/') ? $fileSaveTo : getcwd().'/'.ltrim($fileSaveTo, '/');

            $this->renderFile(
                realpath($pathName),
                $variables,
                $this->option('dry-run') ? false : $fileSaveTo,
            );
        }
    }

    /**
     * Require a file and pass the variables to it.
     */
    protected function requireFile(string $path, array $variables = [])
    {
        // Extract the variables to a local namespace
        $variables['__command'] = $this;

        extract($variables);

        $updatedVariables = require $path;

        unset($variables['__command']);

        return ! is_array($updatedVariables) ? $variables : array_merge($variables, $this->normalizeVariableNames($updatedVariables));
    }

    /**
     * Compute the default save file name for the given file path.
     */
    protected function getDefaultSaveFileName(string $path): string
    {
        $info = new SplFileInfo($path);

        $basename = $info->getBasename('.'.$ext = $info->getExtension());

        if (strpos($basename, '.') === 0 && ".$ext" == $basename) {
            return $basename.'.rendered';
        } else {
            $basename .= '.rendered';
        }

        return $basename.($ext ? '.'.$ext : '');
    }

    /**
     * Compute a save path for the given file path.
     *
     * @return string
     */
    protected function computeSavePath(string $path)
    {
        $info = (new SplFileInfo($path));
        $saveDirectory = dirname($info->getRealPath()).DIRECTORY_SEPARATOR;

        return $this->expandTilde($saveDirectory.$this->getDefaultSaveFileName($path));
    }

    /**
     * Normalize variable names to camel case.
     */
    protected function normalizeVariableNames(array $vars = []): array
    {
        $variables = [];
        foreach ($vars as $k => $value) {
            $variables[Str::camel(strtolower($k))] = $value;
        }

        return $variables;
    }

    /**
     * Register possible view path in the view's configuration for
     * the engine to be able to find the file in the given path. Returns
     * the full directory of the file path.
     */
    public function registerFilePath(string $path): string
    {
        // dont use realpath on phar file paths as it will always be false, since phar files are virtual.
        // TODO:  $directory = str_starts_with($path, 'phar://') ? dirname($path) : realpath(dirname($path));
        $directory = realpath(dirname($path));

        config(['view.paths' => array_merge(config('view.paths'), [$directory])]);

        return $directory;
    }

    /**
     * Gather the variables needed for rendering.
     */
    protected function gatherVariables(): array
    {
        $variables = [];

        foreach ( $this->option('from-yaml', []) as $file) {
            $variables = array_merge($variables, Yaml::parseFile($file));
        }

        foreach ( $this->option('from-json', []) as $file) {
            $variables = array_merge($variables, $this->loadJsonFile($file));
        }

        foreach ($this->option('from-env', []) as $file) {
            $variables = array_merge($variables, $this->getEnvFileVariables($file));
        }

        return $this->normalizeVariableNames(array_merge($variables, $this->arbitraryOptions->all()));
    }

    /**
     * Render the given file using the given variables.
     */
    public function renderFile(string $path, array $vars = [], ?string $saveTo = null): string
    {
        $currentDirectory = getcwd();

        if ($noCache = $this->option('no-cache')) {
            config(['view.cache' => false]);
        }
        // parse the given path and register the file's directory
        $parsedPath = realpath($this->expandTilde($this->normalizeFilePath($path)));

        if (! file_exists($parsedPath)) {
            $this->exit("The file or directory '$path' does not exist.");
        }

        $saveTo = $saveTo === false ? false : (! is_null($saveTo) ? $saveTo : $this->computeSavePath($parsedPath));

        if ($this->option('dry-run')) {
            $saveTo = false;
        }

        if (is_file($saveTo) && ! $this->option('force')) {
            $this->exit("The file '$saveTo' already exists. Use --force to overwrite.");
        }

        if ($this->option('cache-path')) {
            config(['view.compiled' => $this->option('cache-path')]);
        }

        // register the file's directory with the engine and change into it
        // so that file paths and the context of the file is relative.
        chdir($this->registerFilePath($parsedPath));

        try {
            $view = View::make($parsedPath, $vars);

            $contents = $view->render();

        } catch (\Throwable $e) {
            if (in_array(config('app.env'), ['development', 'testing'])) {
                throw $e;
            }
            $this->exit($e->getMessage());
        }

        if ($noCache) {
            @unlink($view->getEngine()->getCompiler()->getCompiledPath($parsedPath));
        }

        chdir($currentDirectory);

        if ($saveTo === false) {

            $this->showDryRun($contents, $parsedPath);

            return $contents;
        }

        $saveDirectory = dirname($saveTo);

        if (! is_dir($saveDirectory)) {
            @mkdir($saveDirectory, recursive: true);
        }

        if (! is_writable($saveDirectory)) {
            $this->exit("The save directory $saveDirectory is not writable.");
        }

        @mkdir($saveDirectory, recursive: true);

        file_put_contents($saveTo, $contents);
        $saveTo = str_replace("//", "/", $saveTo);

        $this->components->info("Rendered file: $saveTo");

        return $contents;
    }

    /**
     * Show the rendered file contents if dry-run option is set.
     */
    protected function showDryRun(string $contents, string $path)
    {
        if (! $this->option('dry-run')) {
            return $contents;
        }

        $this->message('DRY RUN', "Template file: $path");
        foreach (explode(PHP_EOL, $contents) as $line) {
            $this->line('  '.$line);
        }
        $this->line('');
    }
}
