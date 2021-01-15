<?php

namespace Jsor\HalClient\HttpClient;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Response;
use Jsor\HalClient\Exception\{BadResponseException, HttpClientException};
use Jsor\HalClient\{HalClient, TestCase};

class Guzzle7HttpClientTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('GuzzleHttp\ClientInterface::MAJOR_VERSION') || GuzzleClientInterface::MAJOR_VERSION < 7)
        {
            $this->markTestIncomplete("GuzzleHttp version other than ^7.0 installed (Installed version ${GuzzleClientInterface::MAJOR_VERSION}.");
        }
    }

    /**
     * @test
     */
    public function it_will_call_send() : void
    {
        $response = new Response(200, ['Content-Type' => 'application/hal+json']);

        $guzzleClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle7HttpClient($guzzleClient)
        );

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_will_transform_exception() : void
    {
        $guzzleClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                throw GuzzleRequestException::create($request);
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle7HttpClient($guzzleClient)
        );

        $this->expectException(HttpClientException::class);

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_will_transform_exception_with_500_response() : void
    {
        $guzzleClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                throw GuzzleRequestException::create(
                    $request,
                    new Response(500)
                );
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle7HttpClient($guzzleClient)
        );

        $this->expectException(BadResponseException::class);

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_will_transform_exception_with_404_response() : void
    {
        $guzzleClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                throw GuzzleRequestException::create(
                    $request,
                    new Response(404)
                );
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle7HttpClient($guzzleClient)
        );

        $this->expectException(BadResponseException::class);

        $client->request('GET', '/');
    }

    /**
     * @test
     */
    public function it_will_transform_bad_response_exception_without_response() : void
    {
        $guzzleClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                $emptyResponse = new \GuzzleHttp\Psr7\Response();

                throw new GuzzleBadResponseException(
                    'Error',
                    $request,
                    $emptyResponse
                );
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle7HttpClient($guzzleClient)
        );

        $this->expectException(BadResponseException::class);

        $client->request('GET', '/');
    }
}
