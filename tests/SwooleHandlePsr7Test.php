<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat\Tests;

use Compwright\SwoolePsr7Compat\SwooleHandlePsr7;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Swoole\Http\Response as SwooleResponse;

class SwooleHandlePsr7Test extends TestCase
{
    private $prophet;
    private $swooleResponseMock;

    public function setUp(): void
    {
        $this->prophet = new Prophet();
        $this->swooleResponseMock = $this->prophet->prophesize(SwooleResponse::class);
    }

    public function tearDown(): void
    {
        $this->prophet->checkPredictions();
    }

    public function testEmitStatusCode(): void
    {
        $psrResponse = (new PsrResponse())->withStatus(200);
        $this->swooleResponseMock->status(200)->shouldBeCalled();
        $this->swooleResponseMock->end()->shouldNotBeCalled();
        $result = SwooleHandlePsr7::emitStatusCode($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }

    public function testEmitHeaders(): void
    {
        $psrResponse = (new PsrResponse())
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Length', '256');
        
        $this->swooleResponseMock->header('Content-Type', 'text/plain')->shouldBeCalled();
        $this->swooleResponseMock->header('Content-Length', '256')->shouldBeCalled();
        $this->swooleResponseMock->end()->shouldNotBeCalled();
        
        $result = SwooleHandlePsr7::emitHeaders($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }

    public function testEmitCookies(): void
    {
        $psrResponse = (new PsrResponse())
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz');
        
        $cookie = SetCookie::create('baz')
            ->withValue('qux')
            ->withDomain('somecompany.co.uk')
            ->withPath('/')
            ->withExpires('Wed, 09 Jun 2021 10:18:14 GMT')
            ->withSecure(true)
            ->withHttpOnly(true);
        $psrResponse = FigResponseCookies::set($psrResponse, $cookie);

        $this->swooleResponseMock->cookie('foo', 'bar', 0, '/', '', false, false, '')->shouldBeCalled();
        $this->swooleResponseMock->cookie('bar', 'baz', 0, '/', '', false, false, '')->shouldBeCalled();
        $this->swooleResponseMock->cookie('baz', 'qux', 1623233894, '/', 'somecompany.co.uk', true, true, '')->shouldBeCalled();
        $this->swooleResponseMock->header('Set-Cookie', Argument::any())->shouldNotBeCalled();
        $this->swooleResponseMock->end()->shouldNotBeCalled();
        
        $result = SwooleHandlePsr7::emitCookies($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }

    public function testEmitBody(): void
    {
        $content = base64_encode(random_bytes((int) round(SwooleHandlePsr7::CHUNK_SIZE * 1.5)));
        $psrResponse = new PsrResponse();
        $psrResponse->getBody()->write($content);

        $this->swooleResponseMock->write(substr($content, 0, SwooleHandlePsr7::CHUNK_SIZE))->shouldBeCalled();
        $this->swooleResponseMock->write(substr($content, SwooleHandlePsr7::CHUNK_SIZE))->shouldBeCalled();
        $this->swooleResponseMock->end()->shouldNotBeCalled();

        $result = SwooleHandlePsr7::emitBody($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }

    public function testEmitEmptyBody(): void
    {
        $psrResponse = new PsrResponse();
        $this->swooleResponseMock->write()->shouldNotBeCalled();

        $result = SwooleHandlePsr7::emitBody($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }

    public function testEmit(): void
    {
        $content = 'Hello, world!';
        
        $psrResponse = (new PsrResponse())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Length', strlen($content));

        $cookie = SetCookie::create('foo')
            ->withValue('bar')
            ->withDomain('test.com')
            ->withPath('/')
            ->withExpires('Wed, 09 Jun 2021 10:18:14 GMT')
            ->withSecure(true)
            ->withHttpOnly(true);
        $psrResponse = FigResponseCookies::set($psrResponse, $cookie);

        $psrResponse->getBody()->write($content);

        $this->swooleResponseMock->status(200)->shouldBeCalled();
        $this->swooleResponseMock->header('Content-Type', 'text/plain')->shouldBeCalled();
        $this->swooleResponseMock->header('Content-Length', (string) strlen($content))->shouldBeCalled();
        $this->swooleResponseMock->cookie('foo', 'bar', 1623233894, '/', 'test.com', true, true, '')->shouldBeCalled();
        $this->swooleResponseMock->header('Set-Cookie', Argument::any())->shouldNotBeCalled();
        $this->swooleResponseMock->write($content)->shouldBeCalled();
        $this->swooleResponseMock->end()->shouldNotBeCalled();

        $result = SwooleHandlePsr7::emit($psrResponse, $this->swooleResponseMock->reveal());
        $this->assertSame($result, null);
    }
}
