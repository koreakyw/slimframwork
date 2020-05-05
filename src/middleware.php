<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

// CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// CORS
$app->add(function ($req, $res, $next) {

    $env = $req->getHeaderLine('env');
    $this->settings['env'] = ($env == 'on') ? 'prod': 'test';
    //$this->settings['env'] = 'test';
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, jwt, env')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
});

$normal = function ($request, $response, $next) {
    return $next($request, $response);
};

// 토큰 파싱. 디비검사는 하지 않기로.
// 토큰 없거나 파싱 오류나면 500 리턴
$onlyMember = function ($request, $response, $next) {
    
    $token = $request->getHeaderLine('jwt');

    try {     

        require_once '../vendor/firebase/php-jwt/src/JWT.php';

        $jwt = new \Firebase\JWT\JWT;

        $decoded = $jwt::decode(
            $token,
            $this->settings['jwtkey'],
            array('HS256')
        );

    } catch (Exception $e) {
        return $response->withStatus(400)->write('잘못된 세션 토큰');
    }

    $request = $request->withAttribute('token', $decoded);
    $this->settings['userInfo'] = array(
        'id' => $decoded->id,
        'name' => $decoded->name,
        'email' => $decoded->email
    );

    $history = array(
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'methods' => $request->getMethod(),
        'path' => $request->getUri()->getPath()
    );
    
    $request = $request->withAttribute('history', $history);

    return $next($request, $response);

};

$env_set = function ($request, $response, $next){
    $env = 'off';
    if ($_SERVER['SERVER_ADDR'] == '52.78.194.15' || $_SERVER['SERVER_ADDR'] == '52.79.119.107'
        || $_SERVER['SERVER_ADDR'] == '172.31.29.201' || $_SERVER['SERVER_ADDR'] == '172.31.20.130' ) { 
        $env = 'on';            
    }

    $this->settings['env'] = ($env == 'on') ? 'prod': 'test';

    return $next($request, $response);
};