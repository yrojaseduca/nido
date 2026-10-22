<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$f3 = Base::instance();

$f3->set('AUTOLOAD', dirname(__DIR__) . '/src/');

$registerRoutes = require dirname(__DIR__) . '/src/Routes/api.php';
$registerRoutes($f3);

$f3->run();
