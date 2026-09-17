<?php

namespace Oak\Contracts\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    /**
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response);
}
