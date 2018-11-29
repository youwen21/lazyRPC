<?php

namespace youwen\simplerpc\server;

class RpcServerProxy
{

    public $JsonRpcServer = null;

    public function __construct()
    {
        $this->JsonRpcServer = new JsonRpcServer();
    }

    /**
     * 验证签名
     * @author baiyouwen
     */
    public function verifySign(array $conf, string $sign, $json='')
    {
        if(empty($json)){
            $json = file_get_contents("php://input");
        }

        $generateSign = md5('app_key='.$conf['appKey'].'&json='.$json);
        return $generateSign == $sign;
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->JsonRpcServer, $method], $params);
    }
}