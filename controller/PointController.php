<?php

class PointController {
    protected $ci;
    protected $relay_domain;

    public function __construct($ci) {
        $this->ci = $ci;
        $sv = $this->ci->settings['env'];
        $relay_domain = $this->ci->settings['domain'][$sv];
        $this->relay_domain = $relay_domain;

        $env = 'off';
        if ($_SERVER['SERVER_ADDR'] == '52.78.194.15' || $_SERVER['SERVER_ADDR'] == '52.79.119.107'
	  		|| $_SERVER['SERVER_ADDR'] == '172.31.29.201' || $_SERVER['SERVER_ADDR'] == '172.31.20.130' ) { 
            $env = 'on';            
        }
        $this->env =$env;
    }
    
    // 포인트 예약
    public function postPointReservation($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $mem_seq = $params['mem_seq'];
            $point_card_code = $params['point_card_code'];
            $billing_key = $params['billing_key'];
            $billing_password = $params['billing_password'];
            $amount = $params['amount'];
            $pointAmount = $params['pointAmount'];
            $park_operate_ct = $params['park_operate_ct'];
  
            $last_seq = $this->ci->point->pointReservation($mem_seq, $point_card_code, $billing_key, $billing_password, $amount, $pointAmount, $park_operate_ct);
            
            $msg = $this->ci->message->apiMessage['success'];
            $msg['seq'] = $last_seq;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////////////// L 포인트 //////////////////////////////////////////////////

    // L포인트 조회
    public function getLPointInfo($request, $response, $args)
    {
        try {
            // HTTP 헤더 X-Openpoint 필드 추가
            // X-Openpoint=burC=O750|aesYn=Y
            // AES (CBC mode, PKCS5Padding) Encryption
            // iv value : l-members-lpoint
            // Base64 Encoding
            // 암/복호화 Key : 첨부 문서 참조
            // 상세 내용 연동가이드 문서 참조

            $params = $this->ci->util->getParams($request);

            $billing_key = $params['card_no'];
            $billing_password = $params['pswd'];
            $memb_seq = $params['memb_seq'];
            $accumulate = (int) $params['accumulate'] ?? 0;

            // 조회, 사용, 취소 에 대한 history 를 남기자. 관리를 하자.
            $전문번호 = 'O100';
            $기관코드 = 'O750';

            $flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);

            // 우리 DB에 등록되어있는지 여부 확인. 해당 부분은 파킹패쓰때 사용할수도 있음.
            // $member_point_card_yn = $this->ci->point->memberPointCardCheck($memb_seq, 'LTP');

            $point_info = $this->ci->point->LPointCardInfo($flwNo, $billing_key, $memb_seq);

            // 카드 조회가 되지 않을 때 바로 리턴.
            if($point_info['control']['rspC'] != "00") {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['point_info'] = $point_info;
                // $msg['member_point_card_yn'] = $member_point_card_yn;
                return $response->withJson($msg);
            } else {

                // 비밀번호 인증 조회, 포인트 적립일 경우에는 카드조회만 하면 됨.
                if($accumulate == 0) {
                    $전문번호 = 'O720';
                    $flwNo = $this->ci->point->LPointCreateFlwNo($전문번호, $기관코드);
                    $user_check = $this->ci->point->LPointAuthCheck($flwNo, $billing_key, $billing_password, $memb_seq);
                } 
                
                $msg = $this->ci->message->apiMessage['success'];
                $msg['user_check'] = $user_check;
                $msg['point_info'] = $point_info;
                // $msg['member_point_card_yn'] = $member_point_card_yn;
                return $response->withJson($msg);
            }
                        
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // L포인트 사용
    public function postLPointUse($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            // 조회, 사용, 취소 에 대한 history 를 남기자. 관리를 하자.
 
            $applDate = date('Y-m-d');
            $applTime = date('h:i:s');

            $billing_key = $params['card_no'];
            $billing_password = $params['pswd'];
            $park_operate_ct = $params['park_operate_ct'];
            $amount = $params['amount'];
            $pointAmount = $params['pointAmount'];    
            $ppsl_seq = $params['ppsl_seq'];
            $memb_seq = $params['memb_seq'];
            $payment_channel = $params['payment_channel'];

            $operating_cmpy_cd = $params['operating_cmpy_cd']; 
            $park_seq = $params['park_seq'];
            $prdt_product_cd = $params['prdt_product_cd'];
            $ppsl_seq = $params['ppsl_seq'];
            $product_cd = $params['product_cd'];
            $bcar_seq = $params['bcar_seq'];
            $bcar_number = $params['bcar_number'];
            $prdt_seq = $params['prdt_seq'];
            $product_cd = $params['product_cd'];
            $version = $params['version'] ?? 'v1.0';
                   
            $apply_orderno = str_pad($operating_cmpy_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($park_seq, 5, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($prdt_product_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= '1';
            $apply_orderno .= str_pad($ppsl_seq, 8, "0", STR_PAD_LEFT);
            
            // 모든 사용 시에는 카드번호와 패스워드를 받는다.
            if($park_operate_ct == null) throw new exception("민영/공영 구분이 되지 않았습니다.");

            list($point_info, $result_code) = $this->ci->point->LPointUse($version, $billing_key, $billing_password, $park_operate_ct, $amount, $pointAmount, $ppsl_seq, $memb_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);

            if($result_code == "00") {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['point_info'] = $point_info;
            } else {
                $msg = $this->ci->message->apiMessage['fail'];
                $msg['point_info'] = $point_info;
            }

            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }


    // L포인트 사용취소
    public function postLPointCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            // 조회, 사용, 취소 에 대한 history 를 남기자. 관리를 하자.
            $운영사승인번호 = $params['applCopMcno'];
            $운영사원거래일자 = $params['applDate'];
            $memb_seq = $params['memb_seq'];
            
            list($point_info, $result_code) = $this->ci->point->LPointCancel($운영사승인번호, $운영사원거래일자, $memb_seq);

            if($result_code == "00") {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['point_info'] = $point_info;
            } else {
                $msg = $this->ci->message->apiMessage['fail'];
                $msg['point_info'] = $point_info;
            }

            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // L.point 포인트 사용 망취소
    public function postLPointUseReverseCancel($request, $response, $args)
    {
        try {
            
            $params = $this->ci->util->getParams($request);

            $flwNo = $params['flwNO'];
            $billing_key = $params['billing_key'];
            $billing_password = $params['billing_password'];
            $copMcno = $params['copMcno'];
            $park_operate_ct = $params['park_operate_ct'];
            $거래일자 = $params['applDate'];
            $거래시간 = $params['applTime'];
            $ccoAprno = $params['ccoAprno'];
            $amount = $params['amount'];
            $pointAmount = $params['pointAmount'];
            $memb_seq = $params['memb_seq'];

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

            $openssl_encrypt_data = openssl_encrypt($data, 'AES-128-CBC', $this->ci->point->fixKey($key), OPENSSL_RAW_DATA, 'l-members-lpoint');
            
            $base64_encode_data = base64_encode($openssl_encrypt_data);

            $requestBody = [
                'headers' => [
                    'X-Openpoint' => 'burC=O750|aesYn=Y'
                ],
                'body' => $base64_encode_data,
                'timeout' => 60,
                'connect_timeout' => 60
            ];

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

            if($result_code == "00") {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                return print_r($point_use_reverse_cancel);
            }

            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // L포인트 적립
    public function postLPointAccumulate($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            // 조회, 사용, 취소 에 대한 history 를 남기자. 관리를 하자.
            
            $운영사승인번호 = $params['applCopMcno'];
            $운영사원거래일자 = $params['applDate'];
            $id = $params['id'];
            $name = $params['name'];
            $park_operate_ct = $params['park_operate_ct'];
            $memb_seq = $params['memb_seq'];
      
            $point_info = $this->ci->point->LPointAccumulate($park_operate_ct, $운영사승인번호, $운영사원거래일자, $memb_seq, $id, $name);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // L포인트 적립취소
    public function postLPointAccumulateCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            // 조회, 사용, 취소 에 대한 history 를 남기자. 관리를 하자.
           
            $운영사승인번호 = $params['applCopMcno'];
            $운영사원거래일자 = $params['applDate'];
            $id = $params['id'];
            $name = $params['name'];
            $memb_seq = $params['memb_seq'];
            
            $point_info = $this->ci->point->LPointAccumulateCancel($운영사승인번호, $운영사원거래일자, $memb_seq, $id, $name);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // L포인트 적립 망취소
    public function postLPointAccmulateReverseCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            
            $flwNo = $params['flwNo'];
            $card_no = $params['card_no'];
            $copMcno = $params['copMcno'];
            $park_operate_ct = $params['park_operate_ct'];
            $거래일자 = $params['applDate'];
            $거래시간 = $params['applTime'];
            $ccoAprno = $params['ccoAprno'];
            $결제금액 = $params['amount'];
            $pointAmount = $params['pointAmount'];
            $id = $params['id'];
            $name = $params['name'];
            $memb_seq = $params['memb_seq'];

            $point_info = $this->ci->point->LPointAccumulateReverseCancel($flwNo, $card_no, $copMcno, $park_operate_ct, $거래일자, $거래시간, $ccoAprno, $결제금액, $pointAmount, $memb_seq, $id, $name);
            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);


        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////////////// L 포인트 끝 //////////////////////////////////////////////////

    ////////////////////////////////////////////////// GS포인트 ////////////////////////////////////////////////////

    // GS포인트 조회
    public function getGsPointInfo($request, $response, $args)
    {
        try {

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

            $params = $this->ci->util->getParams($request);

            $card_no = $params['card_no'];
            $pwd = $params['pwd'];

            // 우리 DB에 등록되어있는지 여부 확인.
            $member_point_card_yn = $this->ci->point->memberPointCardCheck($memb_seq, 'GSP');

            $point_info = $this->ci->point->gsPointInfo($card_no, $pwd);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            $msg['member_point_card_yn'] = $member_point_card_yn;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }

    }

    
    // GS포인트 사용
    public function postGsPointUse($request, $response, $args)
    {
        try {
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

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $card_no = $params['card_no'];
            $pwd = $params['pwd'];
            $pointAmount = $params['pointAmount'];
            $amount = $params['amount'];
            $park_operate_ct = $params['park_operate_ct'];

            $operating_cmpy_cd = $params['operating_cmpy_cd']; 
            $park_seq = $params['park_seq'];
            $prdt_product_cd = $params['prdt_product_cd'];
            $ppsl_seq = $params['ppsl_seq'];
            $version = $params['version'] ?? 'v1.0';

            $product_cd = $params['product_cd'];
            $bcar_seq = $params['bcar_seq'];
            $bcar_number = $params['bcar_number'];
            $park_seq = $params['park_seq'];
            $prdt_seq = $params['prdt_seq'];

            $apply_orderno = str_pad($operating_cmpy_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($park_seq, 5, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($prdt_product_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= '1';
            $apply_orderno .= str_pad($ppsl_seq, 8, "0", STR_PAD_LEFT);

            list($point_info, $result_code) = $this->ci->point->gsPointUse($version, $card_no, $park_operate_ct, $amount, $pointAmount, $memb_seq, $ppsl_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);

            if($result_code == "00") {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['point_info'] = $point_info;
            } else {
                $msg = $this->ci->message->apiMessage['fail'];
                $msg['point_info'] = $point_info;
            }

            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
    

    // GS포인트 사용취소
    public function postGsPointCancel($request, $response, $args)
    {
        try {
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

            $params = $this->ci->util->getParams($request);

            $approv_date = $params['approv_date']; 
            $approv_no = $params['approv_no'];
            $memb_seq = $params['memb_seq'];

            $point_info = $this->ci->point->gsPointCancel($approv_date, $approv_no, $memb_seq);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // GS포인트 사용 망취소
    public function postGsPointReserveCancel($request, $response, $args)
    {
        try {
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

            $params = $this->ci->util->getParams($request);

            $approv_date = $params['approv_date'];  // 통합 승인 일자
            $approv_no = $params['approv_no']; // 통합 승인 번호
            $memb_seq = $params['memb_seq'];
            $card_no = $params['card_no']; 
            $trans_date = $params['trans_date']; 
            $trans_time = $params['trans_time']; 
            $chasing_no = $params['chasing_no']; 
            $amount = $params['amount']; 
            $pointAmount = $params['pointAmount']; 

            $point_info = $this->ci->point->gsPointReserveCancel($card_no, $trans_date, $trans_time, $chasing_no, $approv_date, $approv_no, $amount, $pointAmount, $memb_seq);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // GS포인트 적립
    public function postGsPointAccumulate($request, $response, $args)
    {
        try {

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

            $params = $this->ci->util->getParams($request);
            $approv_date = $params['approv_date'];
            $approv_no = $params['approv_no'];
            $memb_seq = $params['memb_seq'];
            $park_operate_ct = $params['park_operate_ct'];

            if($park_operate_ct != 1) throw new Exception ("직영/제휴에서만 적립이 가능합니다");

            $point_info = $this->ci->point->gsPointAccumulateUse($approv_date, $approv_no, $memb_seq, $park_operate_ct);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // GS포인트 적립 취소
    public function postGsPointAccumulateCancel($request, $response, $args)
    {
        try {

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

            $params = $this->ci->util->getParams($request);
            $approv_date = $params['approv_date'];
            $approv_no = $params['approv_no'];
            $memb_seq = $params['memb_seq'];

            $point_info = $this->ci->point->gsPointAccumulateCancel($approv_date, $approv_no, $memb_seq);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['point_info'] = $point_info;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////////////// GS포인트 끝 //////////////////////////////////////////////////


    ////////////////////////////////////////////////// 블루포인트 ////////////////////////////////////////////////////

    // 블루포인트 조회
    public function getBluePointInfo($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];
            $card_num = $params['card_num'];
            $park_seq = $params['park_seq'];

            if($card_num != null) {
                $card_num = $this->ci->util->aes_256_encrypted($card_num);
            }

            $card_passwd = $params['card_passwd'];
            $version = $params['version'] ?? 'v1.0';

            if(is_numeric($card_passwd)) {
                $card_passwd = (int) $card_passwd;
                $card_passwd = $this->ci->util->aes_256_encrypted($card_passwd);
            } 

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'card_num' => $card_num,
                'card_passwd' => $card_passwd,
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

                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/search/bluepoint';

                $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
 
                $http_result = $this->ci->http->post(
                    $url,
                    $requestBody
                );
        
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);

                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                $msg = $this->ci->message->apiMessage['success'];
                if($result['result'] != '0000') {
                    $msg = $this->ci->message->apiMessage['fail'];
                    $msg['message'] = $result['resultMessage'];
                    $msg['data'] = array(
                        'resultCode' => $result['result'],
                        'resultMessage' => $result['resultMessage']
                    );
                } else {
                    $msg['data'] = $result['resultData'];
                }

                
                return $response->withJson($msg);

            } catch (RequestException $e) {   
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 블루포인트 인증
    public function postBluePointAuth($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];
            $card_num = $params['card_num'];
            $card_passwd = $params['card_passwd'];
            $version = $params['version'] ?? 'v1.0';

            if($card_num != null) {
                $card_num = $this->ci->util->aes_256_encrypted($card_num);
            }
            
            $card_passwd = $this->ci->util->aes_256_encrypted($card_passwd);

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'card_number' => $card_num,
                'card_passwd' => $card_passwd
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

                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/auth/bluepoint';

                $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
 
                $http_result = $this->ci->http->post(
                    $url,
                    $requestBody
                );
        
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);

                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                $msg = $this->ci->message->apiMessage['success'];
                
                if($result['result'] != '0000') {
                    $msg = $this->ci->message->apiMessage['fail'];
                    $msg['message'] = $result['resultMessage'];
                    $msg['data'] = array(
                        'resultCode' => $result['result'],
                        'resultMessage' => $result['resultMessage']
                    );
                } else {
                    $msg['data'] = $result['resultData'];
                }

                return $response->withJson($msg);

            } catch (RequestException $e) {   
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 블루포인트 사용
    public function postBluePointPayment($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];
            $use_point = $params['use_point'];
            $version = $params['version'] ?? 'v1.0';
            $park_seq = $params['park_seq'];

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'use_point' => $use_point,
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
                
                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                if($result['result'] != '0000') throw new Exception($result['resultMessage']);

                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $result['resultData'];
                return $response->withJson($msg);

            } catch (RequestException $e) {   
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 블루포인트 사용취소
    public function postBluePointPaymentCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];
            $cancel_point = $params['cancel_point'];
            $appl_num = $params['appl_num'];
            $appl_date = $params['appl_date'];
            $appl_time = $params['appl_time'];
            $tid  = $params['tid'];
            $version = $params['version'] ?? 'v1.0';
            $park_seq = $params['park_seq'];

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'cancel_point' => $cancel_point,
                'appl_num' => $appl_num,
                'appl_date' => $appl_date,
                'appl_time' => $appl_time,
                'tid' => $tid,
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

                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/cancel/bluepoint';

                $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
 
                $http_result = $this->ci->http->post(
                    $url,
                    $requestBody
                );
        
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);

                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                if($result['result'] != '0000') throw new Exception($result['resultMessage']);

                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $result['resultData'];
                return $response->withJson($msg);

            } catch (RequestException $e) {   
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////////////// 블루포인트 끝 //////////////////////////////////////////////////


    // 포인트 일일한도/일회한도 체크
    public function getPointLimitCheck ($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request); 
            
            $point_card_code = $params['point_card_code'];      // 포인트 코드
            $memb_seq = $params['memb_seq'];                    // 회원번호
            $pointAmount = $params['pointAmount'];              // 포인트
            $amount = $params['amount'];                        // 상품가격
            $park_operate_ct = $params['park_operate_ct'];      // 1:민영 2:공영
            $now = date('Y-m-d');

            if($pointAmount != 0){
                if($pointAmount < 100) throw new ErrorException ("최소 결제 포인트는 100P 입니다");
            }

            list($max_avail_point, $code) = $this->ci->point->oneTimeLimit($point_card_code, $pointAmount, $amount, $now, $park_operate_ct); // 일회한도비교 

            if($code == "00"){
    
                list($over_point, $code) = $this->ci->point->oneDayLimit($point_card_code, $memb_seq, $pointAmount, $now, $max_avail_point); // 일일간 사용한 누적 포인트
                
                if($code == "99")throw new ErrorException ("포인트 사용이 일일 한도를 초과했습니다. 초과 포인트는 ".$over_point."P 입니다.");
                
            }else if($code == "99"){
                throw new ErrorException ("포인트 1회 한도를 초과했습니다. 최대 사용 가능 포인트는 ".number_format($max_avail_point,0)."P 입니다.");
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);
            

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

}