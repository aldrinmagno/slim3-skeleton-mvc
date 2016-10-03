<?php
// DIC configuration
$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// CSRF
$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard;
};

// Twig
$container['view'] = function ($c) {
    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());

    return $view;
};

// Session
$container['session'] = function ($c) {
    $session_factory = new \Aura\Session\SessionFactory;
    $session = $session_factory->newInstance($_COOKIE);
    return $session;
};

// Flash Messages
$container['flash'] = function ($c) {
    return new \Slim\Flash\Messages;
};

// Aura SQL
$container['db'] = function($c) {
    $settings = $c->get('settings');

    $pdo = new Aura\Sql\ExtendedPdo(
        'mysql:host='.$settings['pdo']['host'].';dbname='.$settings['pdo']['dbname'],
        $settings['pdo']['user'],
        $settings['pdo']['password']
    );

    return $pdo;
};

// Aura SQL Builder
$container['sql'] = function($c) {
    $query_factory = new \Aura\SqlQuery\QueryFactory('mysql');
    return $query_factory;
};

// Validation
$container['v'] = function($c) {
    return new \Sirius\Validation\Validator;
};

// Validation Helper
$container['vh'] = function($c) {
    return new \Sirius\Validation\Helper;
};

// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// Monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings');
    $logger = new \Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
    return $logger;
};

// -----------------------------------------------------------------------------
// Controller factories
// -----------------------------------------------------------------------------

$container['App\Controller\HomeController'] = function ($c) {
    return new App\Controller\HomeController($c);
};

// -----------------------------------------------------------------------------
// Model factories
// -----------------------------------------------------------------------------

// Example
//$container['users'] = function ($c) {
//    return new App\Model\Tblusers($c);
//};

