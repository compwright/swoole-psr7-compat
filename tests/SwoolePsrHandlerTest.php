<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat\Tests;

use Compwright\SwoolePsr7Compat\SwoolePsrHandler;
use Compwright\SwoolePsr7Compat\SwoolePsrRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class SwoolePsrHandlerTest extends TestCase
{
    /**
     * @var SwoolePsrRequestFactory
     */
    private $requestFactory;

    /**
     * @var SwooleRequest
     */
    private $swooleRequest;

    /**
     * @var SwooleResponse|MockObject
     * @psalm-var MockObject&SwooleResponse
     */
    private $swooleResponse;

    public function setUp(): void
    {
        $p = new Psr17Factory();
        $serverRequestCreator = new ServerRequestCreator($p, $p, $p, $p);
        $this->requestFactory = new SwoolePsrRequestFactory($serverRequestCreator);
        $this->swooleRequest = SwooleRequest::create();
        $this->swooleRequest->parse(file_get_contents(__DIR__ . '/test.txt'));
        $this->swooleResponse = $this->createMock(SwooleResponse::class);
    }

    public function testHandle(): void
    {
        $handler = new SwoolePsrHandler($this->requestFactory);
        $swooleHandler = $handler(new Mocks\EchoHandler('Hello, world!'));

        $this->swooleResponse->expects($this->once())
            ->method('status')->with(200);

        $this->swooleResponse->expects($this->exactly(2))
            ->method('header')->withConsecutive(
                ['Content-Length', '7'],
                ['Content-Type', 'text/plain']
            );

        $this->swooleResponse->expects($this->once())
            ->method('end')->with('Hello, world!');

        $swooleHandler($this->swooleRequest, $this->swooleResponse);
    }
}
