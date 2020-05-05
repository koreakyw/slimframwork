<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {

    $env = $c['settings']['env'];

    $client = new Raven_Client('https://4eb31fa192cb4cf79784b9808da85b17:0b76357685fc4365ba9dc2943f53a9d4@sentry.parkingcloud.co.kr/14');
    $handler = new Monolog\Handler\RavenHandler($client);
    $handler->setFormatter(new Monolog\Formatter\LineFormatter("%message% %context% %extra%\n"));

    $settings = $c->get('settings')['monolog'];
    $logger = new \Monolog\Logger($settings['projectname']);

    if($env == 'prod') {      
        $logger->pushHandler($handler);
        $logger->pushHandler(new \Monolog\Handler\ChromePHPHandler(\Monolog\Logger::DEBUG));
        $logger->pushProcessor(new Monolog\Processor\WebProcessor());
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        
    } 
    
    return $logger;
};

$container['iparkingCmsDb'] = function ($c) {
    
    $env = $c['settings']['env'];
    $db = $c['settings']['db'][$env]['iparkingCmsDb']; // settings.db

    $pdo = new PDO(
        "mysql:host=" . $db['host'] .";port=" . $db['port'] . "dbname=" . $db['dbname'] . ";charset=utf8",
        $db['user'],
        $db['pass']
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );

    return $pdo;
};

$container['iparkingCloudDb'] = function ($c) {
    
    $env = $c['settings']['env'];
    $db = $c['settings']['db'][$env]['iparkingCloudDb']; // settings.db

    $pdo = new PDO(
        "mysql:host=" . $db['host'] .";port=" . $db['port'] . "dbname=" . $db['dbname'] . ";charset=utf8",
        $db['user'],
        $db['pass']
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );

    return $pdo;
};

// Http
$container['http'] = function ($c) {
    return new GuzzleHttp\Client();
};

// UUID
$container['uuid'] = function ($c) {
    // $uuid4 = Ramsey\Uuid\Uuid::uuid4();

    // return $uuid4->toString();
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
};

$container['dbutil'] = function ($c) {
    return new DbUtil($c);
};

$container['util'] = function ($c) {
    return new Util($c);
};

$container['message'] = function ($c) {
    return new Msg($c);
};

$container['mail'] = function ($c) {
    return new Email($c);
};

$container['file'] = function ($c) {
    return new File($c);
};

$container['label'] = function ($c) {
    return new Label($c);
};

$container['phpMailer'] = function ($c) {
    return new PHPMailer\PHPMailer\PHPMailer(true);
};

$container['point'] = function ($c) {
    return new Point($c);
};

// AWS
$container['s3'] = function ($c) {
    return new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => 'ap-northeast-2',
        'credentials' => [
            'key' => 'AKIAIHTTM7R4424P5ARA',
            'secret' => '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'
        ]
    ]);
};


$container['auth'] = function ($c) {
    return new Auth($c);
};

$container['faq'] = function ($c) {
    return new FaQ($c);
};

$container['sms'] = function ($c) {
    return new Sms($c);
};

$container['eventBanner'] = function ($c) {
    return new EventBanner($c);
};

$container['policy'] = function ($c) {
    return new Policy($c);
};

$container['phpRenderer'] = function ($c) {
    return new \Slim\Views\PhpRenderer("../templates");
};

$container['coupon'] = function ($c) {
    return new Coupon($c);
};

$container['log'] = function ($c) {
    return new Log($c);
};

$container['relay'] = function ($c) {
    return new Relay($c);
};

$container['userInfo'] = function ($c) {
    return new UserInfo($c);
};