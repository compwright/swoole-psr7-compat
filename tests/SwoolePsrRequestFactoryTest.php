<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat\Tests;

use Compwright\SwoolePsr7Compat\SwoolePsrRequestFactory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class SwoolePsrRequestFactoryTest extends TestCase
{
    /**
     * @var Request
     */
    private $swooleRequest;

    public function setUp(): void
    {
        $this->swooleRequest = Request::create();
        $this->swooleRequest->parse(file_get_contents(__DIR__ . '/test.txt'));
    }

    public function testNyholm(): void
    {
        $serverRequestCreator = new ServerRequestCreator(
            new \Nyholm\Psr7\Factory\Psr17Factory(),
            new \Nyholm\Psr7\Factory\Psr17Factory(),
            new \Nyholm\Psr7\Factory\Psr17Factory(),
            new \Nyholm\Psr7\Factory\Psr17Factory()
        );
        $psrRequestFactory = new SwoolePsrRequestFactory($serverRequestCreator);
        $psrRequest = $psrRequestFactory->createFromSwoole($this->swooleRequest);
        $this->runAssertions($psrRequest);
    }

    public function testLaminas(): void
    {
        $serverRequestCreator = new ServerRequestCreator(
            new \Laminas\Diactoros\ServerRequestFactory(),
            new \Laminas\Diactoros\UriFactory(),
            new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\StreamFactory()
        );
        $psrRequestFactory = new SwoolePsrRequestFactory($serverRequestCreator);
        $psrRequest = $psrRequestFactory->createFromSwoole($this->swooleRequest);
        $this->runAssertions($psrRequest);
    }

    public function testSlim(): void
    {
        $serverRequestCreator = new ServerRequestCreator(
            new \Slim\Psr7\Factory\ServerRequestFactory(),
            new \Slim\Psr7\Factory\UriFactory(),
            new \Slim\Psr7\Factory\UploadedFileFactory(),
            new \Slim\Psr7\Factory\StreamFactory()
        );
        $psrRequestFactory = new SwoolePsrRequestFactory($serverRequestCreator);
        $psrRequest = $psrRequestFactory->createFromSwoole($this->swooleRequest);
        $this->runAssertions($psrRequest);
    }

    private function runAssertions($psrRequest): void
    {
        $this->assertSame('POST', $psrRequest->getMethod());
        $this->assertSame('1.1', $psrRequest->getProtocolVersion());
        $this->assertSame('/hello/joe', $psrRequest->getUri()->getPath());
        $this->assertSame(['foo' => 'bar'], $psrRequest->getQueryParams());
        $this->assertSame('PostmanRuntime/7.28.2', $psrRequest->getHeaderLine('User-Agent'));
        $this->assertSame('*/*', $psrRequest->getHeaderLine('Accept'));
        $this->assertSame('131fef54-a5fb-4d65-9dd4-e0ce51d2d538', $psrRequest->getHeaderLine('Postman-Token'));
        $this->assertSame(8090, $psrRequest->getUri()->getPort());
        $this->assertSame('localhost', $psrRequest->getUri()->getHost());
        $this->assertSame('gzip, deflate, br', $psrRequest->getHeaderLine('Accept-Encoding'));
        $this->assertSame('keep-alive', $psrRequest->getHeaderLine('Connection'));
        $this->assertSame('application/x-www-form-urlencoded', $psrRequest->getHeaderLine('Content-Type'));
        $this->assertSame('7', $psrRequest->getHeaderLine('Content-Length'));
        $this->assertSame(['Cookie_1' => 'value'], $psrRequest->getCookieParams());
        $this->assertSame(['bar' => 'baz'], $psrRequest->getParsedBody());
    }
}
