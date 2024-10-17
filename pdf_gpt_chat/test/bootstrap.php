<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'testing');
$kernel->boot();
$kernel->preHandle($request);

// Make the container available to tests.
$container = $kernel->getContainer();