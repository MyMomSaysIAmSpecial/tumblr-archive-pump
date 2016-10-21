<?php

if (!empty($argv)) {
    $container = require __DIR__ . '/app/bootstrap.php';
    $container->get('console')->run();
} else {
    echo 'It\'s a console app (╯°□°）╯︵ ┻━┻ ';
}
