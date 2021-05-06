<?php

namespace ElmDash\Menu;

use Illuminate\Support\Str;

class Glob
{
    protected $regexes = [];

    /**
     * Turns a list of route names with wildcards into regexes
     * If the glob is already a regex, it's not transformed.
     *
     * Possible values:
     * - "users.*" (will not match "users", but matches "users.anything")
     * - "users.settings.*|account|account.edit.*"
     * - "/^users\.account\..*$/"
     *
     * @param string $glob
     * @param string $delim
     */
    public function __construct($glob, $delim = '/')
    {
        $match = ['.', '*',];
        $replace = ['\.', '.*',];

        $patterns = is_array($glob) ? $glob : explode('|', $glob);

        foreach ($patterns as $pattern) {
            if (Str::startsWith($pattern, $delim)) {
                $this->regexes[] = $pattern;
                continue;
            }
            $regex = '/^' . str_replace($match, $replace, $pattern) . '$/';
            $this->regexes[] = $regex;
        }
    }

    /**
     * @param string $routeName
     * @return bool
     */
    public function match($routeName)
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $routeName)) {
                return true;
            }
        }
        return false;
    }
}
