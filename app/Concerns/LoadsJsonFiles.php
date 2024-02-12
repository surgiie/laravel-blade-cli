<?php

namespace App\Concerns;

use App\Exceptions\Commands\ExitException;

trait LoadsJsonFiles
{
    /**
     * Return a more human readable error message for json errors.
     */
    protected function formatJsonParseError(string $error): string
    {
        switch ($error) {
            case JSON_ERROR_DEPTH:
                return 'JSON Error - Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'JSON Error - Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'JSON Error - Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'JSON Error - Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'JSON Error - Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'JSON Error - Unknown error';
        }
    }

    /**
     * Loads a json file and returns the decoded json.
     */
    public function loadJsonFile(string $path, $options = JSON_OBJECT_AS_ARRAY): array
    {
        if (! is_file($path)) {
            throw new ExitException("The json file '$path' does not exist.");
        }

        $data = json_decode(file_get_contents($path), $options);

        $error = json_last_error();

        if ($error !== JSON_ERROR_NONE) {
            throw new ExitException($this->formatJsonParseError(json_last_error()));
        }

        return $data;
    }
}
