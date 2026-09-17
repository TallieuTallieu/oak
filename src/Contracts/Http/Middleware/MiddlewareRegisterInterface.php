<?php

namespace Oak\Contracts\Http\Middleware;

interface MiddlewareRegisterInterface
{
    /**
     * @param string $name
     * @param array<int, class-string<\Psr\Http\Server\MiddlewareInterface>> $middleware
     * @return void
     */
    public function middleware(string $name, array $middleware);

    /**
     * @param string $name
     * @return array<int, class-string<\Psr\Http\Server\MiddlewareInterface>>
     */
    public function getMiddleware(string $name): array;
}
