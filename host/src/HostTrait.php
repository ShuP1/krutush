<?php

namespace Krutush\Host;

use Krutush\Router;

trait HostTrait{
    protected $host;

    public function getHost() {
		return clone $this->host;
	}

    public function __construct(array $data = array()){
        parent::__construct($data);
        $this->host = new Host(isset($data['host']) ? $data['host'] : (Path::isset('config') ? (Path::get('config').'/Hosts.php') : null));
    }

    public function run(string $uri = null, array $filters = array()){
        if($this->host->getUrl() === false or $this->host->getDomain() === false)
            $this->error();

        $filters['domain'] = $this->host->getDomain();
        parent::run($uri, $filters);
    }
}