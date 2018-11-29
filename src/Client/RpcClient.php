<?php

namespace Youwen\LazyRPC\Client;

use Youwen\LazyRPC\RpcException;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Closure;
use Youwen\LazyRPC\RpcRequest;

class RpcClient
{
    private $_url = '';

    // json字符串生成器,负责把请求的内容转成json字付串
    private $_rpcRquest=null;
    // 加密器，把明文json字付串加密，生成最终的post body体
    private $_encrypt=null;
    // 签名生成器, 根据post body生成sign和带有sign的URL
    private $_signMaker=null;

    private $_service=null;
    private $_version=null;

    protected $logger=null;
    protected $requestOptions = [
        'headers' => [
            'Accept' => 'application/json'
        ]
    ];

    public function __construct($url, $rpcRequest = null, callable $encrypt = null, callable $signMaker = null)
    {
        $this->_url = $url;
        $this->_rpcRquest = is_null($rpcRequest) ? new RpcRequest : $rpcRequest;
        $this->_encrypt = $encrypt;
        $this->_signMaker = $signMaker;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function setRpcRequest($rpcRequest)
    {
        $this->_rpcRquest = $rpcRequest;
        return $this;
    }

    public function setEncrypt(Closure $encrypt)
    {
        $this->_encrypt = $encrypt;
        return $this;
    }

    /**
     * setSignMaker(function($name, $arguments){
     *      return new \xx\bb\rpcRequest($name, $arguments);
     * })
     * @param  [type] $signMaker [description]
     * @author baiyouwen
     */
    public function setSignMaker(Closure $signMaker)
    {
        $ths->_signMaker = $signMaker;
        return $this;
    }

    public function setService($service)
    {
        $this->_rpcRquest->setService($service);
        return $this;
    }

    public function setVersion($version)
    {
        $this->_rpcRquest->setVersion($version);
        return $this;
    }

    public function setMeta($meta)
    {
        $this->_rpcRquest->setMeta($meta);
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function withOptions(array $options = [])
    {
        $this->requestOptions = $options;
        return $this;
    }

    public function __call($name, $arguments)
    {
        $this->_rpcRquest->setId();
        $this->_rpcRquest->setMethod($name);
        $this->_rpcRquest->setParam($arguments);
        if (!is_null($this->_service)) {
            $this->_rpcRquest->setService($this->_service);
        }
        if (!is_null($this->_version)) {
            $this->_rpcRquest->setVersion($this->_version);
        }

        $postStr = $this->_rpcRquest->getRpcRequestJson();

        if (!is_null($this->_encrypt)) {
            $postStr = $this->_encrypt($postStr);
        }

        if (!is_null($this->_signMaker)) {
            $this->_url = $this->_signMaker($this->url, $postStr);
        }

        if (!is_null($this->logger)) {
            $this->logger->debug($this->_url, ['url']);
            $this->logger->debug($postStr, ['request']);
        }

        $httpClient = new Client();
        $response = $httpClient->post($this->_url, ['body'=>$postStr], $this->requestOptions);
        $json = $response->getBody()->getContents();
        if (!is_null($this->logger)) {
            $this->logger->debug($json, ['response']);
        }
        $result = json_decode($json, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RpcException(json_last_error_msg(), json_last_error());
        }
        return $result;
    }
}
