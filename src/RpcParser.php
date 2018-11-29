<?php

namespace Youwen\LazyRPC;

use Closure;
use Youwen\LazyRPC\RpcException;

/**
 * {
 *      "jsonrpc":"2.0"
 *      'id':1,
 *      'method':haha,
 *      "service":'userModel',
 *      "param":{"aa":1, "bb":2}
 * }
 */
/**
 * serviceSolver
 * function(service){
 *      return '\App\Model\\'.$service;
 * }
 */
class RpcParser
{
    private $json;

    private $_requests = [];

    private $_serviceSolver;

    public function __construct(Closure $serviceSolver, $json = null)
    {
        $this->_serviceSolver = $serviceSolver;
        if (is_null($json)) {
            $this->json = file_get_contents("php://input");
        } else {
            $this->json = $json;
        }
        $arr = json_decode($this->json, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RpcException(json_last_error_msg(), json_last_error());
        }

        if (isset($arr['version'])) {
            $this->_handle($arr);
        } else {
            foreach ($arr as $value) {
                if (!isset($value['version'])) {
                    throw new RpcException("无效的消息体", 780);
                }
                $this->_handle($value);
            }
        }
    }

    private function _handle($requestArr)
    {
        $requestArr['realService'] = ($this->_serviceSolver)($requestArr['service']);
        $this->_requests[] = $requestArr;
    }

    public function service()
    {
        // if (!isset($this->_requests[0]['realService'])) {
        //     throw new RpcException('没有服务项', 790);
        // }
        // if(!is_file($this->_requests[0]['realService'])){
        //     throw new RpcException('不存在有效的RPC服务', 791);
        // }
        $object = new $this->_requests[0]['realService'];
        return [$object, $this->_requests[0]['method']];
    }

    public function param()
    {
        if (!isset($this->_requests[0]['param'])) {
            return [];
        }
        return $this->_requests[0]['param'];
    }

    public function getRequests($num = null)
    {
        if (is_null($num)) {
            return $this->_requests;
        }
        return isset($this->_requests[$num]) ? $this->_requests[$num] : null;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function __get($name)
    {
        return isset($this->_requests[0][$name]) ? $this->_requests[0][$name] : null;
    }
}
