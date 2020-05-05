<?php
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Guzzle\Http\Exception\ClientErrorResponseException;
class RelayController {
    protected $ci;
    protected $relay_domain;

    public function __construct($ci) {
        $this->ci = $ci;
        $sv = $this->ci->settings['env'];
        $relay_domain = $this->ci->settings['domain'][$sv];
        $this->relay_domain = $relay_domain;
    }

    function convert(&$value, $key){
        $value = iconv('EUC-KR', 'UTF-8', $value);
    }

    ////////////////////////////////////////// 암호화 & 복호화 /////////////////////////////////////////////
    public function getPointCardDecrypt($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $text = $params['text'];

            $trans = array(
                '%40' => '@',
                '%3A' => ':',
                '%24' => '$',
                '%2C' => ',',
                '%3B' => ';',
                '%2B' => '+',
                '%3D' => '=',
                '%3F' => '?',
                '%2F' => '/'
            );
            
            $text = strtr($text, $trans);

            $data = $this->ci->util->aes_256_decrypted($text);   

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function postEncryptData($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);


            $strParams = json_encode($params);
            
            // Before Version
            // $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($strParams);
            $strParams = "'".$strParams."'";
            
            // After Version 2018.10.09
            putenv('LC_ALL=de_DE.UTF-8');
            exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/CeedAesCrypt/crypto.jar E '.$strParams, $output);
            
            $base64encode_data = "";
            foreach($output as $output_rows) {
                $base64encode_data .= $output_rows;
            }

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $base64encode_data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    } 

    public function getDescryptData($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $text = $params['text'];

            $trans = array(
                '%40' => '@',
                '%3A' => ':',
                '%24' => '$',
                '%2C' => ',',
                '%3B' => ';',
                '%2B' => '+',
                '%3D' => '=',
                '%3F' => '?',
                '%2F' => '/'
            );
            
            $text = strtr($text, $trans);

            // Before Version
            // $data = $this->ci->util->iparkingSecurity('decrypt', $text);

            // After Version 2018.10.09
            putenv('LC_ALL=de_DE.UTF-8');
            exec('/usr/bin/java -jar /home/work/cms-server/peristalsis/CeedAesCrypt/crypto.jar D '.$text, $output);
            
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
            $this->ci->log->decryptHistory($params, $data);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////// 암호화 & 복호화 끝 ///////////////////////////////////////////

    ////////////////////////////////////////// 상품 //////////////////////////////////////////////////////

    // 상품 정보 연동
    public function getProductSalesInfo($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = (int) $params['memb_seq'];
            $prdt_seq = (int) $params['prdt_seq'];
            $vehicle_cd = $params['vehicle_cd'];
            $park_seq = (int) $params['park_seq'];
            $prdt_efct_strt_dt = date('Y-m-d', strtotime($params['prdt_efct_strt_dt']));
            $prdt_efct_end_dt = date('Y-m-d', strtotime($params['prdt_efct_end_dt'])); 
            $version = $params['version'] ?? 'v1.0';

            $result = $this->ci->relay->productSalesInfo($version, $memb_seq, $prdt_seq, $vehicle_cd, $park_seq, $prdt_efct_strt_dt, $prdt_efct_end_dt);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $result;
            return $response->withJson($msg);
           
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 상품 구매 내역 리스트
    public function getProductSalesHistory($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = (int) $params['memb_seq'];
            $version = $params['version'] ?? 'v1.0';
            $ppsl_seq = (int) $params['ppsl_seq'];

            $body_param = [
                'memb_seq' => $memb_seq
            ];

            if($ppsl_seq != null) {
                $body_param['ppsl_seq'] = $ppsl_seq;
            }

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

                $url = $this->relay_domain.'/api/product/'.$version.'/sales/history';

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

    // 상품 구매 내역
    public function postProductSales($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $product_cd = $params['product_cd'];
            $product_seq = $params['product_seq'];
            $version = $params['version'];

            $result = $this->ci->relay->productSales($version, $product_cd, $product_seq);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $result;
            return $response->withJson($msg);
           
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 상품 구매 가격 계산
    public function getProductSalesCalc($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $version = $params['version'] ?? 'v1.0';
            $prdt_seq = (int) $params['prdt_seq'];
            $vehicle_cd = $params['vehicle_cd'];
            $start_date = $params['prdt_efct_strt_dt'];
            $end_date = $params['prdt_efct_end_dt'];
            $redu_seq = (int) $params['redu_seq'] ?? null;
            $cp_hist_seq = (int) $params['cp_hist_seq'] ?? null;
            $point_use = (int) $params['point_use'] ?? null;

            $body_param = [
                'prdt_seq' => $prdt_seq,
                'vehicle_cd' => $vehicle_cd,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'redu_seq' => $redu_seq,
                'cp_hist_seq' => $cp_hist_seq,
                'point_use' => $point_use
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

                $url = $this->relay_domain.'/api/product/'.$version.'/sales/calc';

               $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'GET', $body_param, $base64encode_data);
                
                $http_result = $this->ci->http->get(
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

    // 상품 배정
    public function postProductSalesAssign($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = (int) $params['memb_seq'];
            $bcar_seq = (int) $params['bcar_seq'];
            $prdt_seq = (int) $params['prdt_seq'];
            $vehicle_cd = $params['vehicle_cd'] ?? null;
            $prdt_efct_strt_dt = $params['prdt_efct_strt_dt'] ?? null;
            $prdt_efct_end_dt = $params['prdt_efct_end_dt'] ?? null;
            $park_seq = (int) $params['park_seq'] ?? null;
            $ppsl_price = (int) $params['ppsl_price'] ?? null;
            $ppsl_cut_price = (int) $params['ppsl_cut_price'] ?? 0;
            $ppsl_price_sale = (int) $params['ppsl_price_sale'] ?? 0;
            $cp_price = (int) $params['cp_price'] ?? 0;
            $point_price = (int) $params['point_price'] ?? 0;
            $redc_seq = (int) $params['redc_seq'] ?? null;
            $redc_price = (int) $params['redc_price'] ?? null;
            $version = $params['version'] ?? 'v1.0';
            $prdt_price = (int) $params['prdt_price'] ?? 0;

            $body_param = [
                'memb_seq' => $memb_seq,
                'bcar_seq' => $bcar_seq,
                'prdt_seq' => $prdt_seq,
                'vehicle_cd' => $vehicle_cd,
                'prdt_efct_strt_dt' => $prdt_efct_strt_dt,
                'prdt_efct_end_dt' => $prdt_efct_end_dt,
                'park_seq' => $park_seq,
                'ppsl_price' => $ppsl_price,
                'ppsl_cut_price' => $ppsl_cut_price,
                'ppsl_price_sale' => $ppsl_price_sale,
                'cp_price' => $cp_price,
                'point_price' => $point_price,
                'prdt_price' => $prdt_price,
                'redc_seq' => $redc_seq,
                'redc_price' => $redc_price
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

                $url = $this->relay_domain.'/api/product/'.$version.'/sales/assign';

               $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
                
                $http_result = $this->ci->http->post(
                    $url,
                    $requestBody
                );
                
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);

                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                if($result['result'] == '0000') {
                    $msg = $this->ci->message->apiMessage['success'];
                } else {
                    $msg = $this->ci->message->apiMessage['fail'];
                    $msg['message'] = $result['resultMessage'];
                    $msg['data_code'] = $result['result'];
                }

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

    ////////////////////////////////////////// 상품 끝 //////////////////////////////////////////////////////

    //////////////////////////////////////////  포인트 ///////////////////////////////////////////////////
    // 포인트 카드 조회
    public function getPointCardList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $version = $params['version'] ?? 'v1.0';

            $body_param = [
                'memb_seq' => $memb_seq
            ];

            $body = json_encode($body_param);

            // $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);
            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);
            // echo ' base64encode_data  :', $base64encode_data;

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
        
                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card';

                $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'GET', $body_param, $base64encode_data);
                
                $http_result = $this->ci->http->get(
                    $url,
                    $requestBody
                );
                
                $result = $http_result->getBody()->getContents();
        
                $result  = json_decode($result, true);

                $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                if($result['result'] != '0000') throw new Exception($result['resultMessage']);

                /*
                * 모바일 하단 디스크립션을 서버에서 관리한다.
                */
                $description = '• 포인트 카드를 등록 해주시면 결제 시마다 카드 조회 필요없이 포인트 사용 가능합니다.'.PHP_EOL;
                $description .= '• 포인트는 ‘아이파킹 존’에서만 사용 가능합니다.'.PHP_EOL;
                $description .= '• 한번 등록 하신 카드는 수정이 불가능하며, 삭제 후 재등록 해주시기 바랍니다.'.PHP_EOL;
                $description .= '• 포인트 카드는 중복 등록이 불가능합니다.';

                $resultData = []; 
                // 롯데포인트는 제외
                foreach($result['resultData'] as $index => $value) {
                    $point_card_code = $value['point_card_code'];
                    if($point_card_code == 'LTP') {
                        unset($resultData[$index]);
                    } else {
                        $resultData[] = $value;
                    }
                }

                $msg = $this->ci->message->apiMessage['success'];
                $msg['data']['point_card_list'] = $resultData;
                $msg['data']['bottom_desc'] = $description;
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

    // 포인트 카드 등록
    public function postPointCardAdd($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];
            $card_number = $params['card_number'] ?? null;
            $card_passwd = $params['card_passwd'] ?? null;
            
            $card_number = $this->ci->util->aes_256_encrypted($card_number);   
            $card_passwd = $this->ci->util->aes_256_encrypted($card_passwd);  

            $version = $params['version'] ?? 'v1.0';

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code,
                'card_number' => $card_number,
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
                
                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/register';

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
                // $msg['data'] = $result['resultData'];
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


    // 포인트 카드 삭제 
    public function putPointCardIsDeleted($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            $point_card_code = $params['point_card_code'];

            $version = $params['version'] ?? 'v1.0';

            $body_param = [
                'memb_seq' => $memb_seq,
                'point_card_code' => $point_card_code
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
                
                $url = $this->relay_domain.'/api/payment/'.$version.'/point/card/delete';

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

            // $params = $this->ci->util->getParams($request);

            // $point_card_code = $params['point_card_code'] ?? null;
            // $card_no = $params['card_no'];
            // $memb_seq = $params['memb_seq'] ?? null;
            // $ip = $_SERVER['REMOTE_ADDR'];
            // $now = date('Y-m-d');

            // $is_mobile = $this->ci->util->isMobile();

            // if($is_mobile) {
            //     $reg_device_code = 3;
            // } else {
            //     $reg_device_code = 2;
            // }

            // $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.member_point_card_info', [
            //     'mod_datetime' => $now,
            //     'mod_sequence' => $memb_seq,
            //     'mod_ip' => $ip,
            //     'mod_device_code' => $reg_device_code,
            //     'is_deleted' => 'Y'
            // ], [
            //     'memb_seq' => $memb_seq,
            //     'point_card_code' => $point_card_code,
            //     'card_no' => $card_no
            // ]);

            // $msg = $this->ci->message->apiMessage['success'];
            // $msg['seq'] = $last_seq;
            // return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //////////////////////////////////////////  포인트 끝 ///////////////////////////////////////////////////

    ////////////////////////////////////////// PG 결제 /////////////////////////////////////////////

    // iparking pay 결제
    public function postIparkingPayPayment($request, $response, $args)
    {
        $iparkingPay_cancel_ny = 0;
        $point_cancel_ny = 0;
        $coupon_cancel_ny = 0;
        $confirm_fail_ny = 0;
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = (int) $params['memb_seq']; // 회원 seq
            $bcar_seq = (int) $params['bcar_seq']; // 차량 seq
            $product_cd = (int) $params['product_cd']; // 상품구분코드 (2:상품 5:시간주차 6:미납 등..)
            $product_seq = (int) $params['product_seq']; // 상품 seq (각 삼풍의 seq정보)
            $nspo_seq = (int) $params['nspo_seq']; // 카드 seq
            $pay_price = (int) $params['pay_price']; // PG 결제금액
            $park_seq = (int) $params['park_seq']; // 주차장 seq
            $tot_price = (int) $params['tot_price'];
            $cp_hist_seq = (int) $params['cp_hist_seq'] ?? null;
            $cp_price = (int) $params['cp_price'];
            $operating_cmpy_cd = (int) $params['operating_cmpy_cd'];
            $point_card_code = $params['point_card_code'];
            $billing_key = $params['billing_key'];
            $billing_password = $params['billing_password'];
            $pointAmount = (int) $params['pointAmount'];
            $park_operate_ct = (int) $params['park_operate_ct'];
            $version = $params['version'] ?? 'v1.0';
            $payment_channel = 'iparkingPay';
            $accumulate_yn = (int) $params['accumulate'];

            $card_point_use_ny = $params['card_point_use_ny'];           

            $bcar_number = $params['bcar_number'];
            $prdt_seq = (int) $params['prdt_seq'];

            // 결제 실패체크
            $fail_check = 0;

            $body_param = [
                'memb_seq' => $memb_seq,
                'bcar_seq' => $bcar_seq,
                'product_cd' => $product_cd,
                'product_seq' => $product_seq,
                'nspo_seq' => $nspo_seq, // 카드 seq
                'pay_price' => $pay_price,  // PG 결제금액
                'park_seq' => $park_seq, // 주차장 seq
                'card_point_use_ny' => $card_point_use_ny,
                'cp_hist_seq' => $cp_hist_seq, // 쿠폰정보확인용으로 연동테이블 파라미터 이력으로 남긴다.
                'cp_price' => $cp_price
            ];

            $body = json_encode($body_param);

            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            try {

                if($pay_price != 0) {
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
    
                    $url = $this->relay_domain.'/api/payment/'.$version.'/credit/card/pay/iparkingpay';
    
                    $last_relay_index = $this->ci->log->relayHistoryInsert($url, 'POST', $body_param, $base64encode_data);
                    
                    $http_result = $this->ci->http->post(
                        $url,
                        $requestBody
                    );
                    
                    $result = $http_result->getBody()->getContents();
            
                    $result  = json_decode($result, true);
    
                    $this->ci->log->relayHistoryUpdate($last_relay_index, $result);

                    $iparkingPay_result_code = $result['result'];
                    $iparkingPay_result_Message = $result['resultMessage'];
                } else {
                    $iparkingPay_result_code = '0000';
                }

                $err_message = null;
                $pay_status = 'S';

                // 아이파킹 페이 상태 체크
                if($iparkingPay_result_code != '0000') {
                    // throw new Exception("iparkingPay 결제 실패하였습니다.");
                    $confirm_fail_ny = 1;
                    throw new Exception($iparkingPay_result_Message);
                }
                
                $cp_use_date = date('Y-m-d');
                $cp_use_time = date('H:i:s');
                $cp_use_datetime = $cp_use_date." ".$cp_use_time;
                

                if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1 ) {

                    // 포인트 주문번호 만들기
                    $apply_orderno = str_pad($operating_cmpy_cd, 3, "0", STR_PAD_LEFT);
                    $apply_orderno .= str_pad($park_seq, 5, "0", STR_PAD_LEFT);
                    $apply_orderno .= str_pad($product_cd, 3, "0", STR_PAD_LEFT);
                    $apply_orderno .= '1';
                    $apply_orderno .= str_pad($product_seq, 8, "0", STR_PAD_LEFT);

                    $use_point = $pointAmount;
                    // 블루포인트
                    if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                        list($point_info, $point_result_code) = $this->ci->point->bluePointUse($version, $memb_seq, $point_card_code, $use_point, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $bcar_seq, $bcar_number, $billing_key, $prdt_seq);
                        
                        if($point_result_code == '00') {
                            $point_approv_no = $point_info['appl_num'];
                            $point_approv_date = $point_info['appl_date'];
                            $point_approv_time = $point_info['appl_time'];
                            $tid = $point_info['tid'];
                            $pg_site_cd = $point_info['pg_site_cd'];

                            // $pglog = fopen('/tmp/pg_site_cd.txt', 'ab') or die("can't open file");
                            // fwrite($pglog, '================pg_site_cd return=============='.chr(13).chr(10));
                            // fwrite($pglog, 'pg_site_cd : '.$pg_site_cd.chr(13).chr(10));

                            // fwrite($pglog, '================ $point_info[pg_site_cd] =============='.chr(13).chr(10));
                            // fwrite($pglog, 'point_info_pg_site_cd : '.$point_info['pg_site_cd'].chr(13).chr(10));


                            // fwrite($pglog, '================point_info=============='.chr(13).chr(10));
                            // fwrite($pglog, print_r($point_info,TRUE) );
                        }
                    
                    } else if($point_card_code == 'GSP') {
                        list($point_info, $point_result_code) = $this->ci->point->gsPointUse($version, $billing_key, $park_operate_ct, $pay_price, $pointAmount, $memb_seq, $product_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                        
                        if($point_result_code == '00') {
                            $point_approv_no = $point_info['approv_no'];
                            $point_approv_date = $point_info['approv_date'];
                            $point_approv_time = $point_info['approv_time'];
                            $tid = $point_info['chasing_no'];

                            if($park_operate_ct == 2){
                                if($pay_price != $pointAmount && $pay_price > $pointAmount){
                                    $save_point = $point_info['save_point'];
                                }
                            }
                        }
                    } else if($point_card_code == 'LTP') { 
                        if($accumulate_yn == 0){
                            list($point_info, $point_result_code) = $this->ci->point->LPointUse($version, $billing_key, $billing_password, $park_operate_ct, $pay_price, $pointAmount, $product_seq, $memb_seq, $payment_channel, $apply_orderno, $product_cd, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                            
                            if($point_result_code == '00') {
                                $point_approv_no = $point_info['aprno'];
                                $point_approv_date = $point_info['aprDt'];
                                $point_approv_time = $point_info['aprHr'];
                                $tid = $point_info['control']['flwNo'];
                            }
                        } else if($accumulate_yn == 1){
                            list($point_info, $point_result_code) = $this->ci->point->LPointOnlyAccumulate($version, $billing_key, $billing_password, $pay_price, $pointAmount, $park_operate_ct, $memb_seq, $payment_channel, $product_cd, $product_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq);
                            
                            if($point_result_code == '00') {
                                $point_approv_no = $point_info['aprno'];
                                $point_approv_date = $point_info['aprDt'];
                                $point_approv_time = $point_info['aprHr'];
                                $tid = $point_info['control']['flwNo'];
                                $save_point = $point_info['save_point'];
                            }
                        }
                        
                    }

                    if($point_result_code != '00') {

                        if($pay_price != 0) {
                            // 포인트 연동 실패시 아이파킹 페이 취소처리
                            list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                            $iparkingPay_cancel_ny = 1;
                        } 
                        $fail_check = 1;
                        // 포인트사용에 실패했기때문에 포인트취소연동을 호출하지 않아도 된다.
                        $point_cancel_ny = 1;
                    }
                }
                
                // 포인트 실패하지 않은 경우만 쿠폰 진입
                if($cp_hist_seq != "" && $cp_hist_seq != null && $fail_check == 0) {
                    // 쿠폰 사용처리
                    $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 1, $cp_use_datetime, $product_seq);

                    // 쿠폰 결제 연동 B2B API
                    if($coupon_result == "OK") {
                        // 쿠폰사용처리 후 연동
                        $coupon_result = $this->ci->relay->couponReflect($product_cd, $product_seq, $result_code="0000", $result_msg="성공", $cp_price, $cp_use_date, $cp_use_time, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                    }

                    // 쿠폰연동 실패했다면
                    if($coupon_result == "Fail") {

                        $fail_check = 2;

                        // 쿠폰사용 취소
                        $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);

                        // 쿠폰사용에 실패했기때문에 쿠포취소연동 api 를 굳이호출하지 않아도 된다.                        
                        $coupon_cancel_ny = 1;

                        if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1) {

                            $point_cancel_msg = '포인트 취소';
                            // 포인트 취소
                            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                // fwrite($pglog, '================pg_site_cd cancel==============');
                                // fwrite($pglog, 'pg_site_cd : '.$pg_site_cd);
                                // fclose($pglog);

                                list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if ($point_card_code == 'GSP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['approv_no'];
                                $point_cancel_appl_date = $cancel_data['approv_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'LTP') {
                                if($accumulate_yn == 0){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ($accumulate_yn == 1){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_msg = '포인트 적립 취소';
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                }
                            }
                            // 포인트 결제 취소 연동 B2B API 
                            if($point_cancel_code == "00"){
                                // $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                // $product_seq, $result_code, $point_cancel_msg, $use_point, $point_approv_date, $point_approv_time, $point_approv_no, 
                                // $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);
                                
                                // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                $product_seq, $result_code, $point_cancel_msg, $use_point, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                // 포인트 취소연동 api를 호출했기때문에 더이상 호출하지 않아도 된다.
                                $point_cancel_ny = 1;
                                // 쿠폰결제연동 실패시 쿠폰 취소연동 호출하지 않아도 됨
                                // $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                                // $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                            }
                        }
                    }
                }

                if($fail_check == 0){
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm($pay_status, $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                    if($confirm_code == '99') {
                        list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                        $iparkingPay_cancel_ny = 1;
                        if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1) {
                            $point_cancel_msg = '포인트 취소';
                            // 포인트 취소
                            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'GSP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['approv_no'];
                                $point_cancel_appl_date = $cancel_data['approv_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'LTP') {
                                if($accumulate_yn == 0){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ($accumulate_yn == 1){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_msg = '포인트 적립 취소';
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                }
                            }
                            if($point_cancel_code == "00"){
                                // 포인트 결제 취소 연동 B2B API 
                                // $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                // $product_seq, $result_code, $point_cancel_msg, $use_point, $point_approv_date, $point_approv_time, $point_approv_no, 
                                // $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                $product_seq, $result_code, $point_cancel_msg, $use_point, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                //포인트 취소연동 처리됨
                                $point_cancel_ny = 1;
                            }
                            

                        }
                        if($cp_hist_seq != "" && $cp_hist_seq != null) {
                            $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                            // 구매완료 실패시 쿠폰취소 호출
                            $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                            $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                            // 쿠폰취소 처리됨
                            $coupon_cancel_ny = 1;
                        }
                        if($confirm_fail_ny == 0){
                            list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                            $confirm_fail_ny = 1;
                        }
                    }
                    
                    if($confirm_code == '00') {
                        $msg = $this->ci->message->apiMessage['success'];
                    } else {
                        $msg = $this->ci->message->apiMessage['fail'];
                    }
                    $msg['data'] = $confirm_result;
                    return $response->withJson($msg);

                } else if ($fail_check == 1){
                    // 결제취소
                    if ($iparkingPay_cancel_ny == 0){
                        list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                        $iparkingPay_cancel_ny = 1;
                    }

                    if($confirm_fail_ny == 0){
                        list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                        $confirm_fail_ny = 1;
                    }
                    throw new Exception("포인트 결제 처리에 실패했습니다.");
                } else if ($fail_check == 2){
                    // 쿠폰사용 취소
                    if($coupon_cancel_ny == 0){
                        $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                        // 구매완료 실패시 쿠폰취소 호출
                        $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);

                        $coupon_cancel_ny = 1;
                    }
                    if($iparkingPay_cancel_ny == 0){
                        // 결제취소
                        list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                        $iparkingPay_cancel_ny = 1;
                    }
                    if($confirm_fail_ny == 0){
                        list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                        $confirm_fail_ny = 1;
                    }
                    throw new Exception("쿠폰 결제 처리에 실패했습니다.");
                } 

            } catch (RequestException $e) {
                if($fail_check == 1) {
                    if ( $iparkingPay_cancel_ny == 0)
                    list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);                      
                    $iparkingPay_cancel_ny =1;
                } else if($fail_check == 2) {
                    // 쿠폰사용 취소
                    if($coupon_cancel_ny == 0){
                        $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                        // 구매완료 실패시 쿠폰취소 호출
                        $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);

                        $coupon_cancel_ny = 1;
                    }
                    if($iparkingPay_cancel_ny == 0){
                        // 결제취소
                        list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                        $iparkingPay_cancel_ny = 1;
                    }
                    
                    if($point_cancel_ny == 0){
                        if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1) {
                            $point_cancel_msg = '포인트 취소';
                            // 포인트 취소
                            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if ($point_card_code == 'GSP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['approv_no'];
                                $point_cancel_appl_date = $cancel_data['approv_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'LTP') {
                                if($accumulate_yn == 0){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ($accumulate_yn == 1){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_msg = '포인트 적립 취소';
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                }
                            }
                            if($point_cancel_code == "00"){
                                // 포인트 결제 취소 연동 B2B API 
                                // $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                // $product_seq, $result_code, $point_cancel_msg, $use_point, $point_approv_date, $point_approv_time, $point_approv_no, 
                                // $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                $product_seq, $result_code, $point_cancel_msg, $use_point, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                $point_cancel_ny = 1;
                            }
                        }
                    }
                }
                if($confirm_fail_ny == 0){
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                    $confirm_fail_ny = 1;
                }
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                if($fail_check == 1) {
                    if ( $iparkingPay_cancel_ny == 0)
                    list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);                      
                    $iparkingPay_cancel_ny =1;
                } else if($fail_check == 2) {
                    // 쿠폰사용 취소
                    if($coupon_cancel_ny == 0){
                        $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                        // 구매완료 실패시 쿠폰취소 호출
                        $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                        $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                        
                        $coupon_cancel_ny = 1;
                    }
                    if($iparkingPay_cancel_ny == 0){
                        // 결제취소
                        list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                        $iparkingPay_cancel_ny = 1;
                    }
                    
                    if($point_cancel_ny == 0){
                        if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1) {
                            $point_cancel_msg = '포인트 취소';
                            // 포인트 취소
                            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if ($point_card_code == 'GSP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['approv_no'];
                                $point_cancel_appl_date = $cancel_data['approv_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'LTP') {
                                if($accumulate_yn == 0){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ($accumulate_yn == 1){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_msg = '포인트 적립 취소';
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                }
                            }
                            if($point_cancel_code == "00"){
                                // 포인트 결제 취소 연동 B2B API 
                                // $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                // $product_seq, $result_code, $point_cancel_msg, $use_point, $point_approv_date, $point_approv_time, $point_approv_no, 
                                // $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                $product_seq, $result_code, $point_cancel_msg, $use_point, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                                $point_cancel_ny = 1;
                            }
                        }
                    }
                }
                if($confirm_fail_ny == 0){
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                    $confirm_fail_ny = 1;
                }
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            if($fail_check == 1) {
                if ( $iparkingPay_cancel_ny == 0)
                list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);                      
                $iparkingPay_cancel_ny = 1;
            } else if($fail_check == 2) {
                // 쿠폰사용 취소
                if($coupon_cancel_ny == 0){
                    $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                    // 구매완료 실패시 쿠폰취소 호출
                    $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                    $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                    
                    $coupon_cancel_ny = 1;
                }
                if($iparkingPay_cancel_ny == 0){
                    // 결제취소
                    list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                    $iparkingPay_cancel_ny = 1;
                }
                
                if($point_cancel_ny == 0){
                    if($pointAmount != 0 && $point_card_code != null || $accumulate_yn == 1) {
                        $point_cancel_msg = '포인트 취소';
                        // 포인트 취소
                        if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                            list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                            $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                            $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                            $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                        } else if ($point_card_code == 'GSP') {
                            list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                            $point_cancel_appl_num = $cancel_data['approv_no'];
                            $point_cancel_appl_date = $cancel_data['approv_date'];
                            $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                        } else if($point_card_code == 'LTP') {
                            if($accumulate_yn == 0){
                                list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['aprno'];
                                $point_cancel_appl_date = $cancel_data['aprDt'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if ($accumulate_yn == 1){
                                list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($point_approv_no, $point_approv_date, $memb_seq);
                                $point_cancel_msg = '포인트 적립 취소';
                                $point_cancel_appl_num = $cancel_data['aprno'];
                                $point_cancel_appl_date = $cancel_data['aprDt'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            }
                        }
                        if($point_cancel_code == "00"){
                            // 포인트 결제 취소 연동 B2B API 
                            // $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                            // $product_seq, $result_code, $point_cancel_msg, $use_point, $point_approv_date, $point_approv_time, $point_approv_no, 
                            // $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);
                            
                            // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                            $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                            $product_seq, $result_code, $point_cancel_msg, $use_point, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                            $tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no, $save_point);

                            $point_cancel_ny = 1;
                        }
                    }
                }
            }
            if($confirm_fail_ny == 0){
                list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm("F", $product_cd, $product_seq, $tot_price, $pay_price, $cp_price, $version, $pointAmount);
                $confirm_fail_ny = 1;
            }
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // iparking pay 결제취소
    public function postIparkingPayPaymentCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = (int) $params['memb_seq']; // 회원 seq
            $bcar_seq = (int) $params['bcar_seq']; // 차량 seq
            $product_cd = (int) $params['product_cd]']; // 상품구분코드 (2:상품 5:시간주차 6:미납 등..)
            $product_seq = (int) $params['product_seq']; // 상품 seq (각 삼풍의 seq정보)
            $nspo_seq = (int) $params['nspo_seq']; // 카드 seq
            $pay_price = (int) $params['pay_price']; // PG 결제금액
            $park_seq = (int) $params['park_seq']; // 주차장 seq
            $version = $params['version'] ?? 'v1.0';

            try {
                
                list($result, $result_code) = $this->ci->relay->iparkingPayPaymentCancel($memb_seq, $bcar_seq, $product_cd, $product_seq, $nspo_seq, $pay_price, $park_seq, $version);
                
                if($result_code == '99') {
                    throw new Exception($result['resultMessage']);
                } else {
                    $msg = $this->ci->message->apiMessage['success'];
                    $msg['data'] = $result['resultData'];
                    return $response->withJson($msg);
                } 

            } catch (RequestException $e) {   
                return $response->withJson(['error' => $e->getMessage()]);
            } catch (BadResponseException $e) {
                return $response->withJson(['error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 포인트 결제 연동
    public function postPointCardReflect($requet, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $point_card_code = $params['point_card_code'];
            $product_cd = (int) $params['product_cd']; // 상품구분코드 (2:상품 5:시간주차 6:미납 등..)
            $product_seq = (int) $params['product_seq']; // 상품 seq (각 삼풍의 seq정보)
            
            // 성공시만 호출하기 때문에 코드통일
            $result_code = "0000";
            $result_msg = $params['result_msg'];
            $use_point = (int) $params['use_point'];
            $appl_date = $params['appl_date'];
            $appl_time = $params['appl_time'];
            $appl_num = $params['appl_num'];
            $tid = $params['tid'];
            $memb_seq = (int) $params['memb_seq']; // 회원 seq
            $bcar_seq = (int) $params['bcar_seq']; // 차량 seq
            $bcar_number = $params['bcar_number'];
            $park_seq = (int) $params['park_seq']; // 주차장 seq
            $prdt_seq = (int) $params['prdt_seq'];
            $billing_key = $params['billing_key'];

            $version = $params['version'] ?? 'v1.0';

            $body_param = [
                'point_card_code' => $point_card_code,
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
                'billing_key' => $billing_key
            ];

            $body = json_encode($body_param);

            $base64encode_data = $this->ci->util->iparkingSeedAesEncrypt($body);

            try {

                if($pay_price != 0) {
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
                }

                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $confirm_result;
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

    // 쿠폰 결제 연동
    public function postCouponReflect($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $product_cd = (int) $params['product_cd']; // 상품구분코드 (2:상품 5:시간주차 6:미납 등..)
            $product_seq = (int) $params['product_seq']; // 상품 seq (각 삼풍의 seq정보)
            $result_code = $params['result_code'];
            $result_msg = $params['result_msg'];
            $pay_price = (int) $params['pay_price'];
            $appl_date = $params['appl_date'];
            $appl_time = $params['appl_time'];
            $memb_seq = (int) $params['memb_seq']; // 회원 seq
            $bcar_seq = (int) $params['bcar_seq']; // 차량 seq
            $bcar_number = $params['bcar_number'];
            $park_seq = (int) $params['park_seq']; // 주차장 seq
            $prdt_seq = (int) $params['prdt_seq'];
            $cp_hist_seq = (int) $params['cp_hist_seq'];

            $version = $params['version'] ?? 'v1.0';

            list($result, $result_code) = $this->ci->relay->couponReflect($product_cd, $product_seq, $result_code, $result_msg, $pay_price, $appl_date, $appl_time, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);

            if($result_code == '99') {
                throw new Exception($result['resultMessage']);
            } else {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $result['resultData'];
                return $response->withJson($msg);
            } 

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 포인트카드 취소 연동
    public function postPointCardCancel($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            try {

                $point_card_code = $params['point_card_code'];
                $memb_seq = $params['memb_seq'];
                $appl_num = $params['appl_num']; // 운영사승인번호
                $appl_date = $params['appl_date']; // 운영사원거래일자
                $appl_time = $params['appl_time'];
                $ppsl_seq = $params['ppsl_seq'] ?? null;
                $version = $params['version'] ?? 'v1.0';
                $bcar_number = $params['bcar_number'];
                $product_cd = $params['product_cd'];
                $product_seq = $params['product_seq'];
                $use_point = $params['use_point'];
                $save_point = $params['save_point'];

                $result_code = '99';
                $point_result_arr = array(
                    'result_message' => ""
                );
                // LPoint
                if($point_card_code == 'LTP') {
                    if($ppsl_seq != null) {
                        $stmt = $this->ci->iparkingCmsDb->prepare('
                            SELECT 
                                * 
                            FROM iparking_cms.lpoint 
                            WHERE 
                                ppsl_seq = :ppsl_seq
                            AND
                                type = :type
                        ');

                        $stmt->execute([
                            'ppsl_seq' => $ppsl_seq,
                            'type' => 'u'
                        ]);
                        $lpoint_use_result = $stmt->fetch(); 

                        if(empty($lpoint_use_result)){
                            
                            $stmt = $this->ci->iparkingCmsDb->prepare('
                                SELECT 
                                    * 
                                FROM iparking_cms.lpoint 
                                WHERE 
                                    ppsl_seq = :ppsl_seq
                                AND
                                    type = :type
                            ');

                            $stmt->execute([
                                'ppsl_seq' => $ppsl_seq,
                                'type' => 'a'
                            ]);
                            $lpoint_accumulate_result = $stmt->fetch(); 

                            if(empty($lpoint_accumulate_result)) {
                                $msg = $this->ci->message->apiMessage['point_cancel_fail'];
                                return $response->withJson($msg);
                            } else {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->LPointAccumulateCancel($appl_num, $appl_date, $memb_seq);                         
                                $save_point = $cancel_data['canPt'];
                            }

                        } else {
                            list($cancel_data, $point_cancel_code) =  $this->ci->point->LPointCancel($appl_num, $appl_date, $memb_seq);
                            $use_point = $cancel_data['canPt'];
                        }
                    }

                    if($point_cancel_code == '00') {
                        $point_result_arr['cancel_appl_num'] = $cancel_data['aprno'];
                        $point_result_arr['cancel_appl_date'] = $cancel_data['aprDt'];
                        $point_result_arr['cancel_appl_time'] = $cancel_data['cancel_appl_time'];
                        // $point_result_arr['cancel_point_amount'] = $cancel_data['canPt'];
                        $result_code = '00';
                    } else {
                        $point_cancel_code = '99';
                        $cancel_data['result_message'] = $cancel_data['msgCn1'];
                    }
                } else if($point_card_code == 'GSP') {
                    list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($appl_date, $appl_num, $memb_seq);
                    if($point_cancel_code == '00') {
                        $point_result_arr['cancel_appl_num'] = $cancel_data['approv_no'];
                        $point_result_arr['cancel_appl_date'] = $cancel_data['approv_date'];
                        $point_result_arr['cancel_appl_time'] = $cancel_data['cancel_appl_time'];
                        $use_point = $cancel_data['occur_pt'];
                        $save_point = $cancel_data['cancel_save_point'];
                        $result_code = '00';
                    } else {
                        $result_code = '99';
                        $point_result_arr['result_message'] = $cancel_data['result_message'];
                    }
                    
                } else if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                    $stmt = $this->ci->iparkingCmsDb->prepare('
                        SELECT 
                            *
                        FROM 
                            iparking_cms.ksnet_point
                        WHERE 
                            type = :type
                        AND 
                            appl_num = :appl_num
                        AND 
                            appl_date = :appl_date
                        AND
                            point_card_code = :point_card_code
                        LIMIT 1
                    ');

                    $stmt->execute(['type' => 'u',
                                    'appl_num' => $appl_num,
                                    'appl_date' => $appl_date,
                                    'point_card_code' => $point_card_code
                    ]);

                    $blue_data = $stmt->fetch();

                    $stmt = $this->ci->iparkingCloudDb->prepare('
                        SELECT 
                            park_seq
                        FROM 
                            fdk_parkingcloud.acd_rpms_parking_product_sales
                        WHERE 
                            ppsl_seq = :product_seq
                    
                    ');

                    $stmt->execute(['product_seq' => $blue_data['product_seq'] ]);

                    $park_data = $stmt->fetch();

                    // if(empty($blue_data)) throw new ErrorException ("블루포인트 결제된 내역이 확인되지 않습니다.");

                    // if(empty($park_data)) throw new ErrorException ("상품정보를 찾을수 없습니다.");

                    if(empty($blue_data) || empty($park_data)){
                        $msg = $this->ci->message->apiMessage['point_cancel_fail'];
                        return $response->withJson($msg);
                    }


                    list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel(
                        $version, $memb_seq, $point_card_code, $park_data['park_seq'], 
                        $blue_data['park_operate_ct'], 2, $blue_data['product_seq'], 
                        $blue_data['pay_price'], $blue_data['tot_price'], $blue_data['cp_price'], 
                        $blue_data['point_price'], $blue_data['payment_channel'], $blue_data['cp_hist_seq'],
                        $blue_data['appl_num'], $blue_data['appl_date'], $blue_data['appl_time'], $blue_data['pg_site_cd']
                    );

                    if($point_cancel_code == '0000') {
                        $point_result_arr['cancel_appl_num'] = $cancel_data['cancel_appl_num'];
                        $point_result_arr['cancel_appl_date'] = $cancel_data['cancel_appl_date'];
                        $point_result_arr['cancel_appl_time'] = $cancel_data['cancel_appl_time'];
                        $use_point = $blue_data['point_price'];
                        $point_cancel_code = '00';
                        $result_code = '00';
                    } else {
                        $msg['message'] = $cancel_data['resultMessage'];
                        return $response->withJson($msg);
                    }
                }

                if($point_cancel_code == '00'){
                    // 포인트 결제 취소 연동 B2B API 
                    list($point_info, $relay_result_code) = $this->ci->relay->pointCardAdminCancelReflect(
                        $version, $point_card_code, $product_cd, $ppsl_seq, $memb_seq,
                        $use_point, $point_result_arr['cancel_appl_date'], $point_result_arr['cancel_appl_time'], 
                        $point_result_arr['cancel_appl_num'], $save_point
                    );
                }
                
                if($relay_result_code == '00') {
                    $msg = $this->ci->message->apiMessage['point_cancel_success'];
                } else {
                    if( $result_code == '00'){
                        $msg = $this->ci->message->apiMessage['point_cancel_relay_fail'];
                    } else {
                        $msg = $this->ci->message->apiMessage['point_cancel_fail'];
                    }
                }
                
                $msg['data'] = $point_result_arr;
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


    // 포인트카드취소에 대해 취소연동만함
    public function postOnlyPointCardCancelRelay($request, $response, $args)
    {
        try {
            try {
                $params = $this->ci->util->getParams($request);


                $point_card_code = $params['point_card_code'];
                $memb_seq = $params['memb_seq'];
                $appl_num = $params['appl_num']; 
                $appl_date = $params['appl_date']; 
                $appl_time = $params['appl_time'];

                $ppsl_seq = $params['ppsl_seq'] ?? null;
                $version = $params['version'] ?? 'v1.0';
                $bcar_number = $params['bcar_number'];
                $result_code = $params['result_code'];
                $use_point = $params['use_point'];
                $save_point = $params['save_point'];
                
                $card_no = $params['card_no'];

                list($point_info, $relay_result_code) = $this->ci->relay->pointCardCancelReflect($version, 
                            $point_card_code, 2, $ppsl_seq, $result_code, '[포인트 구매내역] 취소요청', $use_point
                            , $appl_date, $appl_time, $appl_num, 
                            "", $memb_seq, "", "", "", "", $card_no, $save_point);


                $msg['data'] = $point_info;
                $msg['relay_result_code'] = $relay_result_code;

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
    ////////////////////////////////////////// PG 결제 끝 /////////////////////////////////////////////

}