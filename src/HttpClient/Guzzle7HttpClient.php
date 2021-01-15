<?php

namespace Jsor\HalClient\HttpClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

final class Guzzle7HttpClient implements HttpClientInterface
{
    private GuzzleClientInterface $client;

    public function __construct(GuzzleClientInterface $client = null)
    {
        $this->client = $client ?? new GuzzleClient();
    }

    public function send(RequestInterface $request) : ResponseInterface
    {
        try
        {
            return $this->client->send($request);
        }
        catch (GuzzleBadResponseException $e)
        {
            return $e->getResponse();
        }
    }
}
