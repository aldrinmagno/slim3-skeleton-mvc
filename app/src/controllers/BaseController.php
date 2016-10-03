<?php

namespace App\Controller;

use Slim\Container;

class BaseController
{
    protected $view; // Twig
    protected $logger; // Monolog
    protected $flash; // Flash
    protected $csrf; // CSRF
    protected $session; // Aura/Session
    protected $v; // Validation
    protected $vh; // Validation Helper

    public function __construct(Container $c)
    {
        $this->view = $c->get('view');
        $this->logger = $c->get('logger');
        $this->flash = $c->get('flash');
        $this->csrf = $c->get('csrf');
        $this->session = $c->get('session');
        $this->v = $c->get('v');
        $this->vh = $c->get('vh');
    }

    // Sanitize Inputs
    public function sanitize($input)
    {
        foreach ($input as $key => $value) {
            $san[$key] = htmlentities($value, ENT_QUOTES);
        }

        return $san;
    }
}
