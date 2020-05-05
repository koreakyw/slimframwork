<?php
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
class Point
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;

        $env = 'off';
        if ($_SERVER['SERVER_ADDR'] == '52.78.194.15' || $_SERVER['SERVER_ADDR'] == '52.79.119.107'
	  		|| $_SERVER['SERVER_ADDR'] == '172.31.29.201' || $_SERVER['SERVER_ADDR'] == '172.31.20.130' ) { 
            $env = 'on';            
        }
        $this->env =$env;
        
        $sv = $this->ci->settings['env'];
        $relay_domain = $this->ci->settings['domain'][$sv];
        $this->relay_domain = $relay_domain;

        
    }
/*
    public function getProductDetailInfo($memb_seq, $park_seq=null, $prdt_seq=null, $bcar_seq=null,  $ppsl_seq=null, $product_seq=null, $product_cd=null)
    {

        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                app.park_name,
                aocc.park_operating_cmpy_cd,
                aocc.aocc_oper_name
            FROM
                fdk_parkingcloud.acd_pms_parkinglot app
            LEFT JOIN arf_operation_company_code aocc on app.park_operating_cmpy_cd = aocc.aocc_oper_cd
            WHERE
                app.park_del_ny = 0
            AND
                aocc.aocc_del_ny = 0
            AND 
                app.park_seq = :park_seq     
        ');

        $stmt->execute(['park_seq' => $park_seq]);
        $park_info = $stmt->fetch();
        $park_name = $park_info['park_name'];
        $operating_cmpy_cd = $park_info['park_operating_cmpy_cd'];
        $operating_cmpy_name = $park_info['aocc_oper_name'];

        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT
                bcar_num
            FROM
                fdk_parkingcloud.arf_b2ccore_car
            WHERE
                bcar_del_ny = 0
            AND 
                bcar_seq = :bcar_seq   
        ');

        $stmt->execute(['bcar_seq' => $bcar_seq]);
        $car_info = $stmt->fetch();
        $car_number = $car_info['bcar_num'];

        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                prdt_name
            FROM 
                fdk_parkingcloud.acd_rpms_parking_product
            WHERE 
                prdt_del_ny = 0
            AND 
                prdt_seq = :prdt_seq
        ');

        $stmt->execute(['prdt_seq' => $prdt_seq]);
        $product_info = $stmt->fetch();
        $prdt_name = $product_info['prdt_name'];

        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                concat(memb_phone_1,memb_phone_2,memb_phone_3) as phone_number,
                concat(memb_mobile_1,memb_mobile_2,memb_mobile_3) as mobile_number,
                memb_name
            FROM 
                fdk_parkingcloud.arf_b2ccore_member
            WHERE 
                memb_del_ny = 0 
            AND 
                memb_seq = :memb_seq  
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $mem_info = $stmt->fetch();
        $phone_number = $mem_info['phone_number'];
        $memb_name = $mem_info['memb_name'];
        $mobile_number = $mem_info['mobile_number'];

        if($product_cd == 2) {
            // 가격을 가져와야하는데. 정기권 테이블에서 가져온다.
            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT  
                    ps.ppsl_price as price,
                    mem.memb_name,
                    concat(mem.memb_phone_1,mem.memb_phone_2,mem.memb_phone_3) as phone_number,
                    concat(mem.memb_mobile_1,mem.memb_mobile_2,mem.memb_mobile_3) as mobile_number
                FROM fdk_parkingcloud.acd_rpms_parking_product_sales ps
                LEFT JOIN fdk_parkingcloud.arf_b2ccore_member mem on ps.ppsl_buyer_seq = mem.memb_seq
                WHERE
                    ps.ppsl_del_ny = 0
                AND 
                    mem.memb_del_ny = 0
                AND 
                    ppsl_seq = :ppsl_seq
            ');

            $stmt->execute(['ppsl_seq' => $ppsl_seq]);
            $mem_info = $stmt->fetch();
            $phone_number = $mem_info['phone_number'];
            $memb_name = $mem_info['memb_name'];
            $mobile_number = $mem_info['mobile_number'];
        } 
        // 입출차 테이블에서 가격정보를 가져온다.
        else if($product_cd == 5) {
            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT   
                    inot_price as price
                FROM 
                    fdk_parkingcloud.acd_rpms_inout
                WHERE
                    inot_del_ny = 0
                AND 
                    inot_seq = :product_seq
            ');

            $stmt->execute(['product_seq' => $product_seq]);
            $inout_info = $stmt->fetch();
        }

        if($phone_number == null) {
            $phone_number = $mobile_number;
        }

        return array(
            'phone_number' => $phone_number,
            'memb_name' => $memb_name,
            'prdt_name' => $prdt_name,
            'car_number' => $car_number,
            'park_name' => $park_name,
            'operating_cmpy_cd' => $operating_cmpy_cd,
            'operating_cmpy_name' => $operating_cmpy_name
        );
    }
*/

    // 암호화 하기
    public function fixKey($key) {
        
        if (strlen($key) < 16) {
            //0 pad to len 16
            return str_pad("$key", 16, "0"); 
        }
        
        if (strlen($key) > 16) {
            //truncate to 16 bytes
            return substr($str, 0, 16); 
        }
        return $key;
    }
    
    // xml To Array
    public function xmlToArray($xml, $options = array()) {
        $defaults = array(
            'namespaceSeparator' => ':',//you may want this to be something other than a colon
            'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array(),   //array of xml tag names which should always become arrays
            'autoArray' => true,        //only create arrays for tags which appear more than once
            'textContent' => '$',       //key used for the text content of elements
            'autoText' => true,         //skip textContent key if node has no attributes or child nodes
            'keySearch' => false,       //optional search and replace on tag and attribute names
            'keyReplace' => false       //replace values for above search values (as passed to str_replace())
        );
        $options = array_merge($defaults, $options);
        $namespaces = $xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace
     
        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) $attributeName =
                        str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                $attributeKey = $options['attributePrefix']
                        . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                        . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }
     
        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray = $this->xmlToArray($childXml, $options);
                list($childTagName, $childProperties) = each($childArray);
     
                //replace characters in tag name
                if ($options['keySearch']) $childTagName =
                        str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                //add namespace prefix, if any
                if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
     
                if (!isset($tagsArray[$childTagName])) {
                    //only entry with this key
                    //test if tags of this type should always be arrays, no matter the element count
                    $tagsArray[$childTagName] =
                            in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                            ? array($childProperties) : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    //key already exists and is integer indexed array
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }
     
        //get text content of node
        $textContentArray = array();
        $plainText = trim((string)$xml);
        if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;
     
        //stick it all together
        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
                ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;
     
        //return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }

    // point 예약
    public function pointReservation(
        $memb_seq, $point_card_code, $billing_key, $billing_password, 
        $totalPaymentAmt, $pointAmount, $park_operate_ct,
        $sellerOrderReferenceKey, $ppsl_seq, $payment_channel, $apply_orderno=null, $product_cd=null, $bcar_seq=null, $bcar_number=null, $park_seq=null, $prdt_seq=null, $cp_price, $cp_hist_seq, $accumulate_yn 
    ) {
        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.point_reservation', [[
            'memb_seq' => $memb_seq,
            'point_card_code' => $point_card_code,
            'billing_key' => $this->ci->util->encrypted($billing_key),
            'billing_password' => $this->ci->util->encrypted($billing_password),
            'amount' => $totalPaymentAmt,
            'pointAmount' => $pointAmount,
            'park_operate_ct' => $park_operate_ct,
            'ppsl_seq' => $ppsl_seq, 
            'sellerOrderReferenceKey' => $sellerOrderReferenceKey,         
            'payment_channel' => $payment_channel,
            'apply_orderno' => $apply_orderno,
            'product_cd' => $product_cd, 
            'bcar_seq' => $bcar_seq, 
            'bcar_number' => $bcar_number, 
            'park_seq' => $park_seq, 
            'prdt_seq' => $prdt_seq,
            'cp_price' => $cp_price,
            'cp_hist_seq' => $cp_hist_seq,
            'accumulate_yn ' => $accumulate_yn 
        ]]);

        $last_seq = $this->ci->iparkingCmsDb->lastInsertId();

        return $last_seq;
    }

    // point 예약 정보 가져오기
    public function getPointReservation($idx)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                * 
            FROM 
                iparking_cms.point_reservation 
            WHERE 
                idx = :idx
        ');

        $stmt->execute(['idx' => $idx]);

        $data = $stmt->fetch();

        return $data;
    }

    // point 예약정보로 예약 포인트 사용 실행하기
    public function reservationPayment($idx)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                * 
            FROM 
                iparking_cms.point_reservation 
            WHERE 
                idx = :idx
        ');

        $stmt->execute(['idx' => $idx]);

        $data = $stmt->fetch();

        $result_code = '99';
        if(!empty($data)) {
            
            $memb_seq = $data['memb_seq'];
            $point_card_code = $data['point_card_code'];
            $billing_key = $this->ci->util->decrypted($data['billing_key']);
            $billing_password = $this->ci->util->decrypted($data['billing_password']);
            $amount = $data['amount'] ?? 0;
            $pointAmount = $data['pointAmount'] ?? 0;
            $park_operate_ct = $data['park_operate_ct'];
            $reserve_complete_yn = $data['reserve_complete_yn'];  
            $ppsl_seq = $data['ppsl_seq'];
            $payment_channel = $data['payment_channel'];
            $apply_orderno = $data['apply_orderno'];
            $product_cd = $data['product_cd']; 
            $bcar_seq = $data['bcar_seq']; 
            $bcar_number = $data['bcar_number']; 
            $park_seq = $data['park_seq']; 
            $prdt_seq = $data['prdt_seq'];
            $version = $data['version'] ?? 'v1.0';
            $cp_hist_seq = $data['cp_hist_seq'];
            $cp_price = $data['cp_price'];
            $accumulate_yn = $data['accumulate_yn'];

            if($reserve_complete_yn == 1) throw new Exception("이미 처리되었습니다.");

            // 블루포인트
            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                list($point_info, $result_code) = $this->ci->point->bluePointUse($version, $memb_seq, $point_card_code, $pointAmount, $park_seq, $park_operate_ct, $product_cd, $ppsl_seq, $amount, $amount+$pointAmount+$cp_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $bcar_seq, $bcar_number, $billing_key, $prdt_seq);
                
                if($result_code == '00') {
                    $point_approv_no = $point_info['appl_num'];
                    $point_approv_date = $point_info['appl_date'];
                    $point_approv_time = $point_info['appl_time'];
                    $tid = $point_info['tid'];
                    $pg_site_cd = $point_info['pg_site_cd'];
                }
                
            } else if($point_card_code == 'GSP') {
                list($point_info, $result_code) = $this->ci->point->gsPointUse($version, $billing_key, $park_operate_ct, $amount, $pointAmount, $memb_seq, $ppsl_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                
                if($result_code == '00') {
                    $point_approv_no = $point_info['approv_no'];
                    $point_approv_date = $point_info['approv_date'];
                    $point_approv_time = $point_info['approv_time'];
                    $tid = $point_info['chasing_no'];
                    if( $park_operate_ct == 2 ){
                        if( $amount != $pointAmount && $amount > $pointAmount ){
                            $save_point = $point_info['save_point'];
                        }
                    }
                }

            } else if($point_card_code == 'LTP') { 
                if ( $accumulate_yn == 0){
                    list($point_info, $result_code) = $this->ci->point->LPointUse($version, $billing_key, $billing_password, $park_operate_ct, $amount, $pointAmount, $ppsl_seq, $memb_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                    
                    if($result_code == '00') {
                        $point_approv_no = $point_info['aprno'];
                        $point_approv_date = $point_info['aprDt'];
                        $point_approv_time = $point_info['aprHr'];
                        $tid = $point_info['control']['flwNo'];
                    }
                } else if ( $accumulate_yn == 1){
                    list($point_info, $result_code) = $this->ci->point->LPointOnlyAccumulate($version, $billing_key, $billing_password, $amount, $pointAmount, $park_operate_ct, $memb_seq, $payment_channel, $product_cd, $ppsl_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                    
                    if($result_code == '00') {
                        $point_approv_no = $point_info['aprno'];
                        $point_approv_date = $point_info['aprDt'];
                        $point_approv_time = $point_info['aprHr'];
                        $tid = $point_info['control']['flwNo'];
                        $save_point = $point_info['save_point'];
                    }
                }
                
            }
            
            if($result_code == '00') {
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.point_reservation', [
                    'reserve_complete_yn' => 1
                ], 
                [
                    'idx' => $idx
                ]);

                $point_use['appl_num'] = $point_approv_no;
                $point_use['appl_date'] = $point_approv_date;
                $point_use['appl_time'] = $point_approv_time;
                $point_use['park_operate_ct'] = $park_operate_ct;
                $point_use['billing_key'] = $billing_key;
                $point_use['point_card_code'] = $point_card_code;
                $point_use['point_tid'] = $tid;
                $point_use['pg_site_cd'] = $pg_site_cd;
                $point_use['save_point'] = $save_point;
                return [$point_use, $result_code];
            } else {
                return [(object)[], '99'];
            }
            
        }
        
        return [(object)[], '99'];
    }

    public function memberPointCardCheck($memb_seq, $point_card_code)
    {
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                count(*) as cnt
            FROM 
                fdk_parkingcloud.member_point_card_info 
            WHERE 
                memb_seq = :memb_seq
            AND
                point_card_code = :point_card_code
            AND 
               is_deleted = :is_deleted 
        ');

        $stmt->execute([
            'memb_seq' => $memb_seq,
            'point_card_code' => $point_card_code,
            'is_deleted' => 'N'
        ]);

        $data = $stmt->fetch();

        $count = $data['count'] ?? 0;
        if($count > 1) {
            $member_point_card_yn = 1;
        } else {
            $member_point_card_yn = 0;
        }

        return $member_point_card_yn;
    }

    ////////////////////////////////////////////////// L Point /////////////////////////////////////////////////////
    // L.Point Unique flwNo 생성
    public function LPointCreateFlwNo($전문번호, $기관코드)
    {
        $거래일자 = date('Ymd');

        do {

            $uniqe_seq = "";
            $feed = "0123456789abcdefghijklmnopqrstuvwxyz"; 
            for ($i=0; $i < 6; $i++) {                          
                $uniqe_seq .= substr($feed, rand(0, strlen($feed)-1), 1); 
            }

            $flwNo = $전문번호.$기관코드.$거래일자.$uniqe_seq;
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    count(*) as cnt
                FROM 
                    iparking_cms.lpoint_history
                WHERE 
                    flwNo = :flwNo 
                AND 
                    appldate = :appldate
            ');

            $stmt->execute(['flwNo' => $flwNo, 'appldate' => date('Y-m-d', strtotime($appldate))]);

            $data = $stmt->fetch();

            $count = $data['cnt'] ?? 0;

        } while($count != 0);

        return $flwNo;

    }

    // L.Point 제휴인증사번호 생성
    public function LPointCreateAuthNumber($type) 
    {
        $거래일자 = date("ymd");
        $거래시간 = date("His");
        do {
            // type u : 사용, c : 사용취소
            $uniqe_seq = "";
            $feed = "0123456789abcdefghijklmnopqrstuvwxyz"; 
            for ($i=0; $i < 6; $i++) {                          
                $uniqe_seq .= substr($feed, rand(0, strlen($feed)-1), 1); 
            }

            $ccoAprno = $거래일자.$거래시간.$type.$uniqe_seq;
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    count(*) as cnt
                FROM 
                    iparking_cms.lpoint_history
                WHERE 
                    ccoAprno = :ccoAprno
            ');

            $stmt->execute(['ccoAprno' => $ccoAprno]);

            $data = $stmt->fetch();

            $count = $data['cnt'] ?? 0;
            
            $ccoAprno = $거래일자.$거래시간.$type.$uniqe_seq;

        } while($count != 0);

        return $ccoAprno;
    }

    // L.Point 회원인증
    public function LPointCardInfo($flwNo, $billing_key, $memb_seq)
    {
        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            $copMcno = 'P011300002';
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
            $copMcno = 'P012900002';
        }

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => ""
            ),
            "wcc" => "1",
            "aprAkMdDc" => "4",
            "cstDrmDc" => "1",
            "cdno" => $billing_key,
            "copMcno" => $copMcno
        );
        
        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
       
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data
        ];

        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $point_info = json_decode($aes_decrypt_data, true);

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '조회',
            'rspC' => "",
            'wcc' => "1",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            "copMcno" => $copMcno,
            'memb_seq' => $memb_seq,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $point_info
        ]]);

        return $point_info;
    }

    // L포인트 비밀번호 인증
    public function LPointAuthCheck($flwNo, $billing_key, $billing_password, $memb_seq)
    {  
        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            $copMcno = 'P011300002';
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
            $copMcno = 'P012900002';
        }

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => ""
            ),
            "cdno" => $billing_key,
            "pswd" => md5($billing_password),
            "copMcno" => $copMcno
        );
        
        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data
        ];
        
        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $user_check = json_decode($aes_decrypt_data, true);

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '인증',
            'rspC' => "",
            'wcc' => "1",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            "copMcno" => $copMcno,
            'memb_seq' => $memb_seq,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $user_check
        ]]);

        return $user_check;
    }

    // Lpoint 포인트 적립
    public function LPointOnlyAccumulate($version, $billing_key, $billing_password, $amount, $pointAmount, $park_operate_ct, $memb_seq, $payment_channel, $product_cd, $ppsl_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq)
    {
        // 포인트 사용후 민영일 경우에만 적립
        if($park_operate_ct == '2') {
            if($pointAmount == 0) {

                $전문번호 = 'O200';
                $기관코드 = 'O750';
                $포인트적립flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

                $포인트적립거래일자 = date('Ymd');
                $포인트적립거래시간 = date('His');

                $포인트적립ccoAprno = $this->LPointCreateAuthNumber('c');

                $포인트적립대상금액 = $amount - $pointAmount;

                $env = $this->env;

                if($env == 'on'){
                    $lotte_url = 'https://op.lpoint.com/op';
                    $copMcno = 'P011300002';
                } else{
                    $lotte_url = 'https://devop.lpoint.com:8903/op';
                    $copMcno = 'P012900002';
                }

                try {

                    $data_arr = array(
                        "control" => array(
                            "flwNo" => $포인트적립flwNo,
                            "rspC" => ""
                        ),
                        "wcc" => "3",
                        "aprAkMdDc" => "4",
                        "cstDrmDc" => "1",
                        "cdno" => $billing_key,
                        "cstDrmV" => "",
                        "copMcno" => $copMcno,
                        "ccoAprno" => $포인트적립ccoAprno,
                        "deDt" => $포인트적립거래일자,
                        "deHr" => $포인트적립거래시간,
                        "deDc" => "10",
                        "deRsc" => "100",
                        "rvDc" => "1",
                        "deAkMdDc" => "0", 
                        "ptRvDc" => "1",
                        "totSlAm" => $amount, // 현금+신용카드+상품권+포인트+기타
                        "ptOjAm" => $포인트적립대상금액,
                        "cshSlAm" => "",
                        "ccdSlAm" => $포인트적립대상금액,
                        "mbdSlAm" => "",
                        "ptSlAm" => $pointAmount,
                        "etcSlAm" => "",
                        "cponNo" => "",
                        "deAkInf" => "",
                        "copMbrGdC" => "",
                        "filler" => "",
                        "evnInfCt" => "0",
                        "sttCdCt" => "0"
                    );
                    
                    $data = json_encode($data_arr);

                    $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
                    $key = '';
                    for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
                        $key .= chr($bytes[$i]);
                    }

                    $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
                    
                    $base64_encode_data = base64_encode($openssl_encrypt_data);

                    $requestBody = [
                        'headers' => [
                            'X-Openpoint' => 'burC=O750|aesYn=Y'
                        ],
                        'body' => $base64_encode_data
                    ];
                    
                    $http_result = $this->ci->http->post(
                        $lotte_url,
                        $requestBody
                    );

                    $result_base64 = $http_result->getBody();

                    $base64_decode_data  = base64_decode($result_base64);
                    
                    $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
                    
                    $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

                    if($aes_decrypt_data != "UTF-8") {
                        $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
                    }

                    $point_accumulate = json_decode($aes_decrypt_data, true);

                    $result_code = $point_accumulate['control']['rspC'];

                    if($result_code == '00') {
                        $aprno = $point_accumulate['aprno'];
                        $aprDt = $point_accumulate['aprDt'];
                        $aprHr = $point_accumulate['aprHr'];
                        $point_use['aprno'] = $aprno;
                        $point_use['aprDt'] = $aprDt;
                        $point_use['aprHr'] = $aprHr;
                        $point_use['control']['flwNo'] = $point_accumulate['control']['flwNo'];
                        $point_use['save_point'] = $accumulateAmount;

                        $accumulateAmount = $point_accumulate['ttnCrtPt'];
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                            'flwNo' => $포인트적립flwNo,
                            'type' => 'a',
                            'card_no' => $this->ci->util->encrypted($billing_key),
                            'memb_seq' => $memb_seq,
                            'amount' => $amount,
                            'pointAmount' => $pointAmount,
                            'park_operate_ct'=> $park_operate_ct,
                            'copMcno' => $copMcno,
                            'applDate' => $aprDt,
                            'applCopMcno' => $aprno,
                            // 포인트사용에 대한 운영사승인번호, 운영사원거래일자는 알수 없음
                            // 'applCopMcno' => $운영사승인번호,
                            // 'applDate' => date('Y-m-d', strtotime($운영사원거래일자)),
                            'accumulateAmount' => $accumulateAmount,
                            'accumulateApplCopMcno' => $aprno,
                            'accumulateApplDate' => $aprDt,
                            'create_time' => date('Y-m-d H:i:s'),
                            'ppsl_seq' => $ppsl_seq,
                            'payment_channel' => $payment_channel
                        ]]);

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                            'flwNo' => $포인트적립flwNo,
                            'appldate' => date('Ymd'),
                            'method' => '포인트적립',
                            'memb_seq' => $memb_seq,
                            'rspC' => "",
                            'wcc' => "3",
                            'aprAkMdDc' => "4",
                            'cstDrmDc' => "1",
                            'card_no' => $this->ci->util->encrypted($billing_key),
                            'ccoAprno' => $포인트적립ccoAprno,
                            'copMcno' => $copMcno,
                            'aprno' => $aprno,
                            'aprDt' => $aprDt,
                            'create_time' => date('Y-m-d H:i:s'),
                            'request_parameter' => $data_arr,
                            'response_parameter' => $point_accumulate,
                            'ppsl_seq' => $ppsl_seq,
                            'payment_channel' => $payment_channel
                        ]]);
                        
                        list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                            $version, 'LTP', $product_cd, $ppsl_seq, '0000', '포인트적립',
                            $pointAmount, $aprDt, $aprHr, $aprno, $포인트적립flwNo,
                            $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $pg_site_cd, $accumulateAmount
                        );
                        
                        if($reflect_code == '99') {
                            // $this->LPointUseReverseCancel($포인트적립flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $aprDt, $aprHr, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                            $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $aprDt, $aprHr, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                            return [(object)[], '99'];
                        }
                        return [$point_use, '00'];
                    } else {
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                            'flwNo' => $포인트적립flwNo,
                            'appldate' => date('Ymd'),
                            'method' => '포인트적립실패',
                            'memb_seq' => $memb_seq,
                            'rspC' => "",
                            'wcc' => "3",
                            'aprAkMdDc' => "4",
                            'cstDrmDc' => "1",
                            'card_no' => $this->ci->util->encrypted($billing_key),
                            'ccoAprno' => $포인트적립ccoAprno,
                            'copMcno' => $copMcno,
                            'arpno' => $aprno,
                            'aprDt' => $aprDt,
                            'create_time' => date('Y-m-d H:i:s'),
                            'request_parameter' => $data_arr,
                            'response_parameter' => $point_accumulate,
                            'ppsl_seq' => $ppsl_seq,
                            'payment_channel' => $payment_channel
                        ]]);
                        $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $aprDt, $aprHr, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                        return [(object)[], "99"]; 
                    }                 
                        
                } catch (RequestException $e) {   
                    $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $aprDt, $aprHr, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                    return [(object)[], "99"];   
                } catch (BadResponseException $e) {
                    $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $aprDt, $aprHr, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                    return [(object)[], "99"];   
                }
            }
        }
    }

    // L.point 포인트 사용
    public function LPointUse($version, $billing_key, $billing_password=null, $park_operate_ct=null, $amount=null, $pointAmount=null, $ppsl_seq=null, $memb_seq=null, $payment_channel=null, $apply_orderno=null, $product_cd=null, $bcar_seq=null, $bcar_number=null, $park_seq=null, $prdt_seq=null)
    {
        $전문번호 = 'O730';
        $기관코드 = 'O750';

        $포인트사용flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

        $포인트사용거래일자 = date('Ymd');
        $포인트사용거래시간 = date('His');

        $포인트사용ccoAprno = $this->LPointCreateAuthNumber('u'); 

        $payment_time =  date('Y-m-d H:i:s', strtotime($포인트사용거래일자.$포인트사용거래시간));

        $is_mobile = $this->ci->util->isMobile();

        if($is_mobile) {
            $reg_device_code = 3;
        } else {
            $reg_device_code = 2;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        /*
            일반주차장: P012900002
            공영주차장: P013000002
            
            park_operate_ct
            1: 공영 / 2: 민영
        */

        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            
            if($park_operate_ct == '1') {
                $copMcno = 'P011400002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P011300002';
            }
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';

            if($park_operate_ct == '1') {
                $copMcno = 'P013000002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P012900002';
            }
        }
        

        // 예외처리 발생 케이스 추가
        try {
 
            $data_arr = array(
                "control" => array(
                    "flwNo" => $포인트사용flwNo,
                    "rspC" => ""
                ),
                "wcc" => "3",
                "aprAkMdDc" => "4",
                "cdno" => $billing_key,
                "mbPtUPswd" => md5($billing_password),
                "copMcno" => $copMcno,
                "ccoAprno" => $포인트사용ccoAprno,
                "deDt" => $포인트사용거래일자,
                "deHr" => $포인트사용거래시간,
                "deDc" => "20",
                "deRsc" => "200",
                "uDc" => "1",
                "ptUDc" => "1",
                "ttnUPt" => $pointAmount,
                "slAm" => ""
            );
    
            $data = json_encode($data_arr);
    
            $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
            $key = '';
            for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
                $key .= chr($bytes[$i]);
            }
    
            $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
            
            $base64_encode_data = base64_encode($openssl_encrypt_data);

            $requestBody = [
                'headers' => [                    
                    'X-Openpoint' => 'burC=O750|aesYn=Y'
                ],
                'body' => $base64_encode_data
            ];

            $http_result = $this->ci->http->post(
                $lotte_url,
                $requestBody
            );
    
            $result_base64 = $http_result->getBody();
    
            $base64_decode_data  = base64_decode($result_base64);
            
            $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
            
            $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));
    
            if($aes_decrypt_data != "UTF-8") {
                $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
            }
    
            $point_use = json_decode($aes_decrypt_data, true);

            $result_code = $point_use['control']['rspC'];

            if($result_code == '00') {
                $aprno = $point_use['aprno'];
                $aprDt = $point_use['aprDt'];
                $aprHr = $point_use['aprHr'];
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                    'flwNo' => $포인트사용flwNo,
                    'type' => 'u',
                    'card_no' => $this->ci->util->encrypted($billing_key),
                    'memb_seq' => $memb_seq,
                    'park_operate_ct' => $park_operate_ct,
                    'copMcno' => $copMcno,
                    'amount' => $amount,
                    'pointAmount' => $pointAmount,
                    'applCopMcno' => $aprno,
                    'applDate' => date('Y-m-d', strtotime($aprDt)),
                    'ppsl_seq' => $ppsl_seq,
                    'product_cd' => $product_cd,
                    'payment_channel' => $payment_channel,
                    'create_time' => date('Y-m-d H:i:s')
                ]]);

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                    'flwNo' => $포인트사용flwNo,
                    'appldate' => date('Ymd'),
                    'method' => '포인트사용',
                    'memb_seq' => $memb_seq,
                    'rspC' => "",
                    'wcc' => "3",
                    'aprAkMdDc' => "4",
                    'cstDrmDc' => "1",
                    'card_no' => $this->ci->util->encrypted($billing_key),
                    'copMcno' => $copMcno,
                    'ccoAprno' => $포인트사용ccoAprno,
                    'aprno' => $aprno,
                    'aprDt' => $aprDt,
                    'park_operate_ct' => $park_operate_ct,
                    'create_time' => date('Y-m-d H:i:s'),
                    'request_parameter' => $data_arr,
                    'response_parameter' => $point_use,
                    'ppsl_seq' => $ppsl_seq,
                    'payment_channel' => $payment_channel
                ]]);

                $운영사승인번호 = $aprno;
                $운영사원거래일자 = $aprDt;
                $운영사원거래시간 = $aprHr;

                // L 포인트 적립, 사용 같이 못함 2018.10.18
                // 포인트 사용후 민영일 경우에만 적립
                // if($park_operate_ct == '2') {
                //     if($amount > $pointAmount) {

                //         $전문번호 = 'O200';
                //         $기관코드 = 'O750';
                //         $포인트적립flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

                //         $포인트적립거래일자 = date('Ymd');
                //         $포인트적립거래시간 = date('His');

                //         $포인트적립ccoAprno = $this->LPointCreateAuthNumber('c');

                //         $포인트적립대상금액 = $amount - $pointAmount;

                //         try {

                //             $data_arr = array(
                //                 "control" => array(
                //                     "flwNo" => $포인트적립flwNo,
                //                     "rspC" => ""
                //                 ),
                //                 "wcc" => "3",
                //                 "aprAkMdDc" => "4",
                //                 "cstDrmDc" => "1",
                //                 "cdno" => $billing_key,
                //                 "cstDrmV" => "",
                //                 "copMcno" => $copMcno,
                //                 "ccoAprno" => $포인트적립ccoAprno,
                //                 "deDt" => $포인트적립거래일자,
                //                 "deHr" => $포인트적립거래시간,
                //                 "deDc" => "10",
                //                 "deRsc" => "100",
                //                 "rvDc" => "1",
                //                 "deAkMdDc" => "0", 
                //                 "ptRvDc" => "1",
                //                 "totSlAm" => $amount, // 현금+신용카드+상품권+포인트+기타
                //                 "ptOjAm" => $포인트적립대상금액,
                //                 "cshSlAm" => "",
                //                 "ccdSlAm" => $포인트적립대상금액,
                //                 "mbdSlAm" => "",
                //                 "ptSlAm" => $pointAmount,
                //                 "etcSlAm" => "",
                //                 "cponNo" => "",
                //                 "deAkInf" => "",
                //                 "copMbrGdC" => "",
                //                 "filler" => "",
                //                 "evnInfCt" => "0",
                //                 "sttCdCt" => "0"
                //             );
                            
                //             $data = json_encode($data_arr);
    
                //             $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
                //             $key = '';
                //             for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
                //                 $key .= chr($bytes[$i]);
                //             }
    
                //             $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
                            
                //             $base64_encode_data = base64_encode($openssl_encrypt_data);
    
                //             $requestBody = [
                //                 'headers' => [
                //                     'X-Openpoint' => 'burC=O750|aesYn=Y'
                //                 ],
                //                 'body' => $base64_encode_data
                //             ];
                            
                //             $http_result = $this->ci->http->post(
                //                 $lotte_url,
                //                 $requestBody
                //             );
    
                //             $result_base64 = $http_result->getBody();
    
                //             $base64_decode_data  = base64_decode($result_base64);
                            
                //             $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
                            
                //             $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));
    
                //             if($aes_decrypt_data != "UTF-8") {
                //                 $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
                //             }
    
                //             $point_accumulate = json_decode($aes_decrypt_data, true);
    
                //             $result_code = $point_accumulate['control']['rspC'];

                //             if($result_code == '00') {
                //                 $aprno = $point_accumulate['aprno'];
                //                 $aprDt = $point_accumulate['aprDt'];
                //                 $accumulateAmount = $point_accumulate['ttnCrtPt'];
                //                 $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                //                     'flwNo' => $포인트적립flwNo,
                //                     'type' => 'a',
                //                     'card_no' => $this->ci->util->encrypted($billing_key),
                //                     'memb_seq' => $memb_seq,
                //                     'amount' => $amount,
                //                     'pointAmount' => $pointAmount,
                //                     'park_operate_ct'=> $park_operate_ct,
                //                     'copMcno' => $copMcno,
                //                     'applCopMcno' => $운영사승인번호,
                //                     'applDate' => date('Y-m-d', strtotime($운영사원거래일자)),
                //                     'accumulateAmount' => $accumulateAmount,
                //                     'accumulateApplCopMcno' => $aprno,
                //                     'accumulateApplDate' => $aprDt,
                //                     'create_time' => date('Y-m-d H:i:s'),
                //                     'ppsl_seq' => $ppsl_seq,
                //                     'payment_channel' => $payment_channel
                //                 ]]);

                //                 $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                //                     'flwNo' => $포인트적립flwNo,
                //                     'appldate' => date('Ymd'),
                //                     'method' => '포인트적립',
                //                     'memb_seq' => $memb_seq,
                //                     'rspC' => "",
                //                     'wcc' => "3",
                //                     'aprAkMdDc' => "4",
                //                     'cstDrmDc' => "1",
                //                     'card_no' => $this->ci->util->encrypted($billing_key),
                //                     'ccoAprno' => $포인트적립ccoAprno,
                //                     'copMcno' => $copMcno,
                //                     'create_time' => date('Y-m-d H:i:s'),
                //                     'request_parameter' => $data_arr,
                //                     'response_parameter' => $point_accumulate,
                //                     'ppsl_seq' => $ppsl_seq,
                //                     'payment_channel' => $payment_channel
                //                 ]]);
                                                                
                //                 // if($cp_hist_seq != null && $cp_hist_seq != '' && $cp_hist_seq != 0) {
                //                 //     $coupon_result = $this->ci->coupon->updateUseYn($cp_hist_seq, 1);
                //                 //     if($coupon_result == "Fail") {
                //                 //         $this->ci->point->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $포인트적립거래일자, $포인트적립거래시간, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                //                 //         $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);                
                //                 //         return [(object)[], "99"]; 
                //                 //     }
                //                 // }
                                
                //                 // $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_payment_list', [[
                //                 //     'icpr_seq' => $ppsl_seq,   
                //                 //     'memb_seq' => $memb_seq,
                //                 //     'point_card_code' => 'LTP',
                //                 //     'payment_datetime' => $payment_time,
                //                 //     'apply_orderno' => $apply_orderno,
                //                 //     'apply_number'  => $운영사승인번호,
                //                 //     'apply_date' => date('Y-m-d', strtotime($포인트사용거래일자)),
                //                 //     'payment_state'  => '사용',
                //                 //     'reg_datetime' => date('Y-m-d H:i:s'),
                //                 //     'reg_seq' => $memb_seq,
                //                 //     'reg_ip' => $ip,
                //                 //     'reg_device_code' => $reg_device_code,
                //                 //     'point_amount' => $pointAmount
                //                 // ]]);

                //             } else {
                //                 $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                //                     'flwNo' => $포인트적립flwNo,
                //                     'appldate' => date('Ymd'),
                //                     'method' => '포인트적립실패',
                //                     'rspC' => "",
                //                     'wcc' => "3",
                //                     'aprAkMdDc' => "4",
                //                     'cstDrmDc' => "1",
                //                     'card_no' => $this->ci->util->encrypted($billing_key),
                //                     'memb_seq' => $memb_seq,
                //                     'ccoAprno' => $ccoAprno,
                //                     'copMcno' => $copMcno,
                //                     'create_time' => date('Y-m-d H:i:s'),
                //                     'request_parameter' => $data_arr,
                //                     'response_parameter' => $point_accumulate,
                //                     'ppsl_seq' => $ppsl_seq,
                //                     'payment_channel' => $payment_channel
                //                 ]]);
                //                 $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $포인트적립거래일자, $포인트적립거래시간, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                //                 $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
                //                 return [(object)[], "99"]; 
                //             }                 
                                
                //         } catch (RequestException $e) {   
                //             $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
                //             $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $포인트적립거래일자, $포인트적립거래시간, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                //             return [(object)[], "99"];   
                //         } catch (BadResponseException $e) {
                //             $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $포인트적립거래일자, $포인트적립거래시간, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);
                //             $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
                //             return [(object)[], "99"];   
                //         }
                        
                //     } else {
                //         if($result_code == '00') {
                //             $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                //                 'flwNo' => $포인트사용flwNo,
                //                 'appldate' => date('Ymd'),
                //                 'method' => '포인트사용실패',
                //                 'memb_seq' => $memb_seq,
                //                 'rspC' => "",
                //                 'wcc' => "3",
                //                 'aprAkMdDc' => "4",
                //                 'cstDrmDc' => "1",
                //                 'card_no' => $this->ci->util->encrypted($billing_key),
                //                 'copMcno' => $copMcno,
                //                 'ccoAprno' => $포인트사용ccoAprno,
                //                 'aprno' => $aprno,
                //                 'aprDt' => $aprDt,
                //                 'park_operate_ct' => $park_operate_ct,
                //                 'create_time' => date('Y-m-d H:i:s'),
                //                 'request_parameter' => $data_arr,
                //                 'response_parameter' => $point_use,
                //                 'ppsl_seq' => $ppsl_seq,
                //                 'payment_channel' => $payment_channel
                //             ]]);
                //         }
                //     }
                // }

                list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                    $version, 'LTP', $product_cd, $ppsl_seq, $result_code, '포인트사용', 
                    $pointAmount, $운영사원거래일자, $운영사원거래시간, $운영사승인번호, $apply_orderno,
                    $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $pg_site_cd , ""
                );

                if($reflect_code == '99') {
                    // $this->LPointAccumulateReverseCancel($포인트적립flwNo, $billing_key, $copMcno, $park_operate_ct, $포인트적립거래일자, $포인트적립거래시간, $포인트적립ccoAprno, $amount, $pointAmount, $memb_seq);                                             
                    $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
                    return [(object)[], '99'];
                }

                return [$point_use, $result_code];

            } else {
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                    'flwNo' => $포인트사용flwNo,
                    'appldate' => date('Ymd'),
                    'method' => '포인트사용실패',
                    'memb_seq' => $memb_seq,
                    'rspC' => "",
                    'wcc' => "3",
                    'aprAkMdDc' => "4",
                    'cstDrmDc' => "1",
                    'card_no' => $this->ci->util->encrypted($billing_key),
                    'copMcno' => $copMcno,
                    'ccoAprno' => $포인트사용ccoAprno,
                    'aprno' => $aprno,
                    'aprDt' => $aprDt,
                    'park_operate_ct' => $park_operate_ct,
                    'create_time' => date('Y-m-d H:i:s'),
                    'request_parameter' => $data_arr,
                    'response_parameter' => $point_use,
                    'ppsl_seq' => $ppsl_seq,
                    'payment_channel' => $payment_channel
                ]]);
                $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
                return [(object)[], "99"];      
            }
            
        } catch (RequestException $e) {   
            $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
            return [(object)[], "99"];   
        } catch (BadResponseException $e) {
            $this->LPointUseReverseCancel($포인트사용flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $포인트사용거래일자, $포인트사용거래시간, $포인트사용ccoAprno, $amount, $pointAmount, $memb_seq);
            return [(object)[], "99"];   
        }
    }

    // L.point 포인트 사용 망취소
    public function LPointUseReverseCancel($flwNo, $billing_key, $billing_password, $copMcno, $park_operate_ct, $거래일자, $거래시간, $ccoAprno, $amount, $pointAmount, $memb_seq)
    {

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => "60"
            ),
            "wcc" => "3",
            "aprAkMdDc" => "4",
            "cdno" => $billing_key,
            "mbPtUPswd" => md5($billing_password),
            "copMcno" => $copMcno,
            "ccoAprno" => $ccoAprno,
            "deDt" => $거래일자,
            "deHr" => $거래시간,
            "deDc" => "20",
            "deRsc" => "200",
            "uDc" => "1",
            "ptUDc" => "1",
            "ttnUPt" => $pointAmount,
            "slAm" => ""
        );

        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
        }

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data,
            'timeout' => 60,
            'connect_timeout' => 60
        ];

        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $point_use_reverse_cancel = json_decode($aes_decrypt_data, true);

        $result_code = $point_use_reverse_cancel['control']['rspC'];

        if($result_code == '00') {
            $aprno = $point_use_reverse_cancel['aprno'];
            $aprDt = $point_use_reverse_cancel['aprDt'];
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                'flwNo' => $flwNo,
                'type' => 'urc',
                'card_no' => $this->ci->util->encrypted($billing_key),
                'memb_seq' => $memb_seq, 
                'park_operate_ct' => $park_operate_ct,
                'copMcno' => $copMcno,
                'amount' => $amount,
                'pointAmount' => $pointAmount,
                'applCopMcno' => $aprno,
                'applDate' => date('Y-m-d', strtotime($aprDt)),
                'create_time' => date('Y-m-d H:i:s')
            ]]);
        }       
        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '포인트사용망취소',
            'memb_seq' => $memb_seq, 
            'rspC' => "60",
            'wcc' => "3",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            'copMcno' => $copMcno,
            'ccoAprno' => $ccoAprno,
            'park_operate_ct' => $park_operate_ct,
            'aprno' => $aprno,
            'aprDt' => $aprDt,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $point_use_reverse_cancel
        ]]);    
    }

    // LPoint 사용 취소
    public function LPointCancel($운영사승인번호, $운영사원거래일자, $memb_seq)
    {
        $env = $this->env;

        $전문번호 = 'O740';
        $기관코드 = 'O750';

        $deRsc = 200;

        $포인트취소flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

        $포인트취소거래일자 = date('Ymd');
        $포인트취소거래시간 = date('His');

        $포인트취소ccoAprno = $this->LPointCreateAuthNumber('c');

        $payment_time =  date('Y-m-d H:i:s', strtotime($포인트취소거래일자.$포인트취소거래시간));

        $is_mobile = $this->ci->util->isMobile();

        if($is_mobile) {
            $reg_device_code = 3;
        } else {
            $reg_device_code = 2;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.lpoint 
            WHERE 
                type = :type
            AND
                applCopMcno = :applCopMcno 
            AND 
                applDate = :applDate
            LIMIT 1
        ');

        $stmt->execute([
            'type' => 'u',
            'applCopMcno' => $운영사승인번호,
            'applDate' => date('Y-m-d', strtotime($운영사원거래일자))
        ]);

        
        $data = $stmt->fetch();

        $billing_key = $this->ci->util->decrypted($data['card_no']);
        $amount = $data['amount'];
        $park_operate_ct = $data['park_operate_ct'];

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            // if($park_operate_ct == '1') {
            //     $copMcno = 'P011400002';
            // } else if($park_operate_ct == '2') {
            //     $copMcno = 'P011300002';
            // }
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
            // if($park_operate_ct == '1') {
            //     $copMcno = 'P013000002';
            // } else if($park_operate_ct == '2') {
            //     $copMcno = 'P012900002';
            // }
        }

        $copMcno = $data['copMcno'];
        $pointAmount = $data['pointAmount'];

        try {

            $data_arr = array(
                "control" => array(
                    "flwNo" => $포인트취소flwNo,
                    "rspC" => ""
                ),
                "wcc" => "3",
                "aprAkMdDc" => "4",
                "cdno" => $billing_key,
                "copMcno" => $copMcno,
                "ccoAprno" => $포인트취소ccoAprno,
                "deDt" => $포인트취소거래일자,
                "deHr" => $포인트취소거래시간,
                "deDc" => "20",
                "deRsc" => "200",
                "uDc" => "2",
                "ptUDc" => "1",
                "ttnUPt" => "",
                "otInfYnDc" => "1",
                "otInfDc" => "1",
                "otAprno" => $운영사승인번호,
                "otDt" => $운영사원거래일자
            );
            
            $data = json_encode($data_arr);

            $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
            $key = '';
            for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
                $key .= chr($bytes[$i]);
            }

            $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
            
            $base64_encode_data = base64_encode($openssl_encrypt_data);

            $requestBody = [
                'headers' => [
                    'X-Openpoint' => 'burC=O750|aesYn=Y'
                ],
                'body' => $base64_encode_data,
                'timeout' => 60,
                'connect_timeout' => 60
            ];
            

            $http_result = $this->ci->http->post(
                $lotte_url,
                $requestBody
            );

            $result_base64 = $http_result->getBody();

            $base64_decode_data  = base64_decode($result_base64);
            
            $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
            
            $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

            if($aes_decrypt_data != "UTF-8") {
                $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
            }

            $point_cancel = json_decode($aes_decrypt_data, true);

            $point_cancel['cancel_appl_time'] = $포인트취소거래시간;
            $result_code = $point_cancel['control']['rspC'];
            
            if($result_code == '00') {

                $aprno = $point_cancel['aprno'];
                $aprDt = $point_cancel['aprDt'];
                $cancel_applCopMcno = $운영사승인번호;
                $cancel_applDate = $운영사원거래일자;

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                    'flwNo' => $포인트취소flwNo,
                    'type' => 'uc',
                    'card_no' => $this->ci->util->encrypted($billing_key),
                    'memb_seq' => $memb_seq,
                    'park_operate_ct' => $park_operate_ct,
                    'copMcno' => $copMcno,
                    'amount' => $amount,
                    'pointAmount' => $pointAmount,
                    'applCopMcno' => $aprno,
                    'applDate' => date('Y-m-d', strtotime($aprDt)),
                    'cancel_applCopMcno' => $cancel_applCopMcno,
                    'cancel_applDate' => $cancel_applDate,
                    'create_time' => date('Y-m-d H:i:s')
                ]]);
            }

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
                'flwNo' => $포인트취소flwNo,
                'appldate' => date('Y-m-d', strtotime($aprDt)),
                'method' => '포인트사용취소',
                'rspC' => "",
                'wcc' => "3",
                'aprAkMdDc' => "4",
                'memb_seq' => $memb_seq,
                'cstDrmDc' => "1",
                'card_no' => $this->ci->util->encrypted($billing_key),
                'ccoAprno' => $포인트취소ccoAprno,
                'copMcno' => $copMcno,
                'create_time' => date('Y-m-d H:i:s'),
                'request_parameter' => $data_arr,
                'response_parameter' => $point_cancel
            ]]);

            // 포인트가 취소가 되면 적립도 취소 해야한다.
            // $stmt = $this->ci->iparkingCmsDb->prepare('
            //     SELECT * FROM iparking_cms.lpoint 
            //     WHERE 
            //         type = :type
            //     AND
            //         applCopMcno = :applCopMcno 
            //     AND 
            //         applDate = :applDate
            //     LIMIT 1
            // ');
            
            // $stmt->execute([
            //     'type' => 'a',
            //     'applDate' => date('Y-m-d', strtotime($운영사원거래일자)), 
            //     'applCopMcno' => $운영사승인번호
            // ]);

            // $data = $stmt->fetch();
            // if(!empty($data)) {
            //     $accumulateApplCopMcno = $data['accumulateApplCopMcno'];
            //     $accumulateApplDate = $data['accumulateApplDate'];
            //     $this->LPointAccumulateCancel($accumulateApplCopMcno, $accumulateApplDate, $memb_seq);
            // }

            return [$point_cancel, $result_code];

        } catch (RequestException $e) {   
            return [(object)[], "99"];   
        } catch (BadResponseException $e) {
            return [(object)[], "99"];   
        } 
    }

    // LPoint 적립
    public function LPointAccumulate($park_operate_ct, $운영사승인번호, $운영사원거래일자, $memb_seq)
    {
        $전문번호 = 'O200';
        $기관코드 = 'O750';

        $flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);
        
        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            
            if($park_operate_ct == '1') {
                $copMcno = 'P011400002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P011300002';
            }
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';

            if($park_operate_ct == '1') {
                $copMcno = 'P013000002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P012900002';
            }
        }

        $거래일자 = date('Ymd');
        $거래시간 = date('His');

        $ccoAprno = $this->LPointCreateAuthNumber('c');

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.lpoint 
            WHERE 
                type = :type
            AND
                applCopMcno = :applCopMcno 
            AND 
                applDate = :applDate
            LIMIT 1
        ');
        
        $stmt->execute([
            'type' => 'u',
            'applDate' => date('Y-m-d', strtotime($운영사원거래일자)), 
            'applCopMcno' => $운영사승인번호
        ]);

        $data = $stmt->fetch();

        $billing_key = $this->ci->util->decrypted($data['card_no']);
        $pointAmount = $data['pointAmount'] ?? 0;
        $결제금액 = $data['amount'] ?? 0;
        $포인트적립대상금액 = $결제금액 - $pointAmount;

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => ""
            ),
            "wcc" => "3",
            "aprAkMdDc" => "4",
            "cstDrmDc" => "1",
            "cdno" => $billing_key,
            "cstDrmV" => "",
            "copMcno" => $copMcno,
            "ccoAprno" => $ccoAprno,
            "deDt" => $거래일자,
            "deHr" => $거래시간,
            "deDc" => "10",
            "deRsc" => "100",
            "rvDc" => "1",
            "deAkMdDc" => "0", 
            "ptRvDc" => "1",
            "totSlAm" => $결제금액, // 현금+신용카드+상품권+포인트+기타
            "ptOjAm" => $pointAmount,
            "cshSlAm" => "",
            "ccdSlAm" => $결제금액,
            "mbdSlAm" => "",
            "ptSlAm" => "",
            "etcSlAm" => "",
            "cponNo" => "",
            "deAkInf" => "",
            "copMbrGdC" => "",
            "filler" => "",
            "evnInfCt" => "0",
            "sttCdCt" => "0"
        );
        
        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data
        ];
        
        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $point_accumulate = json_decode($aes_decrypt_data, true);

        $result_code = $point_accumulate['control']['rspC'];
        
        if($result_code == '00') {
            $aprno = $point_accumulate['aprno'];
            $aprDt = $point_accumulate['aprDt'];
            $accumulateAmount = $point_accumulate['ttnCrtPt'];
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                'flwNo' => $flwNo,
                'type' => 'a',
                'card_no' => $this->ci->util->encrypted($billing_key),
                'memb_seq' => $memb_seq,
                'amount' => $결제금액,
                'pointAmount' => $pointAmount,
                'park_operate_ct'=> $park_operate_ct,
                'copMcno' => $copMcno,
                'applCopMcno' => $운영사승인번호,
                'applDate' => date('Y-m-d', strtotime($운영사원거래일자)),
                'accumulateAmount' => $accumulateAmount,
                'accumulateApplCopMcno' => $aprno,
                'accumulateApplDate' => $aprDt,
                'cancel_applCopMcno' => $cancel_applCopMcno,
                'cancel_applDate' => $cancel_applDate,
                'create_time' => date('Y-m-d H:i:s')
            ]]);
        }

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '포인트적립',
            'memb_seq' => $memb_seq,
            'rspC' => "",
            'wcc' => "3",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            'ccoAprno' => $ccoAprno,
            'copMcno' => $copMcno,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $point_accumulate
        ]]);

        return $point_accumulate;
    }

    // LPoint 적립 망 취소
    public function LPointAccumulateReverseCancel($flwNo, $billing_key, $copMcno, $park_operate_ct, $거래일자, $거래시간, $ccoAprno, $amount, $pointAmount, $memb_seq)
    {
        $포인트적립대상금액 = $amount - $pointAmount;

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => "60"
            ),
            "wcc" => "3",
            "aprAkMdDc" => "4",
            "cstDrmDc" => "1",
            "cdno" => $billing_key,
            "cstDrmV" => "",
            "copMcno" => $copMcno,
            "ccoAprno" => $ccoAprno,
            "deDt" => $거래일자,
            "deHr" => $거래시간,
            "deDc" => "10",
            "deRsc" => "100",
            "rvDc" => "1",
            "deAkMdDc" => "0", 
            "ptRvDc" => "1",
            "totSlAm" => $amount, // 현금+신용카드+상품권+포인트+기타
            "ptOjAm" => $포인트적립대상금액,
            "cshSlAm" => "",
            "ccdSlAm" => $포인트적립대상금액,
            "mbdSlAm" => "",
            "ptSlAm" => $pointAmount,
            "etcSlAm" => "",
            "cponNo" => "",
            "deAkInf" => "",
            "copMbrGdC" => "",
            "filler" => "",
            "evnInfCt" => "0",
            "sttCdCt" => "0"
        );
        
        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data
        ];
        
        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
        }

        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $point_accumulate_reverse_cancel = json_decode($aes_decrypt_data, true);

        $result_code = $point_accumulate_reverse_cancel['control']['rspC'];
        
        if($result_code == '00') {
            $aprno = $point_accumulate_reverse_cancel['aprno'];
            $aprDt = $point_accumulate_reverse_cancel['aprDt'];
            $accumulateAmount = $point_accumulate_reverse_cancel['ttnCrtPt'];
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                'flwNo' => $flwNo,
                'type' => 'arc',
                'card_no' => $this->ci->util->encrypted($billing_key),
                'amount' => $amount,
                'memb_seq' => $memb_seq,
                'pointAmount' => $pointAmount,
                'park_operate_ct'=> $park_operate_ct,
                'copMcno' => $copMcno,
                'applCopMcno' => $aprno,
                'applDate' => date('Y-m-d', strtotime($aprDt)),
                'accumulateAmount' => $accumulateAmount,
                'accumulateApplCopMcno' => $aprno,
                'accumulateApplDate' => $aprDt,
                'cancel_applCopMcno' => $cancel_applCopMcno,
                'cancel_applDate' => $cancel_applDate,
                'create_time' => date('Y-m-d H:i:s')
            ]]);
        }

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '포인트적립망취소',
            'memb_seq' => $memb_seq,
            'rspC' => "60",
            'wcc' => "3",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            'ccoAprno' => $ccoAprno,
            'copMcno' => $copMcno,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $point_accumulate_reverse_cancel
        ]]);

    }

    // LPoint 적립 취소
    public function LPointAccumulateCancel($운영사승인번호, $운영사원거래일자, $memb_seq)
    {
        $전문번호 = 'O210';
        $기관코드 = 'O750';
        
        $env = $this->env;

        if($env == 'on'){
            $lotte_url = 'https://op.lpoint.com/op';
            if($park_operate_ct == '1') {
                $copMcno = 'P011400002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P011300002';
            }
        } else{
            $lotte_url = 'https://devop.lpoint.com:8903/op';
            
            if($park_operate_ct == '1') {
                $copMcno = 'P013000002';
            } else if($park_operate_ct == '2') {
                $copMcno = 'P012900002';
            }
        }

        $flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

        $거래일자 = date('Ymd');
        $거래시간 = date('His');

        $ccoAprno = $this->LPointCreateAuthNumber('c');

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.lpoint 
            WHERE 
                type = :type
            AND
                accumulateApplCopMcno = :accumulateApplCopMcno 
            AND 
                accumulateApplDate = :accumulateApplDate
            LIMIT 1
        ');
        
        $stmt->execute([
            'type' => 'a',
            'accumulateApplDate' => $운영사원거래일자, 
            'accumulateApplCopMcno' => $운영사승인번호
        ]);

        $data = $stmt->fetch();

        $billing_key = $this->ci->util->decrypted($data['card_no']);
        $pointAmount = $data['pointAmount'];
        $결제금액 = $data['amount'];
        $copMcno = $data['copMcno'];
        $포인트사용applDate = $data['applDate'];
        $포인트사용applCopMcno = $data['applCopMcno'];

        $data_arr = array(
            "control" => array(
                "flwNo" => $flwNo,
                "rspC" => ""
            ),
            "wcc" => "3",
            "aprAkMdDc" => "4",
            "cstDrmDc" => "1",
            "cdno" => $billing_key,
            "cstDrmV" => "",
            "copMcno" => $copMcno,
            "ccoAprno" => $ccoAprno,
            "deDt" => $거래일자,
            "deHr" => $거래시간,
            "deDc" => "10",
            "deRsc" => "100",
            "rvDc" => "2",
            "deAkMdDc" => "0", 
            "ptRvDc" => "1",
            "totSlAm" => $결제금액, // 현금+신용카드+상품권+포인트+기타
            "ptOjAm" => $pointAmount,
            "cshSlAm" => "",
            "ccdSlAm" => $결제금액,
            "mbdSlAm" => "",
            "ptSlAm" => "",
            "etcSlAm" => "",
            "cponNo" => "",
            "rtgDc" => 0,
            "otInfYnDc" => "1",
            "otInfDc" => "1",
            "otAprno" => $운영사승인번호,
            "otDt" => $운영사원거래일자,
            "deAkInf" => ""
        );
        
        $data = json_encode($data_arr);

        $bytes = array(0xcb,0x3f,0x0a,0x34,0x49,0x26,0x95,0x46,0x98,0x40,0x5a,0x0f,0x15,0x9f,0x41,0x8d);
        $key = '';
        for ($i = 0, $j = count($bytes); $i < $j; ++$i) {
            $key .= chr($bytes[$i]);
        }

        $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $base64_encode_data = base64_encode($openssl_encrypt_data);

        $requestBody = [
            'headers' => [
                'X-Openpoint' => 'burC=O750|aesYn=Y'
            ],
            'body' => $base64_encode_data
        ];
        
        $http_result = $this->ci->http->post(
            $lotte_url,
            $requestBody
        );

        $result_base64 = $http_result->getBody();

        $base64_decode_data  = base64_decode($result_base64);
        
        $aes_decrypt_data = openssl_decrypt($base64_decode_data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, 'l-members-lpoint');
        
        $enc = mb_detect_encoding($aes_decrypt_data, array("UTF-8", "EUC-KR"));

        if($aes_decrypt_data != "UTF-8") {
            $aes_decrypt_data = iconv($enc, "UTF-8", $aes_decrypt_data);
        }

        $point_accumulate_cancel = json_decode($aes_decrypt_data, true);

        $point_accumulate_cancel['cancel_appl_time'] = $거래시간;

        $result_code = $point_accumulate_cancel['control']['rspC'];

        if($result_code == '00') {
            $aprno = $point_accumulate_cancel['aprno'];
            $aprDt = $point_accumulate_cancel['aprDt'];
            $accumulateAmount = $point_accumulate_cancel['canPt'];
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint', [[
                'flwNo' => $flwNo,
                'type' => 'ac',
                'card_no' => $this->ci->util->encrypted($billing_key),
                'memb_seq' => $memb_seq,
                'amount' => $결제금액,
                'pointAmount' => $pointAmount,
                'applDate' => $포인트사용applDate, 
                'applCopMcno' => $포인트사용applCopMcno,
                'accumulateAmount' => $accumulateAmount,
                'accumulateApplCopMcno' => $운영사승인번호,
                'accumulateApplDate' => $운영사원거래일자,
                'accumulateCancelApplCopMcno' => $aprno,
                'accumulateCancelApplDate' => $aprDt,
                'create_time' => date('Y-m-d H:i:s')
            ]]);
        }

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.lpoint_history', [[
            'flwNo' => $flwNo,
            'appldate' => date('Ymd'),
            'method' => '포인트적립취소',
            'memb_seq' => $memb_seq,
            'rspC' => "",
            'wcc' => "3",
            'aprAkMdDc' => "4",
            'cstDrmDc' => "1",
            'card_no' => $this->ci->util->encrypted($billing_key),
            'ccoAprno' => $ccoAprno,
            'copMcno' => $copMcno,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $data_arr,
            'response_parameter' => $point_accumulate_cancel
        ]]);

        return [$point_accumulate_cancel, $result_code];
    }

    ///////////////////////////////////////////////// L Point END ////////////////////////////////////////////////////

    ////////////////////////////////////////////////// GS Point //////////////////////////////////////////////////////
    public function generateGsPointUniqNo()
    {
        $appldate = date('Y-m-d');
        $trans_date = date('ymd');
        $trans_time = date('His');
        
        do {

            $uniqe_seq = "";
            $feed = "0123456789"; 
            for ($i=0; $i < 8; $i++) {                          
                $uniqe_seq .= substr($feed, rand(0, strlen($feed)-1), 1); 
            }

            $flwNo = $trans_date.$trans_time.$uniqe_seq;
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    count(*) as cnt
                FROM 
                    iparking_cms.gspoint_history
                WHERE 
                    chasing_no = :flwNo 
                AND 
                    appldate = :appldate
            ');
    
            $stmt->execute(['flwNo' => $flwNo, 'appldate' => date('Y-m-d', strtotime($appldate))]);
    
            $data = $stmt->fetch();
    
            $count = $data['cnt'] ?? 0;
        
        } while($count != 0);

        return $flwNo;
    }

    public function gsPointInfo($card_no, $pwd)
    {

        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */

        $env = $this->env;

        if($env == 'on'){
            exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/GSPoint/GSC_Crypto_Run.jar E '.$pwd, $output);
        }else {
            exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/GSPoint/GSC_Crypto_Dev_Run.jar E '.$pwd, $output);
        }
        // exec('/usr/bin/java -jar /Users/ywkim/Documents/GitHub/cms-server/peristalsis/GSPoint/GSC_Crypto_Dev_Run.jar E '.$pwd, $output);

        $ceed_pwd = $output[0];


        $requestBody = [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'search_key' => '02', // 조회구분(01:주민번호,02:카드+주민번호,03:웹아이디+ 주민번호, 04:웹아이디, 05:CI값, 06:카드번호+통합고객 번호)
                'crypt_yn' => 'Y', // 암호화 여부(Y/N) “Y”인 경우 주민번호, 카드비밀번호, 웹비밀번호 모두 암호화하여 전송
                'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                'frnchse_div_code' => 'G775',  // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_frnchse_code' => 'G977500001', // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                'res_no' => '',
                'card_no' => $card_no, // 카드번호(조회조건이 ‘02’,’06’인 경우)
                'pwd' => $ceed_pwd, // 카드비밀번호(조회조건이 ‘02’,’06’인 경우) (암호화 사이즈)
                'web_id' => '', // GS&POINT 아이디 (조회조건이 ‘03’,’04’인 필수)
                'web_pwd' => '', // GS&POINT 웹비밀번호(조회조건이 ‘03’,’04’인 필수) (암호화 사이즈)
                'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                'remark' => '', // 비고
                'ci_vlue' => '', // CI 정보 추가
                'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)
            ]
        ];

        $env = $this->env;

        
        if($env == 'on'){
            $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinSearch.do';
        } else{
            $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinSearch.do';
        }

        $http_result = $this->ci->http->get(
            $gs_url,
            $requestBody
        );

        // $last_relay_index = $this->ci->log->relayHistoryInsert($gs_url, 'POST', $requestBody, null);

        $res = $http_result->getBody()->getContents();

        $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

        $result = json_encode($this->xmlToArray($responseXml));

        $result = json_decode($result, true);

        // $this->ci->log->relayHistoryUpdate($last_relay_index, $result);
        
        fclose($gsinfo);


        return $result['gsc-was'];

    }

    // GS포인트 사용 
    public function gsPointUse($version, $card_no, $park_operate_ct, $amount, $pointAmount, $memb_seq, $ppsl_seq, $payment_channel, $apply_orderno=null, $product_cd=null, $bcar_seq=null, $bcar_number=null, $park_seq=null, $prdt_seq=null)
    {
        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */
        $포인트사용_trans_date = date('Ymd');
        $포인트사용_trans_time = date('His');

        $payment_time =  date('Y-m-d H:i:s', strtotime($포인트사용_trans_date.$포인트사용_trans_time));

        $is_mobile = $this->ci->util->isMobile();

        if($is_mobile) {
            $reg_device_code = 3;
        } else {
            $reg_device_code = 2;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        $포인트사용_chasing_no = $this->generateGsPointUniqNo(); // 20자리 유니크한 값 ㄱ

        
        if($park_operate_ct == '1') {
            $sub_co_frnchse_code = 'G977600001';
            $frnchse_div_code = 'G776';
        } else if ($park_operate_ct == '2') {
            $sub_co_frnchse_code = 'G977500001';
            $frnchse_div_code = 'G775';
        }
        
        try {
            $requestBody = [
                'headers' => [
                    'Content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_trans_date' => $포인트사용_trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                    'sub_co_trans_time' => $포인트사용_trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                    'chasing_no' => $포인트사용_chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.
                    'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                    'biz_no' => '1018651015', // 사업자번호
                    'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_order_no' => '', // 자사주문번호
                    'occur_pt' => $pointAmount, // 사용포인트
                    'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                    'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                    'orn_approv_date' => '', // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                    'orn_approv_no' => '', // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                    'trans_rsn_code' => '9775', // 거래 사유 코드(한도초과, 기타 사유)
                    'taxfl_ind_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                    'pymt_pattern_code' => 'W', // 결재유형
                    'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                    'prod_desctn' => '', // 제품명
                    'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                    'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                    'suppprc' => '', // 공급가
                    'vat' => '', // 부가세
                    'sale_amt' => $pointAmount, // 매출금액
                    'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                    'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                    'remark' => '', // 비고
                    'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)                
                ]
            ];
            
            $env = $this->env;

            if($env == 'on'){
                $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinUse.do';
            } else {
                $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinUse.do';
            }

            $http_result = $this->ci->http->post(
                $gs_url,
                $requestBody
            );

            $res = $http_result->getBody()->getContents();

            $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

            $result = json_encode($this->xmlToArray($responseXml));

            $result = json_decode($result, true);

            $point_use = $result['gsc-was'];

            $result_code = $point_use['result_code'];

            $point_use['approv_time'] = $포인트사용_trans_time;
            $point_use['approv_date'] = $포인트사용_trans_date;
            $point_use['chasing_no'] = $포인트사용_chasing_no;

            if($result_code == '00000') {

                $포인트사용_approv_date = $point_use['approv_date'];
                $포인트사용_approv_no = $point_use['approv_no'];
                $card_no = $point_use['card_no'];
                $cust_name = $point_use['cust_name'];
                $reg_ind = $point_use['reg_ind'];
                $pointAmount = (int)$point_use['occur_pt'];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                    'chasing_no' => $포인트사용_chasing_no,   
                    'type' => 'u',
                    'approv_date'  => date('Y-m-d', strtotime($포인트사용_approv_date)),
                    'approv_no'  => $포인트사용_approv_no,
                    'trans_date' => $포인트사용_trans_date,
                    'trans_time' => $포인트사용_trans_time,
                    'card_no'  => $this->ci->util->encrypted($card_no),
                    'cust_name'  => $cust_name,
                    'reg_ind'  => $reg_ind,
                    'park_operate_ct' => $park_operate_ct,
                    'amount' => $amount,
                    'pointAmount'  => $pointAmount,
                    'memb_seq' => $memb_seq, 
                    'ppsl_seq' => $ppsl_seq, 
                    'payment_channel' => $payment_channel
                ]]);

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                    'chasing_no' => $포인트사용_chasing_no,
                    'appldate' => date('Ymd'),
                    'method' => '포인트사용',
                    'amount' => $amount,
                    'pointAmount' => $pointAmount,
                    'approv_date' => date('Y-m-d', strtotime($포인트사용_approv_date)),
                    'approv_no' => $포인트사용_approv_no,
                    'card_no' => $this->ci->util->encrypted($card_no),
                    'memb_seq' => $memb_seq, 
                    'ppsl_seq' => $ppsl_seq, 
                    'payment_channel' => $payment_channel, 
                    'cust_name' => '',
                    'create_time' => date('Y-m-d H:i:s'),
                    'request_parameter' => $requestBody,
                    'response_parameter' => $point_use
                ]]);

                // 민영인 경우에만 적립
                if($sub_co_frnchse_code == 'G977500001') {

                    if($amount != $pointAmount && $amount > $pointAmount) {

                        $포인트적립_trans_date = date('Ymd');
                        $포인트적립_trans_time = date('His');

                        $포인트적립_chasing_no = $this->generateGsPointUniqNo(); // 20자리 유니크한 값 ㄱ
                        
                        $매출금액 = $amount-$pointAmount;

                        try {
                            $requestBody = [
                                'headers' => [
                                    'Content-type' => 'application/x-www-form-urlencoded'
                                ],
                                'form_params' => [
                                    'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                                    'sub_co_trans_date' => $포인트적립_trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                                    'sub_co_trans_time' => $포인트적립_trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                                    'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                                    'chasing_no' => $포인트적립_chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.             
                                    'biz_no' => '1018651015', // 사업자번호
                                    'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                                    'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                                    'sub_co_approv_no' => '', // 자사 승인번호
                                    'sub_co_order_no' => '', // 자사주문번호
                                    'sub_co_approv_date' => '', // 자사 승인일자(YYYYMMDD)
                                    'occur_pt' => 0, // 발생 적립포인트
                                    'gen_rsv_pt' => 0, // 발생 일반포인트
                                    'special_rsv_pt' => 0, // 발생 특별포인트
                                    'cprt_rsv_pt' => 0, // 발생 제휴 포인트
                                    'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                                    'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                                    'orn_sub_co_approv_date' => $포인트사용_approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                                    'orn_sub_co_approv_no' => $포인트사용_approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                                    'trans_rsn_code' => '9776', // 거래 사유 코드(한도초과, 기타 사유)
                                    'taxfl_div_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                                    'pymt_pattern_code' => 'W', // 결재유형
                                    'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                                    'prod_desctn' => '', // 제품명
                                    'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                                    'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                                    'suppprc' => '', // 공급가
                                    'vat' => '', // 부가세
                                    'sale_amt' => $매출금액, // 매출금액
                                    'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                                    'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                                    'remark' => '', // 비고
                                    'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)                
                                ]
                            ];

                            $env = $this->env;
                            
                            if($env == 'on'){
                                $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinReserve.do';
                            } else {
                                $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinReserve.do';
                            }

                            $http_result = $this->ci->http->post(
                                $gs_url,
                                $requestBody
                            );

                            $res = $http_result->getBody()->getContents();

                            $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

                            $result = json_encode($this->xmlToArray($responseXml));

                            $result = json_decode($result, true);

                            $point_accumulate = $result['gsc-was'];

                            $result_code = $point_accumulate['result_code'];
                            $result_message = $point_accumulate['result_message'];
                            // 발생포인트가 0이므로 이런 메시지가 올 경우 성공 처리해야한다.
                            // if($result_code == '71018' && trim($result_message) == '발생포인트가 존재하지 않습니다') {
                            //     $result_code = '00';
                            //     // $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_payment_list', [[
                            //     //     'icpr_seq' => $ppsl_seq,   
                            //     //     'memb_seq' => $memb_seq,
                            //     //     'point_card_code' => 'GSP',
                            //     //     'payment_datetime' => $payment_time,
                            //     //     'apply_number'  => $포인트사용_approv_no,
                            //     //     'payment_state'  => '사용',
                            //     //     'reg_datetime' => date('Y-m-d H:i:s'),
                            //     //     'reg_seq' => $memb_seq,
                            //     //     'reg_ip' => $ip,
                            //     //     'reg_device_code' => $reg_device_code
                            //     // ]]);
                            //     return [$point_use, $result_code];
                            // }
                            
                            if($result_code == '00000') 
                            {
                                $포인트적립_approv_date = $point_accumulate['approv_date'];
                                $포인트적립_approv_no = $point_accumulate['approv_no'];
                                $card_no = $point_accumulate['card_no'];
                                $cust_name = $point_accumulate['cust_name'];
                                $reg_ind = $point_accumulate['reg_ind'];
                                $accumulateAmount = $point_accumulate['occur_pt'];
                                $tot_rsv_gen_pt = $point_accumulate['tot_rsv_gen_pt'];
                                
                                $point_use['save_point'] = $accumulateAmount;

                                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                                    'chasing_no' => $포인트적립_chasing_no,   
                                    'type' => 'a',
                                    'approv_date'  => date('Y-m-d', strtotime($포인트사용_approv_date)),
                                    'approv_no'  => $포인트사용_approv_no,
                                    'trans_date' => $포인트적립_trans_date,
                                    'trans_time' => $포인트적립_trans_time,
                                    'card_no'  => $this->ci->util->encrypted($card_no),
                                    'cust_name'  => $cust_name,
                                    'reg_ind'  => $reg_ind,
                                    'amount' => $amount,
                                    'pointAmount'  => $pointAmount,
                                    'accumulateAmount' => $accumulateAmount,
                                    'accumulate_approv_no' => $포인트적립_approv_no,
                                    'accumulate_approv_date' => $포인트적립_approv_date,
                                    'memb_seq' => $memb_seq, 
                                    'ppsl_seq' => $ppsl_seq, 
                                    'payment_channel' => $payment_channel
                                ]]);

                                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                                    'chasing_no' => $포인트적립_chasing_no,
                                    'appldate' => date('Ymd'),
                                    'method' => '포인트적립',
                                    'amount' => $amount,
                                    'pointAmount'=> $pointAmount,
                                    'accumulateAmount' => $accumulateAmount,
                                    'approv_date' => date('Y-m-d', strtotime($포인트적립_approv_date)),
                                    'approv_no' => $포인트적립_approv_no,
                                    'card_no' => $this->ci->util->encrypted($card_no),
                                    'cust_name' => '',
                                    'memb_seq' => $memb_seq, 
                                    'ppsl_seq' => $ppsl_seq, 
                                    'payment_channel' => $payment_channel, 
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'request_parameter' => $requestBody,
                                    'response_parameter' => $point_accumulate
                                ]]);

                                list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                                    $version, $point_card_code='GSP', $product_cd, $ppsl_seq, $result_code, $result_msg='포인트사용', 
                                    $pointAmount, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_approv_no, $포인트적립_chasing_no,
                                    $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $pg_site_cd, $accumulateAmount
                                );

                                if($reflect_code == '99') {
                                    $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                                    $this->gsPointAccumulateCancel($포인트적립_approv_date, $포인트적립_approv_no, $memb_seq);
                                    return [(object)[], '99'];
                                }

                                if($result_code == '00000') $result_code = '00';

                                return [$point_use, $result_code];
        
                            // PG 결제금액이 100원 일 경우 실제 적립가능한 금액이 없는 케이스 -> 0원 적립은 생성하지 않는다.
                            } else if ($result_code == '71018' && trim($result_message) == '발생포인트가 존재하지 않습니다') {

                                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                                    'chasing_no' => $포인트적립_chasing_no,
                                    'appldate' => date('Ymd'),
                                    'method' => '포인트적립_0원',
                                    'amount' => $amount,
                                    'pointAmount'=> $pointAmount,
                                    'accumulateAmount' => 0,
                                    'approv_date' => date('Ymd'),
                                    'approv_no' => null,
                                    'card_no' => $this->ci->util->encrypted($card_no),
                                    'cust_name' => '',
                                    'memb_seq' => $memb_seq, 
                                    'ppsl_seq' => $ppsl_seq, 
                                    'payment_channel' => $payment_channel, 
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'request_parameter' => $requestBody,
                                    'response_parameter' => $point_accumulate
                                ]]);

                                $result_code = '00000';

                                // 포인트 사용과 같이 전송
                                list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                                    $version, $point_card_code='GSP', $product_cd, $ppsl_seq, $result_code, $result_msg='포인트사용', 
                                    $pointAmount, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_approv_no, $포인트사용_chasing_no,
                                    $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $pg_site_cd, ""
                                );

                                if($reflect_code == '99') {
                                    $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                                    return [(object)[], '99'];
                                }

                                if($result_code == '00000') $result_code = '00';

                                return [$point_use, $result_code];

                            } else if($result_code != '00000') {
                                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                                    'chasing_no' => $포인트적립_chasing_no,
                                    'appldate' => date('Ymd'),
                                    'method' => '포인트적립실패',
                                    'amount' => $amount,
                                    'pointAmount'=> $pointAmount,
                                    'approv_date' => $포인트사용_trans_date,
                                    'approv_no' => $point_accumulate['approv_no'],
                                    'card_no' => $this->ci->util->encrypted($card_no),
                                    'cust_name' => '',
                                    'memb_seq' => $memb_seq, 
                                    'ppsl_seq' => $ppsl_seq, 
                                    'payment_channel' => $payment_channel,
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'request_parameter' => $requestBody,
                                    'response_parameter' => $point_accumulate
                                ]]);
                                $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                                return [(object)[], "99"];  
                            }

                        } catch (RequestException $e) {   
                            // GS 사용 망취소 
                            $this->gsPointAccumulateCancel($포인트적립_approv_date, $포인트적립_approv_no, $memb_seq);
                            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                            return [(object)[], "99"];   
                        } catch (BadResponseException $e) {
                            $this->gsPointAccumulateCancel($포인트적립_approv_date, $포인트적립_approv_no, $memb_seq);
                            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                            return [(object)[], "99"];   
                        }                
                    } else {
                        //민영이지만 적립하지 않고 사용만한경우, 사용성공시
                        if($result_code == '00000') {
                            $result_code = '00';
     
                            list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                                $version, 'GSP', $product_cd, $ppsl_seq, $result_code, '포인트사용', 
                                $pointAmount, $포인트사용_approv_date, $포인트사용_trans_time, $포인트사용_approv_no, $포인트사용_chasing_no,
                                $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $pg_site_cd, ""
                            );
        
                            if($reflect_code == '99') {
                                $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                                return [(object)[], '99'];
                            }
        
                            return [$point_use, $result_code];
                        } else {
                            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                                'chasing_no' => $포인트사용_chasing_no,
                                'appldate' => date('Ymd'),
                                'method' => '포인트사용실패',
                                'amount' => $amount,
                                'pointAmount' => $pointAmount,
                                'approv_date' => date('Y-m-d', strtotime($포인트사용_approv_date)),
                                'approv_no' => $포인트사용_approv_no,
                                'card_no' => $this->ci->util->encrypted($card_no),
                                'memb_seq' => $memb_seq, 
                                'ppsl_seq' => $ppsl_seq, 
                                'payment_channel' => $payment_channel, 
                                'cust_name' => '',
                                'create_time' => date('Y-m-d H:i:s'),
                                'request_parameter' => $requestBody,
                                'response_parameter' => $point_use
                            ]]);

                            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                            return [(object)[], '99'];
                        }
                    }
                } else {
                    if($result_code == '00000') {
                        $result_code = '00';
 
                        list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                            $version, 'GSP', $product_cd, $ppsl_seq, $result_code, '포인트사용', 
                            $pointAmount, $포인트사용_approv_date, $포인트사용_trans_time, $포인트사용_approv_no, $포인트사용_chasing_no,
                            $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $pg_site_cd, ""
                        );

                        if($reflect_code == '99') {
                            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                            return [(object)[], '99'];
                        }

                        return [$point_use, $result_code];
                    } else {
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                            'chasing_no' => $포인트사용_chasing_no,
                            'appldate' => date('Ymd'),
                            'method' => '포인트사용실패',
                            'amount' => $amount,
                            'pointAmount' => $pointAmount,
                            'approv_date' => date('Y-m-d', strtotime($포인트사용_approv_date)),
                            'approv_no' => $포인트사용_approv_no,
                            'card_no' => $this->ci->util->encrypted($card_no),
                            'memb_seq' => $memb_seq, 
                            'ppsl_seq' => $ppsl_seq, 
                            'payment_channel' => $payment_channel, 
                            'cust_name' => '',
                            'create_time' => date('Y-m-d H:i:s'),
                            'request_parameter' => $requestBody,
                            'response_parameter' => $point_use
                        ]]);

                        $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $포인트사용_approv_date, $포인트사용_approv_no, $amount, $pointAmount, $memb_seq);
                        return [(object)[], '99'];
                    }

                }               

            } else if($result_code != '00000') {
                
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                    'chasing_no' => $포인트사용_chasing_no,
                    'appldate' => date('Ymd'),
                    'method' => '포인트사용실패',
                    'amount' => $amount,
                    'pointAmount' => $pointAmount,
                    'approv_date' => date('Y-m-d', strtotime($포인트사용_approv_date)),
                    'approv_no' => $포인트사용_approv_no,
                    'card_no' => $this->ci->util->encrypted($card_no),
                    'memb_seq' => $memb_seq, 
                    'ppsl_seq' => $ppsl_seq, 
                    'payment_channel' => $payment_channel, 
                    'cust_name' => '',
                    'create_time' => date('Y-m-d H:i:s'),
                    'request_parameter' => $requestBody,
                    'response_parameter' => $point_use
                ]]);

                $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, '', '', $amount, $pointAmount, $memb_seq);
                return [(object)[], "99"];  
            }            

        } catch (RequestException $e) {   
            // GS 사용 망취소 
            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $approv_date, $approv_no, $amount, $pointAmount, $memb_seq);
            return [(object)[], "99"];   
        } catch (BadResponseException $e) {
            $this->gsPointReserveCancel($card_no, $포인트사용_trans_date, $포인트사용_trans_time, $포인트사용_chasing_no, $park_operate_ct, $approv_date, $approv_no, $amount, $pointAmount, $memb_seq);
            return [(object)[], "99"];   
        } 

    }

    // GS포인트 사용 취소
    public function gsPointCancel($사용_approv_date, $사용_approv_no, $memb_seq)
    {
        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */

        // point금액은 우리 테이블에서 가져와야한다.

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.gspoint 
            WHERE 
                type = :type 
            AND 
                approv_no = :approv_no 
            AND 
                approv_date = :approv_date
            LIMIT 1
        ');

        $stmt->execute([
            'type' => 'u',
            'approv_date' => date('Y-m-d', strtotime($사용_approv_date)), 
            'approv_no' => $사용_approv_no 
        ]);

        $data = $stmt->fetch();


        $pointAmount = $data['pointAmount'] ?? 0;  
        $amount = $data['amount'];
        $card_no = $this->ci->util->decrypted($data['card_no']);
        $ppsl_seq = $data['ppsl_seq']; 
        $payment_channel = $data['payment_channel']; 
        $park_operate_ct = $data['park_operate_ct'];

        if($park_operate_ct == '1') {
            $sub_co_frnchse_code = 'G977600001';
            $frnchse_div_code = 'G776';
        } else if ($park_operate_ct == '2') {
            $sub_co_frnchse_code = 'G977500001';
            $frnchse_div_code = 'G775';
        }

        $trans_date = date('Ymd');
        $trans_time = date('His');

        $payment_time =  date('Y-m-d H:i:s', strtotime($trans_date.$trans_time));

        $is_mobile = $this->ci->util->isMobile();

        if($is_mobile) {
            $reg_device_code = 3;
        } else {
            $reg_device_code = 2;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        

        $chasing_no = $this->generateGsPointUniqNo();

        $requestBody = [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_trans_date' => $trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                'sub_co_trans_time' => $trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                'chasing_no' => $chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.
                'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                'biz_no' => '1018651015', // 사업자번호
                'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_order_no' => '', // 자사주문번호
                'occur_pt' => $pointAmount, // 사용포인트
                'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                'orn_approv_date' => $사용_approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                'orn_approv_no' => $사용_approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                'trans_rsn_code' => '9775', // 거래 사유 코드(한도초과, 기타 사유)
                'taxfl_ind_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                'pymt_pattern_code' => 'W', // 결재유형
                'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                'prod_desctn' => '', // 제품명
                'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                'suppprc' => '', // 공급가
                'vat' => '', // 부가세
                'sale_amt' => $pointAmount, // 매출금액
                'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                'remark' => '', // 비고
                'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)      
            ]
        ];
        
        $env = $this->env;

        if($env == 'on'){
            $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinUseCancel.do';
        } else {
            $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinUseCancel.do';
        }

        $http_result = $this->ci->http->post(
            $gs_url,
            $requestBody
        );

        $res = $http_result->getBody()->getContents();

        $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

        $result = json_encode($this->xmlToArray($responseXml));

        $result = json_decode($result, true);

        $result['gsc-was']['cancel_appl_time'] = $trans_time;

        $result_gsc_was = $result['gsc-was'];

        $result_code = $result_gsc_was['result_code'];

        $사용취소_approv_date = null;
        $사용취소_approv_no = null;
        if($result_code == '00000') {

            $사용취소_approv_date = $result_gsc_was['approv_date'];
            $사용취소_approv_no = $result_gsc_was['approv_no'];
            $card_no = $result_gsc_was['card_no'];
            $cust_name = $result_gsc_was['cust_name'];
            $reg_ind = $result_gsc_was['reg_ind'];
            $occur_pt = $result_gsc_was['occur_pt'];

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                'chasing_no' => $chasing_no,   
                'type' => 'uc',
                'park_operate_ct' => $park_operate_ct,
                'approv_date'  => $사용취소_approv_date,
                'approv_no'  => $사용취소_approv_no,
                'trans_date' => $trans_date,
                'trans_time' => $trans_time,
                'card_no'  => $this->ci->util->encrypted($card_no),
                'cust_name'  => $cust_name,
                'reg_ind'  => $reg_ind,
                'amount' => $amount,
                'pointAmount'  => $occur_pt,
                'cancel_approv_no' => $사용_approv_no,
                'cancel_approv_date' => $사용_approv_date,
                'memb_seq' => $memb_seq, 
                'ppsl_seq' => $ppsl_seq, 
                'payment_channel' => $payment_channel
            ]]);

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                'chasing_no' => $chasing_no,
                'appldate' => date('Ymd'),
                'method' => '포인트사용취소',
                'amount' => $amount,
                'pointAmount'  => $occur_pt,
                'approv_date' => date('Y-m-d', strtotime($사용취소_approv_date)),
                'approv_no' => $사용취소_approv_no,
                'card_no' => $this->ci->util->encrypted($card_no),
                'cust_name' => '',
                'memb_seq' => $memb_seq, 
                'ppsl_seq' => $ppsl_seq, 
                'payment_channel' => $payment_channel, 
                'create_time' => date('Y-m-d H:i:s'),
                'request_parameter' => $requestBody,
                'response_parameter' => $result['gsc-was']
            ]]);

            // 적립 건이 있는지 확인해서 적립이 있을 경우 적립취소 해야한다.

            $적립취소_trans_date = date('Ymd');
            $적립취소_trans_time = date('His');

            $포인트적립_chasing_no = $this->generateGsPointUniqNo(); // 20자리 유니크한 값 ㄱ

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT * FROM iparking_cms.gspoint 
                WHERE 
                    type = :type 
                AND 
                    approv_no = :approv_no 
                AND 
                    approv_date = :approv_date
                LIMIT 1
            ');

            $stmt->execute([
                'type' => 'a',
                'approv_date' => date('Y-m-d', strtotime($사용_approv_date)), 
                'approv_no' => $사용_approv_no 
            ]);

            $data = $stmt->fetch();
            
            $result_code = "00";

            if(!empty($data)) {
                $pointAmount = $data['pointAmount'] ?? 0;
                $amount = $data['amount'];
                $card_no = $this->ci->util->decrypted($data['card_no']);
                $sub_co_approv_no = "";
                $accumulate_approv_no = $data['accumulate_approv_no'];
                $accumulate_approv_date = $data['accumulate_approv_date'];
        
                $requestBody = [
                    'headers' => [
                        'Content-type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                        'sub_co_trans_date' => $적립취소_trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                        'sub_co_trans_time' => $적립취소_trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                        'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                        'chasing_no' => $포인트적립_chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.             
                        'biz_no' => '1018651015', // 사업자번호
                        'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                        'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                        'sub_co_approv_no' => $accumulate_approv_no, // 자사 승인번호
                        'sub_co_order_no' => '', // 자사주문번호
                        'sub_co_approv_date' => $accumulate_approv_date, // 자사 승인일자(YYYYMMDD)
                        'occur_pt' => 0, // 발생 적립포인트
                        'gen_rsv_pt' => 0, // 발생 일반포인트
                        'special_rsv_pt' => 0, // 발생 특별포인트
                        'cprt_rsv_pt' => 0, // 발생 제휴 포인트
                        'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                        'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                        'orn_sub_co_approv_date' => $accumulate_approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                        'orn_sub_co_approv_no' => $accumulate_approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                        'trans_rsn_code' => '9776', // 거래 사유 코드(한도초과, 기타 사유)
                        'taxfl_div_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                        'pymt_pattern_code' => 'W', // 결재유형
                        'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                        'prod_desctn' => '', // 제품명
                        'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                        'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                        'suppprc' => '', // 공급가
                        'vat' => '', // 부가세
                        'sale_amt' => $amount-$pointAmount, // 매출금액
                        'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                        'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                        'remark' => '', // 비고
                        'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)           
                    ]
                ];

                $env = $this->env;

                if($env == 'on'){
                    $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinReserveCancel.do';
                } else {
                    $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinReserveCancel.do';
                }

                $http_result = $this->ci->http->post(
                    $gs_url,
                    $requestBody
                );

                $res = $http_result->getBody()->getContents();

                $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

                $accumulate_result = json_encode($this->xmlToArray($responseXml));

                $accumulate_result = json_decode($accumulate_result, true);

                $accumulate_result_gsc_was = $accumulate_result['gsc-was'];

                $result_code = $accumulate_result_gsc_was['result_code'];

                $적립취소_approv_date = null;
                $적립취소_approv_no = null;
                if($result_code == '00000') {

                    $적립취소_approv_date = $accumulate_result_gsc_was['approv_date'];
                    $적립취소_approv_no = $accumulate_result_gsc_was['approv_no'];
                    $card_no = $accumulate_result_gsc_was['card_no'];
                    $cust_name = $accumulate_result_gsc_was['cust_name'];
                    $reg_ind = $accumulate_result_gsc_was['reg_ind'];
                    $accumulateAmount = $accumulate_result_gsc_was['occur_pt'];
                    $tot_rsv_gen_pt = $accumulate_result_gsc_was['tot_rsv_gen_pt'];

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                        'chasing_no' => $포인트적립_chasing_no,   
                        'type' => 'ac',
                        'approv_date'  => $적립취소_approv_date,
                        'approv_no'  => $적립취소_approv_no,
                        'trans_date' => $적립취소_trans_date,
                        'trans_time' => $적립취소_trans_time,
                        'accumulate_cancel_approv_no' => $accumulate_approv_no,
                        'accumulate_cancel_approv_date' => $accumulate_approv_date,
                        'card_no'  => $this->ci->util->encrypted($card_no),
                        'cust_name'  => $cust_name,
                        'reg_ind'  => $reg_ind,
                        'amount' => $amount,
                        'pointAmount'  => $pointAmount,
                        'accumulateAmount' => $accumulateAmount,
                        'memb_seq' => $memb_seq
                    ]]);

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                        'chasing_no' => $포인트적립_chasing_no,
                        'appldate' => date('Ymd'),
                        'method' => '포인트적립취소',
                        'amount' => $amount,
                        'pointAmount' => $pointAmount,
                        'accumulateAmount' => $accumulateAmount,
                        'approv_date' => date('Y-m-d', strtotime($적립취소_approv_date)),
                        'approv_no' => $적립취소_approv_no,
                        'card_no' => $this->ci->util->encrypted($card_no),
                        'cust_name' => '',
                        'memb_seq' => $memb_seq,
                        'create_time' => date('Y-m-d H:i:s'),
                        'request_parameter' => $requestBody,
                        'response_parameter' => $accumulate_result_gsc_was
                    ]]);

                    $result_code = "00";
                } else {
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                        'chasing_no' => $포인트적립_chasing_no,
                        'appldate' => date('Ymd'),
                        'method' => '포인트적립취소 실패',
                        'amount' => $amount,
                        'pointAmount' => $pointAmount,
                        'accumulateAmount' => $accumulateAmount,
                        'approv_date' => date('Y-m-d', strtotime($적립취소_approv_date)),
                        'approv_no' => $적립취소_approv_no,
                        'card_no' => $this->ci->util->encrypted($card_no),
                        'cust_name' => '',
                        'memb_seq' => $memb_seq,
                        'create_time' => date('Y-m-d H:i:s'),
                        'request_parameter' => $requestBody,
                        'response_parameter' => $accumulate_result_gsc_was
                    ]]);
                }

                $result['gsc-was']['cancel_save_point'] = $accumulateAmount;
                $result_gsc_was = $result['gsc-was'];
                return [$result_gsc_was, $result_code];

            }
            
            /////////////////
        } else {
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                'chasing_no' => $chasing_no,
                'appldate' => date('Ymd'),
                'method' => '포인트사용취소 실패',
                'amount' => $pointAmount,
                'approv_date' => date('Y-m-d', strtotime($사용취소_approv_date)),
                'approv_no' => $사용취소_approv_no,
                'card_no' => $this->ci->util->encrypted($card_no),
                'cust_name' => '',
                'memb_seq' => $memb_seq, 
                'ppsl_seq' => $ppsl_seq, 
                'payment_channel' => $payment_channel, 
                'create_time' => date('Y-m-d H:i:s'),
                'request_parameter' => $requestBody,
                'response_parameter' => $result['gsc-was']
            ]]);
        }

        

        return [$result['gsc-was'], $result_code];

    }

    // GS포인트 사용 망취소
    public function gsPointReserveCancel($card_no, $trans_date, $trans_time, $chasing_no, $park_operate_ct, $approv_date='', $approv_no='', $amount=0, $pointAmount=0, $memb_seq) 
    {
        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */

        if($park_operate_ct == '1') {
            $sub_co_frnchse_code = 'G977600001';
            $frnchse_div_code = 'G776';
        } else if ($park_operate_ct == '2') {
            $sub_co_frnchse_code = 'G977500001';
            $frnchse_div_code = 'G775';
        }

        $requestBody = [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_trans_date' => $trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                'sub_co_trans_time' => $trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                'chasing_no' => $chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.     
                'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                'biz_no' => '1018651015', // 사업자번호 
                'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_order_no' => '',
                'occur_pt' => $pointAmount, // 사용포인트
                'card_media_ind_code' => '1', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                'orn_approv_date' => $approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                'orn_approv_no' => $approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                'trans_rsn_code' => '9775', // 거래 사유 코드(한도초과, 기타 사유)
                'taxfl_ind_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                'pymt_pattern_code' => 'W', // 결재유형    
                'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                'prod_desctn' => '', // 제품명
                'cnt' => '', // 수량(실제 값에 100을 곱한 값) === 필수 ===
                'unitprc' => '', // 단가(실제 값에 100을 곱한 값) === 필수 ===
                'suppprc' => '', // 공급가
                'vat' => '', // 부가세
                'sale_amt' => $pointAmount, // 매출금액
                'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                'remark' => '', // 비고
                'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)      
            ]
        ];

        $env = $this->env;

        if($env == 'on'){
            $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinNetUseCancel.do';
        } else {
            $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinNetUseCancel.do';
        }
        
        $http_result = $this->ci->http->post(
            $gs_url,
            $requestBody
        );

        $res = $http_result->getBody()->getContents();

        $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

        $result = json_encode($this->xmlToArray($responseXml));

        $result = json_decode($result, true);

        $result_gsc_was = $result['gsc-was'];

        $result_code = $result_gsc_was['result_code'];

        if($result_code == '00000') {

            $cancel_approv_date = $result_gsc_was['approv_date'];
            $cancel_approv_no = $result_gsc_was['approv_no'];
            $card_no = $result_gsc_was['card_no'];
            $cust_name = $result_gsc_was['cust_name'];
            $reg_ind = $result_gsc_was['reg_ind'];
            $occur_pt = $result_gsc_was['occur_pt'];

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                'chasing_no' => $chasing_no,   
                'type' => 'urc',
                'park_operate_ct' => $park_operate_ct,
                'approv_date'  => $cancel_approv_date,
                'approv_no'  => $cancel_approv_no,
                'trans_date' => $trans_date,
                'trans_time' => $trans_time,
                'card_no'  => $this->ci->util->encrypted($card_no),
                'cust_name'  => $cust_name,
                'reg_ind'  => $reg_ind,
                'amount' => $amount,
                'pointAmount'  => $occur_pt,
                'cancel_approv_no' => $approv_no,
                'cancel_approv_date' => $approv_date,
                'memb_seq' => $memb_seq
            ]]);
        }

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
            'chasing_no' => $chasing_no,
            'appldate' => date('Ymd'),
            'method' => '포인트사용망취소',
            'amount' => $pointAmount,
            'approv_date' => date('Y-m-d', strtotime($approv_date)),
            'approv_no' => $approv_no,
            'card_no' => $this->ci->util->encrypted($card_no),
            'cust_name' => '',
            'memb_seq' => $memb_seq,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $requestBody,
            'response_parameter' => $result['gsc-was']
        ]]);

        return $result['gsc-was'];
    }

    // GS포인트 적립
    public function gsPointAccumulateUse($approv_date, $approv_no, $memb_seq, $park_operate_ct)
    {
        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */

        if($park_operate_ct == '1') {
            $sub_co_frnchse_code = 'G977600001';
            $frnchse_div_code = 'G776';
        } else if ($park_operate_ct == '2') {
            $sub_co_frnchse_code = 'G977500001';
            $frnchse_div_code = 'G775';
        }
        
        $trans_date = date('Ymd');
        $trans_time = date('His');

        $chasing_no = $this->generateGsPointUniqNo(); // 20자리 유니크한 값 ㄱ

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.gspoint 
            WHERE 
                type = :type 
            AND 
                approv_no = :approv_no 
            AND 
                approv_date = :approv_date
            LIMIT 1
        ');

        $stmt->execute([
            'type' => 'u',
            'approv_date' => date('Y-m-d', strtotime($approv_date)), 
            'approv_no' => $approv_no 
        ]);

        $data = $stmt->fetch();

        $pointAmount = $data['pointAmount'] ?? 0;
        $amount = $data['amount'];
        $card_no = $this->ci->util->decrypted($data['card_no']);
        $sub_co_approv_no = "";
  
        $requestBody = [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_trans_date' => $trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                'sub_co_trans_time' => $trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                'chasing_no' => $chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.             
                'biz_no' => '1018651015', // 사업자번호
                'frnchse_div_code' => $frnchse_div_code, // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_frnchse_code' => $sub_co_frnchse_code, // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                'sub_co_approv_no' => '', // 자사 승인번호
                'sub_co_order_no' => '', // 자사주문번호
                'sub_co_approv_date' => '', // 자사 승인일자(YYYYMMDD)
                'occur_pt' => 0, // 발생 적립포인트
                'gen_rsv_pt' => 0, // 발생 일반포인트
                'special_rsv_pt' => 0, // 발생 특별포인트
                'cprt_rsv_pt' => 0, // 발생 제휴 포인트
                'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                'orn_sub_co_approv_date' => $approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                'orn_sub_co_approv_no' => $approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                'trans_rsn_code' => '9776', // 거래 사유 코드(한도초과, 기타 사유)
                'taxfl_div_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                'pymt_pattern_code' => 'W', // 결재유형
                'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                'prod_desctn' => '', // 제품명
                'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                'suppprc' => '', // 공급가
                'vat' => '', // 부가세
                'sale_amt' => $amount-$pointAmount, // 매출금액
                'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                'remark' => '', // 비고
                'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)                
            ]
        ];
        
        $env = $this->env;

        if($env == 'on'){
            $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinReserve.do';
        } else {
            $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinReserve.do';
        }

        $http_result = $this->ci->http->post(
            $gs_url,
            $requestBody
        );

        $res = $http_result->getBody()->getContents();

        $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

        $result = json_encode($this->xmlToArray($responseXml));

        $result = json_decode($result, true);

        $result_gsc_was = $result['gsc-was'];

        $result_code = $result_gsc_was['result_code'];
        $result_message = $result_gsc_was['result_message'];

        if($result_code == '00000') {

            $approv_date = $result_gsc_was['approv_date'];
            $approv_no = $result_gsc_was['approv_no'];
            $card_no = $result_gsc_was['card_no'];
            $cust_name = $result_gsc_was['cust_name'];
            $reg_ind = $result_gsc_was['reg_ind'];
            $accumulateAmount = $result_gsc_was['occur_pt'];
            $tot_rsv_gen_pt = $result_gsc_was['tot_rsv_gen_pt'];

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                'chasing_no' => $chasing_no,   
                'type' => 'a',
                'approv_date'  => $approv_date,
                'approv_no'  => $approv_no,
                'trans_date' => $trans_date,
                'trans_time' => $trans_time,
                'card_no'  => $this->ci->util->encrypted($card_no),
                'cust_name'  => $cust_name,
                'reg_ind'  => $reg_ind,
                'amount' => $amount,
                'pointAmount'  => $pointAmount,
                'accumulateAmount' => $accumulateAmount,
                'memb_seq' => $memb_seq
            ]]);
        }

        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
            'chasing_no' => $chasing_no,
            'appldate' => date('Ymd'),
            'method' => '포인트적립',
            'amount' => $amount,
            'pointAmount' => $pointAmount,
            'accumulateAmount' => $accumulateAmount ?? 0,
            'approv_date' => date('Y-m-d', strtotime($approv_date)),
            'approv_no' => $approv_no,
            'card_no' => $this->ci->util->encrypted($card_no),
            'cust_name' => '',
            'memb_seq' => $memb_seq,
            'create_time' => date('Y-m-d H:i:s'),
            'request_parameter' => $requestBody,
            'response_parameter' => $result['gsc-was']
        ]]);

        return $result['gsc-was'];

    }

    // GS포인트 적립 취소
    public function gsPointAccumulateCancel($approv_date=null, $approv_no=null, $memb_seq=null)
    {
        /*
            - 코드 정보
            기관코드(chnl_sub_co_code) : 9775
            가맹점코드(sub_co_frnchse_code) : G977500001
            가맹점구분코드(frnchse_div_code) : G775
            상품코드(prod_code) : 9775
            거래사유코드(trans_rsn_code) : 9775
            사용자ID(input_user_id) : PARKING_CLOUD

            - 테스트 계정 정보
            웹 아이디 : parkingcloud
            웹 PW: 1234
            카드번호 : 0190610500050002
            카드 PW : 1234
        */
        $trans_date = date('Ymd');
        $trans_time = date('His');

        $chasing_no = $this->generateGsPointUniqNo(); // 20자리 유니크한 값 ㄱ

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT * FROM iparking_cms.gspoint 
            WHERE 
                type = :type 
            AND 
                approv_no = :approv_no 
            AND 
                approv_date = :approv_date
            LIMIT 1
        ');

        $stmt->execute([
            'type' => 'a',
            'approv_date' => date('Y-m-d', strtotime($approv_date)), 
            'approv_no' => $approv_no 
        ]);

        $data = $stmt->fetch();

        if(!empty($data)) {
            $pointAmount = $data['pointAmount'] ?? 0;
            $amount = $data['amount'];
            $card_no = $this->ci->util->decrypted($data['card_no']);
            $sub_co_approv_no = "";
    
            $requestBody = [
                'headers' => [
                    'Content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'chnl_sub_co_code' => '9775', // 기관코드(자사코드) 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_trans_date' => $trans_date, // 거래일자(YYYYMMDD): 제휴사 기준 매출 일자
                    'sub_co_trans_time' => $trans_time, // 거래시간(HHMMSS): 제휴사 기준 매출 시간
                    'sub_co_trans_type' => 'ONL', // 거래유형 OFF: 오프라인 거래, ONL: 온라인 거래(WEB)
                    'chasing_no' => $chasing_no,  // 각 제휴사에서 부여(Unique Sequence Number) 망상취소 시 원거래 시 보낸 추적번호와 일치해야 함. CHASING_NO는 20자리 유니크한 값으로 제휴사에서 부여하여야 합니다.             
                    'biz_no' => '1018651015', // 사업자번호
                    'frnchse_div_code' => 'G775', // 가맹점 구분 코드 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_frnchse_code' => 'G977500001', // 제휴 가맹점 코드 제휴사 추가 시 그룹에서 코드 부여
                    'sub_co_approv_no' => $approv_no, // 자사 승인번호
                    'sub_co_order_no' => '', // 자사주문번호
                    'sub_co_approv_date' => $approv_date, // 자사 승인일자(YYYYMMDD)
                    'occur_pt' => 0, // 발생 적립포인트
                    'gen_rsv_pt' => 0, // 발생 일반포인트
                    'special_rsv_pt' => 0, // 발생 특별포인트
                    'cprt_rsv_pt' => 0, // 발생 제휴 포인트
                    'card_media_ind_code' => '', // 카드매체구분코드(1: MS, 2: IC, 3: 기타)
                    'card_no' => $card_no, // 보너스카드번호(카드번호만 받음)
                    'orn_sub_co_approv_date' => $approv_date, // 통합(GS&POINT) 원 승인일자(취소거래시사용)
                    'orn_sub_co_approv_no' => $approv_no, // 통합(GS&POINT) 원 승인번호(취소거래시사용)
                    'trans_rsn_code' => '9776', // 거래 사유 코드(한도초과, 기타 사유)
                    'taxfl_div_code' => '', // 과/면세 구분(1: 과세, 2: 면세, 3 : 기타)
                    'pymt_pattern_code' => 'W', // 결재유형
                    'prod_code' => '9775', // 제품코드 제휴사 추가 시 그룹에서 코드 부여
                    'prod_desctn' => '', // 제품명
                    'cnt' => '', // 수량(실제 값에 100을 곱한 값)
                    'unitprc' => '', // 단가(실제 값에 100을 곱한 값)
                    'suppprc' => '', // 공급가
                    'vat' => '', // 부가세
                    'sale_amt' => $amount-$pointAmount, // 매출금액
                    'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                    'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                    'remark' => '', // 비고
                    'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)           
                ]
            ];

            $env = $this->env;

            if($env == 'on'){
                $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinReserveCancel.do';
            } else {
                $gs_url = 'https://ccodev.gshnpoint.com:8088/gswas/was/pointInfoJoinReserveCancel.do';
            }

            $http_result = $this->ci->http->post(
                $gs_url,
                $requestBody
            );

            $res = $http_result->getBody()->getContents();

            $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

            $result = json_encode($this->xmlToArray($responseXml));

            $result = json_decode($result, true);

            $result_gsc_was = $result['gsc-was'];

            $result_code = $result_gsc_was['result_code'];

            if($result_code == '00000') {

                $approv_date = $result_gsc_was['approv_date'];
                $approv_no = $result_gsc_was['approv_no'];
                $card_no = $result_gsc_was['card_no'];
                $cust_name = $result_gsc_was['cust_name'];
                $reg_ind = $result_gsc_was['reg_ind'];
                $accumulateAmount = $result_gsc_was['occur_pt'];
                $tot_rsv_gen_pt = $result_gsc_was['tot_rsv_gen_pt'];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint', [[
                    'chasing_no' => $chasing_no,   
                    'type' => 'ac',
                    'approv_date'  => $approv_date,
                    'approv_no'  => $approv_no,
                    'trans_date' => $trans_date,
                    'trans_time' => $trans_time,
                    'card_no'  => $this->ci->util->encrypted($card_no),
                    'cust_name'  => $cust_name,
                    'reg_ind'  => $reg_ind,
                    'pointAmount'  => $pointAmount,
                    'accumulateAmount' => $accumulateAmount,
                    'memb_seq' => $memb_seq
                ]]);
            }

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.gspoint_history', [[
                'chasing_no' => $chasing_no,
                'appldate' => date('Ymd'),
                'method' => '포인트적립취소',
                'amount' => $pointAmount,
                'accumulateAmount' => $accumulateAmount,
                'approv_date' => date('Y-m-d', strtotime($approv_date)),
                'approv_no' => $approv_no,
                'card_no' => $this->ci->util->encrypted($card_no),
                'cust_name' => '',
                'memb_seq' => $memb_seq,
                'create_time' => date('Y-m-d H:i:s'),
                'request_parameter' => $requestBody,
                'response_parameter' => $result['gsc-was']
            ]]);

            return $result['gsc-was'];

        }

        
    }

    ////////////////////////////////////////////////// GS Point END //////////////////////////////////////////////////


    
    // 하루동안 회원이 쓴 포인트 합계 
    public function oneDayLimit($point_card_code, $memb_seq, $pointAmount, $now, $max_avail_point){

        $startNow = $now.' 00:00:00';
        $endNow = $now.' 23:59:59';



        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                sum(ppl.point_amount) as sum_amount 
            FROM fdk_parkingcloud.member_point_card_info as pci
            INNER JOIN 
                fdk_parkingcloud.point_payment_list as ppl ON pci.memb_seq = ppl.memb_seq 
            WHERE 
                ppl.payment_state = "사용"
            AND 
                ppl.payment_datetime between :startNow and :endNow
            AND 
                ppl.memb_seq = :memb_seq
            AND 
                ppl.point_card_code = :point_card_code
        ');

        $stmt->execute(['startNow' => $startNow, 'endNow' => $endNow, 'memb_seq' => $memb_seq, 'point_card_code' => $point_card_code ]);

        $data = $stmt->fetch(); 

        $sum_amount = $data['sum_amount'];

        $total_amount = $sum_amount + $pointAmount;

        
        if($total_amount <= $max_avail_point){
            $over_point = null;
            $code = "00";
        }else if($total_amount > $max_avail_point){
            $over_point = $total_amount - $max_avail_point;
            $code = "99";
        }
    
        return [$over_point, $code];

    }


    // 해당 point_card_code의 최대 사용 가능 포인트
    public function oneTimeLimit($point_card_code, $pointAmount, $amount, $now, $park_operate_ct)
    {
    
        if($park_operate_ct == "1")  $operation_method = "PUB";
        if($park_operate_ct == "2")  $operation_method = "PRI";

        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                purchase_max_avail_point, point_use_unit
            FROM 
                fdk_parkingcloud.point_card_policy
            WHERE 
                point_card_code = :point_card_code
            AND 
                cooperation_start_date <= :now1  
            AND 
                cooperation_end_date >= :now2
            AND 
                operation_method = :operation_method
        ');

        $stmt -> execute(['point_card_code' => $point_card_code, 'now1' => $now, 'now2' => $now, 'operation_method' => $operation_method]);

        $data = $stmt->fetch();

        $purchase_max_avail_point =  $data['purchase_max_avail_point'];
        $point_use_unit =  $data['point_use_unit'];
    
        if($point_use_unit = "PCT"){
            $max_avail_point = $amount * ($purchase_max_avail_point/100);
            if($pointAmount <= $max_avail_point){
                $code = "00";
            }else if($pointAmount > $max_avail_point) {
                $code = "99";
            }
        }

        if($point_use_unit = "VAL"){
            if($pointAmount <= $max_avail_point){
                $code = "00";
            }else if($pointAmount > $max_avail_point){
                $code = "99";
            }
        }

        return [$max_avail_point, $code];

    }
    
    // 블루포인트 사용
    public function bluePointUse($version, $memb_seq, $point_card_code, $use_point, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $bcar_seq, $bcar_number, $billing_key, $prdt_seq)
    {
        try {

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'use_point' => $pointAmount,
                'park_seq' => $park_seq
            ];

            $body = json_encode($body_param);

            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            try {

                $requestBody = [
                    'headers' => [
                        'Content-Type' => 'application/json',// 'application/x-www-form-urlencoded', 
                        'cipherApplyYN' => 'Y'
                    ],
                    'body' => json_encode(array(
                        'data' => $base64encode_data
                    )),
                    'timeout' => 60,
                    'connect_timeout' => 60
                ];

                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/pay/bluepoint';

                $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
 
                $http_result = $this->ci->http->post(
                    $url,
                    $requestBody
                );
        
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);
                $result_data = $result['resultData'];
                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);
                $pg_site_cd = $result_data['pg_site_cd'];

                // 블루 포인트 성공시 bluepoint 테이블 인서트 및 B2B 연동
                $bluepoint_history_result = "실패"; 
                if($result['result'] == '0000'){
                    $bluepoint_history_result = "성공";

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.ksnet_point', [[
                        'type' => 'u',
                        'point_card_code' => $point_card_code,
                        'park_operate_ct'  => $park_operate_ct,
                        'appl_num'  => $result_data['appl_num'],
                        'appl_date' => $result_data['appl_date'],
                        'appl_time' => $result_data['appl_time'],
                        'memb_seq'  => $memb_seq,
                        'pay_price'  => $pay_price,
                        'tot_price'  => $tot_price,
                        'cp_price'  => $cp_price,
                        'point_price'  => $pointAmount,
                        'tid'  => $result_data['tid'],
                        'product_cd' => $product_cd,
                        'product_seq' => $product_seq,
                        'payment_channel'  => $payment_channel,
                        'cp_hist_seq'  => $cp_hist_seq,
                        'pg_site_cd' => $pg_site_cd
                    ]]);

                    $last_bluepoint_index = $this->ci->iparkingCmsDb->lastInsertId();

                    // 블루포인트 B2B 연동
                    list($reflect_result, $reflect_code) = $this->ci->relay->pointCardReflect(
                        $version, $point_card_code, $product_cd, $product_seq, '00', '포인트사용', 
                        $pointAmount, $result_data['appl_date'], $result_data['appl_time'], $result_data['appl_num'], $result_data['tid'],
                        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $pg_site_cd, ""
                    );

                    if($reflect_code == '99') {
                        // 블루 포인트 사용연동 실패했을경우 
                        return [(object)[], '99'];
                    }
                }

                // 블루포인트 테이블은 성공시만 히스토리는 성공여부 관계없이 인서트한다.
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.ksnet_point_history', [[
                    'point_card_code' => $point_card_code,
                    'bluepoint_idx' => $last_bluepoint_index,
                    'result'  => $bluepoint_history_result,
                    'request_parameter'  => $body_param,
                    'response_parameter' => $result,
                    'appl_num'  => $result_data['appl_num'],
                    'appl_date' => $result_data['appl_date'],
                    'appl_time' => $result_data['appl_time'],
                    'point_price'  => $point_price,
                    'cancel_point_price'  => $cancel_point_price,
                    'tid'  => $tid,
                    'product_cd' => $product_cd,
                    'product_seq' => $product_seq,
                    'cp_hist_seq'  => $cp_hist_seq,
                    'payment_channel'  => $payment_channel,
                    'cp_price' => $cp_price
                ]]);

                // $last_bluepoint_history_index = $this->ci->iparkingCmsDb->lastInsertId();


                // $blpg = fopen('/tmp/bl_pg_si.txt', 'ab') or die("can't open file");
                // fwrite($blpg, '================bl pg==============\n');
                // fwrite($blpg, 'pg_site_cd : '.$pg_site_cd);

                $point_use = array(
                    'tid' => $result_data['tid'],
                    'appl_num'  => $result_data['appl_num'],
                    'appl_date' => $result_data['appl_date'],
                    'appl_time' => $result_data['appl_time'],
                    'pg_site_cd' => $pg_site_cd
                );
                // fwrite($blpg, '================point_use==============');
                // fwrite($blpg, print_r($point_use,TRUE) );

                // fclose($blpg);
                // 블루포인트 사용 실패시 
                if($result['result'] != '0000'){
                    return [(object)[], '99'];
                }

                return [$point_use, $result['result']];

            } catch (RequestException $e) {   
                return [(object)[], '99'];
                // return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return [(object)[], '99']; 
                // return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return [(object)[], '99'];
            // return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
    // 블루포인트 사용취소
    public function bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $appl_num, $appl_date, $appl_time , $pg_site_cd)
    {
        try {

            if($pg_site_cd == null || $pg_site_cd == ""){
                $pg_site_cd = 0;
            }
            // if($tid == null || $tid == ""){
            //     $tid = 000000;
            // }

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'cancel_point' => $pointAmount,
                'appl_num' => $appl_num,
                'appl_date' => $appl_date,
                'appl_time' => $appl_time,
                'tid' => "000000",
                'park_seq' => $park_seq,
                'pg_site_cd' => $pg_site_cd
            ];

            $body = json_encode($body_param);

            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            $requestBody = [
                'headers' => [
                    'Content-Type' => 'application/json',// 'application/x-www-form-urlencoded', 
                    'cipherApplyYN' => 'Y'
                ],
                'body' => json_encode(array(
                    'data' => $base64encode_data
                )),
                'timeout' => 60,
                'connect_timeout' => 60
            ];

            $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/cancel/bluepoint';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);

            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
    
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);
            $result_data = $result['resultData'];

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            // 블루 포인트 성공시 bluepoint,history 테이블 인서트
            $bluepoint_history_result = "포인트사용 취소 실패"; 
            if($result['result'] == '0000'){
                $bluepoint_history_result = "포인트사용 취소 성공";

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.ksnet_point', [[
                    'type' => 'uc',
                    'point_card_code' => $point_card_code,
                    'park_operate_ct'  => $park_operate_ct,
                    'appl_num'  => $appl_num,
                    'appl_date' => $appl_date,
                    'appl_time' => $appl_time,
                    'cancel_appl_num' => $result_data['cancel_appl_num'],
                    'cancel_appl_date' => $result_data['cancel_appl_date'],
                    'cancel_appl_time' => $result_data['cancel_appl_time'],
                    'memb_seq'  => $memb_seq,
                    'pay_price'  => $pay_price,
                    'tot_price'  => $tot_price,
                    'cp_price'  => $cp_price,
                    'point_price'  => $pointAmount,
                    'cancel_point_price' => $pointAmount,
                    'tid'  => $tid,
                    'product_cd' => $product_cd,
                    'product_seq' => $product_seq,
                    'payment_channel'  => $payment_channel,
                    'cp_hist_seq'  => $cp_hist_seq,
                    'pg_site_cd' => $pg_site_cd
                ]]);

                $last_bluepoint_index = $this->ci->iparkingCmsDb->lastInsertId();

            }

            // 블루포인트 테이블은 성공시만 히스토리는 성공여부 관계없이 인서트한다.
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.ksnet_point_history', [[
                'bluepoint_idx' => $last_bluepoint_index,
                'point_card_code' => $point_card_code,
                'result'  => $bluepoint_history_result,
                'request_parameter'  => $body_param,
                'response_parameter' => $result,
                'appl_num'  => $appl_num,
                'appl_date' => $appl_date,
                'appl_time' => $appl_time,
                'cancel_appl_num' => $result_data['cancel_appl_num'],
                'cancel_appl_date' => $result_data['cancel_appl_date'],
                'cancel_appl_time' => $result_data['cancel_appl_time'],
                'cancel_point_price'  => $pointAmount,
                'tid'  => $tid,
                'product_cd' => $product_cd,
                'product_seq' => $product_seq,
                'cp_hist_seq'  => $cp_hist_seq,
                'payment_channel'  => $payment_channel
            ]]);

            // $last_bluepoint_history_index = $this->ci->iparkingCmsDb->lastInsertId();

            // 블루포인트사용 취소 실패시 
            if($result['result'] != '0000'){
                return [$result, "99"]; 
            }else{
                $result_code = "00";
            }

            $point_use = array(
                'appl_num' => $appl_num, 
                'cancel_appl_num' => $result_data['cancel_appl_num'],
                'cancel_appl_date' => $result_data['cancel_appl_date'],
                'cancel_appl_time' => $result_data['cancel_appl_time']
            );

            return [$point_use, $result_code];

        } catch (RequestException $e) {   
        return $response->withJson(['error' => $e->getMessage()]);
        } catch (BadResponseException $e) {
        return $response->withJson(['error' => $e->getMessage()]);
        }
    }

}



