<?php

namespace App\Engine;

use Illuminate\View\Compilers\BladeCompiler;

class FileCompiler extends BladeCompiler
{
    /**
     * Unindent directives that have leading space.
     * This is necessary to ensure inner content is
     * indented correctly.
     */
    protected function unindentDirectives(string $template): string
    {
        $directives = implode('|', [
            'if',
            'endif',
            'component',
            'push',
            'endpush',
            'foreach',
            'endforeach',
            'forelse',
            'empty',
            'endforelse',
            'for',
            'endfor',
            'while',
            'endwhile',
            'php',
            'pushIf',
            'endPushIf',
            'switch',
            'case',
            'break',
            'endswitch',
        ]);

        // unindent directives that have leading space
        return preg_replace_callback('/^(\h+)@('.$directives.')/mu', function ($match) {
            return ltrim($match[0]);
        }, $template);
    }

    /**
     * Modify compiled directives that render content to include
     * a function call that will indent the lines of the rendered
     * content.
     */
    protected function indentCompiledRenderedDirectives(string $template): string
    {
        return preg_replace_callback('/^(\h*)\<\?php (.*) \$__env->(yieldContent|renderComponent|make|renderWhen|renderUnless|first)\(.*\); \?\>/mu', function ($match) {
            $replacement = $match[0].PHP_EOL;

            if (($spacingTotal = strlen($match[1])) <= 0) {
                return $replacement;
            }

            return ltrim(str_replace([
                '$__env->make',
                '$__env->first',
                '$__env->renderWhen',
                '$__env->renderComponent',
                '$__env->yieldContent',
                '$__env->renderUnless',
                '; ?>',
            ], [
                'indent_lines($__env->make',
                'indent_lines($__env->first',
                'indent_lines($__env->renderWhen',
                'indent_lines($__env->renderComponent',
                'indent_lines($__env->yieldContent',
                'indent_lines($__env->renderUnless',
                ", $spacingTotal); ?>",
            ], $replacement));

            return $replacement;

        }, $template);
    }

    /**
     * Compile Blade statements that start with "@".
     *
     * @param  string  $template
     * @return string
     */
    protected function compileStatements($template)
    {
        // Some directives that have leading space need to be moved to the start of the line
        // in order for the inner content to be indented correctly. We will modify the directives
        // before having the engine compile the template.
        $template = $this->unindentDirectives($template);

        // next after statements have been compiled, we need to modify
        // compiled directives that render or yield content to have line endings
        // and include a function call that will indent each line of the rendered content
        // the same number of spaces next to the directive. This allows the developer
        // to nest those directives and the rendered content will have the same indentation
        // as the include directive.
        $template = $this->indentCompiledRenderedDirectives(parent::compileStatements($template));

        return $template;
    }
}
