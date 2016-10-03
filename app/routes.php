<?php
// Routes
// Home routes
$app->group('', function() {
    $this->get('/', 'App\Controller\HomeController:home')
        ->setName('index');
    $this->post('/signup', 'App\Controller\HomeController:signup')
        ->setName('singup');
    $this->post('/login', 'App\Controller\HomeController:login')
        ->setName('login');
    $this->post('/post', 'App\Controller\HomeController:post')
        ->setName('post');
    $this->post('/comments', 'App\Controller\HomeController:comment')
        ->setName('comment');
    $this->get('/logout', 'App\Controller\HomeController:logout')
        ->setName('logout');
    $this->get('/fblogin', 'App\Controller\HomeController:fbLogin')
        ->setName('fblogin');
    $this->get('/{id}/{username}/', 'App\Controller\HomeController:profile')
        ->setName('profile');
    $this->post('/{id}/add-admin', 'App\Controller\HomeController:addAdmin')
        ->setName('add-admin');
    $this->map(['GET', 'POST'], '/{id}/{username}/edit-profile', 'App\Controller\HomeController:editProfile')
        ->setName('editprofile');
    $this->map(['GET', 'POST'], '/{id}/{username}/add-verb', 'App\Controller\HomeController:addVerb')
        ->setName('addVerb');
    $this->map(['GET', 'POST'], '/{id}/{username}/edit-password', 'App\Controller\HomeController:editPassword')
        ->setName('editpassword');
    $this->post('/pickdate/', 'App\Controller\HomeController:pickDate')
        ->setName('pickdate');
    $this->get('/{viewDate}/', 'App\Controller\HomeController:viewDate')
        ->setName('viewDate');
    $this->get('/invalid-file-type', 'App\Controller\HomeController:invalidPost')
        ->setName('invalid-file-type');
    $this->post('/search/', 'App\Controller\HomeController:search')
        ->setName('search');
});

$app->group('/page', function() {
    $this->get('/pages/{offset}/', 'App\Controller\HomeController:page')
        ->setName('pages');
    $this->get('/verbs/{verb}/', 'App\Controller\HomeController:verb')
        ->setName('verbs');
    $this->get('/verbs-users/{verb}/', 'App\Controller\HomeController:verbUsers')
        ->setName('verbs-users');
    $this->get('/comments/{post}/', 'App\Controller\HomeController:getComments')
        ->setName('verbs');
    $this->post('/deletecomment/', 'App\Controller\HomeController:deleteComment')
        ->setName('delete-comment');
    $this->post('/deletepost/', 'App\Controller\HomeController:deletePost')
        ->setName('delete-post');
    $this->get('/editpost/{id}/', 'App\Controller\HomeController:editPost')
        ->setName('edit-post');
    $this->post('/saveeditpost/', 'App\Controller\HomeController:editSavePost')
        ->setName('save-edit-post');
});


$app->group('/post', function() {
    $this->get('/{id}', 'App\Controller\HomeController:viewPost')
        ->setName('commentperpost');
});
