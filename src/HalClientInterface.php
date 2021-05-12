<?php declare(strict_types=1);

namespace Jsor\HalClient;

use Psr\Http\Message\{ResponseInterface, UriInterface};

/**
 * @phpstan-type HeaderOptionValueType string|string[]
 * @phpstan-type BodyOptionType string|array<mixed>
 * @phpstan-type QueryParametersOptionType string|array<string, int|string|string[]>
 * @phpstan-type RequestOptionsType array{return_raw_response?:bool, version?:string, query?:QueryParametersOptionType, headers?:array<string, HeaderOptionValueType>, body?:BodyOptionType}
 * @phpstan-type ResponseOptionsType array{return_raw_response?:bool}
 */
interface HalClientInterface
{
    public function getRootUrl() : UriInterface;

    /** @return string[] */
    public function getHeader(string $name) : array;

    /** @param string|string[] $value */
    public function withHeader(string $name, string|array $value) : HalClientInterface;

    /** @param RequestOptionsType $options */
    public function root(array $options = []) : HalResource|ResponseInterface;

    /** @param RequestOptionsType $options */
    public function get(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    /** @param RequestOptionsType $options */
    public function post(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    /** @param RequestOptionsType $options */
    public function put(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    /** @param RequestOptionsType $options */
    public function delete(string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;

    /** @param RequestOptionsType $options */
    public function request(string $method, string|UriInterface $uri, array $options = []) : HalResource|ResponseInterface;
}
