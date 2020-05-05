<?php

class Auth {

    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    // 유저 정보 체크
    public function checkUserInfo($where, $binds)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT *  FROM iparking_cms.user 
            WHERE
                1=1 '.$where.'
        ');
        $stmt->execute($binds);

        $data = $stmt->fetch();
        return $data;
    } 

    // 유저 중복체크 (id, email 겸용)
    public function duplicateCheck($column, $columnValue) 
    {
        $where = "";
        $binds = [];

        $where = " AND ".$column." = :".$column;
        $binds[$column] = $columnValue;
        
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT count(*) as cnt  FROM iparking_cms.user 
            WHERE 1=1
                '.$where.'
        ');
        $stmt->execute($binds);

        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;
    }

    // 회원가입
    public function signUp($id, $name, $email, $level)
    {
        $subject = "[파킹클라우드] CMS 임시 비밀번호 발급";
        
        $char = 'abcdefghijklmnopqrstuvwxyz';
        $password = '';
        for($i = 0; $i < 6; $i++) {
            $password .= $char[mt_rand(0, strlen($char))];
        }

        $char = '0123456789';
        for($i = 0; $i < 6; $i++) {
            $password .= $char[mt_rand(0, strlen($char))];
        }

        $char = '!@#%^&*-_+=';
        for($i = 0; $i < 6; $i++) {
            $password .= $char[mt_rand(0, strlen($char))];
        }

        $body = $name."님의 임시비밀번호는 ".$password.' 입니다.<br>';
        $body .= '위의 비밀번호로 로그인 하신 후 \'상단의 비밀번호 변경\' 메뉴를 통해 비밀번호를 변경하시기 바랍니다.';

        list($code, $message) = $this->ci->mail->sendMail($from, $email, $subject, $body);

        $encrypt_password = $this->ci->util->encrypted($password);
        
        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.user', [[
            'id'      => $id,
            'password' => $encrypt_password,
            'name' => $name,
            'email'   => $email,
            'level' => $level,
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
    }

    // 로그인
    public function signIn($id, $password)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT *  FROM iparking_cms.user 
            WHERE
                id = :id
        ');
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch();

        if(!$data) throw new ErrorException ("아이디 또는 비밀번호를 확인해 주세요.");

        $db_password = $data['password'];
        $decrypt_db_password = $this->ci->util->decrypted($db_password);
        $decrypt_login_password = $this->ci->util->decrypted($password);

        if($decrypt_db_password != $decrypt_login_password) throw new ErrorException ("아이디 또는 비밀번호를 확인해 주세요.");

        // tokenKey 초기화
        $stmt = $this->ci->iparkingCmsDb->prepare('
            DELETE  FROM iparking_cms.accessToken 
            WHERE
                id = :id
        ');
        $stmt->execute(['id' => $id]);

        $토큰키 = $this->ci->uuid;
        $토큰페이로드 = array(
            'tokenKey'   => $토큰키,
            'id'      => $id,
            'name' => $data['name'],
            'email' => $data['email']
        );
        ///////////////
        // jwt 생성
        ///////////////
        require_once '../vendor/firebase/php-jwt/src/JWT.php';
        
        $jwt = new \Firebase\JWT\JWT;

        $jwtEncode = $jwt::encode(
            $토큰페이로드,
            $this->ci->settings['jwtkey'],
            'HS256'
        );

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.accessToken', [[
            'id'      => $id,
            // 'expiryTime' => date('Y-m-d H:i:s', strtotime('+ 7 days')),
            'expiryTime' => date('Y-m-d H:i:s', strtotime('+ 8 hours')),
            'tokenKey'   => $토큰키,
            'jwt' => $jwtEncode
        ]]);

        $result = array(
            'tokenKey' => $토큰키, 
            'jwt' => $jwtEncode,
            'id' => $id,
            'name' => $data['name'],
            'level' => $data['level'],
            'email' => $data['email']
        );

        return $result;

    }

    // 비밀번호 변경
    public function changePassword($id, $old_password, $new_password)
    {
        $data = $this->checkUserInfo('AND id = :id', ['id' => $id]);
        $encrypt_db_password = $data['password'];
        if($old_password != $encrypt_db_password) throw new ErrorException ("아이디 또는 비밀번호를 확인해 주세요.");
        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.user', [
            'password' => $new_password
        ], [
            'id' => $id
        ]);
        return true;
    }

    // 토큰 체크
    public function tokenCheck($id, $tokenKey=null)
    {

        $binds = [];
        $where = '';

        if($id) {
            $where .= ' AND id = :id';
            $binds['id'] = $id;
        }

        if($tokenKey) {
            $where .= ' AND tokenKey = :tokenKey';
            $binds['tokenKey'] = $tokenKey;
        }

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT count(*) as cnt, tokenKey, jwt FROM iparking_cms.accessToken 
            WHERE 1=1
            '.$where.'
            AND 
                expiryTime > CURRENT_TIMESTAMP
        ');
        $stmt->execute($binds);
        $session = $stmt->fetch();

        return $session;
    }

    // 토큰 갱신
    public function tokenUpdate($id, $tokenKey=null)
    {
        if($id) {
            $binds['id'] = $id;    
        }

        if($tokenKey) {
            $binds['tokenKey'] = $tokenKey;
        }

        $expiryTime =  date('Y-m-d H:i:s', strtotime('+ 8 hours'));
        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.accessToken', [
            'expiryTime' => $expiryTime
        ], $binds);
        return [$session['tokenKey'], $session['jwt']];
    }

}