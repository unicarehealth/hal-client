<?php declare(strict_types=1);

namespace Jsor\HalClient;

use GuzzleHttp\Psr7\Response;
use Jsor\HalClient\Exception\BadResponseException;
use Jsor\HalClient\Exception\HttpClientException;

class HalClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_default_http_client() : void
    {
        $this->expectNotToPerformAssertions();

        new HalClient('http://propilex.herokuapp.com');
    }

    /**
     * @test
     * @dataProvider urlProvider
     */
    public function it_assembles_urls_correctly(string $root, string $uri, string $expected) : void
    {
        $client = new HalClient($root);

        $request = $client->createRequest('GET', $uri);

        $this->assertEquals((string) $request->getUri(), $expected);
    }

    /** @return array<string[]> */
    public static function urlProvider() : array
    {
        return [
            [
                'http://propilex.herokuapp.com',
                'test',
                'http://propilex.herokuapp.com/test'
            ],
            [
                'http://propilex.herokuapp.com',
                'test/',
                'http://propilex.herokuapp.com/test/'
            ],

            [
                'http://propilex.herokuapp.com/',
                'test',
                'http://propilex.herokuapp.com/test'
            ],
            [
                'http://propilex.herokuapp.com/',
                'test/',
                'http://propilex.herokuapp.com/test/'
            ],

            [
                'http://propilex.herokuapp.com/test',
                'test',
                'http://propilex.herokuapp.com/test'
            ],
            [
                'http://propilex.herokuapp.com/test',
                'test/',
                'http://propilex.herokuapp.com/test/'
            ],

            [
                'http://propilex.herokuapp.com/test/',
                'test',
                'http://propilex.herokuapp.com/test/test'
            ],
            [
                'http://propilex.herokuapp.com/test/',
                'test/',
                'http://propilex.herokuapp.com/test/test/'
            ],

            [
                'http://propilex.herokuapp.com/test',
                '/test',
                'http://propilex.herokuapp.com/test'
            ],
            [
                'http://propilex.herokuapp.com/test',
                '/test/',
                'http://propilex.herokuapp.com/test/'
            ],

            [
                'http://propilex.herokuapp.com/test/',
                '/test',
                'http://propilex.herokuapp.com/test'
            ],
            [
                'http://propilex.herokuapp.com/test/',
                '/test/',
                'http://propilex.herokuapp.com/test/'
            ],
        ];
    }

    /**
     * @test
     */
    public function it_is_immutable() : void
    {
        $httpClient = new FixtureHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $cloned = clone $client;

        $httpClientReflProp = new \ReflectionProperty('Jsor\HalClient\HalClient', 'httpClient');
        $httpClientReflProp->setAccessible(true);
        $defaultRequestReflProp = new \ReflectionProperty('Jsor\HalClient\HalClient', 'defaultRequest');
        $defaultRequestReflProp->setAccessible(true);

        $this->assertNotSame($httpClientReflProp->getValue($client), $httpClientReflProp->getValue($cloned));
        $this->assertNotSame($defaultRequestReflProp->getValue($client), $defaultRequestReflProp->getValue($cloned));

        $changedHeader = $cloned->withHeader('Foo', 'bar');

        $this->assertNotSame($httpClientReflProp->getValue($cloned), $httpClientReflProp->getValue($changedHeader));
        $this->assertNotSame($defaultRequestReflProp->getValue($cloned), $defaultRequestReflProp->getValue($changedHeader));

        $this->assertSame('bar', $changedHeader->getHeader('Foo')[0]);
        $this->assertEmpty($cloned->getHeader('Foo'));

        $changedRootUrl = $changedHeader->withRootUrl('http://example.com');

        $this->assertNotSame($changedRootUrl->getRootUrl(), $changedHeader->getRootUrl());
        $this->assertSame('http://example.com', (string) $changedRootUrl->getRootUrl());
        $this->assertSame('http://propilex.herokuapp.com', (string) $changedHeader->getRootUrl());
    }

    /**
     * @test
     */
    public function it_can_get() : void
    {
        $httpClient = new RecordingHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $client->get('', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => 'key1=key2'
        ]);

        $lastRequest = $httpClient->getLastRequest();

        $this->assertSame('1.0', $lastRequest->getProtocolVersion());
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertSame('http://propilex.herokuapp.com?key1=key2', (string) $lastRequest->getUri());
        $this->assertSame('Body', (string) $lastRequest->getBody());
        $this->assertSame(['bar'], $lastRequest->getHeader('Foo'));
    }

    /**
     * @test
     */
    public function it_can_post() : void
    {
        $httpClient = new RecordingHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $client->post('', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => 'key1=key2'
        ]);

        $lastRequest = $httpClient->getLastRequest();

        $this->assertSame('1.0', $lastRequest->getProtocolVersion());
        $this->assertSame('POST', $lastRequest->getMethod());
        $this->assertSame('http://propilex.herokuapp.com?key1=key2', (string) $lastRequest->getUri());
        $this->assertSame('Body', (string) $lastRequest->getBody());
        $this->assertSame(['bar'], $lastRequest->getHeader('Foo'));
    }

    /**
     * @test
     */
    public function it_can_put() : void
    {
        $httpClient = new RecordingHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $client->put('', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => 'key1=key2'
        ]);

        $lastRequest = $httpClient->getLastRequest();

        $this->assertSame('1.0', $lastRequest->getProtocolVersion());
        $this->assertSame('PUT', $lastRequest->getMethod());
        $this->assertSame('http://propilex.herokuapp.com?key1=key2', (string) $lastRequest->getUri());
        $this->assertSame('Body', (string) $lastRequest->getBody());
        $this->assertSame(['bar'], $lastRequest->getHeader('Foo'));
    }

    /**
     * @test
     */
    public function it_can_delete() : void
    {
        $httpClient = new RecordingHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $client->delete('', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => 'key1=key2'
        ]);

        $lastRequest = $httpClient->getLastRequest();

        $this->assertSame('1.0', $lastRequest->getProtocolVersion());
        $this->assertSame('DELETE', $lastRequest->getMethod());
        $this->assertSame('http://propilex.herokuapp.com?key1=key2', (string) $lastRequest->getUri());
        $this->assertSame('Body', (string) $lastRequest->getBody());
        $this->assertSame(['bar'], $lastRequest->getHeader('Foo'));
    }

    /**
     * @test
     */
    public function it_can_request() : void
    {
        $httpClient = new RecordingHttpClient();

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $client->request('PATCH', '', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => 'key1=key2'
        ]);

        $lastRequest = $httpClient->getLastRequest();

        $this->assertSame('1.0', $lastRequest->getProtocolVersion());
        $this->assertSame('PATCH', $lastRequest->getMethod());
        $this->assertSame('http://propilex.herokuapp.com?key1=key2', (string) $lastRequest->getUri());
        $this->assertSame('Body', (string) $lastRequest->getBody());
        $this->assertSame(['bar'], $lastRequest->getHeader('Foo'));
    }

    /**
     * @test
     */
    public function it_follows_location_for_created_response_with_empty_body() : void
    {
        $response1 = new Response(201, ['Location' => 'http://propilex.herokuapp.com/resource']);
        $response2 = new Response(200, ['Content-Type' => 'application/hal+json'], '{"foo":"bar"}');

        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->exactly(2))
            ->method('send')
            ->will($this->onConsecutiveCalls(
                $response1,
                $response2
            ));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        /** @var \Jsor\HalClient\HalResource $resource */
        $resource = $client->post('');

        $this->assertSame('bar', $resource->getProperty('foo'));
    }

    /**
     * @test
     */
    public function it_does_not_follow_location_for_created_response_with_non_empty_body() : void
    {
        $response = new Response(201, [
            'Location'     => 'http://propilex.herokuapp.com/resource',
            'Content-Type' => 'application/hal+json'
        ], '{"foo":"bar"}');

        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        /** @var \Jsor\HalClient\HalResource $resource */
        $resource = $client->post('');

        $this->assertSame('bar', $resource->getProperty('foo'));
    }

    /**
     * @test
     */
    public function it_returns_raw_response() : void
    {
        $response = new Response(200);

        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $response = $client->request('POST', '', [
            'version' => '1.0',
            'headers' => [
                'Foo' => 'bar'
            ],
            'body'  => 'Body',
            'query' => [
                'key1' => 'key2',
            ],
            'return_raw_response' => true
        ]);

        $this->assertSame($response, $response);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_client_throws() : void
    {
        $exception = new \Exception('Error');

        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->throwException($exception));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Exception thrown by the http client while sending request: Error.');

        try {
            $client->request('GET', '/');
        } catch (\Exception $e) {
            $this->assertSame($exception, $e->getPrevious());


            $this->assertInstanceOf('Jsor\HalClient\Exception\HttpClientException', $e);

            /** @var HttpClientException $typedException */
            $typedException = $e;
            $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $typedException->getRequest());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_client_returns_client_error() : void
    {
        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(404, ['Content-Type' => 'application/hal+json'], '{"msg":"error"}')));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Client error [url] / [http method] GET [status code] 404 [reason phrase] Not Found.');

        try {
            $client->request('GET', '/');
        } catch (BadResponseException $e) {
            $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $e->getRequest());
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $e->getResponse());
            $this->assertInstanceOf('Jsor\HalClient\HalResource', $e->getResource());

            /** @var \Jsor\HalClient\HalResource $resource */
            $resource = $e->getResource();
            $this->assertFalse($resource->hasLink('self'));

            $this->assertTrue($e->isClientError());
            $this->assertFalse($e->isServerError());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_client_returns_server_error() : void
    {
        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(500, ['Content-Type' => 'application/hal+json'], '{"msg":"error"}')));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Server error [url] / [http method] GET [status code] 500 [reason phrase] Internal Server Error.');

        try {
            $client->request('GET', '/');
        } catch (BadResponseException $e) {
            $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $e->getRequest());
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $e->getResponse());
            $this->assertInstanceOf('Jsor\HalClient\HalResource', $e->getResource());

            /** @var \Jsor\HalClient\HalResource $resource */
            $resource = $e->getResource();
            $this->assertFalse($resource->hasLink('self'));

            $this->assertFalse($e->isClientError());
            $this->assertTrue($e->isServerError());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_client_returns_unsuccessful_response() : void
    {
        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(303, ['Content-Type' => 'application/hal+json'], '{"msg":"error"}')));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Unsuccessful response [url] / [http method] GET [status code] 303 [reason phrase] See Other.');

        try {
            $client->request('GET', '/');
        } catch (BadResponseException $e) {
            $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $e->getRequest());
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $e->getResponse());
            $this->assertInstanceOf('Jsor\HalClient\HalResource', $e->getResource());

            /** @var \Jsor\HalClient\HalResource $resource */
            $resource = $e->getResource();
            $this->assertFalse($resource->hasLink('self'));

            $this->assertFalse($e->isClientError());
            $this->assertFalse($e->isServerError());

            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_content_type() : void
    {
        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(200, ['Content-Type' => 'text/plain'])));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Request did not return a valid content type. Returned content type: text/plain.');

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_getting_response_body_throws() : void
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')->getMock();

        $stream
            ->expects($this->once())
            ->method('getContents')
            ->will($this->throwException(new \Exception('Error')));

        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(200, ['Content-Type' => 'application/hal+json'], $stream)));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Error getting response body: Error.');

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_client_returns_invalid_json() : void
    {
        $httpClient = $this->getMockBuilder('Jsor\HalClient\HttpClient\HttpClientInterface')->getMock();

        $httpClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue(new Response(200, ['Content-Type' => 'application/hal+json'], '{')));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            $httpClient
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('JSON parse error: Syntax error');

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_can_browse() : void
    {
        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new FixtureHttpClient()
        );

        /** @var \Jsor\HalClient\HalResource $resource */
        $resource = $client->root();

        $this->assertInstanceOf('Jsor\HalClient\HalResource', $resource);
        $this->assertEmpty($resource->getProperties());
        $this->assertEmpty($resource->getResources());

        /** @var \Jsor\HalClient\HalLink $firstLink */
        $firstLink = $resource->getFirstLink('documents');

        $this->assertInstanceOf('Jsor\HalClient\HalLink', $firstLink);

        $this->assertEquals($firstLink->getHref(), 'http://propilex.herokuapp.com/documents');

        $this->assertNull($resource->getProperty('fake'));

        /** @var \Jsor\HalClient\HalResource $resource */
        $resource = $firstLink->get([], [
            'query' => [
                'page' => 1
            ]
        ]);

        $this->assertInstanceOf('Jsor\HalClient\HalResource', $resource);

        $expected = [
            'page'  => 1,
            'limit' => 10,
            'pages' => 1,
            'total' => 3
        ];

        $this->assertEquals($expected, $resource->getProperties());
        $this->assertEquals(1, $resource->getProperty('page'));
        $this->assertEquals(10, $resource->getProperty('limit'));
        $this->assertEquals(1, $resource->getProperty('pages'));

        $collection = $resource->getResource('documents');

        $this->assertIsArray($collection);

        $this->assertCount(3, $collection);

        foreach ($collection as $child) {
            $this->assertInstanceOf('Jsor\HalClient\HalResource', $child);
            $this->assertNotNull($child->getProperty('title'));
            $this->assertNotNull($child->getProperty('body'));
            $this->assertNotNull($child->getProperty('id'));
            $this->assertNull($child->getProperty('fake'));
        }

        $this->assertEquals('Test 1', $collection[0]->getProperty('title'));
        $this->assertEquals('Test 2', $collection[1]->getProperty('title'));
        $this->assertEquals('Test 3', $collection[2]->getProperty('title'));

        /** @var \Jsor\HalClient\HalResource $document1 */
        $document1 = $collection[0]->get();

        $this->assertEquals('Test 1', $document1->getProperty('title'));

        /** @var \Jsor\HalClient\HalResource $resource */
        $resource = $document1->getFirstLink('documents')?->get();

        /** @var \Jsor\HalClient\HalResource $newResource */
        $newResource = $resource->post([
            'body' => [
                'title' => 'Test 4',
                'body'  => 'Lorem ipsum'
            ]
        ]);

        $this->assertEquals('Test 4', $newResource->getProperty('title'));

        /** @var \Jsor\HalClient\HalResource $changedResource */
        $changedResource = $newResource->put([
            'body' => [
                'title' => 'Test 4 changed',
                'body'  => 'Lorem ipsum'
            ]
        ]);

        $this->assertEquals('Test 4 changed', $changedResource->getProperty('title'));

        /** @var \Jsor\HalClient\HalResource $deletedResource */
        $deletedResource = $changedResource->delete();

        $this->assertEmpty($deletedResource->getProperties());
    }
}
