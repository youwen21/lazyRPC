<?php

namespace Youwen\LazyRPC\Server;

use Closure;
use Psr\Log\LoggerInterface;
use Youwen\LazyRPC\RpcResponse;

class RpcServer
{
    private $_rpcResponder = null;

    protected $logger;

    public function __construct($rpcResponder = null)
    {
        $this->_rpcResponder = $rpcResponder;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 处理单个请求
     */
    public function processingRequest($parser, Closure $intercept = null)
    {
        if (!is_null($this->logger)) {
            $this->logger->debug($parser->getJson(), ['requestJson']);
        }
        $value = $parser->getRequests(0);
        $ret = call_user_func_array($parser->service(), $parser->param());

        if (!is_null($intercept)) {
            $ret = $intercept($ret);
        }
        if (is_null($this->_rpcResponder)) {
            $this->_rpcResponder = new RpcResponse();
        }
        $this->_rpcResponder->setId($parser->id);
        $this->_rpcResponder->setVersion($parser->version);
        $this->_rpcResponder->setData($ret);

        $responseJson = $this->_rpcResponder->getJson();

        if (!is_null($this->logger)) {
            $this->logger->debug($responseJson, ['responseJson']);
        }
        return $responseJson;
    }
}
