<?php

namespace Krutush;

class NeedApp{
    protected $app;

    public function __construct(App $app){ $this->app = $app; }
}