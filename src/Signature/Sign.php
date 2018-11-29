<?php

namespace lazyrpc\Signatrue;

class Sign
{
    public $json=null;
    public $appConf = ['app_id', 'app_secret'];
    public $hashAlgo=null;
    public $linkSymbol=[];

    public function __construct(array $config , string $hashAlgo='md5', $linkSymbol=['&', '='])
    {
        $this->appConf = $config;
        $this->hashAlgo = $hashAlgo;
        $this->linkSymbol = $linkSymbol;
    }


    public function makeSign(string $json)
    {
        $string = 'app_id='.$appConf['app_id'].'&json='.$json;
        return hase($this->hashAlgo, $string);
    }

    public function makeSignUrl($url, $json)
    {
        $sign = $this->makeSign($json);
        $url .= $this->linkSymbol[0].'app_id'.$this->linkSymbol[1].$appConf['app_id'].$this->linkSymbol[0].'sign'.$this->linkSymbol[1].$sign;
        return $url;
    }

    public function check($json, $requestSign)
    {
        $sign = $this->makeSign($json);
        return $sign==$requestSign;
    }
}