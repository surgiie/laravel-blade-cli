# laravel-laravel-blade-cli
Render and save textual files from the command line using Laravel's Blade engine.

![tests](https://github.com/surgiie/laravel-laravel-blade-cli/actions/workflows/tests.yml/badge.svg)

# Introduction

An PHP command-line interface for [Laravel Blade](https://laravel.com/docs/10.x/blade) to render template text files from your command line. Embed any Laravel Blade syntax in any text file and render it with this CLI. It's the perfect for generating CI/CD configuration files, configuration files, code templates, you name it.


## Installation

To install, use composer globally:

`composer global require surgiie/laravel-blade-cli`

## Use
As an example, let's say you have a file named `person.yml` in your current directory with the following content:

```yaml
name: {{ $name }}
relationship: {{ $relationship }}
favorite_food: {{ $favoriteFood }}
@if($includeAddress)
address: 123 example lane
@endif
```
You can render this file using the following command:

```bash
laravel-laravel-blade render ./person.yml \
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address

```
This will render the file and save it in the same directory with the name `person.rendered.yml` with the following contents:

```yaml
name: Bob
relationship: Uncle
favorite_food: Pizza
address: 123 example lane

```


## Rendering With Docker:

If you don't have or want to install php, you can run render files using the provided script which will run the cli's `render` command in a temporary docker container and use volumes to mount the neccessary files and then sync them back to your machine:


```bash
cd /tmp

wget https://raw.githubusercontent.com/surgiie/laravel-blade-cli/master/docker

chmod +x ./docker

mv ./docker /usr/local/bin/laravel-blade-render

laravel-blade-render <path> --var="example"
```


## Custom Filename
By default, all files will be saved to the same directory as the file being rendered with the name `<filename>.rendered.<extension>` or simply `<filename>.rendered` if no extension is present, this is to prevent overwriting the original file. To use a custom file name or change the directory, use the `--save-to` option to specify a file path:

```bash
laravel-blade render ./person.yml \
            ...
            --save-to="/home/bob/custom-name.yml"
```
**Note**: The cli will automatically create the necessary parent directories if it has permission, otherwise an error will be thrown.

## Variable Data

There are three options for passing variable data to your files being rendered, in order of precedence:

- Use YAML files with the `--from-yaml` option and pass a path to the file.
- Use JSON files with the `--from-json` option and pass a path to the file.
- Use env files with the `--from-env` option and pass a path to the .env file.
- Use arbitrary command line options with the render command, like `--example-var=value`.

**Note**: The `--from-yaml`, `--from-json`, and `--from-env` options can be passed multiple times to load from multiple files if needed.

## Variable Naming Convention

Your env, YAML, and JSON file keys can be defined in any naming convention, but the actual variable references MUST be in camel case. This is because PHP does not support kebab case variables and since this is the format used in command line options, all variables will automatically be converted to camel case. For example, if you pass an option or define a variable name in your files in any of these formats: `favorite-food`, `favoriteFood`, or `favorite_food`, the variable for that option should be referenced as `$favoriteFood` in your files.

### Command Line Variable Types

The following types of variables are currently supported:

- String/Single Value Variables: Use a single option key/value format, e.g. `--foo=bar --bar=baz`
- Array Value Variables: Pass the option multiple times, e.g. `--names=Steve --names=Ricky --names=Bob`
- True Boolean Value Variables: Pass the option with no value, e.g. `--should-do-thing`

**Note**: Since variable options are dynamic, "negate/false" options are not supported. Instead, use something like `{{ $shouldDoSomething ?? false }}` in your files to default to false and use true options to "negate" the value.

## Force Write
If you try to render a file that already exists, an exception will be raised. To force overwrite an existing file, use the --force flag:

```bash
laravel-blade render ./person.yml \
                --name="Bob" \
                --relationship="Uncle" \
                --favorite-food="Pizza" \
                --include-address \
                --force # force overwrite person.rendered.yml if it already exists.
```
## Dry Run/Show Rendered Contents
To view the contents of a rendered file without saving it, use the --dry-run flag when rendering a single file:

`laravel-blade render example.yaml --some-var=example --dry-run`

This will display the contents of example.yaml on the terminal without saving it.

## Processing an entire directory of files
You can also pass a directory path instead of a single file when running the command. This can be useful when you want to render multiple template files at once.

`laravel-blade render ./templates --save-dir="/home/bob/templates" --some-data=foo`

**Note**: This command will prompt you for confirmation. To skip confirmation, add the `--force` flag.

**Note**: When rendering an entire directory, the `--save-dir` option is required to export all rendered files to a separate directory. The directory structure of the directory being processed will be mirrored in the directory where the files are saved. In the above example, `/home/bob/templates` will have the same directory structure as `./templates`.


## Cached/Compiled Directory

When compiling a file down to plain php, the compiled/cache file is stored in the following possible locations depending on how the cli is being used:

- If using the standalone/phar build of the cli: `/tmp/.laravel-blade-cli`:
- If using the docker build of the cli, the compiled files use the default location but doesnt matter as they are removed after the container is stopped so the compiled files are not persisted.
- If cloned from the repository and running the cli from the source code, the compiled files are stored in the `storage/framework/compiled` directory.

### Clean Cached Compiled Directory

If you are working with large files or have a lot of files to render, the cached/compiled files directory can grow quite large, consider cleaning it regularly.

To clean the cached/compiled files directory of files older than 24 hours, use the `cache:clean` command:

```bash
laravel-blade cache:clean
```

#### Clean Cached Compiled Directory By Custom Age

To clean the cached/compiled files directory of files older than a specific number of minutes, use the `--expires-minutes` option, for example, to clean files older than 60 minutes:

```bash
laravel-blade cache:clean --expires-minutes=60
```
#### Force Clean Cached Compiled Directory

To force remove all fileds from cached/compiled files directory regardless of age, use the `--force` flag:

```bash
laravel-blade cache:clean --force
```

## Require Files Before Rendering

If using `x-` blade components in your files, or you have some custom classes or logic you need to execute before rendering a file, you can use the `--require` option to require a file before rendering. This can be useful for loading custom classes or functions that can be used in your files. For convenience, a special `$__command` variable is available in the required file which contains the command instance, this can be useful for accessing the command's options and arguments or if you need output text to the console. Your variable data will also be available in the required file. You may also find the need to mutate the variable data before rendering, you can do this in the required file by returning the mutated variables in an array to be merged into the existing variable data.

For example, if you have a file named `required-file.php` with the following content:

```php

```bash
larave-blade render template.yaml --name="Bob" --require="required-file.php"
```

```bash
<?php

if($name == "Bob") {
    $name = "Uncle Bob";
}

// do more stuff

$__command->info("Did stuff!");

// return mutated variables, will be merged to existing variables
// if no mutation is needed, the return statement is not necessary
return [
    "name" => $name,
];

```

## Dry Run/Show Rendered Contents
To view the contents of a rendered file without saving it, use the --dry-run flag when rendering a single file:

`laravel-blade render example.yaml --some-var=example --dry-run`

This will only display the contents of example.yaml and wont save a rendered file.

## Processing An Entire Directory Of Files

You can also pass a directory path instead of a single file when running the command. This can be useful when you want to render multiple template files at once.

`laravel-blade render ./templates --save-to="/home/bob/templates" --some-data=foo`

**Note**: When rendering an entire directory, the `--save-to` option is required to export all rendered files to a separate directory. The directory structure of the directory being processed will be mirrored in the directory where the files are saved. In the above example, `/home/bob/templates` will have the same directory structure as `./templates`.


### X-Blade Components

### Anonymously Rendered Components

If you are using  anonoymous `x-` blade components in your files, you must specify the `--component-path` option to specify a path to where your components are located.

For example passing `--component-path="/home/components"` will look for components in `/home/components` and render them. For example, if you have a component named `example.blade.php`

in the components directory, you can use it in your files like so:

```yaml
<x-example :name="$name" />
```

### Namespacing Anonymously Components

If you wish to use namespace components such as `<x-namespace::component-name />` you can use a `:` delimiter to specify the namespace and component name in the `--component-path` option.

For example, `--component-path="namespace:/home/components"`.


### Contribute

Contributions are always welcome in the following manner:

-   Issue Tracker
-   Pull Requests
-   Discussions

### License

The project is licensed under the MIT license.
