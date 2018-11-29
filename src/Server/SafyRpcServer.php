<?php

namespace youwen\simplerpc\server;

/**
 * 带有验证sign字符串功能的rpc Server
 */
class SafyRpcServer extends JsonRpcServer
{

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

}