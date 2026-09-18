<?php

namespace Oak\Session;

use Oak\Contracts\Config\RepositoryInterface;
use Oak\Contracts\Container\ContainerInterface;

class SessionPath
{
    public static function resolve(
        ContainerInterface $app,
        RepositoryInterface $config,
    ): string {
        $path = $config->get('session.path', 'sessions');
        $path = is_string($path) ? $path : 'sessions';

        if (!str_starts_with($path, '/')) {
            $path = rtrim($app->getEnvPath(), '/') . '/' . $path;
        }

        return $path;
    }
}
