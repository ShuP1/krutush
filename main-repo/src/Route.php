<?php

namespace Krutush;

/** Based on Grafikart Router */
class Route{
	/**
	 * Url
	 * @var string
	 */
	private $path;

	/**
	 * Function to run or 'Controller#function'
	 * @var callable|string
	 */
    private $callable;

	/** @var string */
	private $name;

	/**
	 * string[] Params in url
	 * @var array
	 */
    private $matches = array();

	/**
	 * string[] regex Match for params
	 * @var array
	 */
    private $params = array();

	/**
	 * Others things
	 * @var array
	 */
	private $filters = array();

	/**
	 * Default value of $this->filters
	 * @var array
	 */
	private static $defaultFilters = array();

	/**
	 * Register a new Route
	 * @param string $path Url from Root
	 * @param callable|string $callable Function to run or 'Controller#function'
	 */
    public function __construct(string $path, $callable){
        $this->path = trim($path, '/');
        $this->callable = $callable;
		$this->filters = static::$defaultFilters;
    }
	
	/**
	 * My name is [...]
	 * @param string $name
	 * @return Route
	 */
	public function name(string $name): Route{
		$this->name = $name;
		return $this;
	}

	/**
	 * Add a filter
	 * @param string $name
	 * @param array $values
	 * @return Route
	 */
	public function filter(string $name, array $values): Route{
		$this->filters[$name] = $values;
		return $this;
	}

	/**
	 * Params match regex
	 * @param string $param
	 * @param string $regex
	 * @return Route
	 */
	public function with(string $param, string $regex): Route{
		$this->params[$param] = str_replace('(', '(?:', $regex);
		return $this;
	}

	/**
	 * Check url
	 * @param string $url
	 * @param array $filters
	 * @return bool
	 */
    public function match(string $url, array $filters = array()): bool{
		//Check filters
		foreach($filters as $key => $value){
			if(!isset($this->filters[$key]))
				return false;

			if(!in_array($value, $this->filters[$key]))
				return false;
		}
		
		//Check url
		$url = trim($url, '/');
		$path = preg_replace_callback('#:([\w]+)#', array($this, 'paramMatch'), $this->path);
		$regex = "#^$path$#i";
		if(!preg_match($regex, $url, $matches))
			return false;

		array_shift($matches);
		$this->matches = $matches;
		return true;
	}

	private function paramMatch($match){
		if(isset($this->params[$match[1]])){
			return '(' . $this->params[$match[1]] . ')';
		}
		return '([^/]+)';
	}
	
	/**
	 * Build Url with params
	 * @param array $params
	 * @return string
	 */
	public function getUrl(array $params = array()): string{
		$path = $this->path;
		foreach($params as $k => $v){
			$path = str_replace(":$k", $v, $path);
		}
		return $path;
	}
	
	/**
	 * Check name
	 * @param string $name
	 * @return bool
	 */
	public function matchName(string $name): bool{
		return $this->name == $name;
	}
	
	/**
	 * Run Route
	 * @param App $app
	 * @param string $namespace
	 * @param string $prefix
	 */
	public function call(App $app, string $namespace, string $prefix = "Controller"){
		//is a Controller
		if(is_string($this->callable)){
			$params = explode('#', $this->callable);
			$params[0] = ((isset($namespace) && strpos($params[0], '\\') === false) ? ($namespace.$params[0].$prefix) : $params[0]);
			if(!class_exists($params[0]))
				throw new HttpException(404, 'Class Not Found : '.$params[0]);
			$controller = new $params[0]($app);
			if(!method_exists($controller,$params[1]))
				throw new HttpException(404, 'Function Not Found : '.$params[0].'::'.$params[1]);
			return call_user_func_array(array($controller, $params[1]), $this->matches);
		} else {
			return call_user_func_array($this->callable, $this->matches);
		}
	}

	/**
	 * Set default filters
	 * @param array $array
	 * @return void
	 */
	public static function filters(array $array = array()){
		static::$defaultFilters = $array;
	}
}