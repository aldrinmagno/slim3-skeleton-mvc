<?php

namespace App\Controller;

use Slim\Container;

class BaseController
{
    protected $view;
    protected $logger;
    protected $flash;
    protected $fb;
    protected $csrf;
    protected $session;
    protected $v;
    protected $vh;
    protected $upload;
    protected $hashids;

    // Queries
    protected $users;
    protected $posts;
    protected $comments;
    protected $medias;

    public function __construct(Container $c)
    {
        $this->view = $c->get('view');
        $this->logger = $c->get('logger');
        $this->flash = $c->get('flash');
        $this->fb = $c->get('fb');
        $this->csrf = $c->get('csrf');
        $this->session = $c->get('session');
        $this->v = $c->get('v');
        $this->upload = $c->get('upload');
        $this->hashids = $c->get('hashids');
        $this->vh = $c->get('vh');

        // Queries
        $this->users = $c->get('users');
        $this->posts = $c->get('posts');
        $this->comments = $c->get('comments');
        $this->medias = $c->get('medias');
    }

    public function sanitize($input)
    {
        foreach ($input as $key => $value) {
            $san[$key] = htmlentities($value, ENT_QUOTES);
        }
        return $san;
    }
}
