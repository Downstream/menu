<?php

namespace ElmDash\Menu;

use Illuminate\Support\Facades\Route as Router;

class Route
{
    protected $route;
    protected $params = [];
    /** @var Glob */
    protected $activeGlob;

    public function __construct($name)
    {
        $this->route = $name;
    }

    public function getName()
    {
        return $this->route;
    }

    /**
     * Explicitly set parameters for use when generating the URL
     *
     * @param array $params
     * @param bool $replace
     */
    public function params($params = [], $replace = false)
    {
        if ($replace) {
            $this->params = $params;
        } else {
            $this->params = array_merge($this->params, $params);
        }
    }

    /**
     * Generates a URL for this item
     *
     * @param bool $absolute
     * @return string
     */
    public function href($absolute = false)
    {
        if (!$this->route) {
            return '#';
        }
        return route($this->route, $this->buildParams(), $absolute);
    }

    /**
     * Determines if this item is active
     *
     * @return bool
     */
    public function isActive()
    {
        /** @var \Illuminate\Routing\Route $routeObj */
        $routeObj = Router::current();
        $currentRouteName = $routeObj->getName();
        $currentRouteParams = $routeObj->parameters() ?? [];

        // at a minimum the name must match
        if ($currentRouteName != $this->route) {
            if (!$this->activeGlob) {
                return false;
            }
            if (!$this->activeGlob->match($currentRouteName)) {
                return false;
            }
        }

        if (empty($this->params)) {
            return true;
        }

        // compare apples to apples to allow optional params (i.e. ['param1' => null])
        $requiredParams = array_keys($this->params);
        $default = array_fill_keys($requiredParams, null);
        $relevantCurrentParams = array_only($currentRouteParams, $requiredParams);
        $currentParams = array_merge($default, $relevantCurrentParams);
        return $currentParams == $this->params;
    }

    public function activeFor($glob, $delim = '/')
    {
        $this->activeGlob = new Glob($glob, $delim);
    }

    public function isValid()
    {
        $ourRouteObj = Router::getRoutes()->getByName($this->route);
        if (!$ourRouteObj) {
            return false;
        }

        return true;
    }

    /**
     * Gets all parameters available for building our URL
     *
     * @return array
     */
    protected function buildParams()
    {
        /** @var \Illuminate\Routing\Route $routeObj */
        $routeObj = Router::current();
        $ourRouteObj = Router::getRoutes()->getByName($this->route);
        $currentRouteParams = [];
        if ($routeObj->hasParameters()) {
            $currentRouteParams = $routeObj->parameters();
        }

        $allowed = array_only($currentRouteParams, $ourRouteObj->parameterNames());

        return array_merge($allowed, $this->params);
    }
}
