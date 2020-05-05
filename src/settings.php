<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'path' => __DIR__ . '/../logs/',
            'projectname' => 'cms-server'
        ],
        'jwtkey' => 'cms-server',
        'userInfo' => [
            'email' => '',
            'employname' => '',
            'employno' => '',
            'securelevel' => '',
            'googletoken' => '',
            'googleid' => '',
            'id'=> '',
            'name' => ''
        ],
        'S3도메인' => 'http://image.parkingcloud.co.kr',
        // 디비 환경 : on (live), off (test)
        'env' => 'off',
        // 암호화 키
        'pw_secret_key' => 'cWtxaGFqZGNqZGRs',
        'pw_secret_iv' => 'YnVzaW5lc3Nub3Rl',
        'aes256_secret_key' => 'parkingcloudparkjeonghyeonjjang!',
        'domain' => [
            'prod' => 'http://iparking.co.kr:9060',
            'test' => 'http://stg.iparking.co.kr:7060'
        ],
        // db
        'db' => [
            'prod' => [
                'iparkingCmsDb' => [
                    'host' => 'front-iparking-prod.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                    'user' => 'pkcmyam',
                    'pass' => 'Cndwjdfh!#',
                    'dbname' => 'iparking_cms',
                    'port' => '33069'
                ],
                'iparkingCloudDb' => [
                    'host' => 'iparking-prod.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                    'user' => 'pkcmyam',
                    'pass' => 'Cndwjdfh!#',
                    'dbname' => 'fdk_parkingcloud',
                    'port' => '33069'
                ]
                // 'iparkingCmsDb' => [
                //     'host' => 'front-iparking-dev.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                //     'user' => 'pkcmyam',
                //     'pass' => 'Cndwjdfh!#',
                //     'dbname' => 'iparking_cms',
                //     'port' => '3306'
                // ],
                // 'iparkingCloudDb' => [
                //     'host' => 'iparking-devel.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                //     'user' => 'pkcmyam',
                //     'pass' => 'Cjdeka04!*',
                //     'dbname' => 'fdk_parkingcloud',
                //     'port' => '3306'
                // ]
            ],
            'test' => [
                'iparkingCmsDb' => [
                    'host' => 'front-iparking-dev.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                    'user' => 'pkcmyam',
                    'pass' => 'Cndwjdfh!#',
                    'dbname' => 'iparking_cms',
                    'port' => '3306'
                ],
                'iparkingCloudDb' => [
                    'host' => 'iparking-devel.ci1zlge1srsh.ap-northeast-2.rds.amazonaws.com',
                    'user' => 'pkcmyam',
                    'pass' => 'Cjdeka04!*',
                    'dbname' => 'fdk_parkingcloud',
                    'port' => '3306'
                ]
            ]
        ],
        'objectStorage' => [
            'url' => 'https://api-storage.cloud.toast.com/v1/AUTH_e57a1e6a372243f3925cb5ccce399b6e',
            'Identity' => 'https://api-compute.cloud.toast.com/identity/v2.0',
            'TenantId' => 'e57a1e6a372243f3925cb5ccce399b6e', // 토큰 받을 용도
            'TenantName' => 'Mv4pGgjT',
            'Account' => 'AUTH_e57a1e6a372243f3925cb5ccce399b6e', // API 호출 받을때 반드시 필요
            'TenantPassword' => 'pacl6328'
        ]
    ],
];
