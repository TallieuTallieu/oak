<?php

namespace Oak\Session\Middleware;

use Oak\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StartSession implements MiddlewareInterface
{
    /**
     * @var Session $session
     */
    private $session;

    /**
     * StartSession constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Pick up the session id from the cookie, or mint one
        $this->session->start();

        // Handle the response first
        $response = $handler->handle($request);

        // ...then save any possible session changes and additions
        $this->session->save();

        return $response;
    }
}
