<?php

namespace Oak\Http\Middleware;

trait MiddlewareRegisterTrait
{
    /**
     * @var array<string, array<int, class-string<\Psr\Http\Server\MiddlewareInterface>>> $middlewareGroups
     */
    private $middlewareGroups = [];

    /**
     * Set middlewares for group name
     *
     * @param string $name
     * @param array<int, class-string<\Psr\Http\Server\MiddlewareInterface>> $middlewares
     * @return void
     */
    public function middleware(string $name, array $middlewares = [])
    {
        if (!isset($this->middlewareGroups[$name])) {
            $this->middlewareGroups[$name] = $middlewares;
            return;
        }

        $this->middlewareGroups[$name] = array_merge(
            $this->middlewareGroups[$name],
            $middlewares,
        );
    }

    /**
     * Get middlewares by group name
     *
     * @param string $name
     * @return array<int, class-string<\Psr\Http\Server\MiddlewareInterface>>
     */
    public function getMiddleware(string $name): array
    {
        return $this->middlewareGroups[$name] ?? [];
    }
}
