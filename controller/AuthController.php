<?php

class AuthController
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function signUp($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            
            $id = $params['id'];
            $email = $params['email'];
            $name = $params['name'];
            $level = $params['level'] ?? 0;

            $check_cnt = $this->ci->auth->duplicateCheck('id', $id);
            if($check_cnt > 0) throw new ErrorException ("id 또는 email을 확인해주세요.");

            $check_cnt = $this->ci->auth->duplicateCheck('email', $email);
            if($check_cnt > 0) throw new ErrorException ("id 또는 email을 확인해주세요.");

            $result = $this->ci->auth->signUp($id, $name, $email, $level);            
            
            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(["error" => $e->getMessage()]);
        }
    }

    // 로그인
    public function signIn($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $result = $this->ci->auth->signIn($params['id'], $params['passwd']);

            $msg = $this->ci->message->apiMessage['success'];

            $data =  array(
                'tokenKey' => $result['tokenKey'],
                'jwt' => $result['jwt'],
                'id' => $result['id'],
                'name' => $result['name'],
                'level' => (string)$result['level'],
                'email' => $result['email']
            );

            $secret_data = $this->ci->util->encrypted(json_encode($data));
            
            $msg['data'] = $secret_data;

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 비밀번호 변경
    public function putChangePassword($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $id = $this->ci->settings['userInfo']['id'];
            $old_password = $params['old_password'];
            $new_password = $params['new_password'];

            $result = $this->ci->auth->changePassword($id, $old_password, $new_password);
            
            $msg = $this->ci->message->apiMessage['success'];

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 세션 체크
    public function getSession($request, $response, $args)
    {
        try {

            $tokenKey = $request->getAttribute('token')->tokenKey;
            $session = $this->ci->auth->tokenCheck('', $tokenKey);
            if(!!$session['cnt']) {
                $this->ci->auth->tokenUpdate('', $tokenKey);
                $result['data'] = array(
                    'tokenKey' => $session['tokenKey'],
                    'jwt' => $session['jwt']
                );
                return $response->withJson($result);
            } else {
                // tokenKey 체크
                $stmt = $this->ci->iparkingCmsDb->prepare('
                    SELECT count(*) as cnt FROM iparking_cms.accessToken 
                    WHERE
                        id = :id
                    AND 
                        expiryTime <= CURRENT_TIMESTAMP
                ');
                $stmt->execute(['id' => $this->ci->settings['userInfo']['id']]);
                $token_check = $stmt->fetch();
                if($token_check['cnt'] > 0) throw new ErrorException ('세션이 만료되었습니다. 다시 로그인하세요.');
                else throw new ErrorException ('다른 장치에서 로그인 되어있습니다.');                
            }

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function putResetPassword($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            $id = $params['id'];
            $email = $params['email'];

            $subject = "[파킹클라우드] CMS 비밀번호 초기화";
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
            
            $check_cnt = $this->ci->auth->duplicateCheck('id', $id);
            if($check_cnt > 0) throw new ErrorException ("id 또는 email을 확인해주세요.");

            $check_cnt = $this->ci->auth->duplicateCheck('email', $email);
            if($check_cnt > 0) throw new ErrorException ("id 또는 email을 확인해주세요.");
            
            $body = $name."님의 초기화된 비밀번호는 ".$password.' 입니다.<br>';
            $body .= '위의 비밀번호로 로그인 하신 후 \'상단의 비밀번호 변경\' 메뉴를 통해 비밀번호를 변경하시기 바랍니다.';
    
            list($code, $message) = $this->ci->mail->sendMail($from, $email, $subject, $body);
    
            $encrypt_password = $this->ci->util->encrypted($password);

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.user', [
                'password' => $encrypt_password
            ], [
                'id' => $id
            ]);

            $msg = $this->ci->message->apiMessage['success'];
            
            return $response->withJson($msg);      

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

   // iparking logout > token 삭제
    public function postIparkingLogOut($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $device_id = $params['device_id']; // token

            // mobile header sample
            /*
                env: on
                separatorDevice: ANDROID
                appVersion: 1.9.20
                reqip: 192.168.128.67
                adid: 268b0cf4-cde3-4b2d-be51-d9a1dc29ea9f
                deviceOsVersion: 24
                opercd: 1
                deviceName: SM-N920S
                cipherApplyYN: N
            */

            /*
                "AD_ID" = "21EE942E-4D15-402C-B5B6-04253FCCD4AE";
                "Accept-Language" = "ko;q=1, en-US;q=0.9";
                "Content-Type" = "application/x-www-form-urlencoded";
                "User-Agent" = IOS;
                appVersion = "1.7.6";
                deviceName = ksy;
                deviceOsVersion = "11.0.1";
                env = off;
                opercd = 1;
                reqip = "169.254.120.182";
                separatorDevice = IOS;
            */

            // 모바일 헤더 정보 파싱
            $separatorDevice = $request->getHeaderLine('separatorDevice');
            $ad_id = $request->getHeaderLine('AD_ID'); // 현재 쓰이지 않음.

            $memo_mobile_os_cd = ($separatorDevice == 'IOS') ? 2 : 1;

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT count(*) as cnt FROM fdk_parkingcloud.arf_b2ccore_member_mobile_registrationid_token 
                WHERE
                    memo_memb_seq = :memo_memb_seq 
                AND
                    memo_mobile_device_id = :memo_mobile_device_id
                AND
                    memo_mobile_os_cd = :memo_mobile_os_cd
            ');
            $stmt->execute(['memo_memb_seq' => $memb_seq, 'memo_mobile_device_id' => $device_id, 'memo_mobile_os_cd' => $memo_mobile_os_cd]);
            $token_check = $stmt->fetch();
            $token_count = $token_check['cnt'] ?? 0;

            if($token_count > 0) {
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_member_mobile_registrationid_token', [
                    'memo_mobile_device_id' => null
                ],[
                    'memo_mobile_device_id' => $device_id,
                    'memo_mobile_os_cd' => $memo_mobile_os_cd,
                    'memo_memb_seq' => $memb_seq
                ]);
            }

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT count(*) as cnt FROM fdk_parkingcloud.arf_b2ccore_member 
                WHERE
                    memb_seq = :memb_seq 
                AND
                    memb_mobile_device_id = :memb_mobile_device_id
                AND
                    memb_mobile_os_cd = :memb_mobile_os_cd
            ');
            $stmt->execute(['memb_seq' => $memb_seq, 'memb_mobile_device_id' => $device_id, 'memb_mobile_os_cd' => $memo_mobile_os_cd]);
            $memb_token_check = $stmt->fetch();
            $memb_token_count = $memb_token_check['cnt'] ?? 0;

            if($memb_token_count > 0) {
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_member', [
                    'memb_mobile_device_id' => null
                ],[
                    'memb_mobile_device_id' => $device_id,
                    'memb_mobile_os_cd' => $memo_mobile_os_cd,
                    'memb_seq' => $memb_seq
                ]);
            }

            $msg = $this->ci->message->apiMessage['success'];      
            $msg['token_count'] = $token_count;
            $msg['memb_token_count'] = $memb_token_count;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}