<?php declare(strict_types=1);

namespace Jsor\HalClient\Exception;

use Psr\Http\Message\RequestInterface;
use Throwable;

class HttpClientException extends \RuntimeException implements ExceptionInterface
{
    private RequestInterface $request;

    public function __construct(
        string $message,
        RequestInterface $request,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->request = $request;
    }

    public function getRequest() : RequestInterface
    {
        return $this->request;
    }

    public static function create(
        RequestInterface $request,
        ?Throwable $previous = null,
        ?string $message = null
    ) : self {
        if ($message === null || $message === '')
        {
            $message = 'Exception thrown by the http client while sending request.';

            if ($previous)
            {
                $message = sprintf(
                    'Exception thrown by the http client while sending request: %s.',
                    $previous->getMessage()
                );
            }
        }

        return new self($message, $request, $previous);
    }
}
