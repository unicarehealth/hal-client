<?php declare(strict_types=1);

namespace Jsor\HalClient\Internal;

use Jsor\HalClient\Exception;
use Jsor\HalClient\{HalClientInterface, HalResource};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use JsonException;

final class HalResourceFactory
{
    /** @var string[] $validContentTypes */
    private array $validContentTypes;

    /** @param string[] $validContentTypes */
    public function __construct(array $validContentTypes)
    {
        $this->validContentTypes = $validContentTypes;
    }

    public function createResource(
        HalClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        bool $ignoreInvalidContentType = false
    ) : HalResource {
        if (204 === $response->getStatusCode())
        {
            // No-Content response
            return new HalResource($client);
        }

        $body = trim($this->fetchBody($client, $request, $response));

        if (
            '' === $body &&
            201 === $response->getStatusCode() &&
            $response->hasHeader('Location')
        ) {
            // Created response with Location header
            /** @var HalResource $res */
            $res = $client->request('GET', $response->getHeader('Location')[0]);
            return $res;
        }

        if (!$this->isValidContentType($response))
        {
            return $this->handleInvalidContentType(
                $client,
                $request,
                $response,
                $ignoreInvalidContentType
            );
        }

        return $this->handleValidContentType(
            $client,
            $request,
            $response,
            $body
        );
    }

    private function isValidContentType(ResponseInterface $response) : bool
    {
        if (!$response->hasHeader('Content-Type'))
        {
            return false;
        }

        $contentTypeHeader = $response->getHeaderLine('Content-Type');

        if (preg_match("/^([^;]+)(;[\s]?(charset|boundary)=(.+))?$/", $contentTypeHeader, $match))
        {
            $contentTypeHeader = $match[1];
        }

        return in_array($contentTypeHeader, $this->validContentTypes, true);
    }

    private function handleInvalidContentType(
        HalClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        bool $ignoreInvalidContentType = false
    ) : HalResource {
        if ($ignoreInvalidContentType)
        {
            return new HalResource($client);
        }

        $types = $response->getHeader('Content-Type') ?: ['none'];

        throw new Exception\BadResponseException(
            sprintf(
                'Request did not return a valid content type. Returned content type: %s.',
                implode(', ', $types)
            ),
            $request,
            $response,
            new HalResource($client)
        );
    }

    private function handleValidContentType(
        HalClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        string $body = ''
    ) : HalResource {
        if ('' === $body)
        {
            return new HalResource($client);
        }

        $data = $this->decodeBody($client, $request, $response, $body);

        return HalResource::fromArray($client, (array) $data);
    }

    private function fetchBody(
        HalClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response
    ) : string {
        try
        {
            return $response->getBody()->getContents();
        }
        catch (\Throwable $e)
        {
            throw new Exception\BadResponseException(
                sprintf(
                    'Error getting response body: %s.',
                    $e->getMessage()
                ),
                $request,
                $response,
                new HalResource($client),
                $e
            );
        }
    }

    /** @return array<mixed> */
    private function decodeBody(
        HalClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        string $body = ''
    ) : array {

        try
		{
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $e)
		{
			throw new Exception\BadResponseException(
                sprintf('JSON parse error: %s.', $e->getMessage()),
                $request,
                $response,
                new HalResource($client)
            );
        }
    }
}
