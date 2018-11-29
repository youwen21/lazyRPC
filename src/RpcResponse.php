<?php

namespace Youwen\LazyRPC;

class RpcResponse
{

    private $_response = [
        'id' => 1,
        'version' => '2.0',
        'code' => 0,
        'msg' => '',
        'data' => []
    ];

    public function setId($id)
    {
        $this->_response['id'] = $id;
        return $this;
    }

    public function setVersion($version)
    {
        $this->_response['version'] = $version;
        return $this;
    }

    public function setData($data)
    {
        $this->_response['data'] = $data;
        return $this;
    }

    public function getJson()
    {
        return json_encode($this->_response);
    }

    public function getArray()
    {
        return $this->_response;
    }

    public function __set($name, $value)
    {
        $this->_response[$name] = $value;
        return true;
    }

    public function __get($name)
    {
        return isset($this->_response[$name])? $this->_response[$name] : null;
    }

    public static function exception(\Throwable $e, $data = [])
    {
        return self::error($e->getCode(), $e->getMessage(), $data);
    }

    public static function error($code, $msg, $data = [])
    {
        return json_encode(['code'=>$code, 'msg'=>$msg, 'data'=>$data], JSON_UNESCAPED_UNICODE);
    }
}
