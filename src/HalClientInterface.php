<?php

namespace Jsor\HalClient;

use Psr\Http\Message\{ResponseInterface, UriInterface};

interface HalClientInterface
{
    public function getRootUrl() : UriInterface;

    /** @return string[] */
    public function getHeader(string $name) : array;

    /** @param string|string[] $value */
    public function withHeader(string $name, string|array $value) : HalClientInterface;

    public function root(array $options = []) : HalResource|ResponseInterface;

    public function get(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    public function post(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    public function put(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    public function delete(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    public function request(string $method, string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;
}
