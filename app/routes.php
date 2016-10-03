<?php
// Routes
// Home routes
$app->group('', function() {
    $this->get('/', 'App\Controller\HomeController:home')
        ->setName('index');
});