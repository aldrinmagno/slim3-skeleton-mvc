<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class HomeController extends BaseController
{
    public function fbLogin(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $helper = $this->fb->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
             exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $res = $this->fb->get('/me?fields=id,name,email,hometown,last_name,first_name,location', $accessToken);
        $user = $res->getGraphUser();

        $data = $this->users->count('fldUserEmail = :email', array('email' => $user['email']));

        if($data['cnt'] === '1') {
            $segment->set('user_id', $data['fldUserID']);

            $baseUrl = $request->getUri()->getBaseUrl();
            return $response->withStatus(301)->withHeader('Location', $baseUrl . '/');
            exit;
        }

        $values = [
            'fldUserFName'      => htmlentities($user['first_name']),
            'fldUserLName'      => htmlentities($user['last_name']),
            'fldUserEmail'      => htmlentities($user['email']),
            'fldUserPic'        => "http://graph.facebook.com/" . htmlentities($user['id']) .'/picture?type=large',
            'fldUserLevel'      => 3,
            'fldUserCreatedOn'  => date('Y-m-d'),
            'fldUserDeleted'    => 0
        ];

        $id = $this->users->add($values);
        $segment->set('user_id', $id);

        $baseUrl = $request->getUri()->getBaseUrl();
        return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower(htmlentities($user['first_name']) . htmlentities($user['last_name'])) . '-' . date('mdY') . '/');
        exit;
    }

    public function search(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $search = $this->users->allFindBy(['fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry'], "CONCAT(fldUserFName, ' ', fldUserLName) LIKE :search", $post['search']);

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( date( 'Y-m-01' )." -$i months"));
        }

        $this->view->render($response, 'common/head.html', ['title' => 'Twiding']);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'home/search.html', ['search' => $search]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'modal/comments.html');
        $this->view->render($response, 'common/footer.html');

        return $response;

    }

    public function home(Request $request, Response $response, $args)
    {
        $verb = file_get_contents('assets/js/verb.json');
        $json = json_decode($verb);

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $reports = $this->posts->getPost(array('fldPostCreatedOn DESC'));

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( date( 'Y-m-01' )." -$i months"));
        }

        $this->view->render($response, 'common/head.html', ['title' => 'Twiding']);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'home/post.html', ['verbs' => $json, 'user' => $user, 'reports' => $reports, 'doings' => $doingMost, 'months' => $months]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'modal/comments.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function invalidPost(Request $request, Response $response, $args)
    {
        $verb = file_get_contents('assets/js/verb.json');
        $json = json_decode($verb);

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $reports = $this->posts->getPost(array('fldPostCreatedOn DESC'));
        $error = "Invalid file type.";

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( date( 'Y-m-01' )." -$i months"));
        }

        $this->view->render($response, 'common/head.html', ['title' => 'Twiding']);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'home/post.html', ['verbs' => $json, 'error' => $error, 'user' => $user, 'reports' => $reports, 'doings' => $doingMost, 'months' => $months]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'modal/comments.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function page(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $offset = htmlentities($args['offset'], ENT_QUOTES);
        $reports = $this->posts->getPostPage(array('fldPostCreatedOn DESC'), $offset);

        array_push($reports, array("session" => $segment->get('user_id')));

        return $response->withJson($reports, 200);
    }

    public function verb(Request $request, Response $response, $args)
    {
        $verb = htmlentities($args['verb'], ENT_QUOTES);
        $reports = $this->posts->getPostVerb(array('fldPostCreatedOn DESC'), $verb);

        return $response->withJson($reports, 200);
    }

    public function deleteComment(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());
        $id = $post['id'];
        echo $this->comments->edit($id);
        exit;
    }

    public function deletePost(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());
        $id = $post['id'];
        echo $this->posts->edit($id);
        exit;
    }

    public function editPost(Request $request, Response $response, $args)
    {
        $id = htmlentities($args['id'], ENT_QUOTES);

        $where = "fldPostID = :id";
        $bind = ['id' => $id];
        $postEdit = $this->posts->findBy($where, $bind);

        $data = [
            'post' => strip_tags ($postEdit['fldPost']),
            'verb' => $postEdit['fldPostVerb']
        ];

        return $response->withJson($data, 200);
    }

    public function editSavePost(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());

        $reportStart = strpos($post['fldPost'], "@");

        if($reportStart === 0 || $reportStart) {
            $reportStop = strpos($post['fldPost'], ' ', $reportStart);
            if(!$reportStop)
            $reportStop = strlen($post['fldPost']);
            $stop = (int) $reportStop - (int) $reportStart;
            $reportGet = substr($post['fldPost'], $reportStart, $stop);
            $report = str_replace($reportGet, '<a href="javascript:void(0)">' . $reportGet . '</a>', $post['fldPost']);
        } else {
            $report = $post['fldPost'];
        }

        $data = [
            'id' => $post['id'],
            'fldPost' => $report,
            'fldPostVerb' => $post['fldPostVerb']
        ];
        $this->posts->editPost($data);

        //echo $data;
        return $response->withJson($data, 200);
        exit;
    }

    public function verbUsers(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');
        $id = $segment->get('user_id');

        $verb = htmlentities($args['verb'], ENT_QUOTES);
        $reports = $this->posts->getPostVerbUsers(array('fldPostCreatedOn DESC'), $verb, $id);

        return $response->withJson($reports, 200);
    }

    public function signup(Request $request, Response $response, $args)
    {
        // sanitized input
        $post = $this->sanitize($request->getParsedBody());

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $data = $this->users->count('fldUserEmail = :email', array('email' => $post['txtEmail']));

        if($data['cnt'] === '1') {
            // return errors
            $validationError = "Email already in used";

            $this->view->render($response, 'common/head.html', ['title' => 'Validation Error']);
            $this->view->render($response, 'common/header.html');
            $this->view->render($response, 'errors/validation/signup.html', ['txtEmail' => $validationError]);
            $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
            $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
            $this->view->render($response, 'common/footer.html');

            return $response;
            exit;
        }

        // add validation rules
        $this->v->add(
            array(
                'txtFName:First Name'           => array('required'),
                'txtLName:Last Name'            => array('required'),
                'txtEmail:Email'                => array('required', 'email'),
                'txtPassword:Password'          => array('required', 'minlength(8)'),
                'txtConfPassword:Conf Password' => array('match(txtPassword)'),
                'txtStreet:Street'              => array('required'),
                'txtCity:City'                  => array('required'),
                'txtState:State'                => array('required'),
                'txtCountry:Country'            => array('required'),
                'file:Picture'                  => array('file\image')
            )
        );

        // validatte inputs
        if($_FILES['file']['name'] && $this->v->validate($post)) {

            $storage = new \Upload\Storage\FileSystem(__DIR__ . '/../../../public/assets/uploads/pictures');
            $file = new \Upload\File('file', $storage);

            $new_filename = uniqid();
            $file->setName($new_filename);

            $file->addValidations(array(
                new \Upload\Validation\Mimetype(array('image/png', 'image/jpg', 'image/jpeg', 'image/gif')),
                new \Upload\Validation\Size('3M')
            ));

            $options = [
                'cost' => 10,
            ];
            $pass = password_hash($post['txtPassword'], PASSWORD_BCRYPT, $options);

            $baseUrl = $request->getUri()->getBaseUrl();

            $values = [
                'fldUserFName'      => $post['txtFName'],
                'fldUserLName'      => $post['txtLName'],
                'fldUserEmail'      => $post['txtEmail'],
                'fldUserPassword'   => $pass,
                'fldUserStreet'     => $post['txtStreet'],
                'fldUserCity'       => $post['txtCity'],
                'fldUserState'      => $post['txtState'],
                'fldUserCountry'    => $post['txtCountry'],
                'fldUserPic'        => $baseUrl . '/assets/uploads/pictures/' . $file->getNameWithExtension(),
                'fldUserLevel'      => 3,
                'fldUserCreatedOn'  => date('Y-m-d'),
                'fldUserDeleted'    => 0
            ];
            $id = $this->users->add($values);
            $file->upload();

            $segment = $this->session->getSegment('Aura\Session\SessionFactory');
            $segment->set('user_id', $id);

            return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($post['txtFName'] . $post['txtLName']) . '-' . date('mdY') . '/');
            exit;
        } else {
            // return errors
            $validationError = $this->v->getMessages();

            $helper = $this->fb->getRedirectLoginHelper();
            $permissions = ['email']; // Optional permissions
            $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

            $this->view->render($response, 'common/head.html', ['title' => 'Validation Error']);
            $this->view->render($response, 'common/header.html');
            $this->view->render($response, 'errors/validation/signup.html', ['errorsSignup' => $validationError]);
            $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
            $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
            $this->view->render($response, 'common/footer.html');

            return $response;
        }
    }

    public function login(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());
        $data = $this->users->count('fldUserEmail = :email', array('email' => $post['txtEmail']));

        if($data['cnt'] === '1') {
            $user_data = $this->users->findBy(array('fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel', 'fldUserPassword', 'fldUserID'), 'fldUserEmail = :email', array('email' => $post['txtEmail']));
            if (password_verify($post['txtPassword'], $user_data['fldUserPassword'])) {
                $segment = $this->session->getSegment('Aura\Session\SessionFactory');
                $segment->set('user_id', $user_data['fldUserID']);
                $segment->set('user_level', $user_data['fldUserLevel']);

                return $response->withStatus(301)->withHeader('Location', '/');
                exit;
            }
        }

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $this->view->render($response, 'common/head.html', ['title' => 'Validation Error']);
        $this->view->render($response, 'common/header.html');
        $this->view->render($response, 'errors/validation/login.html', ['errors' => 'Invalid username or password']);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function logout(Request $request, Response $response, $args)
    {
        $this->session->destroy();
        return $response->withStatus(301)->withHeader('Location', '/');
    }

    public function post(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());
        $type = "Text";

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $reportStart = strpos($post['txtaPost'], "@");

        if($reportStart === 0 || $reportStart) {
            $reportStop = strpos($post['txtaPost'], ' ', $reportStart);
            if(!$reportStop)
            $reportStop = strlen($post['txtaPost']);
            $stop = (int) $reportStop - (int) $reportStart;
            $reportGet = substr($post['txtaPost'], $reportStart, $stop);
            $report = str_replace($reportGet, '<a href="javascript:void(0)">' . $reportGet . '</a>', $post['txtaPost']);
        } else {
            $report = $post['txtaPost'];
        }

        $youtubeStart = strpos($report, "https://www.youtube.com/");
        if($youtubeStart >= 0) {
            $youtubeStop = strpos($report, ' ', $youtubeStart);
            if(!$youtubeStop)
            $youtubeStop = strlen($report);
            $ystop = (int) $youtubeStop - (int) $youtubeStart;

            $youtubeGet = substr($report, $youtubeStart, $ystop);
            $query_string = parse_url($youtubeGet, PHP_URL_QUERY);
            parse_str($query_string, $yid);
            if(isset($yid['v'])) {
                $type = "Video";
                $report = str_replace($youtubeGet, '', $report);
            }
        }



        if ( ! empty( $_FILES['fileUpload']['name'][0])) {

            $storage = new \Upload\Storage\FileSystem(__DIR__ . '/../../../public/assets/uploads/reports');
            $file = new \Upload\File('fileUpload', $storage);

            $img = [];

            if (count($file) > 1) {
                foreach ($file as $key => $value) {
                    $new_filename = uniqid();
                    $file[$key]->setName($new_filename);
                }
            } else {
                $new_filename = uniqid();
                $file[0]->setName($new_filename);
            }

            $file->addValidations(array(
                new \Upload\Validation\Mimetype(array('image/png', 'image/jpg', 'image/jpeg')),
                new \Upload\Validation\Size('3M')
            ));

            try {
                $file->upload();
            } catch (\Exception $e) {
                $errors = $file->getErrors();
            }

            foreach ($file as $key => $value) {
                $img[$key] = $file[$key]->getNameWithExtension();
            }
            $values = array(
                'fldPost'             => $report,
                'fldPostVerb'         => $post['dbVerb'],
                'fldPostType'         => 'Image',
                'fldPostCreatedBy'    => $segment->get('user_id'),
                'fldPostCreatedOn'    => date('Y-m-d H:i:s'),
                'fldPostDeleted'      => 0,
                'tblUsers_fldUserID'  => $segment->get('user_id'),
                'fldPostMedia'        => $file[0]->getNameWithExtension(),
                'fldPostMedia1'       => (empty($img[1])) ? '' : $img[1],
                'fldPostMedia2'       => (empty($img[2])) ? '' : $img[2],
                'fldPostMedia3'       => (empty($img[3])) ? '' : $img[3]
            );
        } elseif ($type === "Text") {
            $values = array(
                'fldPost'             => $report,
                'fldPostVerb'         => $post['dbVerb'],
                'fldPostType'         => $type,
                'fldPostCreatedBy'    => $segment->get('user_id'),
                'fldPostCreatedOn'    => date('Y-m-d H:i:s'),
                'fldPostDeleted'      => 0,
                'tblUsers_fldUserID'  => $segment->get('user_id')
            );
        } elseif ($type === "Video") {
            $values = array(
                'fldPost'             => $report,
                'fldPostVerb'         => $post['dbVerb'],
                'fldPostType'         => $type,
                'fldPostCreatedBy'    => $segment->get('user_id'),
                'fldPostCreatedOn'    => date('Y-m-d H:i:s'),
                'fldPostDeleted'      => 0,
                'tblUsers_fldUserID'  => $segment->get('user_id'),
                'fldPostMedia'        => $yid['v']
            );
        }

        $lastId = $this->posts->add($values);

        if ( ! empty( $_FILES['fileUpload']['name'][0])) {
            if (count($file) > 1)
            foreach ($file as $key => $value) {
                $values = [
                    'fldMedia' => $file[$key]->getNameWithExtension(),
                    'fldMediaType' => "Image",
                    'fldMediaCreatedOn' => date("Y-m-d"),
                    'fldMediaCreatedBy' => $segment->get('user_id'),
                    'fldMediaDeleted' => 0,
                    'fldMediaPostID' => $lastId
                ];
                $this->medias->add($values);
            }
        }

        return $response->withStatus(301)->withHeader('Location', '/');
    }

    public function comment(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $values = array(
            'fldComment'          => $post['txtComment'],
            'tblPost_fldPostID'   => $post['tblPost_fldPostID'],
            'tblUsers_fldUserID'  => $segment->get('user_id'),
            'fldCommentCreatedBy' => $segment->get('user_id'),
            'fldCommentCreatedOn' => date('Y-m-d H:i:s'),
            'fldCommentDeleted'   => 0
        );
        $this->comments->add($values);

        $posts = $post['tblPost_fldPostID'];
        $reports = $this->comments->getCommentsUsers(array('post' => $posts));

        array_push($reports, array("session" => $segment->get('user_id')));

        return $response->withJson($reports, 200);
    }

    public function getComments(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $post = htmlentities($args['post'], ENT_QUOTES);
        $reports = $this->comments->getCommentsUsers(array('post' => $post));

        array_push($reports, array("session" => $segment->get('user_id')));

        return $response->withJson($reports, 200);
    }

    public function profile(Request $request, Response $response, $args)
    {
        $verb = file_get_contents('assets/js/verb.json');
        $json = json_decode($verb);

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);
        $username = htmlentities($args['username'], ENT_QUOTES);

        $getDate = substr(strstr($username, '-'), 1);
        $date = str_split($getDate);
        $dates = $date[4].$date[5].$date[6].$date[7]."-".$date[0].$date[1]."-".$date[2].$date[3];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( date( 'Y-m-01' )." -$i months"));
        }

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $reports = $this->posts->custProfile($dates, $id);
        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $id));
        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $this->view->render($response, 'common/head.html', ['title' => $profile['fldUserFName'] . " " . $profile['fldUserLName']]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/profile.html', ['profile' => $profile, 'user' => $user]);
        $this->view->render($response, 'profile/post.html', ['verbs' => $json, 'months' => $months, 'date' => $dates, 'profile' => $profile, 'user' => $user, 'reports' => $reports, 'doings' => $doingMost]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'modal/comments.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function editProfile(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);

        if($request->getParsedBody()) {
            $post = $this->sanitize($request->getParsedBody());

            // add validation rules
            $this->v->add(
                array(
                    'txtFName:First Name'           => array('required'),
                    'txtLName:Last Name'            => array('required'),
                    'txtEmail:Email'                => array('required', 'email'),
                    'txtStreet:Street'              => array('required'),
                    'txtCity:City'                  => array('required'),
                    'txtState:State'                => array('required'),
                    'txtCountry:Country'            => array('required'),
                    'file:Picture'                  => array('file\image')
                )
            );

            // validatte inputs
            if($_FILES['file']['name'] && $this->v->validate($post)) {

                $storage = new \Upload\Storage\FileSystem(__DIR__ . '/../../../public/assets/uploads/pictures');
                $file = new \Upload\File('file', $storage);

                $new_filename = uniqid();
                $file->setName($new_filename);

                $file->addValidations(array(
                    new \Upload\Validation\Mimetype(array('image/png', 'image/jpg', 'image/jpeg', 'image/gif')),
                    new \Upload\Validation\Size('3M')
                ));

                $baseUrl = $request->getUri()->getBaseUrl();

                $values = [
                    'fldUserFName'      => $post['txtFName'],
                    'fldUserLName'      => $post['txtLName'],
                    'fldUserEmail'      => $post['txtEmail'],
                    'fldUserStreet'     => $post['txtStreet'],
                    'fldUserCity'       => $post['txtCity'],
                    'fldUserState'      => $post['txtState'],
                    'fldUserCountry'    => $post['txtCountry'],
                    'fldUserPic'        => $baseUrl . '/assets/uploads/pictures/' . $file->getNameWithExtension(),
                ];
                $this->users->edit($id, $values);
                $file->upload();

                return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($post['txtFName'] . $post['txtLName']) . '-' . date('mdY') . '/edit-profile');
                exit;
            } elseif (!$_FILES['file']['name'] && $this->v->validate($post)) {
                $baseUrl = $request->getUri()->getBaseUrl();

                $values = [
                    'fldUserFName'      => $post['txtFName'],
                    'fldUserLName'      => $post['txtLName'],
                    'fldUserEmail'      => $post['txtEmail'],
                    'fldUserStreet'     => $post['txtStreet'],
                    'fldUserCity'       => $post['txtCity'],
                    'fldUserState'      => $post['txtState'],
                    'fldUserCountry'    => $post['txtCountry']
                ];
                $this->users->edit($id, $values);

                return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($post['txtFName'] . $post['txtLName']) . '-' . date('mdY') . '/edit-profile');
                exit;
            }
        }

        $validationError = $this->v->getMessages();

        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $id));
        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $this->view->render($response, 'common/head.html', ['title' => $profile['fldUserFName'] . " " . $profile['fldUserLName']]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/edit.html', ['profile' => $profile, 'user' => $user, 'doings' => $doingMost, 'errorsEdit' => $validationError]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function editPassword(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);

        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $id));


        if($request->getParsedBody()) {
            $post = $this->sanitize($request->getParsedBody());

            // add validation rules
            $this->v->add(
                array(
                    'txtPassword:Password'          => array('required', 'minlength(8)'),
                    'txtConfPassword:Conf Password' => array('match(txtPassword)'),
                )
            );

            if ($this->v->validate($post)) {
                $baseUrl = $request->getUri()->getBaseUrl();

                $options = [
                    'cost' => 10,
                ];
                $pass = password_hash($post['txtPassword'], PASSWORD_BCRYPT, $options);


                $values = [
                    'fldUserPassword' => $pass,
                ];
                $this->users->edit($id, $values);

                return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($profile['fldUserFName'] . $profile['fldUserLName']) . '-' . date('mdY') . '/edit-password');
                exit;
            }
        }

        $validationError = $this->v->getMessages();

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $this->view->render($response, 'common/head.html', ['title' => $profile['fldUserFName'] . " " . $profile['fldUserLName']]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/password.html', ['profile' => $profile, 'user' => $user, 'doings' => $doingMost, 'errorsEdit' => $validationError]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function addAdmin(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);

        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $id));

        $values = [
            'fldUserLevel' => 1,
        ];
        $this->users->edit($id, $values);

        return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($profile['fldUserFName'] . $profile['fldUserLName']) . '-' . date('mdY') . '/');
    }

    public function addVerb(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);
        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $id));

        if($request->getParsedBody()) {
            $post = $this->sanitize($request->getParsedBody());

            // add validation rules
            $this->v->add(
                array(
                    'atxtVerbs:Verbs' => array('required')
                )
            );

            if ($this->v->validate($post)) {
                $verbsIn = explode(PHP_EOL, $post['atxtVerbs']);
                $json = [];

                foreach ($verbsIn as $row) {
                    array_push($json, ["verb" => $row]);
                }

                array_pop($json);

                $fp = fopen('assets/js/verb.json', 'w');
                fwrite($fp, json_encode($json));
                fclose($fp);

                return $response->withStatus(301)->withHeader('Location', $baseUrl . '/' . $id . '/' . strtolower($profile['fldUserFName'] . $profile['fldUserLName']) . '-' . date('mdY') . '/add-verb');
                exit;
            }
        }
        $verb = file_get_contents('assets/js/verb.json');
        $json = json_decode($verb);

        $validationError = $this->v->getMessages();

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $this->view->render($response, 'common/head.html', ['title' => $profile['fldUserFName'] . " " . $profile['fldUserLName']]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/verb.html', ['verbs' => $json, 'profile' => $profile, 'user' => $user, 'doings' => $doingMost, 'errorsVerb' => $validationError]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function pickDate(Request $request, Response $response, $args)
    {
        $post = $this->sanitize($request->getParsedBody());
        $user = $this->users->findBy(array('fldUserFName', 'fldUserLName'), 'fldUserID = :id', array('id' => $post['id']));
        if($user) {
            $date = date("mdY", strtotime($post['date']));
            $secondParam = strtolower($user['fldUserFName'].$user['fldUserLName'])."-". $date;
            return $response->withStatus(301)->withHeader('Location', '/'.$post['id'].'/'.$secondParam."/");
        } else {
            $date = date("mdY", strtotime($post['date']));
            return $response->withStatus(301)->withHeader('Location', '/'.$date."/");
        }
    }

    public function viewDate(Request $request, Response $response, $args)
    {
        $verb = file_get_contents('assets/js/verb.json');
        $json = json_decode($verb);

        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $getDate = htmlentities($args['viewDate'], ENT_QUOTES);

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        $dateFormat = str_split($getDate);
        if(count($dateFormat) === 8) {
            $dateProper = $dateFormat[4].$dateFormat[5].$dateFormat[6].$dateFormat[7].$dateFormat[0].$dateFormat[1].$dateFormat[2].$dateFormat[3];
            $side = $dateFormat[4].$dateFormat[5].$dateFormat[6].$dateFormat[7]."-".$dateFormat[0].$dateFormat[1];
        } else {
            $dateProper = $dateFormat[2].$dateFormat[3].$dateFormat[4].$dateFormat[5].$dateFormat[0].$dateFormat[1];
            $side = $dateFormat[2].$dateFormat[3].$dateFormat[4].$dateFormat[5]."-".$dateFormat[0].$dateFormat[1];
        }

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( $side." -$i months"));
        }

        if($this->vh->date($dateProper, "Ymd")) {
            $reports = $this->posts->cust($dateProper);
            $date = date("M d, Y", strtotime($dateProper));
        } else {
            $reports = $this->posts->custMonth($dateProper);
            $date = date("M, Y", strtotime($dateProper." -$i months"));
        }

        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $this->view->render($response, 'common/head.html', ['title' => $date]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/post.html', ['verbs' => $json, 'user' => $user, 'reports' => $reports, 'doings' => $doingMost, 'date' => $getDate, 'months' => $months]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'common/footer.html');

        return $response;
    }

    public function viewPost(Request $request, Response $response, $args)
    {
        $segment = $this->session->getSegment('Aura\Session\SessionFactory');

        $id = htmlentities($args['id'], ENT_QUOTES);

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($request->getUri()->getBaseUrl() .'/fblogin', $permissions);

        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("M, Y", strtotime( date( 'Y-m-01' )." -$i months"));
        }

        $reports = $this->posts->getPostComment($id);
        $comm = $this->comments->getComments(array('id' => $id));

        $img = $this->medias->findByAll('fldMediaPostID = :id', ['id' => $id]);
        $profile = $this->users->findBy(array('fldUserID','fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $reports[0]['fldUserID']));
        $user = $this->users->findBy(array('fldUserID', 'fldUserFName', 'fldUserLName', 'fldUserEmail', 'fldUserPic', 'fldUserStreet', 'fldUserCity', 'fldUserState', 'fldUserCountry', 'fldUserLevel'), 'fldUserID = :id', array('id' => $segment->get('user_id')));
        $doingMost = $this->posts->getDoingMost('tblUsers_fldUserID = :id', array('id' => $segment->get('user_id')));

        $this->view->render($response, 'common/head.html', ['title' => $profile['fldUserFName'] . " " . $profile['fldUserLName']]);
        $this->view->render($response, 'common/header.html', ['user' => $user]);
        $this->view->render($response, 'profile/profile.html', ['profile' => $profile, 'user' => $user]);
        $this->view->render($response, 'profile/comments.html', ['imgs' => $img, 'comm' => $comm, 'months' => $months, 'profile' => $profile, 'user' => $user, 'reports' => $reports, 'doings' => $doingMost]);
        $this->view->render($response, 'modal/login.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/signup.html', ['fbLogin' => $loginUrl]);
        $this->view->render($response, 'modal/verb.html');
        $this->view->render($response, 'modal/comments.html');
        $this->view->render($response, 'common/footer.html');

        return $response;
    }
}
