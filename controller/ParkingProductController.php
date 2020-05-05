<?php
/**
 * ParkingProductController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     주차상품관련 클랙스
 * @date      2018/05/17
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class ParkingProductController {
    
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    public function getProductInfo($request, $response, $args) 
    {
        try {
            $params = $this->ci->util->getParams($request);

            $prdt_seq = $params['prdt_seq'];    //상품고유번호
            $ppsl_vehicle_cd = $params['ppsl_vehicle_cd'];    //차종코드

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    PARK.park_seq
                    ,PARK.park_name
                    ,PARK.park_latitude
                    ,PARK.park_longitude
                    ,PRDT.prdt_seq
                    ,PRDT.prdt_name
                    ,PRDT.prdt_product_cd
                    ,codee.code_name AS vehicle
                    ,codeb.code_name AS prdt_biz_hour
                    ,codec.code_name AS prdt_sun_hol_biz_hour
                    ,coded.code_name AS prdt_sat_biz_hour
                    ,codea.code_name AS prdt_month_cd_name
                    ,PPPR.pppr_price_normal_small AS price_normal
                    ,PPPR.pppr_price_sale_small AS price_sale
                    ,PPPR.pppr_total_num_van
                    ,PPPR.pppr_total_num_small
                    ,PPPR.pppr_total_num_midsize
                    ,PPPR.pppr_total_num_mid_truck
                    ,PPPR.pppr_total_num_big_truck
                    ,PPPR.pppr_total_num_disabled
                    ,PPPR.pppr_total_num_compact
                    ,PRDT.prdt_info_desc
                    ,PRDT.prdt_description
                    ,PRDT.prdt_month_cd	
                    ,PRDT.prdt_list_order
                    ,PRDT.prdt_hour_time
                    ,PRDT.prdt_day_time
                FROM
                    fdk_parkingcloud.acd_rpms_parking_product PRDT
                    INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON PRDT.park_seq = PARK.park_seq
                    INNER JOIN fdk_parkingcloud.acd_rpms_parking_product_price PPPR ON PRDT.prdt_seq = PPPR.prdt_seq AND PPPR.pppr_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_core_code codee USE INDEX (fk_arf_code_arf_codegroup1_idx) ON codee.code_cd = :ppsl_vehicle_cd AND codee.cogr_cd = 116
                    LEFT JOIN fdk_parkingcloud.arf_core_code codea USE INDEX (fk_arf_code_arf_codegroup1_idx) ON PRDT.prdt_month_cd = codea.code_cd AND codea.cogr_cd = 172             
                    LEFT JOIN fdk_parkingcloud.arf_core_code codeb USE INDEX (fk_arf_code_arf_codegroup1_idx) ON PRDT.prdt_biz_hour_cd = codeb.code_cd AND codeb.cogr_cd = 103             
                    LEFT JOIN fdk_parkingcloud.arf_core_code codec USE INDEX (fk_arf_code_arf_codegroup1_idx) ON PRDT.prdt_sun_hol_biz_hour_cd = codec.code_cd AND codec.cogr_cd = 103             
                    LEFT JOIN fdk_parkingcloud.arf_core_code coded USE INDEX (fk_arf_code_arf_codegroup1_idx) ON PRDT.prdt_sat_biz_hour_cd = coded.code_cd AND coded.cogr_cd = 103
                WHERE
                    PRDT.prdt_del_ny=0
                    AND PRDT.prdt_seq = :prdt_seq
                ORDER BY
                    PPPR.pppr_seq DESC
                LIMIT 1
            ');
            $stmt->execute(['ppsl_vehicle_cd' => $ppsl_vehicle_cd, 'prdt_seq' => $prdt_seq]);
            $result = $stmt->fetch();


            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $result;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = json_decode('{}');
            }

            return $response->withJson($msg);
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            // $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    /**
     * getMyWaitingList function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     주차권 구매내역 정기권 대기리스트 가져오는 함수
     * @date      2018/05/17
     * @see       기존 api정보 : /iparking/myWaitingList.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getMyWaitingList($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);
            $memb_seq = $params['memb_seq'];

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT	/* app-mp.xml myWaitingList */
                *
                FROM (
                    SELECT
                        PARK.park_name
                        , CAR.bcar_num AS car_name
                        , PRDT.prdt_name AS productName
                        , MPW.mpw_seq
                        , getCode(PRDT.prdt_month_cd, 172) AS day_type
                        , MPW.mpw_appl_cd AS mpw_appl_cd
                        , getCode(MPW.mpw_appl_cd, 3014) AS mpw_appl_cd_name
                        , MPW.mpw_vehicle_cd
                        , getCode(MPW.mpw_vehicle_cd, 116) AS mpw_vehicle_cd_name
                        , MPW.mpw_order AS waiting_rank
                        , MPW.mpw_reg_datetime
                    FROM month_product_wait MPW
                        INNER JOIN acd_pms_parkinglot PARK
                            ON MPW.park_seq = PARK.park_seq
                            AND PARK.park_del_ny = 0
                        INNER JOIN arf_b2ccore_car CAR
                            ON MPW.bcar_seq = CAR.bcar_seq
                            AND CAR.bcar_del_ny = 0
                        INNER JOIN acd_rpms_parking_product PRDT
                            ON MPW.mpw_prdt_seq = PRDT.prdt_seq
                            AND PRDT.prdt_del_ny = 0
                    WHERE MPW.memb_seq = :memb_seq
                        AND MPW.mpw_appl_cd = 1
            
                    UNION ALL
            
                    SELECT
                        PARK.park_name
                        , CAR.bcar_num AS car_name
                        , PRDT.prdt_name AS productName
                        , MPW.mpw_seq
                        , getCode(PRDT.prdt_month_cd, 172) AS day_type
                        , MPW.mpw_appl_cd AS mpw_appl_cd
                        , getCode(MPW.mpw_appl_cd, 3014) AS mpw_appl_cd_name
                        , MPW.mpw_vehicle_cd
                        , getCode(MPW.mpw_vehicle_cd, 116) AS mpw_vehicle_cd_name
                        , -1 AS waiting_rank
                        , MPW.mpw_reg_datetime
                    FROM month_product_wait MPW
                        INNER JOIN acd_pms_parkinglot PARK
                            ON MPW.park_seq = PARK.park_seq
                            AND PARK.park_del_ny = 0
                        INNER JOIN arf_b2ccore_car CAR
                            ON MPW.bcar_seq = CAR.bcar_seq
                            AND CAR.bcar_del_ny = 0
                        INNER JOIN acd_rpms_parking_product PRDT
                            ON MPW.mpw_prdt_seq = PRDT.prdt_seq
                            AND PRDT.prdt_del_ny = 0
                    WHERE MPW.memb_seq = :memb_seq
                        AND MPW.mpw_appl_cd = 2
                        AND MPW.mpw_mod_datetime >= SUBDATE(sysdate(), INTERVAL 1 MONTH)
                ) AS WAITING_LIST
                ORDER BY WAITING_LIST.mpw_reg_datetime DESC
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
            $totalCnt = $stmt->fetch();
            
            $msg['result'] = $this->ci->message->oldMessage['success'];
            $msg['result']['total'] = $totalCnt['totalCount'];
            $msg['desc']['items'] = $result;

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getMyList($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);
            $thisPage = $params['parkingProductItem.thisPage'];
            $listRows = $params['parkingProductItem.listRows'];
            $onPaging = $params['parkingProductItem.onPaging'];
            $ppsl_buyer_seq = $params['parkingProductItem.ppsl_buyer_seq'];
            
            $select = "";
            if ($onPaging) {
                $select = "SELECT a.* FROM ( SELECT a.*, @NO:=@NO+1 AS RNUM FROM (";
            }
    
            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT 
                    count(*) AS totalCount
                FROM 
                    fdk_parkingcloud.acd_rpms_parking_product_sales PPSL
                    LEFT JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON PPSL.park_seq = PARK.park_seq AND PARK.park_del_ny=0
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON PPSL.ppsl_car_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON PPSL.ppsl_buyer_seq = MEMB.memb_seq
                    LEFT JOIN fdk_parkingcloud.inicis_payment_result IPAY ON PPSL.ppsl_seq = IPAY.icpr_product_seq AND IPAY.icpr_product_cd = 2 AND IPAY.pg_cd NOT IN(7, 10, 11)
                    LEFT JOIN fdk_parkingcloud.alliance_point alpo ON alpo.alpo_icpr_seq = IPAY.icpr_seq AND alpo.alpo_cancel_ny = 0 
                WHERE 
                    (IFNULL(PPSL.ppsl_parent_seq,0)=0 OR IFNULL(PPSL.ppsl_pay_cd,0) != 0)
                    AND PPSL.ppsl_buyer_seq = :memb_seq
                    AND PPSL.ppsl_del_ny = '0'
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
            $totalCnt = $stmt->fetch();

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT a.* FROM ( SELECT a.*, @NO:=@NO+1 AS RNUM FROM (

                    SELECT		/* fdkrpmsparkingproduct-mp.xml		getParkingProductListData */
                        PPSL.ppsl_seq
                        ,PPSL.ppsl_upper_seq
                        ,PPSL.ppsl_parent_seq
                        ,PPSL.ppsl_extend_ny
                        ,PPSL.ppsl_buyer_type_cd
                        ,PPSL.ppsl_pay_cd
                        ,PPSL.ppsl_buyer_seq
                        ,PPSL.ppsl_mobile_1
                        ,PPSL.ppsl_mobile_2
                        ,PPSL.ppsl_mobile_3
                        ,PPSL.ppsl_email_id
                        ,PPSL.ppsl_email_domain
                        ,PPSL.ppsl_car_brand
                        ,PPSL.ppsl_model_name
                        ,PPSL.ppsl_car_seq
                        ,PPSL.ppsl_car_number
                        ,PPSL.ppsl_vehicle_cd
                        ,PPSL.ppsl_start_datetime
                        ,PPSL.ppsl_end_datetime
                        ,PPSL.ppsl_ticket_use_ny
                        ,ifnull(alpo.alpo_use_point,0) as alpo_use_point
                        ,(
                            SELECT 
                                BCAR.bcar_num 
                            FROM 
                                arf_b2ccore_car BCAR 
                            WHERE 
                                PPSL.ppsl_car_seq = BCAR.bcar_seq
                        ) AS bcar_num
                        ,CASE WHEN 0 > DATEDIFF(PPSL.ppsl_start_datetime, sysdate())
                            AND IFNULL(PPSL.ppsl_pay_cd,0) = 0
                            THEN 0
                            ELSE 1
                            END AS prdt_availablePay_ny
                        ,CASE
                        WHEN ((0   <=   DATEDIFF(PPSL.ppsl_end_datetime, sysdate())
                        AND DATEDIFF(PPSL.ppsl_end_datetime, sysdate())   <=   7)
                        AND IFNULL(PPSL.ppsl_pay_cd,0) NOT IN (0,10)
                        AND IFNULL(ppsl_extend_ny,0) = 0)
                        AND ( (
                        SELECT
                        COUNT(*)
                        FROM
                        acd_rpms_parking_product_sales PPSL2
                        INNER JOIN arf_b2ccore_car BCAR2
                        ON PPSL2.ppsl_car_seq = BCAR2.bcar_seq
                        AND BCAR2.bcar_del_ny = 0
                        INNER JOIN acd_rpms_parking_product PRDT2
                        ON PPSL2.prdt_seq = PRDT2.cmpy_seq
                        AND PRDT2.prdt_del_ny = 0
                        WHERE
                        PPSL2.ppsl_del_ny = 0
                        AND IFNULL(PPSL2.ppsl_pay_cd,0) NOT IN (0,10)
                        AND PPSL.park_seq = PPSL2.park_seq
                        AND BCAR.bcar_num = BCAR2.bcar_num
                        AND PPSL.ppsl_end_datetime    <=    PPSL2.ppsl_start_datetime
                        )    <    1 )
                        THEN 1
                        ELSE 0
                        END AS prdt_extend_ny

                        ,PPSL.ppsl_price
                        ,(
                        SELECT
                        SUM(PPSL.ppsl_price)
                        FROM
                        acd_rpms_parking_product_sales PPSL
                        LEFT JOIN acd_pms_parkinglot PARK
                        ON PPSL.park_seq = PARK.park_seq
                        AND PARK.park_del_ny=0
                        LEFT JOIN acd_rpms_parking_product PRDT
                        ON PPSL.prdt_seq = PRDT.prdt_seq
                        AND PRDT.prdt_del_ny = 0
                        LEFT JOIN arf_b2ccore_car BCAR
                        ON PPSL.ppsl_car_seq = BCAR.bcar_seq
                        AND BCAR.bcar_del_ny = 0
                        LEFT JOIN arf_b2ccore_member MEMB
                        ON PPSL.ppsl_buyer_seq = MEMB.memb_seq
                        WHERE
                        (IFNULL(PPSL.ppsl_parent_seq,0)=0 OR IFNULL(PPSL.ppsl_pay_cd,0) != 0)
                        AND PPSL.ppsl_buyer_seq = 2925
                        AND PPSL.ppsl_del_ny = '0'
                        ) as ppsl_total_price
                        ,PPSL.ppsl_reg_ip
                        ,PPSL.ppsl_reg_seq
                        ,PPSL.ppsl_reg_device_cd
                        ,PPSL.ppsl_reg_datetime
                        ,PPSL.ppsl_mod_ip
                        ,PPSL.ppsl_mod_seq
                        ,PPSL.ppsl_mod_device_cd
                        ,PPSL.ppsl_mod_datetime
                        ,PPSL.ppsl_operating_cmpy_cd
                        ,PPSL.ppsl_del_ny
                        ,PARK.park_seq
                        ,PARK.park_name
                        ,PARK.park_pacl_name
                        ,PRDT.prdt_seq
                        ,PRDT.prdt_name
                        ,PRDT.prdt_product_cd
                        ,PRDT.prdt_division_cd
                        ,PRDT.prdt_hour_time
                        ,PRDT.prdt_day_time
                        ,PRDT.prdt_month_cd
                        ,getCode(PRDT.prdt_month_cd, 172) AS prdt_month_cd_name
                        ,MEMB.memb_name as ppsl_reg_name
                        ,getCode(PPSL.ppsl_vehicle_cd,116) AS ppsl_vehicle
                        ,(
                        SELECT
                        CONCAT(atch_dir,'/', atch_save_name)
                        FROM
                        acd_pms_parkinglot_attach
                        WHERE
                        atch_parents_seq = PARK.park_seq
                        AND atch_group_num = 5
                        AND atch_del_ny = 0
                        ORDER BY atch_seq
                        DESC limit 1
                        ) as atch_dir
                        ,(SELECT COUNT(*) FROM acd_rpms_parkinglot_favorite WHERE pafv_parkinglot_seq = PARK.park_seq AND memb_seq = 2925 AND pafv_del_ny = 0) AS pafv_ny
                        ,PRDT.prdt_del_ny
                    FROM
                        acd_rpms_parking_product_sales PPSL
                        LEFT JOIN acd_pms_parkinglot PARK ON PPSL.park_seq = PARK.park_seq AND PARK.park_del_ny=0
                        LEFT JOIN acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                        LEFT JOIN arf_b2ccore_car BCAR ON PPSL.ppsl_car_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
                        LEFT JOIN arf_b2ccore_member MEMB ON PPSL.ppsl_buyer_seq = MEMB.memb_seq
                        LEFT JOIN inicis_payment_result IPAY ON PPSL.ppsl_seq = IPAY.icpr_product_seq AND IPAY.icpr_product_cd = 2 AND IPAY.pg_cd NOT IN(7, 10, 11)
                        LEFT JOIN alliance_point alpo ON alpo.alpo_icpr_seq = IPAY.icpr_seq AND alpo.alpo_cancel_ny = 0 
                    WHERE 
                        (IFNULL(PPSL.ppsl_parent_seq,0)=0 OR IFNULL(PPSL.ppsl_pay_cd,0) != 0)
                        AND PPSL.ppsl_buyer_seq = 2925
                        AND PPSL.ppsl_del_ny = '0'
                    ORDER BY PPSL.ppsl_reg_datetime DESC
                ) a ,( SELECT @NO := 0 ) B ) a WHERE a.rnum > 0 AND a.rnum <= 10000
            ");
            
            $msg['result'] = $this->ci->message->oldMessage['success'];
            $msg['result']['total'] = $totalCnt['totalCount'];
            $msg['desc']['items'] = $result;

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
    // Inicis 결제 내역 리스트
    public function getPaymentInicis ($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 50,
                'offset' => 0
            ]);

            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'] ?? 'DESC';
            $orderByColumn = $params['orderByColumn'] ?? 'ApplDateTime';
            $queryOrderBy = $orderByColumn.' '.$orderBy;
            $search_category = $params['search_category']; 
            //prdt_name(상품명) prdt_product_cd(상품코드): memb_name(주문자명) bcar_number(차량번호) moid(구매번호) park_name(주차장명) ApplNum (승인번호)
            $search_word = $params['search_word'];
            $search_date_category = $params['search_date_category']; // use_start (사용시작일), use_end (사용종료일),  payment (결제일)
            $start_date = $params['start_date'];
            $end_date = $params['end_date']; 
            $now = date('Y-m-d');
            
            /*
                상세 검색 * 전체클릭시 모든 체크박스

                *기간검색           (결제일/사용시작일/사용종료일) 
                *검색어             (상품명/상품코드/주문자명/차량번호/구매번호/주차장명/승인번호)
                *결제수단(array)    (전체/신용카드/PAYCO/신한카드포인트[?]/삼성카드포인트[?]/L.Point/GS&Point/블루멤버스/쿠폰) 
                    CA_ // 카드              CA_1(신용카드), CA_13(PAYCO)
                    CP_ // 카드포인트        CP_SHP, CP_SSP
                    PO_ // 포인트            PO_LTP, PO_GSP, PO_BLP
                    CU_ // 쿠폰              CU_C

                *구매채널(array)    (전체/파킹APP[3]/파킹WEB[2]/MOST[?])
                *결제PG             (전체/KCP[?]/PAYCO[13]/이니시스[?])
                *주차장 
                    1) 운영사 : DB에 등록된 운영사 정보 표시
                    2) 주차장 : 운영사별 주차장 정보 표시
                *구매유형(array)    (전체/정기권[1]/시간권[5]/일일권[2]/발렛[?])
                *구매상태(array)    (전체/결제[0]/취소[10]/완료[1,13])
                *결제금액           (min_price - max_price)    */



            $payment_method = $params['payment_method']; // 결제수단
            $payment_channel = $params['payment_channel']; // 결제채널
            $payment_pg = $params['payment_pg']; // 결제PG
            $payment_type = $params['payment_type']; // 구매유형
            $payment_state = $params['payment_state']; // 결제상태

            $aocc_oper_code = $params['aocc_oper_code'] ?? 1; // 운영사별 주차장 정보
            $aocc_park_name  = $params['aocc_park_name']; // 주차장 정보 

            $min_price = $params['min_price']; // 최소결제금액
            $max_price = $params['max_price']; // 최대결제금액


            $where = '';
            $binds = [];
            $where_cut_date_ppsl = '';
            $where_cut_date_inot = '';

            if(empty($start_date) || empty($end_date) || empty($search_date_category)){ 
                $now = date('Ymd');
                $where_cut_date_ppsl = " AND IPAY.ApplDate = ".$now;
                $where_cut_date_inot = " AND IPAY.ApplDate = ".$now;
            } else if(!empty($start_date) && !empty($end_date) && !empty($search_date_category)){
                if($search_date_category == 'payment'){ // 결제일시 
                    $start_date =  str_replace('-','',$start_date);
                    $end_date =  str_replace('-','',$end_date);
                    $where_cut_date_ppsl = " AND IPAY.ApplDate between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND IPAY.ApplDate between '".$start_date."' and '".$end_date."'";
                } else if($search_date_category == 'use_start'){ // 사용시작일 
                    $start_date = $start_date.' 00:00:00'; 
                    $end_date = $end_date.' 23:59:59'; 
                    $where_cut_date_ppsl = " AND PPSL.ppsl_start_datetime between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND INOT.inot_enter_datetime between '".$start_date ."' and '".$end_date."'";
                } else if($search_date_category == 'use_end'){ // 사용종료일   
                    $start_date = $start_date.' 00:00:00'; 
                    $end_date = $end_date.' 23:59:59'; 
                    $where_cut_date_ppsl = " AND PPSL.ppsl_end_datetime between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND INOT.inot_exit_datetime between'".$start_date ."' and '".$end_date."'";
                }
            }
            
            if(!empty($search_category) && !empty($search_word)){ // 기획팀과 의논 후 like 인지 = 인지 정하기 (속도차이)
                if ($search_category == 'prdt_name'){
                    $where .= " AND PRDT.prdt_name like '%".$search_word."%'";
                } else if ($search_category == 'memb_name'){
                    $where .= " AND MEMB.memb_name = '".$search_word."'";
                } else if ($search_category == 'bcar_number'){
                    $where .= " AND IPAY.bcar_number ='".$search_word."'";
                } else if ($search_category == 'moid'){
                    $where .= " AND IPAY.MOID ='".$search_word."'";
                } else if ($search_category == 'ApplNum'){
                    $where .= " AND IPAY.ApplNum = '".$search_word."'";
                } else if ($search_category == 'prdt_product_cd'){
                    $where .= " AND PRDT.prdt_product_cd = ".$search_word;
                } else if ($search_category == 'point_appl_num'){
                    $where .= " AND PPRE.ppre_appl_num = ".$search_word;
                }
            }
            //////////////////////////////////////////////////////////////////////// 상세 검색 ///////////////////////////////////////////////////////////////////////////
            // 결제수단  
            if(!empty($payment_method)){
                foreach($payment_method as $payment_method_list){
                    $method_type = substr($payment_method_list,0,2);
                    if(!empty($method_type == 'CA')){ 
                        $card_array[] = substr($payment_method_list,3);
                    }
                    if(!empty($method_type == 'PO')){ 
                        $point_array[] = substr($payment_method_list,3);
                    }
                    if(!empty($method_type == 'CU')){
                        $coupon_array[] = substr($payment_method_list,3);
                    }
                }
                $where .=  ' AND (';
                if(!empty($card_array)){
                    if(!in_array("KCP",$card_array) && in_array("PAYCO",$card_array)){
                        $where .= " IPAY.pg_cd = 13 AND IPAY.Totprice > 0";
                    }
                    if(in_array("KCP",$card_array) && !in_array("PAYCO",$card_array)){
                        $where .= " IPAY.pg_cd != 13 AND IPAY.Totprice > 0";
                    }
                    if(in_array("KCP",$card_array) && in_array("PAYCO",$card_array)){
                        $where .= " IPAY.Totprice > 0";
                    }
                }
                if(!empty($point_array)){
                    if(!empty($card_array)) $where .=  " OR ";
                    $where .= "PPRE.ppre_point_cd in (".$this->ci->dbutil->arrayToInQuery($point_array).")";
                }  
                if(!empty($coupon_array)){  
                    if(!empty($card_array) || !empty($point_array)) $where .=  ' OR ';
                    $where .= 'COUP.price > 0';
                }
                $where .= ')';  
            }
            
            // 구매유형
            if(!empty($payment_type)){
                $where .= " AND (";
                if(in_array('9',$payment_type)){ // 시간주차 선택시
                    $where .= 'IPAY.icpr_product_cd = 5 ';
                }
                $where .= ' PRDT.prdt_product_cd in ('.$this->ci->dbutil->arrayToInQuery($payment_type).')'; // 
                $where .= ")";  
                $where = str_replace("  ", " OR ", $where);   
            }


            // 구매상태
            if(!empty($payment_state)){
                $where .= ' AND (';
                if(in_array('0', $payment_state)){      // 결제상태                 
                    $where .= ' ( PMCC.cancel_price IS NULL AND PTCC.ppca_cancel_point IS NULL) ';
                }
                if(in_array('1', $payment_state)){      // 전체취소 (PG결제취소,포인트 결제된 경우 포인트도 취소)
                    $where .=  " ((IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point) OR (PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price) OR (IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point)) ";
                }
                if(in_array('2', $payment_state)){      // 부분취소 (PG결제취소,포인트결제취소 X)
                    $where .=  " ((IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1) OR (IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL))";
                }
                $where = str_replace("  ", " OR ", $where);         
                $where .= ')';
            }
            
      

            // 결제PG
            if(!empty($payment_pg) && $payment_pg != 'ALL'){
                // KCP = 6 : 4,5,6,8
                // 이니시스 = 1
                // PAYCO = 13 
                if($payment_pg == '6'){
                    $where .= ' AND IPAY.pg_cd IN (4,5,6,8)';
                }else {
                    $where .= " AND IPAY.pg_cd = ".$payment_pg;
                }
            }

            if(isset($min_price) && isset($max_price)){
                         if($min_price == null || $min_price == '') $min_price = 0;
                if($max_price == null || $max_price == '') $max_price = 999999999;
                $where .= " AND convert(IPAY.TotPrice,UNSIGNED) between ".$min_price." AND ".$max_price;
            }


            // 운영사 정보 aocc_oper_code
            // 운영사 별 주차장 정보 aocc_park_name
            if(!empty($aocc_oper_code) && !empty($aocc_park_name)){ 
                if($aocc_oper_code == 1){
                    $aocc_oper_code = 'in (0,1)';
                }else{
                    $aocc_oper_code = '= '.$aocc_oper_code;
                }
                $where .= " AND IPAY.icpr_operating_cmpy_cd ".$aocc_oper_code." AND PARK.park_name like '%".$aocc_park_name."%'";
            }
    
            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT 
                    IFNULL(sum(TotPrice),0) AS sum_TotPrice, IFNULL(sum(cancel_price),0) AS sum_cancel_price
                FROM ( 
                    SELECT 
                        IFNULL(IPAY.TotPrice,0) AS TotPrice,
                        IFNULL(PMCC.cancel_price,0) AS cancel_price
                    FROM  fdk_parkingcloud.inicis_payment_result IPAY
                    INNER JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq AND IPAY.icpr_product_cd = 2 AND IPAY.pg_cd NOT IN(7, 10, 11) ".$where_cut_date_ppsl."
                    INNER JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                    INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON PPSL.park_seq = PARK.park_seq AND PARK.park_del_ny =0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON PPSL.ppsl_car_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                    LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 2
                    LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                    LEFT JOIN fdk_parkingcloud.payment_cancel PMCC on PMCC.icpr_seq = IPAY.icpr_seq AND PMCC.icpr_seq 
                    LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                    WHERE 1 = 1  
                    ".$where."
                UNION ALL           
                    SELECT 
                        IFNULL(IPAY.TotPrice,0) AS TotPrice,
                        IFNULL(PMCC.cancel_price,0) AS cancel_price
                    FROM  fdk_parkingcloud.inicis_payment_result IPAY  
                    INNER JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq AND IPAY.icpr_product_cd = 5 AND (INOT.inot_local_pay_machine_cd = 0 OR INOT.inot_local_pay_machine_cd IS NULL)
                    ".$where_cut_date_inot."
                    INNER JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                    INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.payment_cancel PMCC on PMCC.icpr_seq = IPAY.icpr_seq
                    LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 5 
                    LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON (IPAY.ApplDate = COUP.apply_date) AND COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq
                    LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                    WHERE 1 = 1  
                    ".$where."
                ) b 
            "); 

            $stmt->execute($binds);
            $total_point = $stmt->fetch(); 

            $TotPrice = $total_point['sum_TotPrice']; 
            $cancel_price = $total_point['sum_cancel_price'];
            $total_use_price = $TotPrice - $cancel_price;
    
        
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCloudDb',
                'select' => " 
                    idx,
                    code_name,
                    moid,
                    ApplDateTime,
                    ppsl_start_datetime,
                    ppsl_end_datetime,
                    parking_type,
                    prdt_name,
                    park_name,
                    REPLACE(memb_name, substr(memb_name,2,1), '*') AS memb_name,
                    bcar_number,
                    ApplNum,
                    prht_discount_price,
                    ppre_use_point,
                    cp_price,
                    pay_method,
                    TotPrice,
                    product_price,
                    payment_state,
                    point_code,
                    ppre_save_point
                ",
                'query' => "
                    SELECT 
                        %%
                        FROM (
                            SELECT 
                            IPAY.icpr_seq AS idx,
                            (   
                                CASE
                                    WHEN PPSL.ppsl_reg_device_cd = 1 THEN '기타' 
                                    WHEN PPSL.ppsl_reg_device_cd in (3,9) THEN '파킹 APP' 
                                    WHEN PPSL.ppsl_reg_device_cd = 2 THEN '파킹 WEB' 
                                    ELSE '' 
                                END  
                            ) AS code_name,
                            IPAY.MOID AS moid,
                            concat(
                                substr(IPAY.ApplDate,1,4),'-',substr(IPAY.ApplDate,5,2),'-',substr(IPAY.ApplDate,7,2),' '
                                ,substr(IPAY.ApplTime,1,2),':',substr(IPAY.ApplTime,3,2),':',substr(IPAY.ApplTime,5,2)
                            ) AS ApplDateTime ,
                            DATE_FORMAT(PPSL.ppsl_start_datetime,'%Y-%m-%d') AS ppsl_start_datetime,
                            DATE_FORMAT(PPSL.ppsl_end_datetime,'%Y-%m-%d') AS ppsl_end_datetime,
                            (
                                CASE 
                                    WHEN PRDT.prdt_product_cd = 1 THEN '정기' 
                                    WHEN PRDT.prdt_product_cd = 2 THEN '일일' 
                                    WHEN PRDT.prdt_product_cd  = 5 THEN '시간' 
                                    ELSE '' 
                                END
                            ) AS parking_type,
                            PRDT.prdt_name AS prdt_name,
                            PARK.park_name AS park_name,
                            MEMB.memb_name,
                            PPSL.ppsl_car_number AS bcar_number,
                            IPAY.ApplNum AS ApplNum,
                            IFNULL(SALE.prht_discount_price, 0) AS prht_discount_price,
                            ( 
                                CASE 
                                    WHEN PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN CONCAT('-',PPRE.ppre_use_point)
                                    ELSE IFNULL(PPRE.ppre_use_point,0)
                                END 
                            ) AS ppre_use_point,
                            ( 
                                CASE 
                                    WHEN COUP.price >= 1 AND PMCC.cancel_price >= 1 THEN CONCAT('-',COUP.price)
                                    ELSE IFNULL(COUP.price,0)
                                END
                            ) AS cp_price,
                            (   
                                CASE 
                                    WHEN IPAY.pg_cd  = 13 AND IPAY.TotPrice >= 1 THEN 'PAYCO'
                                    WHEN IPAY.pg_cd  != 13 AND IPAY.TotPrice >= 1 THEN 'KCP'
                                    ELSE null 
                                END
                            ) AS pay_method,
                            ( 
                                CASE 
                                    WHEN PMCC.cancel_price >= 1 THEN CONCAT('-',IPAY.Totprice)
                                    ELSE IFNULL(IPAY.Totprice,0)
                                END
                            ) AS TotPrice,
                            (
                                CASE
                                    WHEN BCAR.bcar_vehicle_cd = 1 THEN PPPR.pppr_price_sale_small
                                    WHEN BCAR.bcar_vehicle_cd = 2 THEN PPPR.pppr_price_sale_midsize
                                    WHEN BCAR.bcar_vehicle_cd = 3 THEN PPPR.pppr_price_sale_van
                                    WHEN BCAR.bcar_vehicle_cd = 4 THEN PPPR.pppr_price_normal_mid_truck
                                    WHEN BCAR.bcar_vehicle_cd = 5 THEN PPPR.pppr_price_normal_big_truck
                                    ELSE null
                                END 
                            ) AS product_price,
                            (
                                CASE 
                                    WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '부분취소'
                                    WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                                    WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '전체취소' 
                                    WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '전체취소' 
                                    WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '전체취소'
                                    WHEN PMCC.cancel_price IS NULL AND  PTCC.ppca_cancel_point IS NULL THEN '결제완료'
                                    ELSE ''
                                END
                            ) AS payment_state,
                            PPRE.ppre_point_cd AS point_code,
                            PRDT.prdt_product_cd AS prdt_product_cd,
                            IPAY.pg_cd AS pg_cd, 
                            IPAY.icpr_operating_cmpy_cd AS aocc_oper_cd,
                            (
                                CASE
                                    WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                                    ELSE IFNULL(PPRE.ppre_save_point,0)
                                END
                            ) AS ppre_save_point
                        FROM  fdk_parkingcloud.inicis_payment_result IPAY
                        INNER JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq AND IPAY.icpr_product_cd = 2 AND IPAY.pg_cd NOT IN(7, 10, 11) 
                        ".$where_cut_date_ppsl."
                        INNER JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                        INNER JOIN (
                            SELECT *
                            FROM (
                                SELECT a.*
                                    ,(CASE @v_prdt_seq WHEN a.prdt_seq THEN @rownum:=@rownum+1 ELSE @rownum:=1 END) rnum
                                    ,(@v_prdt_seq:=a.prdt_seq) v_prdt_seq
                                FROM  fdk_parkingcloud.acd_rpms_parking_product_price a, (SELECT @v_prdt_seq:='', @rownum:=0 FROM DUAL) b
                                WHERE pppr_del_ny = 0
                                ORDER BY a.prdt_seq desc, pppr_seq desc
                                ) c
                            WHERE rnum = 1 
                        ) PPPR ON PRDT.prdt_seq = PPPR.prdt_seq AND PPPR.pppr_del_ny=0
                        INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON PPSL.park_seq = PARK.park_seq AND PARK.park_del_ny =0
                        LEFT JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON PPSL.ppsl_car_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
                        LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON PPSL.ppsl_buyer_seq = MEMB.memb_seq
                        LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 2
                        LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON (IPAY.ApplDate = COUP.apply_date) AND COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                        LEFT JOIN fdk_parkingcloud.card_name_info CARD ON CARD.card_code = IPAY.CARD_Code
                        LEFT JOIN fdk_parkingcloud.product_sale_history SALE on PPSL.ppsl_seq = SALE.prht_ppsl_seq AND SALE.prht_type_cd = 1
                        LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                        LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1 
                        LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq WHERE 1 = 1   
                        ".$where."
                    UNION ALL          
                        SELECT 
                            IPAY.icpr_seq AS idx,
                            '파킹 APP' AS code_name,
                            IPAY.MOID AS moid,
                            concat(
                                substr(IPAY.ApplDate,1,4),'-',substr(IPAY.ApplDate,5,2),'-',substr(IPAY.ApplDate,7,2),' '
                                ,substr(IPAY.ApplTime,1,2),':',substr(IPAY.ApplTime,3,2),':',substr(IPAY.ApplTime,5,2)
                            ) AS ApplDateTime,
                            DATE_FORMAT(INOT.inot_enter_datetime,'%Y-%m-%d') AS ppsl_start_datetime,
                            DATE_FORMAT(INOT.inot_exit_datetime,'%Y-%m-%d') AS ppsl_end_datetime,
                            '시간주차' AS parking_type,
                            CONCAT(IFNULL(IFNULL(inot_duration,TIMESTAMPDIFF(minute, inot_enter_datetime, inot_exit_datetime)),0),'분') AS prdt_name,
                            PARK.park_name AS park_name,
                            MEMB.memb_name ,
                            IPAY.bcar_number AS bcar_number,				
                            IPAY.ApplNum AS ApplNum,
                            IFNULL(inot_discount_price,0) AS prht_discount_price,
                            ( 
                                CASE 
                                    WHEN PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN CONCAT('-',PPRE.ppre_use_point)
                                    ELSE IFNULL(PPRE.ppre_use_point,0)
                                END 
                            ) AS ppre_use_point,
                            ( 
                                CASE 
                                    WHEN COUP.price >= 1 AND PMCC.cancel_price >= 1 THEN CONCAT('-',COUP.price)
                                    ELSE IFNULL(COUP.price,0)
                                END
                            ) AS cp_price,
                            (   
                            CASE 
                                WHEN IPAY.pg_cd  = 13 AND IPAY.TotPrice >= 1 THEN 'PAYCO'
                                WHEN IPAY.pg_cd  != 13 AND IPAY.TotPrice >= 1 THEN 'KCP'
                                ELSE null 
                            END
                            ) AS pay_method,
                            ( 
                                CASE 
                                    WHEN PMCC.cancel_price >= 1 THEN CONCAT('-',IPAY.Totprice)
                                    ELSE IFNULL(IPAY.Totprice,0)
                                END
                            ) AS TotPrice,
                            IFNULL(inot_basic_price,0) AS product_price,
                            (
                                CASE 
                                    WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '부분취소'
                                    WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                                    WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '전체취소' 
                                    WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '전체취소' 
                                    WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '전체취소'
                                    WHEN PMCC.cancel_price IS NULL AND  PTCC.ppca_cancel_point IS NULL THEN '결제완료'
                                    ELSE ''
                                END
                            ) AS payment_state,
                            PPRE.ppre_point_cd AS point_code,
                            '9' AS prdt_product_cd,
                            IPAY.pg_cd AS pg_cd,
                            IPAY.icpr_operating_cmpy_cd AS aocc_oper_cd,
                            (
                                CASE
                                    WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                                    ELSE IFNULL(PPRE.ppre_save_point,0)
                                END
                            ) AS ppre_save_point
                        FROM  fdk_parkingcloud.inicis_payment_result IPAY  
                        INNER JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq AND IPAY.icpr_product_cd = 5 AND (INOT.inot_local_pay_machine_cd = 0 OR INOT.inot_local_pay_machine_cd IS NULL)
                        ".$where_cut_date_inot."
                        -- AND IPAY.ApplDate = 20190222
                        INNER JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                        INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                        LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1 
                        LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 5 
                        LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON (IPAY.ApplDate = COUP.apply_date) AND COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                        LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq
                        LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq  
                        LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                        WHERE 1 = 1 
                        ".$where."
                    ) b 
                ",
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $queryOrderBy
            ]);

            $point_code = '';
            $cp = '';
            $card = '';

            foreach($result['data'] as &$result_row){
                if($result_row['point_code'] != null) {
                    $point_code = $result_row['point_code'];
                } else {
                    $point_code = '';
                }
                if($result_row['cp_price'] != null && $result_row['cp_price'] != 0) {
                    $cp = 'C';
                } else if($result_row['cp_price'] == 0) {
                    $cp = '';
                }
                if($result_row['pay_method'] != null && $result_row['TotPrice'] != 0) {
                    $card = $result_row['pay_method'];
                } else {
                    $card = "";
                }
                if(!empty($card)) {
                    $result_row['payment_method'][] = array('key' => 'card', 'value' => $card);
                }
                if(!empty($cp)) {
                    $result_row['payment_method'][] = array('key' => 'cp', 'value' => $cp);
                }
                if(!empty($point_code)) {
                    $result_row['payment_method'][] = array('key' => 'point_code', 'value' => $point_code);
                }
            }
        
            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result['pageInfo']['total_use_price'] = $total_use_price;


            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 포인트 결제 내역 리스트
    public function getPaymentPoint ($request, $response, $args)
    {
      
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 50,
                'offset' => 0
            ]);

            $limit = $params['limit'];
            $offset = $params['offset'];

            $now =  date('Ymd');
            $payment_method = $params['payment_method'];    // 결제수단 : 전체(세개 다 체크)/L.Point(LTP)/GS&Point(GSP)/BlueMemebers(BLP)
            $search_category = $params['search_category'];  // 검색카테고리 :memb_name(구매자명) bcar_number(차량번호): park_name(주차장명) ppre_appl_num(승인번호)
            $search_word = $params['search_word'];
            $start_date =  $params['start_date'];
            $end_date = $params['end_date'];
      
            $orderBy = $params['orderBy'] ?? 'DESC';
            $orderByColumn = $params['orderByColumn'] ?? 'ApplDateTime';
            $queryOrderBy = $orderByColumn.' '.$orderBy;
  
            $where = '';
            $binds = [];

            // Default 24시간 기준
            if(empty($start_date) || empty($end_date)){ 
                $where = 'WHERE PPRE.ppre_appl_date = :now';
                $binds['now'] = $now;
            } else if(!empty($start_date) && !empty($end_date)){
                $start_date = date('Ymd', strtotime($start_date));
                $end_date = date('Ymd', strtotime($end_date));  
                $where = 'WHERE PPRE.ppre_appl_date between :start_date and :end_date';
                $binds['start_date'] = $start_date;
                $binds['end_date'] = $end_date;
            }

            if(!empty($search_category) && !empty($search_word)){
                if ($search_category == 'park_name'){
                    $where .= ' AND '.$search_category.' like :search_word';
                    $binds['search_word'] = '%'.$search_word.'%';
                } else{ 
                    $where .= ' AND '.$search_category.' = :search_word';
                    $binds['search_word'] = $search_word;
                }
            }
      
            if(!empty($payment_method)){
                $where .= ' AND PCLI.point_card_code in ('.$this->ci->dbutil->arrayToInQuery($payment_method).')';
            }
            

            // 전체 개수
            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT count(*) as cnt FROM fdk_parkingcloud.point_payment_result
            '); 
            $stmt->execute();
            $count = $stmt->fetch();
            $allcount = $count['cnt']; 


            /*
            
            *** 포인트 취소 유무 잠시 보류 *** 무조건 취소 가능하게
            ( 
                CASE
                    WHEN IPAY.icpr_product_cd = 5 THEN '1'
                    WHEN IPAY.CancelResultCode = '0000' AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '1'
                    WHEN PMCC.cancel_cd != NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '1'
                    WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '1'
                    ELSE '0'
                END
            ) AS cancel_possible_yn
            
            
            
            
            */
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCloudDb',
                'select' => "
                    PPRE.ppre_seq,
                    ( 
						CASE 
							WHEN IPAY.icpr_product_cd = 2 THEN  PPSL.ppsl_seq
                            WHEN IPAY.icpr_product_cd = 5 THEN  INOT.inot_seq
                            ELSE ''
						END
					) AS ppsl_seq,
                    PCLI.point_card_name AS point_card_name,
                    (
						CASE 
							WHEN IPAY.icpr_operating_cmpy_cd = 0 THEN '아이파킹'
                            ELSE AOCC.aocc_oper_name
						END 
					) AS aocc_oper_name,
                    (
                        CASE 
                            WHEN PPSL.ppsl_reg_device_cd in (3,9) THEN '파킹 APP' 
                            WHEN PPSL.ppsl_reg_device_cd = 2 THEN '파킹 WEB' 
                            WHEN IPAY.icpr_product_cd = 5 THEN '파킹 APP'
                        END 
                    ) AS code_name,
                    CONCAT(
                        substr(ppre_appl_date,1,4),'-',substr(ppre_appl_date,5,2),'-',substr(ppre_appl_date,7,2),' '
                        ,substr(ppre_appl_time,1,2),':',substr(ppre_appl_time,3,2),':',substr(ppre_appl_time,5,2)
                    ) AS ApplDateTime,
                    (
                        CASE 
                            WHEN IPAY.icpr_product_cd = 5 THEN '시간주차'
                            WHEN PRDT.prdt_product_cd  = 1 THEN '정기'
                            WHEN PRDT.prdt_product_cd  = 2 THEN '일일' 
                            WHEN PRDT.prdt_product_cd   = 5 THEN '시간' 
                            ELSE '' 
                        END
                    ) AS parking_type,
                    PARK.park_seq,
                    PARK.park_name AS park_name,
                    REPLACE(MEMB.memb_name, substr(MEMB.memb_name,2,1), '*') AS memb_name,
                    IPAY.bcar_number AS bcar_number, 
                    (   
                        CASE 
                            WHEN PTCC.ppca_cancel_point IS NOT NULL THEN '1' 
                            WHEN PPRE.ppre_use_point = 0 AND PPRE.ppre_save_point >= 1 THEN '2' 
                            ELSE '0' 
                        END
                    ) AS point_state,
                    (
                        CASE 
                            WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NULL THEN '3'
                            WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '2'
                            WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '2'
                            WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '1' 
                            WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '1'
                            WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NOT NULL THEN '1'
                            WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '1'
                            ELSE '10'
                        END 
                    ) AS payment_state,
                    (
                        CASE
                            WHEN PTCC.ppca_cancel_point >= 1 THEN concat('-',PPRE.ppre_use_point)
                            ELSE IFNULL(ppre_use_point,0)
                        END
                    ) AS ppre_use_point,
                    PPRE.ppre_appl_date AS ppre_appl_date,
                    PCLI.point_card_code AS point_card_code,
                    PPRE.ppre_appl_num AS ppre_appl_num,
                    (
                        CASE
                            WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                            ELSE IFNULL(PPRE.ppre_save_point,0)
                        END
                    ) AS ppre_save_point,
                    MEMB.memb_seq,
                    (
                        CASE 
                            WHEN PTCC.ppca_cancel_point IS NULL THEN '1'
                            ELSE '0'
                        END
                    ) AS cancel_possible_yn,
                    IPAY.icpr_product_cd AS product_cd,
                    PRDT.prdt_seq AS prdt_seq,
                    PPRE.ppre_site_cd AS ppre_site_cd,
                    IPAY.icpr_product_seq  AS product_seq
                    
                ",
                'query' => "
                    SELECT
                        %%
                    FROM fdk_parkingcloud.point_payment_result PPRE
                    LEFT JOIN fdk_parkingcloud.inicis_payment_result IPAY ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd in (2,5)
                    LEFT JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq 
                    LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                    LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq 
                    AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1
                    LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                    LEFT JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_operation_company_code AOCC ON AOCC.aocc_oper_cd = IPAY.icpr_operating_cmpy_cd
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                    
                ".$where,
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $queryOrderBy
            ]);

            // 총 결제 금액
            $where_plus = ' AND PTCC.ppca_cancel_point  IS NULL ';
            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT 
                    IFNULL(sum(ppre_use_point),0) AS total_use_point
                FROM fdk_parkingcloud.point_payment_result PPRE
                LEFT JOIN fdk_parkingcloud.inicis_payment_result IPAY ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd in (2,5)
                LEFT JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq 
                LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq 
                LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq 
                AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1
                LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                LEFT JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                LEFT JOIN fdk_parkingcloud.arf_operation_company_code AOCC ON AOCC.aocc_oper_cd = IPAY.icpr_operating_cmpy_cd
                LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
            ".$where.$where_plus); 

            $stmt->execute($binds);
            $total_point = $stmt->fetch();   
            $total_use_point = $total_point['total_use_point']; 
            
            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
           
            $result['pageInfo']['allcount'] = $allcount; 
            $result['pageInfo']['total_use_point'] = $total_use_point; 
            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }

    }
    // 포인트 결제내역 엑셀 다운로드
    public function getBeforePaymentPointExcelDownload($request, $response, $args)
    {
        try {

            ini_set('memory_limit', '-1');
            set_time_limit(0);
            

            $params = $this->ci->util->getParams($request);
            $now =  date('Ymd');
            $payment_method = $params['payment_method'];    // 결제수단 : 전체(세개 다 체크)/L.Point(LTP)/GS&Point(GSP)/BlueMemebers(BLP)
            $search_category = $params['search_category'];  // 검색카테고리 :memb_name(구매자명) bcar_number(차량번호): park_name(주차장명) ppre_appl_num(승인번호)
            $search_word = $params['search_word'];
            $start_date =  $params['start_date'];
            $end_date = $params['end_date'];
            $orderBy = $params['orderBy'] ?? 'DESC';
            $orderByColumn = $params['orderByColumn'] ?? 'ApplDateTime';
    
            $where = '';
            $binds = [];

            // Default 24시간 기준
            if(empty($start_date) || empty($end_date)){ 
                $where = 'WHERE PPRE.ppre_appl_date = :now';
                $binds['now'] = $now;
            } else if(!empty($start_date) && !empty($end_date)){
                $start_date = date('Ymd', strtotime($start_date));
                $end_date = date('Ymd', strtotime($end_date));  
                $where = 'WHERE PPRE.ppre_appl_date between :start_date and :end_date';
                $binds['start_date'] = $start_date;
                $binds['end_date'] = $end_date;
            }

            if(!empty($search_category) && !empty($search_word)){
                if ($search_category == 'park_name'){
                    $where .= ' AND '.$search_category.' like :search_word';
                    $binds['search_word'] = '%'.$search_word.'%';
                } else{ 
                    $where .= ' AND '.$search_category.' = :search_word';
                    $binds['search_word'] = $search_word;
                }
            }
            
            if(!empty($payment_method)){
                $where .= ' AND PCLI.point_card_code in ('.$this->ci->dbutil->arrayToInQuery($payment_method).')';
            }
            
            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT
                    PPRE.ppre_seq, 
                    PCLI.point_card_name AS point_card_name,
                    IPAY.MOID AS moid,
                    (
                        CASE
                            WHEN PPSL.ppsl_reg_device_cd in (3,9) THEN '파킹 APP' 
                            WHEN PPSL.ppsl_reg_device_cd = 2 THEN '파킹 WEB' 
                            WHEN IPAY.icpr_product_cd = 5 THEN '파킹 APP'
                        END  
                    ) as code_name,
                    CONCAT(
                        substr(ppre_appl_date,1,4),'-',substr(ppre_appl_date,5,2),'-',substr(ppre_appl_date,7,2),' ',substr(ppre_appl_time,1,2),':',substr(ppre_appl_time,3,2),':',substr(ppre_appl_time,5,2)
                    ) AS ApplDateTime,
                    (
                        CASE 
                            WHEN IPAY.icpr_product_cd = 5 THEN '시간주차'
                            WHEN PRDT.prdt_product_cd  = 1 THEN '정기'
                            WHEN PRDT.prdt_product_cd  = 2 THEN '일일' 
                            WHEN PRDT.prdt_product_cd  = 5 THEN '시간'  
                        END
                    ) AS parking_type,
                    ppre_product_cd,
                    (
						CASE 
							WHEN IPAY.icpr_product_cd = 2 THEN PRDT.prdt_name 
                            WHEN IPAY.icpr_product_cd = 5 THEN CONCAT(IFNULL(IFNULL(inot_duration,TIMESTAMPDIFF(minute, inot_enter_datetime, inot_exit_datetime)),0),'분')
						END
					) AS prdt_name,
                    PARK.park_name AS park_name,
                    REPLACE(MEMB.memb_name, substr(MEMB.memb_name,2,1), '*') AS memb_name,
                    CONCAT(
                            memb_mobile_1,'-',memb_mobile_2,'-',memb_mobile_3
                    ) AS memb_tel,
                    IPAY.bcar_number AS bcar_number, 
                    (
                        CASE 
                            WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분결제'
                            WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '부분취소'
                            WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                            WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '전체취소' 
                            WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '전체취소'
                            WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NOT NULL THEN '전체취소'
                            WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '1'
                            ELSE '결제완료'
                        END 
                    ) AS payment_state,
                    (   
                        CASE 
                            WHEN PTCC.ppca_cancel_point IS NOT NULL THEN '취소' 
                            WHEN PPRE.ppre_use_point = 0 AND PPRE.ppre_save_point >= 1 THEN '적립' 
                            ELSE '결제' 
                        END
                    ) AS point_state,
                    ppre_cancel_ny,
                    ppre_appl_num,
                    (
                        CASE
                            WHEN PTCC.ppca_cancel_point >= 1 THEN concat('-',PPRE.ppre_use_point)
                            ELSE IFNULL(PPRE.ppre_use_point,0)
                        END
                    ) AS use_point,
                    (
                        CASE
                            WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                            ELSE IFNULL(PPRE.ppre_save_point,0)
                        END
                    ) AS ppre_save_point,
                    IPAY.Totprice AS Totprice,
                    CONCAT(
                        substr(PMCC.cancel_date,1,4),'-',substr(PMCC.cancel_date,5,2),'-',substr(PMCC.cancel_date,7,2),' '
                        ,substr(PMCC.cancel_time,1,2),':',substr(PMCC.cancel_time,3,2),':',substr(PMCC.cancel_time,5,2)
                    ) AS cancel_datetime 
                    FROM fdk_parkingcloud.point_payment_result PPRE
                    LEFT JOIN fdk_parkingcloud.inicis_payment_result IPAY ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd in (2,5)
                    LEFT JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq 
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq 
                    LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                    LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq 
                    AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1
                    LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                    LEFT JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_operation_company_code AOCC ON AOCC.aocc_oper_cd = IPAY.icpr_operating_cmpy_cd
                    LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                    
                ".$where." ORDER BY ".$orderByColumn.' '.$orderBy);

            $stmt->execute($binds);
            $result = $this->ci->dbutil->fetchAllWithJson($stmt);

            $fileName = '포인트결제내역.xls';
            $excelData = [
                array(
                    'name' => '포인트결제내역',
                    'type' => 'countHistory',
                    'data' => $result,
                    'key' => ["번호" ,"포인트구분", "구매채널", "구매번호", "결제일시", "주차구분","주차상품명", "주차장", "구매자명", "핸드폰번호","차량번호","결제상태","포인트상태","승인번호","포인트사용","포인트적립","결제금액","취소일시"],
                    'column' => ["ppre_seq","point_card_name", "code_name", "moid" ,"ApplDateTime", "parking_type", "prdt_name", "park_name", "memb_name", "memb_tel" ,"bcar_number","payment_state","point_state","ppre_appl_num","use_point","ppre_save_point","Totprice","cancel_datetime"],
                    'size' => [9, 11, 11, 13, 17, 11, 11, 11, 11, 11, 11, 11, 11, 11,11,11,11,11]
                )
            ];
            
            $엑셀파일 = $this->ci->file->makeExcelDownload($fileName, $excelData);

            return $엑셀파일;          

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 결제내역 엑셀 다운로드
    public function getPaymentInicisExcelDownload($request, $response, $args)
    {
       
        try {

            ini_set('memory_limit', '-1');
            set_time_limit(0);

            $params = $this->ci->util->getParams($request);
            $orderBy = $params['orderBy'] ?? 'DESC';
            $orderByColumn = $params['orderByColumn'] ?? 'ApplDateTime';
            $queryOrderBy = $orderByColumn.' '.$orderBy;

            $search_category = $params['search_category']; 
            //prdt_name(상품명) prdt_product_cd(상품코드): memb_name(주문자명) bcar_number(차량번호) moid(구매번호) park_name(주차장명) ApplNum (승인번호) point_appl_num(포인트승인번호)
            $search_word = $params['search_word'];
            $search_date_category = $params['search_date_category']; // use_start (사용시작일), use_end (사용종료일),  payment (결제일)
            $start_date = $params['start_date'];
            $end_date = $params['end_date']; 
            $now = date('Y-m-d');
                   
            /*
                상세 검색 * 전체클릭시 모든 체크박스

                *기간검색           (결제일/사용시작일/사용종료일) 
                *검색어             (상품명/상품코드/주문자명/차량번호/구매번호/주차장명/승인번호)
                *결제수단(array)    (전체/신용카드/PAYCO/신한카드포인트[?]/삼성카드포인트[?]/L.Point/GS&Point/블루멤버스/쿠폰) 
                    CA_ // 카드              CA_1(신용카드), CA_13(PAYCO)
                    CP_ // 카드포인트        CP_SHP, CP_SSP
                    PO_ // 포인트            PO_LTP, PO_GSP, PO_BLP
                    CU_ // 쿠폰              CU_C

                *구매채널(array)    (전체/파킹APP[3]/파킹WEB[2]/MOST[?])
                *결제PG             (전체/KCP[?]/PAYCO[13]/이니시스[?])
                *주차장 
                    1) 운영사 : DB에 등록된 운영사 정보 표시
                    2) 주차장 : 운영사별 주차장 정보 표시
                *구매유형(array)    (전체/정기권[1]/시간권[5]/일일권[2]/발렛[?])
                *구매상태(array)    (전체/결제[0]/취소[10]/완료[1,13])
                *결제금액           (min_price - max_price)    */

            $payment_method = $params['payment_method']; // 결제수단
            $payment_channel = $params['payment_channel']; // 결제채널
            $payment_pg = $params['payment_pg']; // 결제PG
            $payment_type = $params['payment_type']; // 구매유형
            $payment_state = $params['payment_state']; // 결제상태
            $aocc_oper_code = $params['aocc_oper_code'] ?? 1; // 운영사별 주차장 정보
            $aocc_park_name  = $params['aocc_park_name']; // 주차장 정보 
            $min_price = $params['min_price']; // 최소결제금액
            $max_price = $params['max_price']; // 최대결제금액
            
    
            $where = '';
            $binds = [];
            $where_cut_date_ppsl = '';
            $where_cut_date_inot = '';

            if(empty($start_date) || empty($end_date) || empty($search_date_category)){ 
                $now = date('Ymd');
                $where_cut_date_ppsl = " AND IPAY.ApplDate = ".$now;
                $where_cut_date_inot = " AND IPAY.ApplDate = ".$now;
            } else if(!empty($start_date) && !empty($end_date) && !empty($search_date_category)){

                if($search_date_category == 'payment'){ // 결제일시 
                    $start_date =  str_replace('-','',$start_date);
                    $end_date =  str_replace('-','',$end_date);
                    $where_cut_date_ppsl = " AND IPAY.ApplDate between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND IPAY.ApplDate between '".$start_date."' and '".$end_date."'";

                } else if($search_date_category == 'use_start'){ // 사용시작일 
                    $start_date = $start_date.' 00:00:00'; 
                    $end_date = $end_date.' 23:59:59'; 
                    $where_cut_date_ppsl = " AND PPSL.ppsl_start_datetime between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND INOT.inot_enter_datetime between '".$start_date ."' and '".$end_date."'";

                } else if($search_date_category == 'use_end'){ // 사용종료일   
                    $start_date = $start_date.' 00:00:00'; 
                    $end_date = $end_date.' 23:59:59'; 
                    $where_cut_date_ppsl = " AND PPSL.ppsl_end_datetime between '".$start_date ."' and '".$end_date."'";
                    $where_cut_date_inot = " AND INOT.inot_exit_datetime between'".$start_date ."' and '".$end_date."'";
        
                }
            }
            
            if(!empty($search_category) && !empty($search_word)){ // 기획팀과 의논 후 like 인지 = 인지 정하기 (속도차이)
                if ($search_category == 'prdt_name'){
                    $where .= " AND PRDT.prdt_name like '%".$search_word."%'";
                } else if ($search_category == 'memb_name'){
                    $where .= " AND MEMB.memb_name = '".$search_word."'";
                } else if ($search_category == 'bcar_number'){
                    $where .= " AND IPAY.bcar_number ='".$search_word."'";
                } else if ($search_category == 'moid'){
                    $where .= " AND IPAY.MOID ='".$search_word."'";
                } else if ($search_category == 'ApplNum'){
                    $where .= " AND IPAY.ApplNum = '".$search_word."'";
                } else if ($search_category == 'prdt_product_cd'){
                    $where .= " AND PRDT.prdt_product_cd = ".$search_word;
                } else if ($search_category == 'point_appl_num'){
                    $where .= " AND PPRE.ppre_appl_num = ".$search_word;
                }
            }

            //////////////////////////////////////////////////////////////////////// 상세 검색 ///////////////////////////////////////////////////////////////////////////
            // 결제수단  
            if(!empty($payment_method)){    
                foreach($payment_method as $payment_method_list){
                    $method_type = substr($payment_method_list,0,2);
                    if(!empty($method_type == 'CA')){ 
                        $card_array[] = substr($payment_method_list,3);
                    }
                    if(!empty($method_type == 'PO')){ 
                        $point_array[] = substr($payment_method_list,3);
                    }
                    if(!empty($method_type == 'CU')){
                        $coupon_array[] = substr($payment_method_list,3);
                    }
                }
                $where .=  ' AND (';
                if(!empty($card_array)){
                    if(!in_array("KCP",$card_array) && in_array("PAYCO",$card_array)){
                        $where .= " IPAY.pg_cd = 13 AND IPAY.Totprice > 0";
                    }
                    if(in_array("KCP",$card_array) && !in_array("PAYCO",$card_array)){
                        $where .= " IPAY.pg_cd != 13 AND IPAY.Totprice > 0";
                    }
                    if(in_array("KCP",$card_array) && in_array("PAYCO",$card_array)){
                        $where .= " IPAY.Totprice > 0";
                    }
                }
                if(!empty($point_array)){
                    if(!empty($card_array)) $where .=  " OR ";
                    $where .= "PPRE.ppre_point_cd in (".$this->ci->dbutil->arrayToInQuery($point_array).")";
                }  
                if(!empty($coupon_array)){  
                    if(!empty($card_array) || !empty($point_array)) $where .=  ' OR ';
                    $where .= 'COUP.price > 0';
                }
                $where .= ')';  
            }
  
            // 구매유형
            if(!empty($payment_type)){
                $where .= " AND (";
                if(in_array('9',$payment_type)){ // 시간주차 선택시
                    $where .= 'IPAY.icpr_product_cd = 5 ';
                }
                $where .= ' PRDT.prdt_product_cd in ('.$this->ci->dbutil->arrayToInQuery($payment_type).')'; // 
                $where .= " )";  
                $where = str_replace("  ", " OR ", $where);   
            }

       
               
            // 구매상태
            if(!empty($payment_state)){
                $where .= ' AND (';
                if(in_array('0', $payment_state)){      // 결제상태                 
                    $where .= ' ( PMCC.cancel_price IS NULL AND PTCC.ppca_cancel_point IS NULL) ';
                }
                if(in_array('1', $payment_state)){      // 전체취소 (PG결제취소,포인트 결제된 경우 포인트도 취소)
                    $where .=  " ((IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point) OR (PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price) OR (IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point)) ";
                }
                if(in_array('2', $payment_state)){      // 부분취소 (PG결제취소,포인트결제취소 X)
                    $where .=  " ((IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1) OR (IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL))";
                }
                $where = str_replace("  ", " OR ", $where);         
                $where .= ')';
            }

            
        
            // 결제PG
            if(!empty($payment_pg) && $payment_pg != 'ALL'){
                // KCP = 6 : 4,5,6,8
                // 이니시스 = 1
                // PAYCO = 13 
                if($payment_pg == '6'){
                    $where .= ' AND IPAY.pg_cd IN (4,5,6,8)';
                } else {
                    $where .= " AND IPAY.pg_cd = ".$payment_pg;
                }
            }

            if(isset($min_price) && isset($max_price)){
                if($min_price == null || $min_price == '') $min_price = 0;
                if($max_price == null || $max_price == '') $max_price = 999999999;
                $where .= " AND convert(IPAY.TotPrice,UNSIGNED) between ".$min_price." AND ".$max_price;
            }
    
            // 운영사 정보 aocc_oper_code
            // 운영사 별 주차장 정보 aocc_park_name

            if(!empty($aocc_oper_code) && !empty($aocc_park_name)){ 
                if($aocc_oper_code == 1){
                    $aocc_oper_code = 'in (0,1)';
                }else{
                    $aocc_oper_code = '= '.$aocc_oper_code;
                }
                $where .= " AND IPAY.icpr_operating_cmpy_cd ".$aocc_oper_code." AND PARK.park_name like '%".$aocc_park_name."%'";
            }
       
            $stmt = $this->ci->iparkingCloudDb->prepare("	
                SELECT 
                    REPLACE(b.memb_name, substr(b.memb_name,2,1), '*') AS member_name,
                    b.*
                FROM (
                    SELECT 
                        IPAY.icpr_seq AS idx,
                        (   
                            CASE 
                                WHEN PPSL.ppsl_reg_device_cd = 1 THEN '기타'
                                WHEN PPSL.ppsl_reg_device_cd in (3,9) THEN '파킹 APP' 
                                WHEN PPSL.ppsl_reg_device_cd = 2 THEN '파킹 WEB'  
                                ELSE '' 
                            END  
                        ) AS code_name,
                        IPAY.MOID AS moid,
                        concat(
                            substr(IPAY.ApplDate,1,4),'-',substr(IPAY.ApplDate,5,2),'-',substr(IPAY.ApplDate,7,2),' ',substr(IPAY.ApplTime,1,2),':',substr(IPAY.ApplTime,3,2),':',substr(IPAY.ApplTime,5,2)
                        ) AS ApplDateTime,
                        concat(
                            substr(PMCC.cancel_date,1,4),'-',substr(PMCC.cancel_date,5,2),'-',substr(PMCC.cancel_date,7,2),' ',substr(PMCC.cancel_time,1,2),':',substr(PMCC.cancel_time,3,2),':',substr(PMCC.cancel_time,5,2)
                        ) AS cancel_date,
                        CONCAT(DATE_FORMAT(PPSL.ppsl_start_datetime,'%Y-%m-%d'),'-',DATE_FORMAT(PPSL.ppsl_end_datetime,'%Y-%m-%d')) AS validity_date,
                        (
                            CASE 
                                WHEN PRDT.prdt_product_cd = 1 THEN '정기' 
                                WHEN PRDT.prdt_product_cd = 2 THEN '일일' 
                                WHEN PRDT.prdt_product_cd  = 5 THEN '시간' 
                                ELSE '' 
                            END
                        ) AS parking_type,
                        PRDT.prdt_name AS prdt_name,
                        PARK.park_name AS park_name,
                        MEMB.memb_name AS memb_name,
                        PPSL.ppsl_car_number AS bcar_number,
                        IPAY.ApplNum AS ApplNum,
                        IFNULL(SALE.prht_discount_price, 0) AS prht_discount_price,
                        ( 
                            CASE 
                                WHEN PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN CONCAT('-',PPRE.ppre_use_point)
                                ELSE IFNULL(PPRE.ppre_use_point,0)
                            END 
                        ) AS ppre_use_point,
                        COUP.price AS cp_price,
						( 
								CASE 
									WHEN COUP.price >= 1 AND PMCC.cancel_price >= 1 THEN CONCAT('-',COUP.price)
									ELSE IFNULL(COUP.price ,0)
								END
						) AS cp_cancel_price,
                        (   
                            CASE 
                                WHEN IPAY.pg_cd  = 13 AND IPAY.TotPrice >= 1 THEN 'PAYCO'
                                WHEN IPAY.pg_cd  != 13 AND IPAY.TotPrice >= 1 THEN 'KCP'
                                ELSE null 
                            END
                        ) AS pay_method,
                        ( 
                            CASE 
                                WHEN PMCC.cancel_price >= 1 THEN CONCAT('-',IPAY.Totprice)
                                ELSE IFNULL(IPAY.Totprice,0)
                            END
                        ) AS TotPrice,
                        (
                            CASE
                                WHEN BCAR.bcar_vehicle_cd = 1 THEN PPPR.pppr_price_sale_small
                                WHEN BCAR.bcar_vehicle_cd = 2 THEN PPPR.pppr_price_sale_midsize
                                WHEN BCAR.bcar_vehicle_cd = 3 THEN PPPR.pppr_price_sale_van
                                WHEN BCAR.bcar_vehicle_cd = 4 THEN PPPR.pppr_price_normal_mid_truck
                                WHEN BCAR.bcar_vehicle_cd = 5 THEN PPPR.pppr_price_normal_big_truck
                                ELSE null
                            END 
                        ) AS product_price,
                        (
                            CASE 
                                WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '취소'
                                WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '취소'
                                WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '취소' 
                                WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '취소' 
                                WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '취소'
                                WHEN PMCC.cancel_price IS NULL AND  PTCC.ppca_cancel_point IS NULL THEN '결제'
                                ELSE ''
                            END
                        ) AS payment_state,
                        PPRE.ppre_point_cd AS point_code,
                        PRDT.prdt_product_cd AS prdt_product_cd,
                        IPAY.pg_cd AS pg_cd, 
                        IPAY.icpr_operating_cmpy_cd AS aocc_oper_cd,
                        (
                            CASE 
                                WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price = 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point != NULL THEN '부분취소'
                                WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                                ELSE ''
                            END
                        ) AS cancel_state,
                        CONCAT(MEMB.memb_mobile_1,'-',memb_mobile_2,'-',memb_mobile_3) AS memb_tel,
                        PPSL.ppsl_cut_price AS ppsl_cut_price,
                        CARD.card_name AS card_name,
                        (
                            CASE
                                WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                                ELSE IFNULL(PPRE.ppre_save_point,0)
                            END
                        ) AS ppre_save_point
                    FROM  fdk_parkingcloud.inicis_payment_result IPAY
                    INNER JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq AND IPAY.icpr_product_cd = 2 AND IPAY.pg_cd NOT IN(7, 10, 11) 
                    ".$where_cut_date_ppsl."
                    INNER JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                    INNER JOIN (
                        SELECT *
                        FROM (
                            SELECT a.*
                                ,(CASE @v_prdt_seq WHEN a.prdt_seq THEN @rownum:=@rownum+1 ELSE @rownum:=1 END) rnum
                                ,(@v_prdt_seq:=a.prdt_seq) v_prdt_seq
                            FROM  fdk_parkingcloud.acd_rpms_parking_product_price a, (SELECT @v_prdt_seq:='', @rownum:=0 FROM DUAL) b
                            WHERE pppr_del_ny = 0
                            ORDER BY a.prdt_seq desc, pppr_seq desc
                            ) c
                        WHERE rnum = 1 
                    ) PPPR ON PRDT.prdt_seq = PPPR.prdt_seq AND PPPR.pppr_del_ny=0
                    INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON PPSL.park_seq = PARK.park_seq AND PARK.park_del_ny =0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON PPSL.ppsl_car_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
                    LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON PPSL.ppsl_buyer_seq = MEMB.memb_seq
                    LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 2
                    LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON (IPAY.ApplDate = COUP.apply_date) AND COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                    LEFT JOIN fdk_parkingcloud.card_name_info CARD ON CARD.card_code = IPAY.CARD_Code
                    LEFT JOIN fdk_parkingcloud.product_sale_history SALE on PPSL.ppsl_seq = SALE.prht_ppsl_seq AND SALE.prht_type_cd = 1
                    LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                    LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1 
                    LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq WHERE 1 = 1   
                    ".$where."
                UNION ALL          
                SELECT 
                    IPAY.icpr_seq AS idx,
                    '파킹 APP' AS code_name,
                    IPAY.MOID AS moid,
                    concat(
                        substr(IPAY.ApplDate,1,4),'-',substr(IPAY.ApplDate,5,2),'-',substr(IPAY.ApplDate,7,2),' ',substr(IPAY.ApplTime,1,2),':',substr(IPAY.ApplTime,3,2),':',substr(IPAY.ApplTime,5,2)
                    ) AS ApplDateTime,
                    concat(
                        substr(PMCC.cancel_date,1,4),'-',substr(PMCC.cancel_date,5,2),'-',substr(PMCC.cancel_date,7,2),' ',substr(PMCC.cancel_time,1,2),':',substr(PMCC.cancel_time,3,2),':',substr(PMCC.cancel_time,5,2)
                    ) AS cancel_date,
                    CONCAT(DATE_FORMAT(INOT.inot_enter_datetime,'%Y-%m-%d'),'-',DATE_FORMAT(INOT.inot_exit_datetime,'%Y-%m-%d')) AS validity_date,
                    '시간주차' AS parking_type,
                    CONCAT(IFNULL(IFNULL(inot_duration,TIMESTAMPDIFF(minute, inot_enter_datetime, inot_exit_datetime)),0),'분') AS prdt_name,
                    PARK.park_name AS park_name,
                    MEMB.memb_name AS memb_name,
                    IPAY.bcar_number AS bcar_number,				
                    IPAY.ApplNum AS ApplNum,
                    IFNULL(INOT.inot_discount_price,0) AS prht_discount_price,
                    ( 
                            CASE 
                                WHEN PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN CONCAT('-',PPRE.ppre_use_point)
                                ELSE IFNULL(PPRE.ppre_use_point,0)
                            END 
                    ) AS ppre_use_point,
                    COUP.price AS cp_price,
                    ( 
                            CASE 
                                WHEN COUP.price >= 1 AND PMCC.cancel_price >= 1 THEN CONCAT('-',COUP.price)
                                ELSE IFNULL(COUP.price ,0)
                            END
                    ) AS cp_cancel_price,
                    (   
                        CASE 
                            WHEN IPAY.pg_cd  = 13 AND IPAY.TotPrice >= 1 THEN 'PAYCO'
                            WHEN IPAY.pg_cd  != 13 AND IPAY.TotPrice >= 1 THEN 'KCP'
                            ELSE null 
                        END
                    ) AS pay_method,
                    ( 
                        CASE 
                            WHEN PMCC.cancel_price >= 1 THEN CONCAT('-',IPAY.Totprice)
                            ELSE IFNULL(IPAY.Totprice,0)
                        END
                    ) AS TotPrice,
                    IFNULL(INOT.inot_basic_price,0) AS product_price,
                    (
                        CASE 
                            WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '취소'
                            WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '취소'
                            WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '취소' 
                            WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '취소' 
                            WHEN (PMCC.cancel_cd != NULL OR IPAY.CancelResultCode = '0000') AND IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price THEN '취소' 
                            WHEN (PMCC.cancel_cd != NULL OR IPAY.CancelResultCode = '0000') AND PPRE.ppre_use_point = PTCC.ppca_cancel_point != NULL THEN '취소' 
                            WHEN PMCC.cancel_price IS NULL AND  PTCC.ppca_cancel_point IS NULL THEN '결제'
                            ELSE ''
                        END
                    ) AS payment_state,
                    PPRE.ppre_point_cd AS point_code,
                    '9' AS prdt_product_cd,
                    IPAY.pg_cd AS pg_cd,
                    IPAY.icpr_operating_cmpy_cd AS aocc_oper_cd,
                    (
                        CASE 
                            WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '부분취소'
                            WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                            ELSE ''
                        END
                    ) AS cancel_state,
                    CONCAT(MEMB.memb_mobile_1,'-',memb_mobile_2,'-',memb_mobile_3) AS memb_tel,
                    IFNULL((INOT.inot_price%100),0) AS ppsl_cut_price,
                    CARD.card_name AS card_name,
                    (
                        CASE
                            WHEN PPRE.ppre_save_point >= 1 AND PTCC.ppca_cancel_save_point >= 1 THEN CONCAT('-',PPRE.ppre_save_point)
                            ELSE IFNULL(PPRE.ppre_save_point,0)
                        END
                    ) AS ppre_save_point
                FROM  fdk_parkingcloud.inicis_payment_result IPAY  
                INNER JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq AND IPAY.icpr_product_cd = 5 AND (INOT.inot_local_pay_machine_cd = 0 OR INOT.inot_local_pay_machine_cd IS NULL)
                ".$where_cut_date_inot."
                INNER JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1 
                LEFT JOIN fdk_parkingcloud.point_payment_result PPRE ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd = 5 
                LEFT JOIN fdk_parkingcloud.coupon_payment_result COUP ON (IPAY.ApplDate = COUP.apply_date) AND COUP.icpr_seq = IPAY.icpr_seq AND COUP.is_deleted = 'N'
                LEFT JOIN fdk_parkingcloud.card_name_info CARD ON CARD.card_code = IPAY.CARD_Code
                LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq
                LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq  
                LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                WHERE 1 = 1 ".$where."
                ) b ORDER BY ".$orderByColumn.' '.$orderBy
            );


            $stmt->execute();
            $result = $this->ci->dbutil->fetchAllWithJson($stmt);
            $data = [];

            foreach($result as $result_rows){

                $member_name = $result_rows["member_name"];
                $idx = $result_rows["idx"];
                $code_name = $result_rows["code_name"];
                $moid = $result_rows["moid"];
                $ApplDateTime = $result_rows["ApplDateTime"];
                $cancel_date = $result_rows["cancel_date"];
                $validity_date = $result_rows["validity_date"];
                $parking_type = $result_rows["parking_type"];
                $prdt_name = $result_rows["prdt_name"];
                $park_name = $result_rows["park_name"];
                $memb_name = $result_rows["memb_name"];
                $bcar_number = $result_rows["bcar_number"];
                $ApplNum = $result_rows["ApplNum"];
                $prht_discount_price = $result_rows["prht_discount_price"];
                $ppre_use_point = $result_rows["ppre_use_point"];
                $cp_price = $result_rows["cp_price"]?? 0;
                $cp_cancel_price = $result_rows["cp_cancel_price"];
                $pay_method = $result_rows["pay_method"];
                $TotPrice = $result_rows["TotPrice"];
                $product_price = $result_rows["product_price"];
                $payment_state = $result_rows["payment_state"];
                $point_code = $result_rows["point_code"];
                $prdt_product_cd = $result_rows["prdt_product_cd"];
                $pg_cd = $result_rows["pg_cd"];
                $aocc_oper_cd = $result_rows["aocc_oper_cd"];
                $cancel_state = $result_rows["cancel_state"];
                $memb_tel = $result_rows["memb_tel"];
                $ppsl_cut_price = $result_rows["ppsl_cut_price"];
                $card_name = $result_rows["card_name"];
                $ppre_save_point = $result_rows["ppre_save_point"];
                

                array_push ($data,array(
                    "member_name" => $member_name,
                    "idx" => $idx, 
                    "code_name" => $code_name, 
                    "moid" => $moid, 
                    "ApplDateTime" => $ApplDateTime, 
                    "cancel_date" => null,
                    "validity_date" => $validity_date, 
                    "parking_type" => $parking_type, 
                    "prdt_name" => $prdt_name, 
                    "park_name" => $park_name, 
                    "memb_name" => $memb_name, 
                    "bcar_number" => $bcar_number, 
                    "ApplNum" => $ApplNum, 
                    "prht_discount_price" => $prht_discount_price, 
                    "ppre_use_point" => $ppre_use_point, 
                    "cp_price" => $cp_price, 
                    "pay_method" => $pay_method, 
                    "TotPrice" => str_replace("-","",$TotPrice), 
                    "product_price" => $product_price, 
                    "payment_state" => "결제", 
                    "point_code" => $point_code, 
                    "prdt_product_cd" => $prdt_product_cd, 
                    "pg_cd" => $pg_cd, 
                    "aocc_oper_cd" => $aocc_oper_cd, 
                    "cancel_state" => $cancel_state, 
                    "memb_tel" => $memb_tel, 
                    "ppsl_cut_price" => $ppsl_cut_price, 
                    "card_name" => $card_name, 
                    "ppre_save_point" => $ppre_save_point
                ));

                if(isset($cancel_date)){

                    array_push ($data,array(
                        "member_name" => $member_name,
                        "idx" => $idx, 
                        "code_name" => $code_name, 
                        "moid" => $moid, 
                        "ApplDateTime" => $ApplDateTime, 
                        "cancel_date" => $cancel_date,
                        "validity_date" => $validity_date, 
                        "parking_type" => $parking_type, 
                        "prdt_name" => $prdt_name, 
                        "park_name" => $park_name, 
                        "memb_name" => $memb_name, 
                        "bcar_number" => $bcar_number, 
                        "ApplNum" => $ApplNum, 
                        "prht_discount_price" => $prht_discount_price, 
                        "ppre_use_point" => $ppre_use_point, 
                        "cp_price" => $cp_cancel_price, 
                        "pay_method" => $pay_method, 
                        "TotPrice" => $TotPrice, 
                        "product_price" => $product_price, 
                        "payment_state" => $payment_state, 
                        "point_code" => $point_code, 
                        "prdt_product_cd" => $prdt_product_cd, 
                        "pg_cd" => $pg_cd, 
                        "aocc_oper_cd" => $aocc_oper_cd, 
                        "cancel_state" => $cancel_state, 
                        "memb_tel" => $memb_tel, 
                        "ppsl_cut_price" => $ppsl_cut_price, 
                        "card_name" => $card_name, 
                        "ppre_save_point" => $ppre_save_point
                    ));
                }
            }
                
            
            $fileName = '결제내역.xls';
            $excelData = [
                array(
                    'name' => '결제내역',
                    'type' => 'countHistory',
                    'data' => $data,
                    'key' => ["결제채널", "구매번호", "결제일시", "취소일시", "사용기간", "주차구분", "주차상품명","주차장명","구매자명","휴대폰번호","차량번호","결제상태","결제수단","포인트수단","승인번호","감면","절삭","상품가","쿠폰","적립포인트 ","사용포인트","결제금액","취소여부"],
                    'column' => ["code_name", "moid", "ApplDateTime", "cancel_date", "validity_date", "parking_type", "prdt_name","park_name","member_name",
                    "memb_tel","bcar_number","payment_state","card_name","point_code","ApplNum","prht_discount_price","ppsl_cut_price","product_price","cp_price","ppre_save_point","ppre_use_point","TotPrice","cancel_state"],
                    'size' => [11, 19, 19, 19, 19,11, 11, 19, 11, 11, 11, 11, 11, 11, 11, 11, 11, 15, 11, 15, 11, 11, 11]
                )
            ];

            
            $엑셀파일 = $this->ci->file->makeExcelDownload($fileName, $excelData);

            return $엑셀파일;          

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 결제내역 결제수단 payment_method
    // 결제내역 PG payment_pg 
    // 포인트 결제수단 point_method 
    // 운영사 aocc_oper_code
    public function getVariableValue ($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            
            $type = $params['type']; 

            if($type == 'point_method'){
                $data = array(
                    ['text' => 'L.Point', 'value' => 'LTP'],
                    ['text' => 'GS&Point', 'value' => 'GSP'],
                    ['text' => '블루멤버스', 'value' => 'BLP'],
                    ['text' => '기아 레드멤버스', 'value' => 'BLP']
                );
            } else if($type == 'payment_method'){
                $data = array(
                    ['text' => '신용카드', 'value' => 'CA_KCP'],
                    ['text' => 'PAYCO', 'value' => 'CA_PAYCO'],
                    ['text' => 'L.POINT', 'value' => 'PO_LTP'],
                    ['text' => 'GS&Point', 'value' => 'PO_GSP'],
                    ['text' => '블루멤버스', 'value' => 'PO_BLP'],
                    ['text' => '기아 레드멤버스', 'value' => 'BLP'],
                    ['text' => '쿠폰', 'value' => 'CU_C']
                );
            } else if($type == 'payment_pg'){
                $data = array(
                    ['text' => '전체', 'value' => 'ALL'],
                    ['text' => 'KCP', 'value' => '6'],
                    ['text' => 'PAYCO', 'value' => '13'],
                    ['text' => '이니시스', 'value' => '1'],
                );
            } else if($type == 'aocc_oper_code'){
                $data = array(
                    ['text' => '아이파킹', 'value' => '1']
                );
            }
            
            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];

            }  

            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }


     // 포인트 결제내역 엑셀 다운로드
     public function getPaymentPointExcelDownload($request, $response, $args)
     {
         try {
 


            $env = $request->getHeaderLine('env');

             ini_set('memory_limit', '-1');
             set_time_limit(0);
 
             $params = $this->ci->util->getParams($request);
             $now =  date('Ymd');
             $payment_method = $params['payment_method'];    // 결제수단 : 전체(세개 다 체크)/L.Point(LTP)/GS&Point(GSP)/BlueMemebers(BLP)
             $search_category = $params['search_category'];  // 검색카테고리 :memb_name(구매자명) bcar_number(차량번호): park_name(주차장명) ppre_appl_num(승인번호)
             $search_word = $params['search_word'];
             $start_date =  $params['start_date'];
             $end_date = $params['end_date'];
             $orderBy = $params['orderBy'] ?? 'DESC';
             $orderByColumn = $params['orderByColumn'] ?? 'ApplDateTime';
     
             $where = '';
             $binds = [];
 
             // Default 24시간 기준
             if(empty($start_date) || empty($end_date)){ 
                 $where = 'WHERE PPRE.ppre_appl_date = :now';
                 $binds['now'] = $now;
             } else if(!empty($start_date) && !empty($end_date)){
                 $start_date = date('Ymd', strtotime($start_date));
                 $end_date = date('Ymd', strtotime($end_date));  
                 $where = 'WHERE PPRE.ppre_appl_date between :start_date and :end_date';
                 $binds['start_date'] = $start_date;
                 $binds['end_date'] = $end_date;
             }
 
             if(!empty($search_category) && !empty($search_word)){
                 if ($search_category == 'park_name'){
                     $where .= ' AND '.$search_category.' like :search_word';
                     $binds['search_word'] = '%'.$search_word.'%';
                 } else{ 
                     $where .= ' AND '.$search_category.' = :search_word';
                     $binds['search_word'] = $search_word;
                 }
             }
             
             if(!empty($payment_method)){
                 $where .= ' AND PCLI.point_card_code in ('.$this->ci->dbutil->arrayToInQuery($payment_method).')';
             }
             
             $stmt = $this->ci->iparkingCloudDb->prepare("
                 SELECT
                 IPAY.icpr_seq AS icpr_seq,
                 PPRE.ppre_seq, 
                 PCLI.point_card_name AS point_card_name,
                 IPAY.MOID AS moid,
                 (
                     CASE
                         WHEN PPSL.ppsl_reg_device_cd in (3,9) THEN '파킹 APP' 
                         WHEN PPSL.ppsl_reg_device_cd = 2 THEN '파킹 WEB' 
                         WHEN IPAY.icpr_product_cd = 5 THEN '파킹 APP'
                     END  
                 ) as code_name,
                 CONCAT(
                     substr(PPRE.ppre_appl_date,1,4),'-',substr(PPRE.ppre_appl_date,5,2),'-',substr(PPRE.ppre_appl_date,7,2),' ',substr(PPRE.ppre_appl_time,1,2),':',substr(PPRE.ppre_appl_time,3,2),':',substr(PPRE.ppre_appl_time,5,2)
                 ) AS ApplDateTime,
                CONCAT(
                     substr(PTCC.ppca_date,1,4),'-',substr(PTCC.ppca_date,5,2),'-',substr(PTCC.ppca_date,7,2),' ',substr(PTCC.ppca_time,1,2),':',substr(PTCC.ppca_time,3,2),':',substr(PTCC.ppca_time,5,2)
                 ) AS PointCancelDateTime,
                 (
                     CASE 
                         WHEN IPAY.icpr_product_cd = 5 THEN '시간주차'
                         WHEN PRDT.prdt_product_cd  = 1 THEN '정기'
                         WHEN PRDT.prdt_product_cd  = 2 THEN '일일' 
                         WHEN PRDT.prdt_product_cd  = 5 THEN '시간'  
                     END
                 ) AS parking_type,
                 ppre_product_cd,
                 (
                     CASE 
                         WHEN IPAY.icpr_product_cd = 2 THEN PRDT.prdt_name 
                         WHEN IPAY.icpr_product_cd = 5 THEN CONCAT(IFNULL(IFNULL(inot_duration,TIMESTAMPDIFF(minute, inot_enter_datetime, inot_exit_datetime)),0),'분')
                     END
                 ) AS prdt_name,
                 PARK.park_name AS park_name,
                 REPLACE(MEMB.memb_name, substr(MEMB.memb_name,2,1), '*') AS memb_name,
                 CONCAT(
                         memb_mobile_1,'-',memb_mobile_2,'-',memb_mobile_3
                 ) AS memb_tel,
                 IPAY.bcar_number AS bcar_number, 
                 (
                     CASE 
                         WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분결제'
                         WHEN IPAY.Totprice >= 1 AND PMCC.cancel_price IS NULL AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point >= 1 THEN '부분취소'
                         WHEN IPAY.TotPrice >= 1 AND PMCC.cancel_price != 0 AND PPRE.ppre_use_point >= 1 AND PTCC.ppca_cancel_point IS NULL THEN '부분취소'
                         WHEN IPAY.TotPrice = 0 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point  THEN '전체취소' 
                         WHEN PPRE.ppre_use_point IS NULL AND IPAY.TotPrice = PMCC.cancel_price  THEN '전체취소'
                         WHEN INOT.inot_local_pay_machine_cd = 1 AND PTCC.ppca_cancel_point IS NOT NULL THEN '전체취소'
                         WHEN IPAY.TotPrice != 0 AND IPAY.TotPrice = PMCC.cancel_price && PPRE.ppre_use_point >= 1 AND PPRE.ppre_use_point = PTCC.ppca_cancel_point THEN '전체취소'
                         ELSE '결제완료'
                     END 
                 ) AS payment_state,
                 (   
                     CASE 
                         WHEN PTCC.ppca_cancel_point IS NOT NULL THEN '취소' 
                         WHEN PPRE.ppre_use_point = 0 AND PPRE.ppre_save_point >= 1 THEN '적립' 
                         ELSE '결제' 
                     END
                 ) AS point_state,
                 ppre_cancel_ny,
                 ppre_appl_num,
                 IFNULL(PPRE.ppre_use_point,0) AS use_point,
                 IFNULL(PPRE.ppre_save_point,0) AS ppre_save_point,
                 IFNULL(PTCC.ppca_cancel_point,0) AS ppca_cancel_point,
                 IFNULL(PTCC.ppca_cancel_save_point,0) AS ppca_cancel_save_point,
                 IPAY.Totprice AS Totprice,
                 CONCAT(
                     substr(PMCC.cancel_date,1,4),'-',substr(PMCC.cancel_date,5,2),'-',substr(PMCC.cancel_date,7,2),' '
                     ,substr(PMCC.cancel_time,1,2),':',substr(PMCC.cancel_time,3,2),':',substr(PMCC.cancel_time,5,2)
                 ) AS cancel_datetime, 
                PPRE.ppre_tid AS ppre_tid,
                PTCC.ppca_tid AS ppca_tid,
                (
                    CASE 
                        WHEN PPRE.ppre_use_point >= 1 THEN 1 
                        ELSE 0
                    END 
                )AS point_use_ny,
                (
                    CASE 
                        WHEN PTCC.ppca_cancel_point >= 1 THEN 1 
                        ELSE 0
                    END 
                )AS point_use_cancel_ny,
                (
                    CASE 
                        WHEN PPRE.ppre_save_point >= 1 THEN 1 
                        ELSE 0
                    END 
                )AS point_save_ny,
                (
                    CASE 
                        WHEN PTCC.ppca_cancel_save_point >= 1 THEN 1 
                        ELSE 0
                    END 
                )AS point_save_cancel_ny
                 FROM fdk_parkingcloud.point_payment_result PPRE
                 LEFT JOIN fdk_parkingcloud.inicis_payment_result IPAY ON IPAY.icpr_seq = PPRE.icpr_seq AND icpr_product_cd in (2,5)
                 LEFT JOIN fdk_parkingcloud.acd_rpms_inout INOT ON INOT.inot_icpr_seq = IPAY.icpr_seq 
                 LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product_sales PPSL ON PPSL.ppsl_seq = IPAY.icpr_product_seq 
                 LEFT JOIN fdk_parkingcloud.point_card_list PCLI ON PCLI.point_card_code = PPRE.ppre_point_cd
                 LEFT JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON IPAY.memb_seq = MEMB.memb_seq
                 LEFT JOIN fdk_parkingcloud.payment_cancel PMCC ON PMCC.tid = IPAY.tid AND PMCC.icpr_seq = IPAY.icpr_seq 
                 AND PMCC.cancel_code = 0000 AND PMCC.cancel_price >= 1
                 LEFT JOIN fdk_parkingcloud.point_cancel PTCC ON PTCC.icpr_seq = IPAY.icpr_seq
                 LEFT JOIN fdk_parkingcloud.acd_pms_parkinglot PARK ON IPAY.park_seq = PARK.park_seq AND PARK.park_del_ny = 0
                 LEFT JOIN fdk_parkingcloud.arf_operation_company_code AOCC ON AOCC.aocc_oper_cd = IPAY.icpr_operating_cmpy_cd
                 LEFT JOIN fdk_parkingcloud.acd_rpms_parking_product PRDT ON PPSL.prdt_seq = PRDT.prdt_seq AND PRDT.prdt_del_ny = 0
                     
                 ".$where." ORDER BY ".$orderByColumn.' '.$orderBy);
 
             $stmt->execute($binds);
             $result = $this->ci->dbutil->fetchAllWithJson($stmt);

            $data = [];
            $point_use = '';
            $point_use_cancel = '';
            $point_save = '';
            $point_save_cancel = '';

            foreach($result as $result_row){
                $icpr_seq = $result_row['icpr_seq'];
                $payment_state = $result_row['payment_state'];
                $ppre_seq = $result_row['ppre_seq'];
                $point_use_ny = $result_row['point_use_ny'];
                $point_use_cancel_ny = $result_row['point_use_cancel_ny'];
                $point_save_ny = $result_row['point_save_ny'];
                $point_save_cancel_ny = $result_row['point_save_cancel_ny'];
                $point_card_name = $result_row['point_card_name'];
                $moid = $result_row['moid'];
                $code_name = $result_row['code_name'];
                $ApplDateTime = $result_row['ApplDateTime'];
                $PointCancelDateTime = $result_row['PointCancelDateTime'];
                $parking_type = $result_row['parking_type'];
                $ppre_product_cd = $result_row['ppre_product_cd'];
                $prdt_name = $result_row['prdt_name'];
                $park_name = $result_row['park_name'];
                $memb_name = $result_row['memb_name'];
                $memb_tel = $result_row['memb_tel'];
                $bcar_number = $result_row['bcar_number'];
                $payment_state = $result_row['payment_state'];
                $point_state = '';
                $ppre_cancel_ny = $result_row['ppre_cancel_ny'];
                $ppre_appl_num = $result_row['ppre_appl_num'];
                $use_point = $result_row['use_point'];
                $ppre_save_point = $result_row['ppre_save_point'];
                $ppca_cancel_point = $result_row['ppca_cancel_point'];
                $ppca_cancel_save_point = $result_row['ppca_cancel_save_point'];
                $Totprice = $result_row['Totprice'];
                $cancel_datetime = $result_row['cancel_datetime'];
                $ppre_tid = $result_row['ppre_tid'];
                $ppca_tid = $result_row['ppca_tid'];

                // ppre_tid, ppca_tid 도 넣어야하는지?
                
                if($point_use_ny != 0){
                    array_push($data,array(
                        'icpr_seq' => $icpr_seq,
                        'ppre_seq' => $ppre_seq,
                        'code_name' => $code_name, 
                        'point_card_name' => $point_card_name,
                        'moid' => $moid,
                        'point_state' => '결제',
                        'ppre_save_point' => 0,
                        'use_point' => $use_point,
                        'ApplDateTime' => $ApplDateTime,
                        'parking_type' => $parking_type,
                        'ppre_product_cd' => $ppre_product_cd,
                        'prdt_name' => $prdt_name,
                        'park_name' => $park_name,
                        'memb_name' => $memb_name,
                        'memb_tel' => $memb_tel,
                        'bcar_number' => $bcar_number,
                        'payment_state' => $payment_state,
                        'ppre_appl_num' => $ppre_appl_num,
                        'ppre_tid' => $ppre_tid, 
                        'ppca_tid' => '',
                        'cancel_datetime' => $cancel_datetime,
                        'Totprice' => $Totprice 
                    ));
                }
                if($point_use_cancel_ny != 0){
                    array_push($data,array(
                        'icpr_seq' => $icpr_seq,
                        'ppre_seq' => $ppre_seq,
                        'code_name' => $code_name, 
                        'point_card_name' => $point_card_name,
                        'moid' => $moid,
                        'point_state' => '결제취소',
                        'ppre_save_point' => 0,
                        'use_point' => '-'.$ppca_cancel_point,
                        'ApplDateTime' => $PointCancelDateTime,
                        'parking_type' => $parking_type,
                        'ppre_product_cd' => $ppre_product_cd,
                        'prdt_name' => $prdt_name,
                        'park_name' => $park_name,
                        'memb_name' => $memb_name,
                        'memb_tel' => $memb_tel,
                        'bcar_number' => $bcar_number,
                        'payment_state' => $payment_state,
                        'ppre_appl_num' => $ppre_appl_num,
                        'ppre_tid' => $ppre_tid, 
                        'ppca_tid' => $ppca_tid,
                        'cancel_datetime' => $cancel_datetime,
                        'Totprice' => $Totprice 
                    ));
                }
                if($point_save_ny != 0){
                    array_push($data,array(
                        'icpr_seq' => $icpr_seq,
                        'ppre_seq' => $ppre_seq,
                        'code_name' => $code_name, 
                        'point_card_name' => $point_card_name,
                        'moid' => $moid,
                        'point_state' => '적립',
                        'use_point' => 0,
                        'ppre_save_point' => $ppre_save_point,
                        'ApplDateTime' => $ApplDateTime,
                        'parking_type' => $parking_type,
                        'ppre_product_cd' => $ppre_product_cd,
                        'prdt_name' => $prdt_name,
                        'park_name' => $park_name,
                        'memb_name' => $memb_name,
                        'memb_tel' => $memb_tel,
                        'bcar_number' => $bcar_number,
                        'payment_state' => $payment_state,
                        'ppre_appl_num' => $ppre_appl_num,
                        'ppre_tid' => $ppre_tid, 
                        'ppca_tid' => '',
                        'cancel_datetime' => $cancel_datetime,
                        'Totprice' => $Totprice 
                    ));
                }

                if($point_save_cancel_ny != 0){
                    array_push($data,array(
                        'icpr_seq' => $icpr_seq,
                        'ppre_seq' => $ppre_seq,
                        'code_name' => $code_name, 
                        'point_card_name' => $point_card_name,
                        'moid' => $moid,
                        'point_state' => '적립취소',
                        'use_point' => 0,
                        'ppre_save_point' => '-'.$ppca_cancel_save_point,
                        'ApplDateTime' => $PointCancelDateTime,
                        'parking_type' => $parking_type,
                        'ppre_product_cd' => $ppre_product_cd,
                        'prdt_name' => $prdt_name,
                        'park_name' => $park_name,
                        'memb_name' => $memb_name,
                        'memb_tel' => $memb_tel,
                        'bcar_number' => $bcar_number,
                        'payment_state' => $payment_state,
                        'ppre_appl_num' => $ppre_appl_num,
                        'ppre_tid' => $ppre_tid, 
                        'ppca_tid' => $ppca_tid,
                        'cancel_datetime' => $cancel_datetime,
                        'Totprice' => $Totprice 
                    ));
                }
            }

             $fileName = '포인트결제내역.xls';
             $excelData = [
                 array(
                     'name' => '포인트결제내역',
                     'type' => 'countHistory',
                     'data' => $data,
                     'key' => ["번호" ,"구매채널", "포인트구분", "포인트상태", "포인트사용","포인트적립", "사용/취소날짜","주차구분", "상품명", "주차장명", "구매자명", "핸드폰번호", "차량번호","최종PG결제상태","포인트승인번호","결제거래번호","취소거래번호","PG결제금액","최종PG취소일시"],
                     'column' => ["ppre_seq","code_name", "point_card_name", "point_state","use_point","ppre_save_point","ApplDateTime", "parking_type", "prdt_name", "park_name", "memb_name", "memb_tel", "bcar_number","payment_state","ppre_appl_num","ppre_tid","ppca_tid","Totprice","cancel_datetime"],
                     'size' => [9, 11, 11, 13, 11, 11, 25, 11, 11, 25, 11, 19, 11, 11, 11, 27, 27, 11, 19]
                 )
             ];
             
             $엑셀파일 = $this->ci->file->makeExcelDownload($fileName, $excelData);
 
             return $엑셀파일;          
 
         } catch (Exception $e) {
             return $response->withJson(['error' => $e->getMessage()]);
         }
     }
    
}

