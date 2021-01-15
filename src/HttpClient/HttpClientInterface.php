<?php

namespace Jsor\HalClient\HttpClient;

use Psr\Http\Message\{RequestInterface, ResponseInterface};

interface HttpClientInterface
{
    /**
     * Note, that this method must not throw exceptions but always return a response.
     */
    public function send(RequestInterface $request) : ResponseInterface;
}
