<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);
//$app->add(new \Slim\Csrf\Guard);
