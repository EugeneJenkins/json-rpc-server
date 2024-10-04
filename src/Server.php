<?php

namespace EugeneJenkins\JsonRpcServer;

use Closure;
use Throwable;
use EugeneJenkins\JsonRpcServer\Utils\CallbackList;
use EugeneJenkins\JsonRpcServer\Response\RpcResponse;
use EugeneJenkins\JsonRpcServer\Response\ServerResponse;
use EugeneJenkins\JsonRpcServer\Handlers\ExceptionHandler;
use EugeneJenkins\JsonRpcServer\Processors\RequestProcessor;
use EugeneJenkins\JsonRpcServer\Handlers\StringPayloadHandler;
use EugeneJenkins\JsonRpcServer\Controllers\RequestController;
use EugeneJenkins\JsonRpcServer\Handlers\PhpInputPayloadHandler;

class Server
{
    private CallbackList $callbackList;
    private RpcResponse $response;
    private ExceptionHandler $exceptionHandler;

    public function __construct(readonly private string $payload = '')
    {
        $this->response = new RpcResponse;
        $this->callbackList = new CallbackList;
        $this->exceptionHandler = new ExceptionHandler($this->response);
    }

    public function register(string $name, Closure $callback): void
    {
        $this->callbackList->add($name, $callback);
    }

    public function execute(): ServerResponse
    {
        //Registering Payload Receiving Methods
        $controller = new RequestController($this->callbackList);
        $controller->registerHandler(new StringPayloadHandler);
        $controller->registerHandler(new PhpInputPayloadHandler);

        try {
            $requests = $controller->handleRequest($this->payload);

            $processor = new RequestProcessor($requests, $this->callbackList, $this->response);
            $responses = $processor->process();
        } catch (Throwable $exception) {
            $responses = $this->exceptionHandler->setException($exception)->handle();
        }

        return new ServerResponse($responses);
    }
}
