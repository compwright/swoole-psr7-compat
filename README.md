# Openswoole PSR-7 Compatibility Package

[![Build Status](https://travis-ci.org/compwright/swoole-psr7-compat.svg?branch=master)](https://travis-ci.org/compwright/swoole-psr7-compat)
[![Coverage Status](https://coveralls.io/repos/github/compwright/swoole-psr7-compat/badge.svg?branch=master)](https://coveralls.io/github/compwright/swoole-psr7-compat?branch=master)

PSR compatibility adapters for [Openswoole](https://openswoole.com)

## Install

Via Composer

``` bash
$ composer require compwright/swoole-psr7-compat
```

## Usage

``` php
use Compwright\SwoolePsr7Compat\SwoolePsrRequestFactory;
use Compwright\SwoolePsr7Compat\SwoolePsrHandler;
use Nyholm\Psr7Server\ServerRequestCreator;

$serverRequestCreator = new ServerRequestCreator(
    // Pass in the factories here for your desired PSR-7 implementation
    new \Laminas\Diactoros\ServerRequestFactory(),
    new \Laminas\Diactoros\UriFactory(),
    new \Laminas\Diactoros\UploadedFileFactory(),
    new \Laminas\Diactoros\StreamFactory()
);
$requestFactory = new SwoolePsrRequestFactory($serverRequestCreator);
$handler = new SwoolePsrHandler($requestFactory);

$server = new Swoole\HTTP\Server('0.0.0.0', 9501);

// $app is any Psr\Http\Server\RequestHandlerInterface
$server->on('request', $handler($app));

$server->start();
```
