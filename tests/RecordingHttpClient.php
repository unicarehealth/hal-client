<?php declare(strict_types=1);

namespace Jsor\HalClient;

use GuzzleHttp\Psr7\Response;
use Jsor\HalClient\HttpClient\HttpClientInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

class RecordingHttpClient implements HttpClientInterface
{
    /** @var RequestInterface[] $requests */
    public array $requests = [];

    public function send(RequestInterface $request) : ResponseInterface
    {
        $this->requests[] = $request;

        return new Response(200, ['Content-Type' => 'application/hal+json']);
    }

    public function getLastRequest() : RequestInterface
    {
        return $this->requests[count($this->requests) - 1];
    }
}
