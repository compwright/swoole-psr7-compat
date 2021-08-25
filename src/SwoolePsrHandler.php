<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat;

use Mezzio\Swoole\SwooleEmitter;
use Psr\Http\Server\RequestHandlerInterface;

class SwoolePsrHandler
{
    /**
     * @var SwoolePsrRequestFactory
     */
    private $requestFactory;

    public function __construct(SwoolePsrRequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    public function __invoke(RequestHandlerInterface $handler): callable
    {
        return function ($request, $response) use ($handler) {
            $psr7Request = $this->requestFactory->createFromSwoole($request);
            $psr7Response = $handler->handle($psr7Request);
            $emitter = new SwooleEmitter($response);
            $emitter->emit($psr7Response);
        };
    }
}
