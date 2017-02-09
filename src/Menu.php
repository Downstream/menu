<?php

namespace ElmDash;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class Menu
{
    protected $route;
    protected $params = [];
    protected $depth = 1;
    protected $match = [];
    protected $label;

    protected $langNamespace;

    // require authentication by default
    protected $protected = true;

    /** @var Menu */
    protected $parent;
    protected $children = [];

    public function __construct($route = null, Menu $parent = null)
    {
        $this->route = $route;
        if ($parent) {
            $this->parent = $parent;
            $this->depth = $parent->depth + 1;
        }
    }

    public function children()
    {
        return array_filter($this->children, function (Menu $m) {
            return $m->isVisible();
        });
    }

    /**
     * Adds an item to this menu
     *
     * @param string $route
     * @param \Closure|null $fn
     * @return Menu
     */
    public function add($route = null, $fn = null)
    {
        $m = new Menu($route, $this);
        $this->children[] = $m;
        if ($fn) {
            $fn($m);
        }
        return $m;
    }

    /**
     * Generates a URL for this item
     *
     * @return string
     */
    public function href()
    {
        if (!$this->route) {
            return '#';
        }
        return route($this->route, $this->params);
    }

    /**
     * Set or get the lang namespace
     *
     * @param string $namespace
     * @return string|$this
     */
    public function langNamespace($namespace = null)
    {
        if ($namespace) {
            $this->langNamespace = $namespace;
            return $this;
        }
        return $this->langNamespace;
    }

    /**
     * Set or get the label
     *
     * @param bool $label
     * @return $this|string
     */
    public function label($label = false)
    {
        if ($label) {
            $this->label = $label;
            return $this;
        }

        if ($this->label) {
            return $this->label;
        }

        $key = "menu.{$this->route}";

        $ns = $this->deriveLangNamespace();
        if ($ns) {
            $key = "$ns::$key";
        }

        return trans($key);
    }

    public function route($route = false)
    {
        if ($route) {
            $this->route = $route;
            return $this;
        }
        return $this->route;
    }

    /**
     * Adds params for generating the route
     *
     * @param array $params
     * @return $this
     */
    public function params($params = [])
    {
        $this->params = $params;
        return $this;
    }

    /**
     * If true, this item will only be visible if the
     * user has not been authenticated.
     *
     * @param bool $bool
     * @return $this
     */
    public function guests($bool = true)
    {
        $this->protected = ! $bool;
        return $this;
    }

    /**
     * If a route matches this glob, then it will
     * be considered active. If params have been
     * provided, those params will also need to match.
     *
     * Possible values:
     * - "users.*" (will not match "users", but matches "users.anything")
     * - "users.settings.*|account|account.edit.*"
     * - "/^users\.account\..*$/"
     *
     * @param string $glob
     * @param string $delim If using a real regex, specify delim here
     * @return $this
     */
    public function match($glob, $delim = '/')
    {
        static $match = ['.', '*',];
        static $replace = ['\.', '.*',];

        $patterns = explode('|', $glob);
        $regexes = [];

        foreach ($patterns as $pattern) {
            if (starts_with($pattern, $delim)) {
                $regexes[] = $pattern;
                continue;
            }
            $regex = '/^' . str_replace($match, $replace, $pattern) . '$/';
            $regexes[] = $regex;
        }
        $this->match = $regexes;
        return $this;
    }


    /**
     * Determines if this item is active
     *
     * @return bool
     */
    public function isActive()
    {
        /** @var \Illuminate\Routing\Route $routeObj */
        $routeObj = Route::current();
        $currentRouteName = $routeObj->getName();
        $currentRouteParams = $routeObj->parameters() ?? [];

        $paramsMatch = false;
        $namesMatch = $currentRouteName == $this->route;

        if (!empty($currentRouteParams) && !empty($this->routeParams)) {
            $paramsMatch = empty(array_diff_assoc($this->routeParams, $routeObj->parameters()));
        } else if (empty($currentRouteParams) && empty($this->routeParams)) {
            $paramsMatch = true;
        }

        if (!$namesMatch) {
            foreach ($this->match as $regex) {
                if (preg_match($regex, $currentRouteName)) {
                    $namesMatch = true;
                    break;
                }
            }
        }

        return $namesMatch && $paramsMatch;

    }

    protected function isVisible()
    {
        $loggedIn = Auth::check();
        if ($this->protected && !$loggedIn) {
            return false;
        }

        if (!$this->protected && $loggedIn) {
            return false;
        }

        // if we have children, but none are visible,
        // we should not be visible either
        if (!empty($this->children) && empty($this->children())) {
            return false;
        }

        return true;
    }

    protected function deriveLangNamespace()
    {
        if ($this->langNamespace) {
            return $this->langNamespace;
        }
        if ($this->parent) {
            return $this->parent->deriveLangNamespace();
        }
        return false;
    }
}
