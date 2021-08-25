<?php

declare(strict_types=1);

namespace Compwright\SwoolePsr7Compat;

use Mezzio\Swoole\SwooleStream;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;

class SwoolePsrRequestFactory
{
    /**
     * @var ServerRequestCreator
     */
    private $requestCreator;

    public function __construct(ServerRequestCreator $requestCreator)
    {
        $this->requestCreator = $requestCreator;
    }

    public function createFromSwoole(SwooleRequest $request): ServerRequestInterface
    {
        $server = array_change_key_case($request->server, CASE_UPPER);

        // Swoole does not provide http_host and http_port in $request->server
        list($host, $port) = explode(':', $request->header['host']);
        $server['HTTP_HOST'] = $host;
        if ($port) {
            $server['SERVER_PORT'] = (int) $port;
        }

        return $this->requestCreator->fromArrays(
            $server,
            $request->header ?? [],
            $request->cookie ?? [],
            $request->get ?? [],
            $request->post ?? [],
            $request->files ?? [],
            new SwooleStream($request)
        );
    }
}
