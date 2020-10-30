# Swoole PSR-7 Compatibility Package

[![Build Status](https://travis-ci.org/compwright/swoole-psr7-compat.svg?branch=master)](https://travis-ci.org/compwright/swoole-psr7-compat)
[![Coverage Status](https://coveralls.io/repos/github/compwright/swoole-psr7-compat/badge.svg?branch=master)](https://coveralls.io/github/compwright/swoole-psr7-compat?branch=master)

Converts PSR-7 Request/Response to Swoole Request/Response

## Install

Via Composer

``` bash
$ composer require compwright/swoole-psr7-compat
```

## Usage

``` php
use Swoole\HTTP\Server as SwooleServer;
use Compwright\SwoolePsr7Compat\SwooleHandlePsr7;

$server = new SwooleServer('0.0.0.0', 9501);

// $app is any Psr\Http\Server\RequestHandlerInterface
$server->on('request', new SwooleHandlePsr7($app));

$server->start();
```
