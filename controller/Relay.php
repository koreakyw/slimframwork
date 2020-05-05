<?php
class Relay
{
    protected $ci;
    protected $relay_domain;

    public function __construct($ci) {
        $this->ci = $ci;
        $sv = $this->ci->settings['env'];
        $relay_domain = $this->ci->settings['domain'][$sv];
        $this->relay_domain = $relay_domain;
    }

    ////////////////////////////////////////// 상품 ////////////////////////////////////////////////////////
    public function productSalesInfo($version, $memb_seq, $prdt_seq, $vehicle_cd, $park_seq, $prdt_efct_strt_dt, $prdt_efct_end_dt)
    {
        try {

            $body_param = [
                'memb_seq' => $memb_seq,
                'prdt_seq' => $prdt_seq,
                'vehicle_cd' => $vehicle_cd,
                'park_seq' => $park_seq,
                'prdt_efct_strt_dt' => $prdt_efct_strt_dt,
                'prdt_efct_end_dt' => $prdt_efct_end_dt
            ];
    
            $body = json_encode($body_param);
    
            // print_r($body);
    
            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            $requestBody = [
                'headers' => [
                    'Content-Type' => 'application/json',// 'application/x-www-form-urlencoded', 
                    'cipherApplyYN' => 'Y'
                ],
                'body' => json_encode(array(
                    'data' => $base64encode_data
                )),
                'timeout' => 360,
                'connect_timeout' => 360
            ];

            $url = $this->relay_domain.'/api/product/'.$version.'/sales/info';

           $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'GET', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->get(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

           $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] != '0000') throw new Exception($result['resultMessage']);
            
            return $result['resultData'];

        } catch (RequestException $e) {   
            return $response->withJson(['error' => $e->getMessage()]);
        } catch (BadResponseException $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 상품구매내역
    public function productSales($version, $product_cd, $product_seq)
    {
        try {

            $body_param = [
                'product_cd' => (int) $product_cd,
                'product_seq' => (int) $product_seq
            ];
    
            $body = json_encode($body_param);
    
            // print_r($body);
    
            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            $requestBody = [
                'headers' => [
                    'Content-Type' => 'application/json',// 'application/x-www-form-urlencoded', 
                    'cipherApplyYN' => 'Y'
                ],
                'body' => json_encode(array(
                    'data' => $base64encode_data
                )),
                'timeout' => 360,
                'connect_timeout' => 360
            ];

            $url = $this->relay_domain.'/api/product/'.$version.'/sales';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] != '0000') throw new Exception($result['resultMessage']);
            
            return $result['resultData'];

        } catch (RequestException $e) {   
            return $response->withJson(['error' => $e->getMessage()]);
        } catch (BadResponseException $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
    ////////////////////////////////////////// 상품 끝 //////////////////////////////////////////////////////

      // APP TO APP 결제취소 프로세스
      public function iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version)
    {
        try {

            $body_param = [
                'memb_seq' => $memb_seq,
                'bcar_seq' => $bcar_seq,
                'product_cd' => $product_cd,
                'product_seq' => $product_seq,
                'nspo_seq' => $nspo_seq, // 카드 seq
                'pay_price' => $pay_price,  // PG 결제금액
                'park_seq' => $park_seq // 주차장 seq
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
            
            $url = $this->relay_domain.'/api/payment/'.$version.'/credit/card/cancel/iparkingpay';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] == '0000') {
                $resultData = $result['resultData'];
                return [$resultData, '00'];
            } else { 
                $resultData = $result['resultData'];
                return [$result['resultMessage'], '99'];
            }
            
        } catch (RequestException $e) {   
            return [$e->getMessage(), '99'];
        } catch (BadResponseException $e) {
            return [$e->getMessage(), '99'];
        }
    }

    // iparking 완납 처리 프로세스
    public function payConfirm($pay_status, $product_cd, $product_seq, $tot_price, $pg_price, $cp_price, $version, $pointAmount)
    {
        try {

            $body_param = [
                'pay_status' => $pay_status,
                'product_cd' => $product_cd,
                'product_seq' => $product_seq,
                'tot_price' => $tot_price,
                'pg_price' => $pg_price,
                'cp_price' => $cp_price,
                'point_price' => $pointAmount
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
            
            $url = $this->relay_domain.'/api/payment/'.$version.'/pay/confirm';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            // if($result['result'] != '0000') throw new Exception($result['resultMessage']);
            if($result['result'] != '0000'){
                return [$result['resultMessage'] ,'99'];
            }

            $resultData = $result['resultData'];
            return [$resultData, '00'];
            
        } catch (RequestException $e) {   
            return [$e->getMessage(), '99'];
        } catch (BadResponseException $e) {
            return [$e->getMessage(), '99'];
        }

    }

    // APP To APP 결제완료 프로세스
    public function AppToAppPayment($pay_type, $bcar_seq, $product_cd, $product_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $payco_order_no, $version='v1.0')
    {
        try {

            $body_param = [
                'pay_type' => $pay_type,
                'bcar_seq' => (int)$bcar_seq,
                'product_cd' => (int)$product_cd,
                'product_seq' => (int)$product_seq,
                'result_code' => $result_code,
                'result_msg' => $result_msg,
                'card_cd' => $card_cd,
                'pay_price' => (int)$pay_price,
                'appl_date' => $appl_date,
                'appl_time' => $appl_time,
                'appl_num' => $appl_num,
                'order_no' => $order_no,
                'tid' => $tid,
                'memb_seq' => (int)$memb_seq,
                'bcar_number' => $bcar_number,
                'park_seq' => (int)$park_seq,
                'prdt_seq' => (int)$prdt_seq,
                'payco_order_no' => $payco_order_no
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
            
            $url = $this->relay_domain.'/api/payment/'.$version.'/pay/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);
            
            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] == '0000') {
                $result_code = '00';
            } else {
                $result_code = '99';
            }

            $resultData = $result['resultData'];
            return [$resultData, '00'];
            
        } catch (RequestException $e) {   
            return [$e->getMessage(), '99'];
        } catch (BadResponseException $e) {
            return [$e->getMessage(), '99'];
        }
    }

    // APP TO APP 결제취소 프로세스
    public function AppToAppPaymentCancel(
        $pay_type, $bcar_seq, $product_cd, $product_seq, $result_code, $result_msg, $card_cd,
        $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version='v1.0')
    {
        try {

            $body_param = [
                'pay_type' => $pay_type,
                'bcar_seq' => (int)$bcar_seq,
                'product_cd' => (int)$product_cd,
                'product_seq' => (int)$product_seq,
                'result_code' => $result_code,
                'result_msg' => $result_msg,
                'card_cd' => $card_cd,
                'pay_price' => (int)$pay_price,
                'appl_date' => $appl_date,
                'appl_time' => $appl_time,
                'appl_num' => $appl_num,
                'order_no' => $order_no,
                'tid' => $tid,
                'memb_seq' => (int)$memb_seq,
                'bcar_number' => $bcar_number,
                'park_seq' => (int)$park_seq,
                'prdt_seq' => (int)$prdt_seq
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
            
            $url = $this->relay_domain.'/api/payment/'.$version.'/pay/cancel/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] != '0000') throw new Exception($result['resultMessage']);

            $resultData = $result['resultData'];
            return [$resultData, '00'];
            
        } catch (RequestException $e) {   
            return [$e->getMessage(), '99'];
        } catch (BadResponseException $e) {
            return [$e->getMessage(), '99'];
        }
    }

    // 포인트 결제 연동 B2B API
    public function pointCardReflect(
        $version, $point_card_code, $product_cd, $product_seq, $result_code, $result_msg, 
        $use_point, $appl_date, $appl_time, $appl_num, $tid,
        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $pg_site_cd, $save_point
    )
    {
        if ($pg_site_cd == null){
            $pg_site_cd = "";
        }
        if ($save_point == null || $save_point == ""){
            $save_point = 0;
        }

        // 성공시만 호출하기 때문에 코드통일
        $result_code = "0000";

        $billing_key = $this->ci->util->aes_256_encrypted($card_no);
        $body_param = [
            'save_point' => $save_point,
            'point_cd' => $point_card_code,
            'product_cd' => $product_cd,
            'product_seq' => $product_seq,
            'result_code' => $result_code,
            'result_msg' => $result_msg,
            'use_point' => $use_point,
            'appl_date' => $appl_date,
            'appl_time' => $appl_time,
            'appl_num' => $appl_num,
            'tid' => $tid,
            'memb_seq' => $memb_seq,
            'bcar_seq' => $bcar_seq,
            'bcar_number' => $bcar_number,
            'park_seq' => $park_seq,
            'prdt_seq' => $prdt_seq,
            'billing_key' => $billing_key,
            'pg_site_cd' => $pg_site_cd
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

            $url = $this->relay_domain.'/api/payment/'.$version.'/point/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            $result_code = $result['result'];
            if($result_code == '0000') {
                $result_code = '00';
            } else {
                $result_code = '99';
            }
            return [$result, $result_code];

        } catch (RequestException $e) {   
            return [(object)[], '99'];
        } catch (BadResponseException $e) {
            return [(object)[], '99'];
        }
    }  
    
    // 포인트 결제 취소 연동 B2B API 
    public function pointCardCancelReflect(
        $version, $point_card_code, $product_cd, $product_seq, $result_code, $result_msg, 
        $use_point, $appl_date, $appl_time, $appl_num, $tid,
        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point
    )
    {
        if($save_point == null || $save_point == ""){
            $save_point = 0;
        }

        $billing_key = $this->ci->util->aes_256_encrypted($card_no);
        $body_param = [
            'point_cd' => $point_card_code,
            'product_cd' => $product_cd,
            'product_seq' => $product_seq,
            'result_code' => $result_code,
            'result_msg' => $result_msg,
            'use_point' => $use_point,
            'appl_date' => $appl_date,
            'appl_time' => $appl_time,
            'appl_num' => $appl_num,
            'tid' => $tid,
            'memb_seq' => $memb_seq,
            'bcar_seq' => $bcar_seq,
            'bcar_number' => $bcar_number,
            'park_seq' => $park_seq,
            'prdt_seq' => $prdt_seq,
            'billing_key' => $billing_key,
            'save_point' => $save_point
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

            $url = $this->relay_domain.'/api/payment/'.$version.'/point/cancel/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            $result_code = $result['result'];
            if($result_code == '0000') {
                $result_code = '00';
            } else {
                $result_code = '99';
            }
            return [$result, $result_code];

        } catch (RequestException $e) {   
            return [(object)[], '99'];
        } catch (BadResponseException $e) {
            return [(object)[], '99'];
        }
    }

    // 관리자 포인트 결제 취소 연동 B2B API 
    public function pointCardAdminCancelReflect(
        $version, $point_card_code, $product_cd, $ppsl_seq, $memb_seq, $use_point, $appl_date, $appl_time, $appl_num, $save_point
    )
    {
        $body_param = [
            'point_cd' => $point_card_code,
            'product_cd' => $product_cd,
            'product_seq' => $ppsl_seq,
            'use_point' => $use_point,
            'appl_num' => $appl_num,
            'appl_date' => $appl_date,
            'appl_time' => $appl_time,
            'memb_seq' => $memb_seq,
            'save_point' => $save_point
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

            $url = $this->relay_domain.'/api/payment/'.$version.'/point/admin/cancel/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            $result_code = $result['result'];
            if($result_code == '0000') {
                $result_code = '00';
            } else {
                $result_code = '99';
            }
            return [$result, $result_code];

        } catch (RequestException $e) {   
            return [(object)[], '99'];
        } catch (BadResponseException $e) {
            return [(object)[], '99'];
        }
    }

    // 쿠폰 결제 연동 B2B API
    public function couponReflect(
        $product_cd, $product_seq, $result_code, $result_msg, $cp_price, $cp_use_date, $cp_use_time, 
        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version
    )
    {
        $body_param = [
            'product_cd' => $product_cd,
            'product_seq' => $product_seq,
            'result_code' => $result_code,
            'result_msg' => $result_msg,
            'pay_price' => $cp_price,
            'appl_date' => str_replace("-","",$cp_use_date),
            'appl_time' => str_replace(":","",$cp_use_time),
            'memb_seq' => $memb_seq,
            'bcar_seq' => $bcar_seq,
            'bcar_number' => $bcar_number,
            'park_seq' => $park_seq,
            'prdt_seq' => $prdt_seq,
            'cp_hist_seq' => $cp_hist_seq
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

            $url = $this->relay_domain.'/api/payment/'.$version.'/coupon/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            // if($result['result'] != '0000') throw new Exception($result['resultMessage']);
            if($result['result'] != '0000'){
                return "Fail";
            }

            $resultData = $result['resultData'];
            return [$resultData, '00'];
           
        } catch (RequestException $e) {   
            return "Fail";
        } catch (BadResponseException $e) {
            return "Fail";
        }
        // } catch (RequestException $e) {   
        //     return [$e->getMessage(), '99'];
        // } catch (BadResponseException $e) {
        //     return [$e->getMessage(), '99'];
        // }
    }  

    // 쿠폰 결제 취소 연동 B2B API 
    public function couponCancelReflect(
        $product_cd, $product_seq, $result_code, $result_msg, $pay_price, $appl_date, $appl_time, 
        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version='v1.0'
    )
    {
        $body_param = [
            'product_cd' => $product_cd,
            'product_seq' => $product_seq,
            'result_code' => $result_code,
            'result_msg' => $result_msg,
            'pay_price' => $pay_price,
            'appl_date' => $appl_date,
            'appl_time' => $appl_time,
            'memb_seq' => $memb_seq,
            'bcar_seq' => $bcar_seq,
            'bcar_number' => $bcar_number,
            'park_seq' => $park_seq,
            'prdt_seq' => $prdt_seq,
            'cp_hist_seq' => $cp_hist_seq
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

            $url = $this->relay_domain.'/api/payment/'.$version.'/coupon/cancel/reflect';

            $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
            
            $http_result = $this->ci->http->post(
                $url,
                $requestBody
            );
            
            $result = $http_result->getBody()->getContents();
    
            $result  = json_decode($result, true);

            $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

            if($result['result'] != '0000') throw new Exception($result['resultMessage']);

            $resultData = $result['resultData'];
            return [$resultData, '00'];
            
        } catch (RequestException $e) {   
            return [$e->getMessage(), '99'];
        } catch (BadResponseException $e) {
            return [$e->getMessage(), '99'];
        }
    }
}