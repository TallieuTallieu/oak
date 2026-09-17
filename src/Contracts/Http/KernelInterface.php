<?php

namespace Oak\Contracts\Http;

use Psr\Http\Message\ServerRequestInterface;

interface KernelInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return void
     */
    public function handle(ServerRequestInterface $request);
}
