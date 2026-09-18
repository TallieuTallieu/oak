<?php

namespace Oak\Http\Middleware;

use Oak\Http\Controller\BaseController;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CoreRequestHandler implements RequestHandlerInterface
{
    /**
     * @var BaseController $controller
     */
    private $controller;

    /**
     * @var string $method
     */
    private $method;

    /**
     * @var array<int|string, mixed> $params
     */
    private $params;

    /**
     * CoreRequestHandler constructor.
     * @param BaseController $controller
     * @param string $method
     * @param array<int|string, mixed> $params
     */
    public function __construct(
        BaseController $controller,
        string $method,
        array $params = [],
    ) {
        $this->controller = $controller;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $callback = [$this->controller, $this->method];

        if (!is_callable($callback)) {
            throw new \BadMethodCallException(
                'Controller method ' .
                    get_class($this->controller) .
                    '::' .
                    $this->method .
                    ' is not callable',
            );
        }

        $output = call_user_func_array($callback, $this->params);

        // Check if we already have a response
        if ($output instanceof ResponseInterface) {
            // It's already a response, return it
            return $output;
        }

        // Get the response from the controller
        $response = $this->controller->getResponse();

        // ...and write to its body
        $body = '';

        if (is_scalar($output) || $output instanceof \Stringable) {
            $body = (string) $output;
        }

        $response->getBody()->write($body);

        return $response;
    }
}
