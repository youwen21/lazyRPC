<?php

namespace Youwen\LazyRPC;

class RpcRequest
{
    private static $_id = 1;

    private $_request = [
        'id' => 1,
        'version' => '2.0',
        'service' => null,
        'method' => null,
        'meta' => null,
        'param' => null
    ];

    public function __construct()
    {
    }

    private function getId()
    {
        return self::$_id++;
    }

    public function setId()
    {
        $this->request['id'] = $this->getId();
    }

    public function setVersion($version)
    {
        $this->_request['version'] = $version;
    }

    public function setService($service)
    {
        $this->_request['service'] = $service;
    }

    public function setMethod($method)
    {
        $this->_request['method'] = $method;
    }

    public function setMeta($key, $value)
    {
        $this->_request['meta'][$key] = $value;
    }

    public function setParam($param)
    {
        $this->_request['param'] = $param;
    }

    public function getRpcRequestJson()
    {
        return json_encode($this->_request);
    }

    public function getRpcRequestArray()
    {
        return $this->_request;
    }
}
