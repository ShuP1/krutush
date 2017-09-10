<?php

namespace Krutush;

class App{
	/**
	 * Current Router
	 * @var Router
	 */
	protected $router;
	
	/**
	 * Get a copy of Router
	 * @return Router
	 */
	public function getRouter(): Router{
		return clone $this->router;
	}
	
	/**
	 * Config storage
	 * @var array
	 */
	protected $config = array(
		'namespace' => '',
		'controller' => 'Controller',
		'debug' => true
	);

	/**
	 * Needed config values
	 * @var array
	 */
	protected static $configMap = array(
		'namespace' => true,
		'controller' => false,
		'debug' => false
	);

	public function __construct(array $data = array()){		
		foreach(static::$configMap as $var => $needed){
			if(isset($data['app'][$var])){
				$this->config[$var] = $data['app'][$var];
			}else{
				if($needed)
					trigger_error('app.'.$var.' must be define in your app');
			}
		}

		//TODO error handler
		//if($this->config['debug'])			

		if(isset($data['path'])){
			Path::sets($data['path']);
		}else{
			trigger_error('path.root must be define in your app');
		}

		$this->router = new Router(isset($data['router']) ? $data['router'] : Path::get('config').'/Routes.php');
	}
	
	public function run(string $uri = null, array $filters = array()){
		$route = $this->router->run(($uri ?: $_SERVER['REQUEST_URI']), $filters);

		if(!isset($route))
			$this->error(new HttpException(404));

		try{
			$route->call($this, $this->config['namespace'], $this->config['controller']);
		}catch(HttpException $e){
			if(!$this->config['debug'])
				$e = new HttpException($e->getHttpCode(), 'Server internal error');
			$this->error($e);
		}catch(\Exception $e){
			$this->error(new HttpException(500, $this->config['debug'] ? $e->getMessage() : 'Server internal error'));
		}
	}

	public function error(HttpException $e){
		http_response_code($e->getHttpCode());
		echo $e->getMessage();
		exit;
	}
}