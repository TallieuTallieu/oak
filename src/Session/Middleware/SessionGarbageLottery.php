<?php

namespace Oak\Session\Middleware;

use Oak\Contracts\Config\RepositoryInterface;
use Oak\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionGarbageLottery implements MiddlewareInterface
{
    /**
     * @var RepositoryInterface $config
     */
    private $config;

    /**
     * @var Session $session
     */
    private $session;

    /**
     * SessionGarbageLottery constructor.
     * @param RepositoryInterface $config
     * @param Session $session
     */
    public function __construct(RepositoryInterface $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Garbage collection lottery
        $lottery = $this->config->get('session.lottery', 200);
        $lottery = is_numeric($lottery) ? (int) $lottery : 200;

        if (rand(0, $lottery) === 1) {
            $maxLifetime = $this->config->get('session.max_lifetime', 1000);

            $this->session
                ->getHandler()
                ->gc(is_numeric($maxLifetime) ? (int) $maxLifetime : 1000);
        }

        return $handler->handle($request);
    }
}
