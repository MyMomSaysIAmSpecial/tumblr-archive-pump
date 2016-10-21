<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

$container->register('http', \GuzzleHttp\Client::class);

$container->register('fs', \Symfony\Component\Filesystem\Filesystem::class);

$container->register('console', Symfony\Component\Console\Application::class)
    ->addMethodCall('add', [new Reference('extract_photos_command')]);

$container->register('extract_photos_command', \GirlsExtractor\Command\ExtractPhotos::class)
    ->addArgument(new Reference('service_container'));

return $container;