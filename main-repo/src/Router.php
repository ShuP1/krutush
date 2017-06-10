<?php

namespace Krutush;

/**
 * Load Controller From Url
 */
class Router{
	/**
	 * Route[]
	 * @var array
	 */
    private $routes = array();
	
	/**
	 * @param string $path
	 * Path to a list of routes.
	 * See exemple/src/Config/Routes.php
	 */
	public function __construct(string $path = null){
		if(isset($path)){
			$r = $this; //4 is too much
			include($path); //unsafe...
		}
    }

	/**
	 * Register a new Route
	 *
	 * @param string $path Url from Root
	 * @param callable|string $callable Function to run or 'Controller#function'
	 * @return Route
	 */
    public function add(string $path, $callable):Route{
		$route = new Route($path, $callable);
		$this->routes[] = $route;
		return $route;
	}
	
	/**
	 * Get (first) Route matching url and filters
	 *
	 * @param string $url
	 * @param array $filters
	 * @return Route|null
	 */
	public function run(string $url, array $filters):?Route{
		foreach($this->routes as $route){
			if($route->match($url, $filters))
				return $route;
		}
		return null;
	}
	
	/**
	 * Get (first) Route by name
	 *
	 * @param string $name
	 * @return Route|null
	 */
	public function get(string $name):?Route{
        foreach($this->routes as $route){
			if($route->matchName($name))
				return $route;
		}
		return null;
    }

	/**
	 * Redirect helper
	 *
	 * @param string $url
	 * @param bool $stop
	 * @return void
	 */
	public static function redirect(string $url, bool $stop = true){
		header('Location: '.$url);
  		if($stop)
			exit();
	}
}