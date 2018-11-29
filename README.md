# LazyRPC

LazyRPC是一个基于PHP开发的jsonRPC公众包，使用Composer安装  

LazyRPC基于GuzzleHttp包执行Curl请求  

LazyRPC可以明文调用，可以为URL添加签名字符串，还可把json消息体加密  
 
## 安装

composer require youwen/LazyRPC

## 入门

```php
use Youwen\LazyRPC\RpcException;
use Youwen\LazyRPC\RpcParser;
use Youwen\LazyRPC\RpcResponse;
use Youwen\LazyRPC\RpcRequest;
use Youwen\LazyRPC\Server\RpcServer;
use Youwen\LazyRPC\Client\RpcClient;
```

```php
    // Client
    public function jsonRcpClient()
    {
        // monolog
        $logger = Log::getInstance()->getLogger(
            ROOT.'/Runtime/jsonrpc.log'
        );

        $reqeust = new RpcRequest();

        $url = 'http://owenadmin.com/index.php?/jsonrpc/jsonRpcServer';
        $client = new RpcClient($url, $reqeust);
        $client -> setLogger($logger);
        $ret = $client->setService('ServiceA')->haha($aa=1, $bb='123');
        echo '<pre>';
        print_r( $ret );
        exit('</pre>');
    }

    //Server
    public function jsonRpcServer()
    {
        $postStr = file_get_contents("php://input");
        if(empty($postStr)){
            echo '<pre>';
            print_r( 'post is empty' );
            exit('</pre>');
        }
        try {
            // 实例化一个请求解析器
            // 把json转成数组
            // 定位真正的service地址
            $parser = new RpcParser(function ($service) {
                return '\App\Index\Service\\' . $service;
            }, $postStr);
            // monolog
            $logger = Log::getInstance()->getLogger(
                ROOT.'/Runtime/rpcServer.log'
            );
            // 提供jsonRPC服务
            $server = new RpcServer();
            $server->setLogger($logger);
            $ret = $server->processingRequest($parser);

            echo $ret;
            exit;
        } catch (RpcException $RpcE) {
            $json = RpcResponse::exception($RpcE);
            echo $json;
        } catch (\Exception $e) {
            $json = RpcResponse::error(700, 'error');
            echo $json;
        }
    }

```


## 加密消息体



## 生成签名字符串

## 开源

