<?php

namespace ElmDash\Menu;

use Illuminate\Support\Facades\Auth;

class Menu
{
    protected $name;
    protected $params = [];
    protected $label;
    protected $langNamespace;
    protected $flags = [];

    /** @var Route */
    protected $route;

    // require authentication by default
    protected $protected = true;

    /** @var Menu */
    protected $parent;
    protected $children = [];
    protected $depth = 1;

    protected $activeCache;

    /**
     * Create a new menu item
     *
     * @param string $name Top-level only name of menu
     * @param string $route Name of route to render
     * @param Menu $parent
     */
    public function __construct($name = null, $route = null, Menu $parent = null)
    {
        if ($parent) {
            $this->route = new Route($route);
            $this->parent = $parent;
            $this->depth = $parent->depth + 1;
        }
        if ($name) {
            $this->name = $name;
        }
    }

    /**
     * Get all the visible children for this menu item
     * You should only call this in the request cycle,
     * NOT from a service provider.
     *
     * @return array
     */
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
        $m = new Menu(false, $route, $this);
        $this->children[] = $m;
        if ($fn) {
            $fn($m);
        }
        return $m;
    }

    /**
     * Generates a URL for this item
     *
     * @param bool $absolute
     * @return string
     */
    public function href($absolute = false)
    {
        return $this->route->href($absolute);
    }

    /**
     * Check if this item has a flag
     *
     * @param string|array $flags
     * @return bool
     */
    public function is($flags)
    {
        if (!is_array($flags)) {
            $flags = [$flags];
        }
        return !empty(array_intersect($this->flags, $flags));
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
     * @param string $label
     * @return $this|string
     */
    public function label($label = null)
    {
        if ($label) {
            $this->label = $label;
            return $this;
        }

        if ($this->label) {
            return $this->label;
        }

        $keyParts = ['menu'];
        $name = $this->root()->name;
        if ($name) {
            $keyParts[] = $name;
        }
        $keyParts[] = str_replace('.', '-', $this->route->getName());

        $key = implode('.', $keyParts);

        $ns = $this->deriveLangNamespace();
        if ($ns) {
            $key = "$ns::$key";
        }

        return trans($key);
    }

    /**
     * Get or set the route for this menu item
     *
     * @param string $route
     * @return Menu|string
     */
    public function route($route = null)
    {
        if ($route) {
            $this->route = new Route($route);
            return $this;
        }
        return $this->route;
    }

    /**
     * Adds param values for generating the route.
     *
     * example:
     * // route = /blog/post/list/{type}
     * $menu->add('post.list')->params(['type' => 'featured']);
     *
     * @param array $params
     * @return $this
     */
    public function params($params = [], $replace = false)
    {
        $this->route->params($params, $replace);
        return $this;
    }

    /**
     * Set flags for checking items in the view later with is().
     *
     * @param array $flags
     * @param bool $replace
     * @return $this
     */
    public function flags($flags = [], $replace = false)
    {
        if (!is_array($flags)) {
            $flags = [$flags];
        }
        if ($replace) {
            $this->flags = $flags;
            return $this;
        }
        $this->flags = array_merge($this->flags, $flags);
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
        $this->protected = !$bool;
        return $this;
    }

    /**
     * If a route matches this glob, then it will
     * be considered active. If params have been
     * provided, those params will also need to match.
     *
     * @param string $glob
     * @param string $delim If using a real regex, specify delim here
     * @return $this
     */
    public function activeFor($glob, $delim = '/')
    {
        $this->route->activeFor($glob, $delim);
        return $this;
    }

    /**
     * Determines if this item is active
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->activeCache === null) {
            $this->activeCache = $this->route->isActive();
        }
        return $this->activeCache;
    }

    /**
     * Items are visible by default. If an item is
     * marked as guests-only, it will be hidden if the
     * user is logged in. Items are also hidden if all
     * their children are hidden.
     *
     * @return bool
     */
    protected function isVisible()
    {
        $loggedIn = Auth::check();
        if ($this->protected && !$loggedIn) {
            return false;
        }

        if (!$this->protected && $loggedIn) {
            return false;
        }

        if (!$this->route->isValid()) {
            return false;
        }

        // if we have children, but none are visible,
        // we should not be visible either
        if (!empty($this->children) && empty($this->children())) {
            return false;
        }

        return true;
    }


    /**
     * Gets namespace set on this item or look for the namespace
     * up the tree of parents.
     *
     * @return bool|string
     */
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

    protected function root()
    {
        if (!$this->parent) {
            return $this;
        }
        return $this->parent->root();
    }
}
