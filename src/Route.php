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

        $paramsMatch = true;
        $namesMatch = $currentRouteName == $this->route;

        // only check params if they are part of our route definition
        if (!empty($this->params)) {
            $paramsMatch = empty(array_diff_assoc($this->params, $currentRouteParams));
        }

        if (!$namesMatch && $this->activeGlob) {
            $namesMatch = $this->activeGlob->match($currentRouteName);
        }

        return $namesMatch && $paramsMatch;
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
