<?php

namespace Compwright\SwoolePsr7Compat;

use Dflydev\FigCookies\SetCookies;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class SwooleHandlePsr7
{
    // https://www.swoole.co.uk/docs/modules/swoole-http-server/methods-properties#swoole-http-response-write
    public const CHUNK_SIZE = 2097152;

    private $psrRequestFactory;
    private $psrHandler;

    public function __construct(RequestHandlerInterface $psrHandler)
    {
        $psr17factory = new Psr17Factory();

        $this->psrRequestFactory = new ServerRequestCreator(
            $psr17factory, // ServerRequestFactory
            $psr17factory, // UriFactory
            $psr17factory, // UploadedFileFactory
            $psr17factory  // StreamFactory
        );

        $this->psrHandler = $psrHandler;
    }

    public function __invoke(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        $psrRequest = $this->psrRequestFactory->fromArrays(
            array_change_key_case($swooleRequest->server, CASE_UPPER), // server
            $swooleRequest->header ?? [],
            $swooleRequest->cookie ?? [],
            $swooleRequest->get ?? [],
            $swooleRequest->post ?? [],
            $swooleRequest->files ?? [],
            $swooleRequest->rawContent() ?: ''
        );
        
        $psrResponse = $this->psrHandler->handle($psrRequest);

        self::emit($psrResponse, $swooleResponse);

        $swooleResponse->end();
    }

    public static function emit(PsrResponse $psrResponse, SwooleResponse $swooleResponse): void
    {
        self::emitStatusCode($psrResponse, $swooleResponse);
        self::emitHeaders($psrResponse, $swooleResponse);
        self::emitCookies($psrResponse, $swooleResponse);
        self::emitBody($psrResponse, $swooleResponse);
    }

    public static function emitStatusCode(PsrResponse $psrResponse, SwooleResponse $swooleResponse): void
    {
        $swooleResponse->status($psrResponse->getStatusCode());
    }

    public static function emitHeaders(PsrResponse $psrResponse, SwooleResponse $swooleResponse): void
    {
        $headers = $psrResponse->withoutHeader(SetCookies::SET_COOKIE_HEADER)->getHeaders();
        foreach ($headers as $name => $values) {
            $name = ucwords($name, '-');
            $swooleResponse->header($name, implode(', ', $values));
        }
    }

    public static function emitCookies(PsrResponse $psrResponse, SwooleResponse $swooleResponse): void
    {
        foreach (SetCookies::fromResponse($psrResponse)->getAll() as $cookie) {
            $swooleResponse->cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpires(),
                $cookie->getPath() ?: '/',
                $cookie->getDomain() ?: '',
                $cookie->getSecure(),
                $cookie->getHttpOnly()
            );
        }
    }

    public static function emitBody(PsrResponse $psrResponse, SwooleResponse $swooleResponse, $chunkSize = self::CHUNK_SIZE): void
    {
        $body = $psrResponse->getBody();

        if ($body->isReadable() && $body->getSize() > $chunkSize) {
            $body->rewind();
            while (!$body->eof()) {
                $chunk = $body->read($chunkSize);
                if (!empty($chunk)) {
                    $swooleResponse->write($chunk);
                }
            }
            return;
        }

        $bodyStr = (string) $body;
        if (!empty($bodyStr)) {
            $swooleResponse->write($bodyStr);
        }
    }
}
