<?php

class TestController
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

        // $this->ci->settings['env']= $env;
        $sv = $this->ci->settings['env'];
        $relay_domain = $this->ci->settings['domain'][$sv];
        $this->relay_domain = $relay_domain;
    }

    public function sttest($request, $response, $args)
    {
        $A = array(1, 3, 6, 4, 1, 2);

        sort($A);

        $A = array_unique($A);
        $temp = 0;
        foreach($A as $row_a){
            $temp++;

            if($row_a <1){
                $temp=1;
            } else if ($row_a != $temp){
                break;
            }
        }

        echo $temp;
        // print_r($A);

        // return $A;
        
    }
    
    function serverIp()
    {    
        if(isset($_SERVER)){    
            if($_SERVER['SERVER_ADDR']){    
                $server_ip=$_SERVER['SERVER_ADDR'];    
            }else{    
                   $server_ip=$_SERVER['LOCAL_ADDR'];    
            }    
        }else{    
            $server_ip = getenv('SERVER_ADDR');    
        }    
          return $server_ip;    
    }    
             
    public function excelTest($request, $response, $args)
    {
        try {

            // $env = $request->getHeaderLine('env');

            // if($env == "") {
            //     $this->ci->settings['env'] = "prod";
            // }

            $stmt = $this->ci->iparkingCmsDb->prepare("
                SELECT 
                    appldate,
                    method,
                    wcc,
                    aprAkMdDc,
                    ccoAprno,
                    create_time,
                    aprno,
                    aprDt
                FROM 
                    iparking_cms.lpoint_history 
                ORDER BY create_time DESC 
            ");

            $stmt->execute($binds);
            $result = $this->ci->dbutil->fetchAllWithJson($stmt);

            $fileName = 'L포인트.xls';
            $excelData = [
                array(
                    'name' => 'L포인트',
                    'type' => 'countHistory',
                    'data' => $result,
                    'key' => ["거래일자" ,"사용용도", "wcc", "aprAkMdDc", "우리쪽 승인번호", "운영사 승인번호","주차상품명", "운영사 일자"],
                    'column' => ["appldate","method", "wcc", "aprAkMdDc" ,"ccoAprno", "create_time", "aprno", "aprDt"],
                    'size' => [13, 11, 11, 11, 11, 11, 11, 11]
                )
            ];
            
            $엑셀파일 = $this->ci->file->makeExcelDownload($fileName, $excelData);

            return $엑셀파일;          

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function excelGsTest($request, $response, $args)
    {
        try {
    
            $stmt = $this->ci->iparkingCmsDb->prepare("
                SELECT 
                    approv_date,
                    approv_no,
                    method,
                    amount,
                    pointAmount,
                    accumulateAmount,
                    appldate,
                    create_time,
                    memb_seq,
                    ppsl_seq
                FROM 
                    iparking_cms.gspoint_history 
                ORDER BY create_time DESC
            ");
    
            $stmt->execute($binds);
            $result = $this->ci->dbutil->fetchAllWithJson($stmt);

            $fileName = 'GS포인트.xls';
            $excelData = [
                array(
                    'name' => 'GS포인트',
                    'type' => 'countHistory',
                    'data' => $result,
                    'key' => ["거래일자" ,"거래번호", "사용용도", "결제금액", "포인트금액","적립금액", "결제일시","생성일시", "회원번호", "상품번호"],
                    'column' => ["approv_date","approv_no", "method", "amount" ,"pointAmount", "accumulateAmount", "appldate", "create_time","memb_seq","ppsl_seq"],
                    'size' => [13, 11, 11, 11, 11, 11, 11, 11,11,11]
                )
            ];
            
            $엑셀파일 = $this->ci->file->makeExcelDownload($fileName, $excelData);

            return $엑셀파일;          
    
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 파킹패스 취소 다이렉트 건.
    public function postTestBluePointDirectCancel($request, $response, $args)
    {

        $params = $this->ci->util->getParams($request);
        
        $memb_seq = $params['memb_seq'];
        $point_card_code = $params['point_card_code'];
        $pointAmount = $params['cancel_point'];
        $appl_num = $params['appl_num'];
        $appl_date = $params['appl_date'];
        $appl_time = $params['appl_time'];
        $park_seq = $params['park_seq'];
        $pg_site_cd = $params['pg_site_cd'];
        $version = $params['version'] ?? 'v1.0';

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

        $http_result = $this->ci->http->post(
            $url,
            $requestBody
        );

        $result = $http_result->getBody()->getContents();

        $result  = json_decode($result, true);
        $result_data = $result['resultData'];

        return $response->withJson([
            'code' => 20000,
            'result' => $result
        ]);

    }

    // GS 포인트 단독 취소건 
    public function postTestGsPointAccumulateCancel($request, $response, $args)
    {
        $params = $this->ci->util->getParams($request);
        
        $trans_date = date('Ymd');
        $trans_time = date('His');

        $chasing_no = $this->ci->point->generateGsPointUniqNo(); // 20자리 유니크한 값
        
        $approv_date = $params['approv_date'];
        $approv_no = $params['approv_no'];
        $card_no = $params['card_no'];
        $accumulateAmount = $params['accumulateAmount'];

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
                'sale_amt' => $accumulateAmount, // 매출금액
                'volunteer_amt' => '', // 봉사료(현행 값 0 처리)   
                'input_user_id' => 'PARKING_CLOUD', // 사용자ID
                'remark' => '', // 비고
                'filler' => '' // 여분필드(조회조건이 ‘06’인 경우 통합고객번호 필수)           
            ]
        ];

        $gs_url = 'https://cco.gshnpoint.com:8030/gswas/was/pointInfoJoinReserveCancel.do';

        $http_result = $this->ci->http->post(
            $gs_url,
            $requestBody
        );

        $res = $http_result->getBody()->getContents();

        $responseXml = simplexml_load_string(trim(str_replace('"', "'", $res)));

        $result = json_encode($this->ci->point->xmlToArray($responseXml));

        $result = json_decode($result, true);

        $result_gsc_was = $result['gsc-was'];

        return $response->withJson($result_gsc_was);
    }

    public function postPayCoReflect($request, $response, $args)
    {

        $params = $this->ci->util->getParams($request);

        $pay_type = 'PAYCO'; 
        $bcar_seq = '10014396'; 
        $product_cd = '2'; 
        $ppsl_seq = '169795'; 
        $result_code = '0'; 
        $result_msg = 'success'; 
        $card_cd = $params['card_cd']; 
        $pay_price = 10000; 
        $appl_date = '20190305'; 
        $appl_time = '164150'; 
        $appl_num = '46293476'; 
        $order_no = '00100979002000169795'; 
        $tid = 'SHApp7pNaZKKqdvegJvuQ7uFhO2rNA4lhsBkqXATL2Q'; 
        $memb_seq = 152442; 
        $bcar_number = '31머0458'; 
        $park_seq = 979; 
        $prdt_seq = 1692;
        $payco_order_no = '201903052297956307';
        $version = 'v1.0';
        
        $result = $this->ci->relay->AppToAppPayment($pay_type, $bcar_seq, $product_cd, $ppsl_seq, $result_code, $result_msg, $card_cd, $pay_price, $appl_date, $appl_time, $appl_num, $order_no, $tid, $memb_seq, $bcar_number, $park_seq, $prdt_seq, $payco_order_no, $version);
        
        return $response->withJson($result);
    }

    public function postTestPayConfirm($request, $response ,$args)
    {
        $params = $this->ci->util->getParams($request);
        $pay_status = 'S';
        $product_cd = 2;
        $product_seq = '169795';
        $tot_price = 10000;
        $pg_price = 10000; 
        $cp_price = 0; 
        $version = 'v1.0'; 
        $pointAmount = 0;
        $result = $this->ci->relay->payConfirm($pay_status, $product_cd, $product_seq, $tot_price, $pg_price, $cp_price, $version, $pointAmount);
        return $response->withJson($result);
    }
}