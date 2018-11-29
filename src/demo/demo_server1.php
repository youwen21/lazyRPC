<?php

$postStr = file_get_contents("php://input");

// 需要检查签名的情况
	$requestSign = '';
	$requestAppId = '';
	$conf = getConf($requestAppId);
	// 实例化一个签名检查
	$signChecker = new signChecker(['appId', 'appSecret']);
	$checkret = $signChecker->check($postStr, $requestSign);

// 是否使用了加密body体加密？
if(1){
    $json = xxtea($postStr);
}else{
    $json = $postStr;
}

// 实例化一个请求解析器
// 把json转成数组
// 定位真正的service地址
$parser = new simpleParser(function($service){
    return '\App\Model\\'.$service;
}, $json);

// 提供jsonRPC服务
$server = new jsonRpcServer($rpcResponder);
$ret = $server->processingRequest($parser);

