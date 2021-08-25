<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat\Tests\Mocks;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EchoHandler implements RequestHandlerInterface
{
    public function __construct(string $echo)
    {
        $this->echo = $echo;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $psr17factory = new Psr17Factory();
        $body = $psr17factory->createStream($this->echo);
        return $psr17factory->createResponse()
            ->withHeader('Content-Length', '7')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($body);
    }
}
