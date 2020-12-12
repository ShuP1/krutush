<?php

namespace Krutush\Host;

class Host{
	/**
	 * Hosts and data
	 * @var array
	 */
	private $hosts = array();
	
	protected $url = false;
	protected $domain = false;
	
	private $isSet = false;
	
	public function __construct(string $path = null){
		if(isset($path))
        	$this->hosts = include($path);
    }
	
	public function getUrl(){
		if($this->isSet === false){
			$this->update();
		}
		return $this->url;
	}
	
	public function getDomain(){
		if($this->isSet === false){
			$this->update();
		}
		return $this->domain;
	}

	public function getData(string $key, string $domain = null){
		$domain = $domain ?: $this->getDomain();
		if(!isset($this->hosts[$domain]['data'][$key]))
			return null;

		return $this->hosts[$domain]['data'][$key];
	}
	
	public function update(){
		$this->url = false;
		$this->domain = false;
		$this->isSet = true;
	
		$host = $_SERVER['HTTP_HOST'] ?: $_SERVER['SERVER_NAME'];
		if(!isset($host))
			return false;
		
		$host = preg_replace('/:\d+$/', '', $host);
		
		foreach($this->hosts as $domain => $infos){
			if(in_array($host, $infos['url'])){
				$this->url = $host;
				$this->domain = $domain;
				return true;
			}
		}
		return false;
	}
}