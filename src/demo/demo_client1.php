<?php

$rpcRequest = new rpcRequest();

// 实例化一个签名检查
// $signChecker = new sign(['appId', 'appSecret']);
// $sign = $signChecker->make($json);
// $url = $signMaker->makeUrl($url, $json, '&');

$client = new client($url, $rpcRequest);

// $client->setRpcRequest='';

$params['aa'] = 1;
$params['bb'] = 2;

$ret = $client->haha($params, 'className');
echo '<pre>';
print_r( $data );
exit('</pre>');