<?php

namespace youwen\simplerpc\server;

class JsonRpcServer
{
    protected $_requestText;
    protected $_listOfCallableServices;
    protected $_requestObject;
    protected $_responseBatchArray = array();

    public function __construct($postRequest = '')
    {
        // ob_start();
        $postRequest  =  $postRequest ? $postRequest :  file_get_contents("php://input");
        // if ($postRequest)
        //         $postRequest = Xxtea::de($postRequest);
        $this->_requestText = $postRequest;
        $this->_listOfCallableServices = array();
    }

    public function addService($classInstance)
    {
        array_push($this->_listOfCallableServices, $classInstance);
    }

    public function processingRequests()
    {
        try {
            $this->parseRequestJson();
            $this->performCalls();
        } catch (\Exception $e) {
            $responseBody = new RpcError($e->getMessage(), $e->getCode());

            $responseObject = new RpcResponse($responseBody);
            $this->doResponse($responseObject->getRpcResponseObject());
        }
    }

    private function parseRequestJson()
    {
        if (!is_null($requestObjects = json_decode($this->_requestText))) {
//            $requestObjects->id = intval($requestObjects->id);
            $this->_requestObject = $requestObjects;
        } else {
            throw new JsonRpcException();
        }
    }

    private function performCalls()
    {
        if ($this->isBatchRequestAndNotEmpty()) {
            $this->performBatchCall();
        } else {
            $this->performSingleCall();
        }
    }

    private function isBatchRequestAndNotEmpty()
    {
        if (is_array($this->_requestObject)) {
            if (empty($this->_requestObject)) {
                throw new JsonRpcException();
            }
            return true;
        } else {
            return false;
        }
    }

    private function performBatchCall()
    {
        foreach ($this->_requestObject as $request) {
            $responseObject = $this->getResponseObject($request);
            if (!$this->isNotification($request)) {
                array_push($this->_responseBatchArray, $responseObject->getRpcResponseObject());
            }
        }
        $this->doResponse($this->_responseBatchArray);
    }

    private function performSingleCall()
    {
        $responseObject = $this->getResponseObject($this->_requestObject);
        $obj = $responseObject->getRpcResponseObject();
        if (!$this->isNotification($this->_requestObject)) {
            $this->doResponse($obj);
        }
    }

    private function isNotification($requestObject)
    {
        if (is_object($requestObject) && is_null($requestObject->id)) {
            return true;
        }
    }

    private function getResponseObject($requestObject)
    {
        try {
            $this->validateRequest($requestObject);
            $methodOwnerService = $this->isMethodAvailable($requestObject);
            $this->validateAndSortParameters($methodOwnerService, $requestObject);
            $responseObject = $this->buildResponseObject($requestObject, $methodOwnerService);
        } catch (JsonRpcException $exception) {
            $responseObject = $this->buildResponseObject($exception, null, $exception->data);
            $responseObject->setResponseObjectId($requestObject->id);
        } catch (\Exception $exception) {
            $responseObject = $this->buildResponseObject($exception);
            $responseObject->setResponseObjectId($requestObject->id);
        }
        return $responseObject;
    }

    private function validateRequest($request)
    {
        if (!$this->isValidRequestObject($request)) {
            throw new JsonRpcException();
        } else {
            return true;
        }
    }

    private function isValidRequestObject($requestObject)
    {
        return ($requestObject->jsonrpc == RpcResponse::VERSION
            && $this->isValidRequestObjectId($requestObject->id)
            && $this->isValidRequestObjectMethod($requestObject->method));
    }

    private function isValidRequestObjectId($requestId)
    {
        return $requestId;
        return (is_null($requestId)
            || is_string($requestId)
            // 2 and "2" is valid but 2.1 and "2.1" is not
            || (ctype_digit($requestId) xor is_int($requestId)));
    }

    private function isValidRequestObjectMethod($requestMethod)
    {
        return (!is_null($requestMethod)
            && is_string($requestMethod)
            && strncmp($reserved = "rpc.", $requestMethod, strlen($reserved)));
    }

    protected function isMethodAvailable($requestObject)
    {
        $length = count($this->_listOfCallableServices);
        for ($i = 0; $i < $length; $i++) {
            if (array_key_exists($requestObject->method, $this->_listOfCallableServices[$i]->getCallableMethodNames())) {
                return $this->_listOfCallableServices[$i];
            }
        }
        throw new JsonRpcException();
    }

    private function validateAndSortParameters($methodOwnerService, $requestObject)
    {
        $validParameters = $methodOwnerService->getCallableMethodParameters($requestObject->method);
        if ($this->isValidParamsNumber($validParameters, $requestObject)
            && $this->isValidParamsName($validParameters, $requestObject)
        ) {

            $this->setMethodParamsSequence($validParameters, $requestObject);
        } else {
            throw new JsonRpcException();
        }
    }

    private function setMethodParamsSequence($validParameters, $requestParameters)
    {
        $sortedObject = new \stdClass();
        if (is_object($requestParameters->params)) {
            foreach ($validParameters as $parameter) {
                $sortedObject->{$parameter->name} = $requestParameters->params->{$parameter->name};
            }
            $requestParameters->params = $sortedObject;
        }
    }

    private function buildResponseObject($requestOrExceptionObject, $service = null, $data = array())
    {
        if (is_null($service)) {
            $responseBody = new RpcError($requestOrExceptionObject->getMessage(), $requestOrExceptionObject->getCode(), $data);
            $responseObject = new RpcResponse($responseBody);
        } else {
            $callbackResult = $this->call($service, $requestOrExceptionObject);
            $responseObject = new RpcResponse($callbackResult, $requestOrExceptionObject->id);
        }
        return $responseObject;
    }

    /** 
     * 这块完全可以算是个BUG
     * @param  [type]  $validParameters 方法要求的参数个数
     * @param  [type]  $requestObject   传入的第一个参数的count数
     * @return boolean                  [两者没有任何关系,  算是BUG]
     * @author baiyouwen
     */
    private function isValidParamsNumber($validParameters, $requestObject)
    {
        $validParameterCount = count($validParameters);
        if(!isset($requestObject->params)){
            return true;
        }
        $requestParameterCount = $this->countRequestParams($requestObject->params);
        if ($validParameterCount != $requestParameterCount) {
            return false;
        }
        return true;
    }

    private function countRequestParams($requestObjectParams)
    {
        if (is_object($requestObjectParams)) {
            return count(get_object_vars($requestObjectParams));
        } else {
            return count($requestObjectParams);
        }
    }

    private function isValidParamsName($validParameters, $requestObject)
    {
        if (is_object($requestObject->params)) {
            $requestParamNames = array_keys(get_object_vars($requestObject->params));
            foreach ($validParameters as $parameter) {
                if (!in_array($parameter->name, $requestParamNames, true)) {
                    return false;
                }
            }
            return true;
        } else {
            return true;
        }
    }

    private function call($methodOwnerService, $requestObject)
    {
        $callbackFunction = array($methodOwnerService, $requestObject->method);
        return call_user_func_array($callbackFunction, $requestObject->params);
    }

    private function doResponse($responseObject)
    {
        $error = ob_get_clean();
        if(strlen($error) > 0)
            logs($error,'jsonrpc_error');
        if (!empty($responseObject)) {
            header('Content-Type: application/json');
            echo json_encode($responseObject);
        }
    }
}