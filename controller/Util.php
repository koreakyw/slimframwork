<?php

class Util
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // 요청방법,기본값을 고려한 파라메터
    public function getParams($request, $defaults = [])
    {

        $query = is_array($request->getQueryParams()) ? $request->getQueryParams() : [];
        $body = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];
        
        $data = array_merge($query, $body);
        // 어레이 depth 가 1보다 클때에는, 아래 array_map 이 문제가 된다.
        // $data = array_map('trim', $data);

        // foreach ($data as $key => $val) {
        //     if (!is_array($val)) {
        //         $data[$key] = $data[$key];
        //     }
        // }

        $cipherApplyYN = $request->getHeaderLine('cipherApplyYN');

        if($cipherApplyYN == 'Y') {

            $method = $request->getMethod();
            if($method == 'GET') {
                $text = $data['data'];
            } else {
                $data_text = $request->getBody();
                $text = explode('data=', $data_text)[1];
                if(empty($text)) {
                    $txt = (array)json_decode($data_text);
                    $text = json_encode($txt['data']);
                }
                $text = str_replace("}\"", "", $text);
            }

            // require_once('../kisa/crypto.php');
            // $crypto = new Crypto;
            // // 공통으로 주는게 data= 이후 암호화 키 값이 온다.
            
            // // base64 decode
            // $base64decode_data = base64_decode($text);

            // // aes 256
            // $aes_decrypted_data = $this->aes_256_decrypted($base64decode_data);

            // // seed
            // $seed_decrypted_data = $crypto->decrypt($aes_decrypted_data);

            // $data = (array)json_decode($seed_decrypted_data);

            // 암호화 다른 버전으로 바뀜.
            $data = $this->iparkingSeedAesDecrypt($text);
        }

        foreach ($defaults as $key => $val) {
            if ($val === false && !isset($data[$key])) {
                throw new Exception($key.' 파라메터가 필요합니다.');
            } elseif (!isset($data[$key])) {
                $data[$key] = $val;
            }
        }
 
        return $data;
    }

    // 출력 포맷 //////////////////////////////////////////////////////////////

    // 전화번호 포맷
    public function formatPhone($str)
    {
        if (strpos($str, '-')) {
            return $str;
        }
        $phone[0] = substr($str, 0, 3);
        $phone[1] = strlen($str)==11 ? substr($str, 3, 4) : substr($str, 3, 3);
        $phone[2] = substr($str, -4);
        return implode('-', $phone);
    }

    // 2010-10-10와 같은 날짜를 0월0일와 같은 식으로 변경
    public function getVerboseDate($date)
    {
        $date = explode('-', $date);
        return (int)$date[1].'월'.(int)$date[2].'일';
    }

    // 숫자와 전화번호 처리
    public function juggleNumber($value)
    {
        // 전화번호
        if (substr($value, 0, 1)==='0' && !strpos($value, '.')) {
            return $value;
        } // integer, float
        elseif (is_numeric($value)) {
            return $value + 0;
        } // string
        else {
            return $value;
        }
    }

    public function imageUpload_curl($basepath, $autorename=false, $file)
    //$fileName=null, $tmp_fileName=null)
    {
        try {
            // + 문자 제거
            // $fileName = preg_replace("/[+]/i", "", $fileName);
            //$info = $this->ci->imageUploadInfo;

            $apiUrl="https://api-image.cloud.toast.com/image/v2.0/appkeys/i33jc39GynLQRJad/images"; // {appkey} 변경 필요

            $data = [];

            $array = [
                "basepath" => $basepath, //업로드 할 절대 경로 지정(folder 지정 시 해당 folder가 존재해야 함)
                "overwrite" => true,
                "autorename" => $autorename
            ];

            $data['params'] = json_encode($array);
            
            // 파일 업로드 할 파일 생성부분
            foreach($file['file']['name'] as $key => $col) {
                // 파일 정보 선언
                $tmp_fileName = $file['file']['tmp_name'][$key];
                $fileName = $file['file']['name'][$key];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $finfo = finfo_file($finfo, $tmp_fileName);
                
                if ((version_compare(PHP_VERSION, '5.5') >= 0)) {
                    $cFile = new CURLFile($tmp_fileName, $finfo, basename($fileName));
                } else {
                     $cFile = "@" . $fileName;
                }

                $data['files['.$key.']'] = $cFile;

            }

            $options=[
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data', 'Authorization: lONIPDrm'] // {secretKey} 변경 필요
                ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, $options);
            $result=curl_exec($ch);
            curl_close($ch);

            return json_decode($result, true);

        } catch (Exception $e) {
            return false;
        }
    }

    // jwt 생성
    public function createJwt($data)
    {
        $jwtEncode = \Firebase\JWT\JWT::encode(
            array(
                'email'=>$data['email'],
                'employname'=>$data['employname'],
                'employno'=>$data['employno'],
                'securelevel'=>$data['securelevel'],
                'googletoken'=>$data['googletoken'],
                'googleid'=>$data['googleid']
            ),
            $this->ci->settings['jwtkey'],
            'HS512'
        );

        return $jwtEncode;
    }

    // 파일 업로드 http TEST 용
    public function imageUpload_http($basepath, $autorename=false, $file)
    {
        try {
            // + 문자 제거
            // $fileName = preg_replace("/[+]/i", "", $fileName);
            //$info = $this->ci->imageUploadInfo;

            // body 부분
            $requestBody = [
                'headers' => [
                    'Authorization' => 'lONIPDrm'
                ],
                'multipart' => [
                    [
                        'name' => 'params',
                        'contents' => json_encode([
                            'basepath' => $basepath,
                            'overwrite' => true,
                            'autorename' => $autorename
                        ])
                    ]
                ]
            ];
                    
            foreach($file['file']['name'] as $key => $col ){

                $tmp_fileName = $file['file']['tmp_name'][$key];
                $fileName = $file['file']['name'][$key];

                array_push($requestBody['multipart'], [
                    'name' => 'files',
                    'contents' => fopen($tmp_fileName, 'r'),
                    'filename' => basename($fileName)    
                ]);

            }

            $result = $this->ci->http->post(
                'https://api-image.cloud.toast.com/image/v2.0/appkeys/i33jc39GynLQRJad/images',
                $requestBody
            );

            return json_decode($result->getBody(), true);

        } catch (Exception $e) {
            return false;
        }
    }

    public function isEmployee($employno) {

        $stmt = $this->ci->businessNoteDb->prepare('
            SELECT
                employno,
                employname,
	            securelevel 
            FROM
	            business_note.employ 
            WHERE 
	            employno = :employno
            AND
                delyn = "N" 
        ');
        $stmt->execute([ 'employno' => $employno ]);
        $data = $stmt->fetch();

        if(count($data) == 0) {
            return false;
        } else {
            $securelevel = (int) $data['securelevel'];
            if($securelevel > 5) {
                return false;
            }
        }
        
        return true;
    }

    function isMobile() {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }

    function encrypted($password)
    {
        $secret_key = $this->ci->settings['pw_secret_key'];
        $secret_iv = $this->ci->settings['pw_secret_iv'];
        return openssl_encrypt($password, 'AES-128-CBC', $secret_key, 0, $secret_iv);
    }

    function decrypted($password) 
    {
        $secret_key = $this->ci->settings['pw_secret_key'];
        $secret_iv = $this->ci->settings['pw_secret_iv'];
        return openssl_decrypt($password, 'AES-128-CBC', $secret_key, 0, $secret_iv);
    }

    function aes_256_encrypted($password)
    {
        $secret_key = $this->ci->settings['aes256_secret_key'];
        return openssl_encrypt($password, 'AES-256-CBC', $secret_key, 0, null);
    }

    function aes_256_decrypted($password) 
    {
        $secret_key = $this->ci->settings['aes256_secret_key'];
        return openssl_decrypt($password, 'AES-256-CBC', $secret_key, 0, null);
    }

    public function iparkingSecurity($type, $data)
    {
        require_once('../kisa/crypto.php');
        $crypto = new Crypto;

        if($type == 'encrypt') {
            $seed_encrypted_data = $crypto->encrypt($data);

            // echo ' seed_encrypted_data : ', $seed_encrypted_data.PHP_EOL;

            // aes 256
            $aes_encrypted_data = $this->ci->util->aes_256_encrypted($seed_encrypted_data);

            // echo ' aes enc : ', $aes_encrypted_data.PHP_EOL;

            $result = base64_encode($aes_encrypted_data);

            // echo ' base64 enc : ', $base64encode_data.PHP_EOL;
        } else if ($type == 'decrypt') {
            // base64 decode
            $base64decode_data = base64_decode($data);

            // aes 256
            $aes_decrypted_data = $this->ci->util->aes_256_decrypted($base64decode_data);

            // seed
            $seed_decrypted_data = $crypto->decrypt($aes_decrypted_data);

            $result = (array)json_decode($seed_decrypted_data);
            
        }
        
        return $result;
        
    }

    public function iparkingSeedAesEncrypt($strParams)
    {
        putenv('LC_ALL=de_DE.UTF-8');
        $strParams = "'".$strParams."'";
        exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/CeedAesCrypt/crypto.jar E '.$strParams, $output);

        $encrypt_data = "";
        foreach($output as $output_rows) {
            $encrypt_data .= $output_rows;
        }
        $encrypt_data = preg_replace("/\s+/", "", $encrypt_data);

        return $encrypt_data;
    }

    public function iparkingSeedAesDecrypt($encrypt_data)
    {
        putenv('LC_ALL=de_DE.UTF-8');
        exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/CeedAesCrypt/crypto.jar D '.$encrypt_data, $output);
        // exec('/usr/bin/java -jar /Users/ywkim/Documents/GitHub/cms-server/peristalsis/CeedAesCrypt/crypto.jar D '.$encrypt_data, $output);

        $output_count = count($output);
        if($output_count == 1) {
            $data = json_decode($output[0], true);
        } else if($output_count > 1) {
            $json_decode_string = "";
            foreach($output as $output_rows) {
                $json_decode_string .= $output_rows;
            }
            $data = json_decode($json_decode_string, true);
        }

        array_walk($data, "convert");

        return $data;
    }

    function convert(&$value, $key){
        $value = iconv('EUC-KR', 'UTF-8', $value);
    }

    public function iparkingCloudUserPasswordEncrypt($pwd)
    {
        putenv('LC_ALL=de_DE.UTF-8');

        // exec('/usr/bin/java -jar /Users/ywkim/Documents/GitHub/cms-server/peristalsis/CeedAesCrypt/parkEncrypt.jar E '.$pwd, $output);
        exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/CeedAesCrypt/parkEncrypt.jar E '.$pwd, $output);

        $encrypt_data = "";
        foreach($output as $output_rows) {
            $encrypt_data .= $output_rows;
        }
        $encrypt_data = preg_replace("/\s+/", "", $encrypt_data);

        return $encrypt_data;
    }
}
