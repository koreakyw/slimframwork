<?php

class PaycoController {
    
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;

        $env = 'off';
        if ($_SERVER['SERVER_ADDR'] == '52.78.194.15' || $_SERVER['SERVER_ADDR'] == '52.79.119.107'
	  		|| $_SERVER['SERVER_ADDR'] == '172.31.29.201' || $_SERVER['SERVER_ADDR'] == '172.31.20.130' ) { 
            $env = 'on';            
        }
        $this->env =$env;
        // $this->ci->settings['env']= $env;
    }

    public function postPaycoReserve($request, $response, $args) 
    {
        try {

            $params = $this->ci->util->getParams($request);

            require("../peristalsis/PayCo/payco_config.php");
            
            $ppsl_seq = $params['ppsl_seq'] ?? rand(000041,999999); //85108;          // 상품 코드 정기권 상품 시퀀스 -> 디비 조회에서 가져와야 함 // 컨퍼런스 상품정보 연동에서 ppsl_seq 필요.
            $prdt_product_cd = $params['prdt_product_cd'] ?? 2;         // 상품 주차 구분 코드 -> 2 : 정기, 5 : 시간 , 6 : 미수, 11 : 멤버스
            $memb_seq = $params['memb_seq'] ?? 90550;           // 사용자 시퀀스 -> 사용자정보 조회용
            $jobType = $params['jobType'] ?? "I";             // 결제 타입 ( I : 신규 결제 , U : 연장 결제 ) 모바일단으로 부터 받아옴
            $cp_hist_seq = $params['cp_hist_seq'] ?? null;      // 쿠폰. 모바일 단으로부터 받아옴 -> 서비스 파라미터에 셋팅을 해준다.
            $price = $params['price'] ?? 200;              // pg에 태울 금액    
                        
            // $vehicle_cd = $parmas['vehicle_cd']; // 소형 중형 대형
            $park_seq = $params['park_seq'] ?? 1365; // 디비 조회에서 가져와야 함 // 컨퍼런스 상품정보 연동에서 park_seq 필요
            $bcar_seq = $params['bcar_seq'] ?? 50145;

            $operating_cmpy_cd = $params['operating_cmpy_cd'] ?? 1;
            $car_number = $params['car_number'] ?? '11가1234';

            // 포인트 예약용 파라메터
            $point_card_code = $params['point_card_code'] ?? 'LTP';
            $billing_key = $params['billing_key']; // 포인트 카드 번호
            $billing_password = $params['billing_password']; // 포인트 카드 비밀번호
            $pointAmount = $params['pointAmount'] ?? 100;
            $park_operate_ct = $params['park_operate_ct'] ?? '1';
            $version = $params['version'];
            $tot_price = $params['tot_price'];
            $cp_price = $params['cp_price'];
            $prdt_seq = $params['prdt_seq'];
            $accumulate_yn = $params['accumulate'];
            $park_name = $params['park_name'];
            $prdt_name = $params['prdt_name'];
           
            /* 페이코 예약 결제 */
            $payment_channel = 'PAYCO';

            $goodName = $park_seq.'_'.urlencode($park_name).'_'.urlencode($prdt_name);  // 상품명 -> 디비 조회에서 가져와야 함 // 컨퍼런스 상품정보 연동에서 park_name + prdt_name 상품명 필요.
            

            // // 이미 예약된 정보가 있으면 바로 결제가 되도록 한다.
            // $stmt = $this->ci->iparkingCmsDb->prepare('
            //     SELECT 
            //         * 
            //     FROM iparking_cms.payco_reserve 
            //     WHERE 
            //         ppsl_seq = :ppsl_seq
            //     AND
            //         prdt_product_cd = :prdt_product_cd 
            //     AND 
            //         memb_seq = :memb_seq
            //     AND
            //         park_seq = :park_seq
            //     AND
            //         bcar_seq = :bcar_seq
            //     AND
            //         operating_cmpy_cd = :operating_cmpy_cd
            // ');

            // $stmt->execute([
            //     'ppsl_seq' => $ppsl_seq,
            //     'prdt_product_cd' => $prdt_product_cd, 
            //     'memb_seq' => $memb_seq,
            //     'park_seq' =>  $park_seq,
            //     'bcar_seq' => $bcar_seq,
            //     'operating_cmpy_cd' => $operating_cmpy_cd
            // ]);

            // $payco_reserve_result = $stmt->fetch();
            // $payco_reserve_result_ppsl_seq = $payco_reserve_result['ppsl_seq'] ?? null;
            // $payco_reserve_res = $payco_reserve_result['res'];

            // if($payco_reserve_result_ppsl_seq != null) {
            //     $payco_reserve_res = json_decode($payco_reserve_res, true);
            //     return $this->ci->phpRenderer->render($response, "../templates/reserve.phtml", $payco_reserve_res);
            // }

            /**
             * customerOrderNumber 조합 로직
             * $product_cd -> lpad, 2, "0"
             * $ppsl_seq -> lpad, 10, "0"
             * park_seq -> $ppsl_seq로 디비를 조회해서 가져와야 함
             * 운영사코드 3자리, 주차장코드 5자리, 상품코드 3자리 ( prdt_seq ), ( ppsl_seq ) 로컬유니크시퀀스 -> ppsl_seq
             */
      
            // $customerOrderNumber = str_pad($product_cd, 2, "0", STR_PAD_LEFT); // 외부가맹점의 주문번호 -> 자체 로직으로 조합해야 함
            // $customerOrderNumber = $customerOrderNumber.str_pad($ppsl_seq, 10, "0", STR_PAD_LEFT);
            // $customerOrderNumber = $customerOrderNumber.$park_seq;
            $customerOrderNumber = str_pad($operating_cmpy_cd, 3, "0", STR_PAD_LEFT);
            $customerOrderNumber .= str_pad($park_seq, 5, "0", STR_PAD_LEFT);
            $customerOrderNumber .= str_pad($prdt_product_cd, 3, "0", STR_PAD_LEFT);
            $customerOrderNumber .= str_pad($ppsl_seq, 9, "0", STR_PAD_LEFT);

            $apply_orderno = str_pad($operating_cmpy_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($park_seq, 5, "0", STR_PAD_LEFT);
            $apply_orderno .= str_pad($prdt_product_cd, 3, "0", STR_PAD_LEFT);
            $apply_orderno .= '1';
            $apply_orderno .= str_pad($ppsl_seq, 8, "0", STR_PAD_LEFT);

            Global $sellerKey, $AppWebPath, $iosYN, $cpId, $productId;

            /*======== 상품정보 변수 선언 및 초기화 ========*/
            $orderNumber = 3;                       // 주문 상품이 여러개일 경우 순번을 매길 변수
            $orderQuantity = 0;                     // (필수) 주문수량
            $productUnitPrice = 0;                  // (필수) 상품단가
            $productUnitPaymentPrice = 0;           // (필수) 상품 결제 단가
            $productAmt = 0;                        // (필수) 상품 결제금액(상품단가 * 수량)
            $deliveryFeeAmt = 0;                    // 

            $productPaymentAmt = 0;
            $totalProductPaymentAmt = 0;            // 주문 상품이 여러개일 경우 상품들의 총 금약을 저장할 변수
            $sortOrdering = 0;
            $unitTaxfreeAmt = 0;                    // 개별상품 단위 면세금액
            $unitTaxableAmt = 0;                    // 개별상품 단위 공급가액
            $unitVatAmt = 0;                        // 개별상품 단위 부가세액
            $totalTaxfreeAmt = 0;                   // 총 면세 금액
            $totalTaxableAmt = 0;                   // 충 과세 공급가액
            $totalVatAmt = 0;                       // 총 과세 부가세액

            $iOption = '';
            $productName = '';
            $productInfoUrl = '';
            $orderConfirmUrl = '';
            $orderConfirmMobileUrl = '';
            $productImageUrl = '';
            $sellerOrderProductReferenceKey = '';
            $taxationType = '';

            // 상품정보 값 입력
            $orderNumber = $orderNumber + 1;
            $orderQuantity = 1;                                                             // [필수]
            $productUnitPrice = $price;                                                     // [필수]
            $productUnitPaymentPrice = $price;                                              // [필수]
            $productAmt = $productUnitPrice * $orderQuantity;                               // [필수]
            $productPaymentAmt = $productUnitPaymentPrice; // = $orderQuantity;                 // [필수]
            $iOption = "280";                                                               // [선택]
            $sortOrdering = $orderNumber;                                                   // [필수] 상품노출순서
            $productName = $goodName;                                                       // [필수]
            $orderConfirmUrl = "";                                                          // [선택] 주문완료 후 주문상품을 확인할 수 있는 url, 4000자 이내
            $orderConfirmMobileUrl = "";                                                    // [선택] 주문완료 후 주문상품을 확인할 수 있는 모바일 url, 4000자 이내
            $productImageUrl = "";                                                          // [선택] 이미지 URL(배송비 상품이 아닌 경우는 필수), 4000자 이내, productImageUrl에 적힌 이미지를 썸네일해서 PAYCO 주문창에 보여줍니다.
            $sellerOrderProductReferenceKey = "parkingTicket_".$ppsl_seq;           // [필수] 가맹점에서 관리하는 상품키, 100자 이내.(외부가맹점에서 관리하는 주문상품 연동 키(sellerOrderProductReferenceKey)는 주문 별로 고유한 key이어야 합니다.)
                                                                                            // 단 주문당 1건에 대한 상품을 보내는 경우는 연동키가 1개이므로 주문별 고유값을 고려하실 필요 없습니다.
            $taxationType = "TAXATION";                                                     // [선택] 과세타입(기본값: 과세). DUTYFREE :면세, COMBINE : 결합상품, TAXATION : 과세


            // totalTaxfreeAmt(면세상품 총액) / totalTaxableAmt(과세상품 총액) / totalVatAmt(부가세 총액) => 일부 필요한 가맹점을위한 예제입니다.
            // 과세상품일 경우
            if( $taxationType == "TAXATION"){
                $unitTaxfreeAmt = 0;
                $unitTaxableAmt = round($productPaymentAmt / 1.1);
                $unitVatAmt = round(($productPaymentAmt / 1.1) * 0.1);

                if ($unitTaxableAmt + $unitVatAmt != $productPaymentAmt) {
                    $unitTaxableAmt = $productPaymentAmt - $unitVatAmt;
                }
            // 면세상품일 경우
            } elseif( $taxationType == "DUTYFREE") {
                $unitTaxfreeAmt = $productPaymentAmt;
                $unitTaxableAmt = 0;
                $unitVatAmt = 0;
            // 복합상품일 경우
            }else{
                $unitTaxfreeAmt = 1000;
                $unitTaxableAmt = round(($productPaymentAmt - $unitTaxfreeAmt) / 1.1);
                $unitVatAmt = round((($productPaymentAmt - $unitTaxfreeAmt) / 1.1) * 0.1);

                if ($unitTaxableAmt + $unitVatAmt != $productPaymentAmt - $unitTaxfreeAmt) {
                    $unitTaxableAmt = ($productPaymentAmt - $unitTaxfreeAmt) - $unitVatAmt;
                }
            }
            $totalTaxfreeAmt = $totalTaxfreeAmt + $unitTaxfreeAmt;
            $totalTaxableAmt = $totalTaxableAmt + $unitTaxableAmt;
            $totalVatAmt = $totalVatAmt + $unitVatAmt;

            //주문정보를 구성하기 위한 상품들 누적 결제 금액(상품결제금액) 
            $totalProductPaymentAmt = $totalProductPaymentAmt + $productPaymentAmt;


            //상품값으로 읽은 변수들로 Json String 을 작성합니다.
            $productRows = array();
            try {
                $productInfo = array();
                $productInfo["cpId"] = $cpId;                                                               //[필수]상점ID - (payco_config에서 정의함)
                $productInfo["productId"] = $productId;                                                     //[필수]상품ID - (payco_config에서 정의함)
                $productInfo["productAmt"] = $productAmt;                                                   //[필수]상품금액(상품단가 * 수량)
                $productInfo["productPaymentAmt"] = $productPaymentAmt;                                     //[필수]상품결제금액(상품결제단가 * 수량)
                $productInfo["orderQuantity"] = $orderQuantity;                                             //[필수]주문수량(배송비 상품인 경우 1로 셋팅)
                $productInfo["option"] = urlencode($iOption);                                               //[선택]상품 옵션
                $productInfo["sortOrdering"] = $sortOrdering;                                               //[필수]상품 노출순서
                $productInfo["productName"] = urlencode($productName);                                      //[필수]상품명
                $productInfo["sellerOrderProductReferenceKey"] = $sellerOrderProductReferenceKey;           //[필수]외부가맹점에서 관리하는 주문상품 연동 키
                $productInfo["taxationType"] = $taxationType;                                               //[선택]과세타입(면세상품 : DUTYFREE, 과세상품 : TAXATION (기본), 결합상품 : COMBINE)

                if ( $orderConfirmUrl					!= "") {		$ProductsList["orderConfirmUrl"]				= $orderConfirmUrl; 				};
                if ( $orderConfirmMobileUrl				!= "") {		$ProductsList["orderConfirmMobileUrl"]			= $orderConfirmMobileUrl;			};
                if ( $productImageUrl					!= "") {		$ProductsList["productImageUrl"]				= $productImageUrl;					};
                array_push($productRows, $productInfo);
            } catch (Exception $e) {          
                $Error_Return = array();
                $Error_Return["result"]		= "DB_RECORDSET_ERROR";
                $Error_Return["message"]	= $e->getMessage();
                $Error_Return["code"]		= $e->getLine();     
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = json_encode($Error_Return);
                return $response->withJson($msg);      
            }

            /*======== 주문정보 변수 선언 및 초기화 ========*/
            $totalOrderAmt = 0;
            $totalDeliveryFeeAmt = 0;
            $totalPaymentAmt = 0;
            $sellerOrderReferenceKey = "";
            $sellerOrderReferenceKeyType = "";
            $iCurrency = "";
            $orderSheetUiType = "APP_BRIDGE_A";
            $orderTitle = "";
            $orderMethod = "";
            $serviceUrl = "";
            $serviceUrlParam = "";
            $returnUrl = "";
            $returnUrlParam = "";
            $nonBankbookDepositInformUrl = "";
            $orderChannel = "";
            $inAppYn = "";
            $individualCustomNoInputYn = "";
            $payMode = "";

            // 주문정보 값 입력
            $sellerOrderReferenceKey = $customerOrderNumber;                                //[필수]외부가맹점의 주문번호
            $sellerOrderReferenceKeyType = "UNIQUE_KEY";                                    //[선택]외부가맹점의 주문번호 타입(UNIQUE_KEY : 기본값, DUPLICATE_KEY : 중복가능한 키->외부가맹점의 주문번호가 중복 가능한 경우 사용)
            $iCurrency = "KRW";                                                             //[선택]통화(default=KRW)
            $totalPaymentAmt = $totalProductPaymentAmt;                                     //[필수]충 결제 할 금액
            $orderTitle = $goodName;                                            //[선택]주문 타이틀
            // $serviceUrl = 'http://52.79.73.171/payco/callBack';

            $serviceUrl = $AppRootPath.'/relay/payco/callBack';

            // $park_seq, $park_name, $product_cd, $product_name, $car_number, $prdt_seq, $prdt_name, $phone_number, $payment_channel
            ////////////////////////////////////////////////////////////////////////////// 포인트 예약 ////////////////////////////////////////////////////////////////////////////
            $point_reservation_seq = $this->ci->point->pointReservation(
                $memb_seq, $point_card_code, $billing_key, $billing_password, 
                $totalPaymentAmt, $pointAmount, $park_operate_ct,
                $sellerOrderReferenceKey, $ppsl_seq, $payment_channel, $apply_orderno,
                $prdt_product_cd, $bcar_seq, $car_number, $park_seq, $prdt_seq, $cp_price, $cp_hist_seq, $accumulate_yn 
            );

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                'method' => 'reserve',
                'sellerOrderReferenceKey' => $sellerOrderReferenceKey
            ]]);

            $payco_history_seq = $this->ci->iparkingCmsDb->lastInsertId();

            $serviceUrlParamArray = array();
            $serviceUrlParamArray["ppsl_seq"] = $ppsl_seq;
            $serviceUrlParamArray["prdt_product_cd"] = $prdt_product_cd;
            $serviceUrlParamArray["memb_seq"] = $memb_seq;
            $serviceUrlParamArray["cp_hist_seq"] = $cp_hist_seq;
            $serviceUrlParamArray["jobType"] = $jobType;
            $serviceUrlParamArray["icprSeq"] = $icprSeq;
            $serviceUrlParamArray["payco_history_seq"] = $payco_history_seq;
            $serviceUrlParamArray["version"] = $version;
            $serviceUrlParamArray['bcar_seq'] = $bcar_seq;
            $serviceUrlParamArray["reservationSeq"] = $point_reservation_seq;
            $serviceUrlParamArray["tot_price"] = $tot_price;
            $serviceUrlParamArray["point_price"] = $pointAmount;
            $serviceUrlParamArray["cp_price"] = $cp_price;
            $serviceUrlParamArray['pay_price'] = $price;
            $serviceUrlParamArray['park_seq'] = $park_seq;
            $serviceUrlParamArray['bcar_number'] = $car_number;
            $serviceUrlParamArray['prdt_seq'] = $prdt_seq;

            $serviceUrlParam = addslashes(json_encode($serviceUrlParamArray));              //[선택]주문완료 시 호출되는 API 와 함께 전달되어야 하는 파라미터(Json 형태의 String)(PAY1) 예: {\"cartNo\":\"CartNo_12345\"}
            // $returnUrl = $AppWebPath.'/payment/purchase/complete?ppsl_seq='.$ppsl_seq;                                    //[선택]주문완료 후 Redirect 되는 Url
            $returnUrl = $AppRootPath.'/payment/purchase/complete';
            $returnUrlParamArray = array();
            $returnUrlParamArray["ppsl_seq"] = $ppsl_seq;
            $returnUrlParamArray["memb_seq"] = $memb_seq;
            $returnUrlParam = addslashes(json_encode($returnUrlParamArray));                //[선택]주문완료 후 Redirect 되는 URL과 함께 전달되어야 하는 파라미터(Json 형태의 String)
            $orderMethod = "EASYPAY";                                                       //[필수]주문유형
            $orderChannel = "MOBILE";                                                       //[선택]주문채널 (default : PC/MOBILE)
            $inAppYn = "Y";                                                                 //[선택]인앱결제 여부(Y/N) (default = N)
            $individualCustomNoInputYn = "N";                                               //[선택]개인통관고유번호 입력 여부 (Y/N) (default = N)
            $payMode = "PAY1";                                                              //[선택]payMode는 선택값이나 값을 넘기지 않으면 DEFALUT 값은 PAY1 으로 셋팅되어있습니다.

            // 기타 데이터
            $extraDataArray = array();
            if ($iosYN) {
                $extraDataArray["appUrl"] = "shtestpayco://";                               //[IOS필수]IOS 인앱 결제시 ISP 모바일 등의 앱에서 결제를 처리한 뒤 복귀할 앱 URL
            }
            $extraDataArray["cancelMobileUrl"] = $AppRootPath."/payment/purchase/cancel";
            $viewOptionsArry = array();
            $viewOptionsArry["showMobileTopGnbYn"] = "N";                                   //[선택]모바일 상단 GNB 노출여부
            $viewOptionsArry["iframeYn"] = "N";                                             //[선택]Iframe 호출(모바일에서 접근하는경우 iframe 사용시 이값을 "Y"로 보내주셔야 합니다.)
            $extraDataArray["viewOptions"] = $viewOptionsArry;
            $extraData = addslashes(json_encode($extraDataArray));

        

            //설정한 주문정보로 Json String 을 작성합니다.
            $orderInfo = array();
            try {
                $orderInfo["sellerKey"] = $sellerKey;                                       //[필수]가맹점 코드
                $orderInfo["sellerOrderReferenceKey"] = $sellerOrderReferenceKey;           //[필수]외부가맹점 주문번호
                $orderInfo["sellerOrderReferenceKeyType"] = $sellerOrderReferenceKeyType;   //[선택]외부가맹점의 주문번호 타입
                $orderInfo["currency"] = $iCurrency;                                        //[선택]통화
                $orderInfo["totalPaymentAmt"] = $totalPaymentAmt;                           //[필수]총 결제금액(면세금액,과세금액,부가세의 합) totalTaxfreeAmt + totalTaxableAmt + totalVatAmt
                $orderInfo["totalTaxfreeAmt"] = $totalTaxfreeAmt;                           //[선택]면세금액(면세상품의 공급가액 합)
                $orderInfo["totalTaxableAmt"] = $totalTaxableAmt;                           //[선택]과세금액(과세상품의 공급가액 합)
                $orderInfo["totalVatAmt"] = $totalVatAmt;                                   //[선택]부가세(과세상품의 부가세 합)
                $orderInfo["orderTitle"] = $orderTitle;                                     //[선택]주문 타이틀
                $orderInfo["serviceUrl"] = $serviceUrl;                                     //[선택]주문완료 시 PAYCO에서 호출할 가맹점의 Service API의 URL
                $orderInfo["serviceUrlParam"] = $serviceUrlParam;                           //[선택]주문완료 시 호출되는 API 와 함께 전달되어야 하는 파라미터(Json 형태의 String)(PAY1)
                $orderInfo["returnUrl"] = $returnUrl;                                       //[선택]주문완료 후 Redirect 되는 URL
                $orderInfo["returnUrlParam"] = $returnUrlParam;                             //[선택]주문완료 후 Redirect 되는 URL과 함께 전달되어야 하는 파라미터(Json 형태의 String)
                // $orderInfo["nonBankbookDepositInformUrl"] = $nonBankbookDepositInformUrl;
                $orderInfo["orderMethod"] = $orderMethod;                                   //[필수]주문유형
                $orderInfo["orderChannel"] = $orderChannel;                                 //[선택]주문채널
                $orderInfo["inAppYn"] = $inAppYn;                                           //[선택]인앱결제 여부
                $orderInfo["individualCustomNoInputYn"] = $individualCustomNoInputYn;       //[선택]개인통관 고유번호 입력 여부
                $orderInfo["orderSheetUiType"] = $orderSheetUiType;                         //[선택]주문서 UI타입 선택
                $orderInfo["payMode"] = $payMode;                                           //[선택]결제모드(PAY1 : 결제인증,승인통합 / PAY2 : 결제인증,승인분리)
                $orderInfo["orderProducts"] = $productRows;                                 //[필수]주문상품 리스트
                $orderInfo["extraData"] = $extraData;                                       //[선택]부가정보 - Json 형태의 String

                $res =  payco_reserve(urldecode(stripslashes(json_encode($orderInfo))));
                
                $this->ci->dbutil->insertUpdate('iparkingCmsDb', 'iparking_cms.payco_reserve', [
                    array(
                        'ppsl_seq'           => $ppsl_seq,
                        'prdt_product_cd'    => $prdt_product_cd,
                        'memb_seq'           => $memb_seq,
                        'park_seq'           => $park_seq,
                        'bcar_seq'           => $bcar_seq,
                        'operating_cmpy_cd'  => $operating_cmpy_cd,
                        'orderInfo'          => $orderInfo,
                        'res'                => $res
                    )
                ],
                [
                    'ppsl_seq',         
                    'prdt_product_cd',  
                    'memb_seq',         
                    'park_seq',         
                    'bcar_seq',         
                    'operating_cmpy_cd',
                    'orderInfo',        
                    'res'              
                ]);

                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.payco_history', [
                    'parameter' => $orderInfo,
                    'res' => $res
                ],[
                    'idx' => $payco_history_seq
                ]);

                if(gettype($res) == 'string') {
                    $res = array(json_decode($res, true))[0]; 
                }           
                return $this->ci->phpRenderer->render($response, "../templates/reserve.phtml", $res);
            
            } catch ( Exception $e ) {
                $Error_Return				= array();
                $Error_Return["result"]		= "RESERVE_ERROR";
                $Error_Return["message"]	= $e->getMessage();
                $Error_Return["code"]		= $e->getCode();
                // Write_Log("payco_reserve.php Logical Error : Code - ".$e->getCode().", Description - ".$e->getMessage());
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = json_encode($Error_Return);
                return $response->withJson($msg);   
            }
        } catch(Exception $e) {
        }
        

    }

    public function postPaycoCallback($request, $response, $args)
    {
        //--------------------------------------------------------------------------------
        // PAYCO 주문완료시 호출되는 가맹점 SERVICE API 페이지 샘플 ( PHP EasyPAY / PAY1 )
        // payco_callback.php
        // 2016-08-31	PAYCO기술지원 <dl_payco_ts@nhnent.com>
        //--------------------------------------------------------------------------------
        

        //--------------------------------------------------------------------------------
        // 이 문서는 text/html 형태의 데이터를 반환합니다. ( OK 또는 ERROR 만 반환 )
        //--------------------------------------------------------------------------------
        // header('Content-type: text/html; charset: UTF-8');
        require("../peristalsis/PayCo/payco_config.php");

        $result_string = null;
        $result_msg = null;
        // 결제 실패체크
        $fail_check = 0;

        try {

            $params = $this->ci->util->getParams($request);

            // payco response 조립
            $result_msg = $params['message'];
            $jobType = $params['jobType'];
            $memb_seq = $params['memb_seq'];
            $code = $params['code'];
            $cp_hist_seq = $params['cp_hist_seq'];
            $icprSeq = $params['icprSeq'];
            $ppsl_seq = $params['ppsl_seq'];
            $version = $params['version'];
            $bcar_seq = $params['bcar_seq'];
            $result_code = $params['code'];

            $resp = $params['response'];
            $res = json_decode($resp, true);
            $orderProducts = $res['orderProducts'];
            $paymentDetails = $res['paymentDetails'];
            $serviceUrlParam = json_decode($res['serviceUrlParam'], true);
            $sellerOrderReferenceKey = $res['sellerOrderReferenceKey'];

            // b2b api parameter seeting
            $pay_type = 'PAYCO';

            $product_cd = (int)$params['prdt_product_cd']; 
            $prdt_seq = (int)$params['prdt_seq'];
            $park_seq = (int)$params['park_seq'];
            $pay_price = (int)$params['pay_price']; 
            $bcar_number = $params['bcar_number']; 

            $card_cd = $paymentDetails[0]['cardSettleInfo']['cardCompanyCode'];    
            $pgAdmissionYmdt = $paymentDetails[0]['pgAdmissionYmdt'];
            $appl_date = substr($pgAdmissionYmdt, 0, 8);
            
            $appl_time = substr($pgAdmissionYmdt, 8, 6);
            $appl_num = $paymentDetails[0]['cardSettleInfo']['cardAdmissionNo']; 
            $order_no = $res['sellerOrderReferenceKey']; 
            $payco_order_no = $res['orderNo'];
            $tid = $res['orderCertifyKey']; 

            // cancel시 파라메터 셋팅
            $paycoOrderNo = $res['orderNo'];
            $orderCertifyKey = $res['orderCertifyKey'];
            $cancelTotalAmt = (double)$res['totalPaymentAmt'];

            // payco history check
            $payco_history_seq = $serviceUrlParam['payco_history_seq']; 
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    * 
                FROM 
                    iparking_cms.payco_history 
                WHERE 
                    idx = :idx
            ');

            $stmt->execute(['idx' => $payco_history_seq]);

            $payco_history_info = $stmt->fetch();
            $is_callback = $payco_history_info['is_callback'];

            $cp_use_date = date('Y-m-d');
            $cp_use_time = date('H:i:s');
            $cp_use_datetime = $cp_use_date." ".$cp_use_time;
            
            if($is_callback == 0) {
                $result_string = "OK";
                // APP TO APP B2B API 통신 (페이코 결제성공 후 B2B연동)
                list($result_data, $result_code) = $this->ci->relay->AppToAppPayment($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $payco_order_no, $version);
                if($result_code == '99' || $result_code == null) {
                    // 페이코 결제했으나 연동 실패인 경우
                    $result_msg = 'success';  
                    $result_string = 'ERROR';    
                    $fail_check = 3;

                    // 페이코 취소처리
                    $cancelOrder["sellerKey"]               = $sellerKey;                           //가맹점 코드. payco_config.php 에 설정
                    $cancelOrder["orderCertifyKey"]         = $orderCertifyKey;                     //주문완료통보시 내려받은 인증값
                    $cancelOrder["cancelTotalAmt"]          = $cancelTotalAmt;                      //주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
                    $cancelOrder["orderNo"]                 = $paycoOrderNo;                                // 주문번호

                    $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                    'method' => 'cancel',
                    'parameter' => $cancelOrder,
                    'sellerOrderReferenceKey' => $sellerOrderReferenceKey
                    ]]);

                    // 페이코 취소연동
                    // list($result_data, $result_code) = $this->ci->relay->AppToAppPaymentCancel($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version);          
                    // 페이코 결제연동 실패했으므로 페이코 취소연동하지 않음
                    throw new Exception("페이코 결제연동에 실패했습니다.");
                }

                // 결제 성공 후 연동도 성공한 경우
                if($result_msg == 'success' && $result_string == 'OK') {
                
                    $totalPaymentAmt = (double)$res['totalPaymentAmt'];
                    $reservationSeq = $serviceUrlParam['reservationSeq'];
                    $version = $serviceUrlParam['version'];
                    $tot_price = (int)$serviceUrlParam['tot_price'];
                    $point_price = (int)$serviceUrlParam['point_price'];
                    $cp_price = (int)$serviceUrlParam['cp_price'];
                    $pay_price = (int)$serviceUrlParam['pay_price'];
                    $product_cd = (int)$serviceUrlParam['prdt_product_cd'];
                    $save_point = 0;
                
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.payco_history', [
                        'callbak_params' => $params,
                        'is_callback' => 1
                    ],[
                        'idx' => $payco_history_seq
                    ]);
                    
                    $reseration_data = $this->ci->point->getPointReservation($reservationSeq);
                    $reseration_data_amount = (double)$reseration_data['amount'] ?? 0;
                    $reseration_data_pointAmount = (int)$reseration_data['pointAmount'] ?? 0;
                    $reseration_data_accumulate_yn = (int)$reseration_data['accumulate_yn'] ?? 0;
                    
                    // 결제된 금액 불일치시 취소
                    if((int)$reseration_data_amount != (int)$totalPaymentAmt) {
                        // 페이코 취소처리
                        $cancelOrder["sellerKey"]               = $sellerKey;                           //가맹점 코드. payco_config.php 에 설정
                        $cancelOrder["orderCertifyKey"]         = $orderCertifyKey;                     //주문완료통보시 내려받은 인증값
                        $cancelOrder["cancelTotalAmt"]          = $cancelTotalAmt;                      //주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
                        $cancelOrder["orderNo"]                 = $paycoOrderNo;                                // 주문번호

                        $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                        'method' => 'cancel',
                        'parameter' => $cancelOrder,
                        'sellerOrderReferenceKey' => $sellerOrderReferenceKey
                        ]]);

                        // 페이코 취소연동
                        list($result_data, $result_code) = $this->ci->relay->AppToAppPaymentCancel($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version);          
                        list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm(
                            "F", $product_cd, $ppsl_seq, $tot_price, $pay_price, $cp_price, $version, $reseration_data_pointAmount
                        );  
                        throw new Exception("페이코 결제금액 오류");
                    }
                    // 페이코 성공확인 포인트 쿠폰 결제 시작
                    
                    // 포인트 금액 있을 경우
                    if($reseration_data_pointAmount > 0 || $reseration_data_accumulate_yn == 1 ) {
                        if( $fail_check == 0 ){
                            //포인트사용 api 호출 포인트사용금액이있어야  reserve_complete_yn = 1이 된다.
                            list($point_info, $point_result_code) = $this->ci->point->reservationPayment($reservationSeq);

                            // 포인트 사용 성공 후 연동도 성공
                            if($point_result_code == '00') {

                                $point_approv_no = $point_info['appl_num'];
                                $point_approv_date = $point_info['appl_date'];
                                $point_approv_time = $point_info['appl_time'];
                                $park_operate_ct = $point_info['park_operate_ct'];
                                $billing_key = $point_info['billing_key'];
                                $point_card_code = $point_info['point_card_code'];
                                $point_tid = $point_info['point_tid'];
                                $pg_site_cd = $point_info['pg_site_cd'];

                                $result_string = 'OK';

                                if($point_info['save_point'] != null || $point_info['save_point'] != ""){
                                    $save_point = $point_info['save_point'];
                                }
                                
                            } else {
                                $fail_check = 1;
                                $result_string = 'ERROR';
                                // 포인트 결제 실패시 B2B 포인트결제취소연동 API 호출하지 않음
                                // 포인트 결제 취소 연동 B2B API 
                                // $this->ci->relay->pointCardCancelReflect(
                                    // $version, $point_card_code, $product_cd, $ppsl_seq, $result_code, $result_msg, 
                                    // $use_point, $appl_date, $appl_time, $appl_num, $tid, 
                                    // $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $card_no);
                            }
                        }
                    }
                        
                    // 쿠폰 사용할 경우 , 포인트사용 실패하지않은 경우에만 진입
                    if($cp_hist_seq != "" && $fail_check == 0) {
                        // $coupon_result = $this->ci->coupon->updateUseYn($cp_hist_seq, 1);
                        // 쿠폰 사용처리
                        $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 1, $cp_use_datetime, $ppsl_seq);
                        // 쿠폰 결제 연동 B2B API
                        if($coupon_result == "OK") {
                            // 쿠폰사용처리 후 연동
                            $coupon_result = $this->ci->relay->couponReflect($product_cd, $ppsl_seq, $result_code="0000", $result_msg="성공", $cp_price, $cp_use_date, $cp_use_time, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                        }

                        if($coupon_result == "Fail") {
                            $fail_check = 2;
                            
                            $result_string = 'ERROR';
                            // 쿠폰결제연동 실패시 쿠폰 취소연동 호출하지 않아도 됨

                            // 쿠폰적용 연동 실패시 쿠폰 사용 취소
                            $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0, null,null);

                            // 포인트 결제도 했다면
                            if($reseration_data_pointAmount > 0 && $point_card_code != null || $reseration_data_accumulate_yn == 1) {
                                $point_cancel_msg = '포인트 취소';
                                // 포인트 취소
                                if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $reseration_data_pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                    $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                    $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ($point_card_code == 'GSP') {
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['approv_no'];
                                    $point_cancel_appl_date = $cancel_data['approv_date'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if($point_card_code == 'LTP') {
                                    if($reseration_data_accumulate_yn == 0){
                                        list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                        $point_cancel_appl_num = $cancel_data['aprno'];
                                        $point_cancel_appl_date = $cancel_data['aprDt'];
                                        $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                    } else if ( $reseration_data_accumulate_yn == 1){
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
                                    // $ppsl_seq, $result_code, $point_cancel_msg, $reseration_data_pointAmount, $point_approv_date, $point_approv_time, $point_approv_no, 
                                    // $point_tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $save_point);

                                    // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                    $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                    $ppsl_seq, $result_code, $point_cancel_msg, $reseration_data_pointAmount, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                    $point_tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $save_point);

                                }
                                
                            }

                        }
                    }
                }
                if($fail_check == 0){
                    $pay_status = 'S';
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm(
                        $pay_status, $product_cd, $ppsl_seq, $tot_price, $pay_price, $cp_price, $version, $reseration_data_pointAmount
                    );  
                    if($confirm_code == '99') {

                        // 페이코 취소처리
                        $cancelOrder["sellerKey"]               = $sellerKey;                           //가맹점 코드. payco_config.php 에 설정
                        $cancelOrder["orderCertifyKey"]         = $orderCertifyKey;                     //주문완료통보시 내려받은 인증값
                        $cancelOrder["cancelTotalAmt"]          = $cancelTotalAmt;                      //주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
                        $cancelOrder["orderNo"]                 = $paycoOrderNo;                                // 주문번호

                        $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                        'method' => 'cancel',
                        'parameter' => $cancelOrder,
                        'sellerOrderReferenceKey' => $sellerOrderReferenceKey
                        ]]);

                        // 페이코 취소연동
                        list($result_data, $result_code) = $this->ci->relay->AppToAppPaymentCancel($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version);          

                        if($reseration_data_pointAmount > 0 && $point_card_code != null || $reseration_data_accumulate_yn == 1) {
                            $point_cancel_msg = '포인트 취소';
                            // 포인트 취소
                            if($point_card_code == 'BLP' || $point_card_code == 'REP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->bluePointCancel($version, $memb_seq, $point_card_code, $park_seq, $park_operate_ct, $product_cd, $product_seq, $pay_price, $tot_price, $cp_price, $reseration_data_pointAmount, $payment_channel, $cp_hist_seq, $point_approv_no, $point_approv_date, $point_approv_time, $pg_site_cd);
                                $point_cancel_appl_num = $cancel_data['cancel_appl_num'];
                                $point_cancel_appl_date = $cancel_data['cancel_appl_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'GSP') {
                                list($cancel_data, $point_cancel_code) = $this->ci->point->gsPointCancel($point_approv_date, $point_approv_no, $memb_seq);
                                $point_cancel_appl_num = $cancel_data['approv_no'];
                                $point_cancel_appl_date = $cancel_data['approv_date'];
                                $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                            } else if($point_card_code == 'LTP') {
                                if($reseration_data_accumulate_yn == 0){
                                    list($cancel_data, $point_cancel_code) = $this->ci->point->LPointCancel($point_approv_no, $point_approv_date, $memb_seq);
                                    $point_cancel_appl_num = $cancel_data['aprno'];
                                    $point_cancel_appl_date = $cancel_data['aprDt'];
                                    $point_cancel_appl_time = $cancel_data['cancel_appl_time'];
                                } else if ( $reseration_data_accumulate_yn == 1){
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
                                // $ppsl_seq, $result_code, $point_cancel_msg, $reseration_data_pointAmount, $point_approv_date, $point_approv_time, $point_approv_no, 
                                // $point_tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $save_point);

                                // 포인트 취소시 취소승인번호, 날짜, 시간으로 보내도록 수정
                                $this->ci->relay->pointCardCancelReflect($version, $point_card_code, $product_cd, 
                                $ppsl_seq, $result_code, $point_cancel_msg, $reseration_data_pointAmount, $point_cancel_appl_date, $point_cancel_appl_time, $point_cancel_appl_num, 
                                $point_tid, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $billing_key, $save_point);
                            }

                        }
                        if($cp_hist_seq != "" && $cp_hist_seq != null) {
                            $coupon_result = $this->ci->coupon->updateCouponUsed($cp_hist_seq, 0,null,null);
                            // 구매완료 실패시 쿠폰취소 호출
                            $this->ci->relay->couponCancelReflect($product_cd, $product_seq, $result_code, $result_msg, $cp_price, $appl_date, $appl_time, 
                            $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);
                        }

                        list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm(
                            "F", $product_cd, $ppsl_seq, $tot_price, $pay_price, $cp_price, $version, $reseration_data_pointAmount
                        );  
                    }
                    if($confirm_code == '00') {
                        $msg = $this->ci->message->apiMessage['success'];
                    } else {
                        $msg = $this->ci->message->apiMessage['fail'];
                    }
                    echo $result_string;
                } else if ($fail_check == 1){
                    // 포인트 연동 오류시 페이코 취소
                    $cancelOrder["sellerKey"]               = $sellerKey;                           //가맹점 코드. payco_config.php 에 설정
                    $cancelOrder["orderCertifyKey"]         = $orderCertifyKey;                     //주문완료통보시 내려받은 인증값
                    $cancelOrder["cancelTotalAmt"]          = $cancelTotalAmt;                      //주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
                    $cancelOrder["orderNo"]                 = $paycoOrderNo;                                // 주문번호

                    $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                    'method' => 'cancel',
                    'parameter' => $cancelOrder,
                    'sellerOrderReferenceKey' => $sellerOrderReferenceKey
                    ]]);
                    // 페이코 취소연동
                    list($result_data, $result_code) = $this->ci->relay->AppToAppPaymentCancel($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version);          
                                        
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm(
                        "F", $product_cd, $ppsl_seq, $tot_price, $pay_price, $cp_price, $version, $reseration_data_pointAmount
                    );  
                    throw new Exception("포인트 결제 처리에 실패했습니다.");
                } else if ($fail_check == 2){
                    // 페이코 취소처리
                    $cancelOrder["sellerKey"]               = $sellerKey;                           //가맹점 코드. payco_config.php 에 설정
                    $cancelOrder["orderCertifyKey"]         = $orderCertifyKey;                     //주문완료통보시 내려받은 인증값
                    $cancelOrder["cancelTotalAmt"]          = $cancelTotalAmt;                      //주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
                    $cancelOrder["orderNo"]                 = $paycoOrderNo;                                // 주문번호

                    $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.payco_history', [[
                    'method' => 'cancel',
                    'parameter' => $cancelOrder,
                    'sellerOrderReferenceKey' => $sellerOrderReferenceKey
                    ]]);

                    // 페이코 취소연동
                    list($result_data, $result_code) = $this->ci->relay->AppToAppPaymentCancel($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $version);          
                    
                    list($confirm_result, $confirm_code) = $this->ci->relay->payConfirm(
                        "F", $product_cd, $ppsl_seq, $tot_price, $pay_price, $cp_price, $version, $reseration_data_pointAmount
                    );  
                    throw new Exception("쿠폰 결제 처리에 실패했습니다.");
                } 
            }

        } catch (Exception $e) {
            // $result_string = 'ERROR';       
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function postPayCoCancel($request, $response, $args)
    {
        try {

            //--------------------------------------------------------------------------------
            // PAYCO 주문 취소 페이지 샘플 ( PHP )
            // payco_cancel.php
            // 2015-03-25	PAYCO기술지원 <dl_payco_ts@nhnent.com>
            //--------------------------------------------------------------------------------

            //--------------------------------------------------------------------------------
            // 이 문서는 json 형태의 데이터를 반환합니다.
            //--------------------------------------------------------------------------------
            require("../peristalsis/PayCo/payco_config.php");

            $params = $this->ci->util->getParams($request);

            //---------------------------------------------------------------------------------
            // 가맹점 주문 번호로 상품 불러오기
            // DB에 연결해서 가맹점 주문 번호로 해당 상품 목록을 불러옵니다.
            //---------------------------------------------------------------------------------
            $resultValue = array();	//결과 리턴용 JSON 변수 선언

            $cancelType						= strtoupper($params["cancelType"]);					// 취소 Type 받기 - ALL 또는 PART
            $orderCertifyKey				= $params["orderCertifyKey"];							// 주문완료통보시 내려받은 인증값 
            $cancelTotalAmt					= $params["cancelTotalAmt"];							// 총 주문 금액
            $paycoOrderNo						= $params["orderNo"];									// 주문번호

            $cancelAmt						= $params["cancelAmt"];								    // 취소 상품 금액 ( PART 취소 시 )
            $requestMemo					= $params["requestMemo"];								// 취소처리 요청메모
            $sellerOrderProductReferenceKey = $params["sellerOrderProductReferenceKey"];			// 가맹점 주문 상품 연동 키 ( PART 취소 시 )
            $totalCancelTaxfreeAmt			= $params["totalCancelTaxfreeAmt"];					    // 총 취소할 면세금액
            $totalCancelTaxableAmt			= $params["totalCancelTaxableAmt"];					    // 총 취소할 과세금액
            $totalCancelVatAmt				= $params["totalCancelVatAmt"];						    // 총 취소할 부가세
            $totalCancelPossibleAmt			= $params["totalCancelPossibleAmt"];					// 총 취소가능금액(현재기준): 취소가능금액 검증
            $cancelDetailContent			= $params["cancelDetailContent"];						// 취소사유

            //-----------------------------------------------------------------------------
            // (로그) 호출 시점과 호출값을 파일에 기록합니다.
            //-----------------------------------------------------------------------------
            // Write_Log("payco_cancel.php is Called - cancelType : $cancelType , sellerOrderProductReferenceKey : $sellerOrderProductReferenceKey, cancelTotalAmt : $cancelTotalAmt, cancelAmt : $cancelAmt ,  requestMemo : $requestMemo , orderNo : $paycoOrderNo, totalCancelTaxfreeAmt : $totalCancelTaxfreeAmt, totalCancelTaxableAmt : $totalCancelTaxableAmt, totalCancelVatAmt : $totalCancelVatAmt, totalCancelPossibleAmt : $totalCancelPossibleAmt  orderCertifyKey : $orderCertifyKey");

            //---------------------------------------------------------------------------------------------------------------------
            // orderNo, cancelTotalAmt 값이 없으면 로그를 기록한 뒤 JSON 형태로 오류를 돌려주고 API를 종료합니다.
            //---------------------------------------------------------------------------------------------------------------------
            if($paycoOrderNo == ""){
                $resultValue["result"]	= "주문번호가 전달되지 않았습니다.";
                $resultValue["message"] = "orderNo is Nothing.";
                $resultValue["code"]	= 9999;		
                echo json_encode($resultValue);
                return;
            }
            if($cancelTotalAmt == ""){
                $resultValue["result"]	= "총 주문금액이 전달되지 않았습니다.";
                $resultValue["message"] = "cancelTotalAmt is Nothing.";
                $resultValue["code"]	= 9999;		
                echo json_encode($resultValue);
                return;
            }

            //----------------------------------------------------------------------------------
            // 상품정보 변수 선언 및 초기화
            //----------------------------------------------------------------------------------
            Global $cpId, $productId;

            //-----------------------------------------------------------------------------------
            // 취소 내역을 담을 JSON OBJECT를 선언합니다.
            //-----------------------------------------------------------------------------------
            $cancelOrder = array();

            //-----------------------------------------------------------------------------------
            // 전체 취소 = "ALL", 부분취소 = "PART"
            //------------------------------------------------------------------------------------
            if($cancelType == "ALL"){
                //---------------------------------------------------------------------------------
                // 파라메터로 값을 받을 경우 필요가 없는 부분이며
                // 주문 키값으로만 DB에서 데이터를 불러와야 한다면 이 부분에서 작업하세요.
                //---------------------------------------------------------------------------------

            }else if($cancelType == "PART"){ 
                //-----------------------------------------------------------------------------------------------------------------------
                // sellerOrderProductReferenceKey, cancelAmt 값이 없으면 로그를 기록한 뒤 JSON 형태로 오류를 돌려주고 API를 종료합니다.
                //-----------------------------------------------------------------------------------------------------------------------	
                if($sellerOrderProductReferenceKey == ""){
                    $resultValue["result"]	= "취소주문연동키 값이 전달되지 않았습니다.";
                    $resultValue["message"] = "sellerOrderProductReferenceKey is Nothing.";
                    $resultValue["code"]	= 9999;		
                    echo json_encode($resultValue);
                    return;
                }
                if($cancelAmt == ""){
                    $resultValue["result"]	= "취소상품 금액이 전달되지 않았습니다.";
                    $resultValue["message"] = "cancelAmt is Nothing.";
                    $resultValue["code"]	= 9999;		
                    echo json_encode($resultValue);
                    return;
                }

                //---------------------------------------------------------------------------------
                // 주문상품 데이터 불러오기
                // 파라메터로 값을 받을 경우 받은 값으로만 작업을 하면 됩니다.
                // 주문 키값으로만 DB에서 취소 상품 데이터를 불러와야 한다면 이 부분에서 작업하세요.
                //---------------------------------------------------------------------------------
                $orderProducts = array();

                //---------------------------------------------------------------------------------
                // 취소 상품값으로 읽은 변수들로 Json String 을 작성합니다.
                //---------------------------------------------------------------------------------		
                $orderProduct = array();
                $orderProduct["cpId"]							= $cpId;							// 상점 ID , payco_config.php 에 설정		
                $orderProduct["productId"]						= $productId;						// 상품 ID , payco_config.php 에 설정
                $orderProduct["productAmt"]						= $cancelAmt;						// 취소 상품 금액 ( 파라메터로 넘겨 받은 금액 - 필요서 DB에서 불러와 대입 )
                $orderProduct["sellerOrderProductReferenceKey"] = $sellerOrderProductReferenceKey;	// 취소 상품 연동 키 ( 파라메터로 넘겨 받은 값 - 필요서 DB에서 불러와 대입 )
                $orderProduct["cancelDetailContent"]			= urlencode($cancelDetailContent);	// 취소 상세 사유			

                array_push($orderProducts, $orderProduct);
            
            }else{
                //---------------------------------------------------------------------------------
                // 취소타입이 잘못되었음. ( ALL과 PART 가 아닐경우 )
                //---------------------------------------------------------------------------------			
                $resultValue["result"]	= "CANCEL_TYPE_ERROR";
                $resultValue["message"] = "취소 요청 타입이 잘못되었습니다.";
                $resultValue["code"]	= 9999;		
                echo json_encode($resultValue);
                return;
            }

            //---------------------------------------------------------------------------------
            // 설정한 주문정보 변수들로 Json String 을 작성합니다.
            //---------------------------------------------------------------------------------

            $cancelOrder["sellerKey"]				= $sellerKey;							//가맹점 코드. payco_config.php 에 설정
            $cancelOrder["orderCertifyKey"]			= $orderCertifyKey;						//주문완료통보시 내려받은 인증값
            $cancelOrder["requestMemo"]				= urlencode($requestMemo);				//취소처리 요청메모
            $cancelOrder["cancelTotalAmt"]			= $cancelTotalAmt;						//주문서의 총 금액을 입력합니다. (전체취소, 부분취소 전부다)
            $cancelOrder["orderProducts"]			= $orderProducts;						//위에서 작성한 상품목록과 배송비상품을 입력

            $cancelOrder["orderNo"]					= $paycoOrderNo;								// 주문번호
            $cancelOrder["totalCancelTaxfreeAmt"]	= $totalCancelTaxfreeAmt;				// 총 취소할 면세금액
            $cancelOrder["totalCancelTaxableAmt"]	= $totalCancelTaxableAmt;				// 총 취소할 과세금액
            $cancelOrder["totalCancelVatAmt"]		= $totalCancelVatAmt;					// 총 취소할 부가세
            $cancelOrder["totalCancelPossibleAmt"]	= $totalCancelPossibleAmt;				// 총 취소가능금액(현재기준): 취소가능금액 검증
            
            //---------------------------------------------------------------------------------
            // 주문 결제 취소 가능 여부 API 호출 ( JSON 데이터로 호출 )
            //---------------------------------------------------------------------------------
            $Result = payco_cancel(urldecode(stripslashes(json_encode($cancelOrder))));

            echo $Result;

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}