<?php declare(strict_types=1);

namespace Jsor\HalClient\Exception;

use Jsor\HalClient\HalResource;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class BadResponseException extends \RuntimeException implements ExceptionInterface
{
    private RequestInterface $request;
    private ResponseInterface $response;
    private HalResource|ResponseInterface $resource;

    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response,
        HalResource|ResponseInterface $resource,
        ?Throwable $previous = null
    ) {
        $code = $response->getStatusCode();
        parent::__construct($message, $code, $previous);

        $this->request  = $request;
        $this->response = $response;
        $this->resource = $resource;
    }

    public static function create(
        RequestInterface $request,
        ResponseInterface $response,
        HalResource|ResponseInterface $resource,
        ?Throwable $previous = null,
        ?string $message = null
    ) : self {
        if ($message === null || $message === '')
        {
            $code = $response->getStatusCode();

            if ($code >= 400 && $code < 500)
            {
                $message = 'Client error';
            }
            elseif ($code >= 500 && $code < 600)
            {
                $message = 'Server error';
            }
            else
            {
                $message = 'Unsuccessful response';
            }
        }

        $message = sprintf(
            '%s [url] %s [http method] %s [status code] %s [reason phrase] %s.',
            $message,
            $request->getRequestTarget(),
            $request->getMethod(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        return new self($message, $request, $response, $resource, $previous);
    }

    public function getRequest() : RequestInterface
    {
        return $this->request;
    }

    public function getResponse() : ResponseInterface
    {
        return $this->response;
    }

    public function getResource() : HalResource|ResponseInterface
    {
        return $this->resource;
    }

    public function isClientError() : bool
    {
        return $this->getCode() >= 400 && $this->getCode() < 500;
    }

    public function isServerError() : bool
    {
        return $this->getCode() >= 500 && $this->getCode() < 600;
    }
}
