<?php

class PointPolicyController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    // 포인트 정책 목록
    public function getPolicyList($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request, [
                'limit' => 10,
                'offset' =>0
            ]);

            $limit = (int) $params['limit'];
            $offset = (int) $params['offset'];

            

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT count(*) as cnt FROM fdk_parkingcloud.point_card_list pcl
                INNER JOIN (
                    SELECT 
                        point_card_code
                    FROM 
                        fdk_parkingcloud.point_card_policy 
                    group by point_card_code
                ) pcp ON pcp.point_card_code = pcl.point_card_code
            ');
            $stmt -> execute();
            $totalCountList = $stmt->fetch();
            $totalCount = $totalCountList['cnt'] ?? 0;

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    pcl.point_card_code,
                    pcl.point_card_name,
                    pcl.is_new
                FROM
                    fdk_parkingcloud.point_card_list pcl
                INNER JOIN (
                    SELECT 
                        point_card_code
                    FROM 
                        fdk_parkingcloud.point_card_policy 
                    group by point_card_code
                ) pcp ON pcp.point_card_code = pcl.point_card_code
                LIMIT :limit OFFSET :offset
            ');
            $stmt -> execute([
                'limit' => $limit,
                'offset' => $offset
            ]);
            $pointCardList = $stmt->fetchAll();

            $pointCard_arr = [];
            foreach($pointCardList as $rows) {
                $pointCard_arr[] = $rows['point_card_code'];
            }


            $orderBy = 'ORDER BY ';
            if (isset($params['startdateOrderBy']) && $params['startdateOrderBy'] == 'DESC') {
                $orderBy = 'pcp.cooperation_start_date DESC';
            } else if (isset($params['startdateOrderBy']) && $params['startdateOrderBy'] == 'ASC') {
                $orderBy = 'pcp.cooperation_start_date ASC';
            }
            if($orderBy == null){
                $orderBy = 'pcp.cooperation_start_date ASC';
            }

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    point_card_code,
                    cooperation_start_date,
                    cooperation_end_date,
                    purchase_max_avail_point,
                    parkingpass_max_avail_point,
                    payment_min_point,
                    is_parkingpass_sync,
                    operation_method,
                    save_rate,
                    point_use_unit
                FROM
                    fdk_parkingcloud.point_card_policy
                WHERE
                    point_card_code in ('.$this->ci->dbutil->arrayToInQuery($pointCard_arr).')
                ORDER BY point_card_code ASC
            ');
            $stmt -> execute();
            $pointPolicyList = $stmt->fetchAll();

            $result['data'] = [];
            foreach($pointCardList as $pointCardListRows) {
                
                $point_card_code = $pointCardListRows['point_card_code'];
                $point_card_name = $pointCardListRows['point_card_name'];
                $is_new = $pointCardListRows['is_new'];
                
                foreach($pointPolicyList as $pointPoilicyRows) {
                    $point_policy_point_card_code = $pointPoilicyRows['point_card_code'];   
                    $operation_method = $pointPoilicyRows['operation_method'];
                    $cooperation_start_date = $pointPoilicyRows['cooperation_start_date'];
                    $cooperation_end_date = $pointPoilicyRows['cooperation_end_date'];
                    $purchase_max_avail_point = $pointPoilicyRows['purchase_max_avail_point'];
                    $parkingpass_max_avail_point = $pointPoilicyRows['parkingpass_max_avail_point'];
                    $payment_min_point = $pointPoilicyRows['payment_min_point'];
                    $is_parkingpass_sync = $pointPoilicyRows['is_parkingpass_sync'];
                    $save_rate = $pointPoilicyRows['save_rate'];
                    $point_use_unit = $pointPoilicyRows['point_use_unit'];
                    if($point_card_code == $point_policy_point_card_code) {
                        if(in_array(
                                $result_arr['point_card_code'],
                                array(
                                    'point_card_code' => $point_card_code
                                )
                            ) && ($save_rate != null)
                        ) {
                            $result_arr['operation_method'] .= ",".$operation_method;                       
                        } else {
                            if($save_rate != null) {
                                $result_arr['point_card_code'] = $point_card_code;
                                $result_arr['point_card_name'] = $point_card_name;
                                $result_arr['is_new'] = $is_new;                          
                                $result_arr['operation_method'] = $operation_method;
                                $result_arr['cooperation_start_date'] = $cooperation_start_date;
                                $result_arr['cooperation_end_date'] = $cooperation_end_date;
                                $result_arr['purchase_max_avail_point'] = $purchase_max_avail_point;
                                $result_arr['parkingpass_max_avail_point'] = $parkingpass_max_avail_point;
                                $result_arr['payment_min_point'] = $payment_min_point;
                                $result_arr['is_parkingpass_sync'] = $is_parkingpass_sync;
                                $result_arr['point_use_unit'] = $point_use_unit;
                            }
                        }
                    }
                    
                }
                $result['data'][] = $result_arr;
            }

            $currentPage = ($offset /$limit) + 1;
            $pageCount = ceil( (int)$totalCount / $limit );

            $result['pageInfo'] = array(
                'currentPage' => $currentPage,
                'limit' => $limit,
                'offset' => $offset,
                'pageCount' => $pageCount,
                'totalCount' => $totalCount
            );

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);
            
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }



    // 현재 날짜를 포함 포인트 정책 목록 리스트
    public function getCompriseList($request, $response, $args)
    {
        try {
            
            $now = date("Y-m-d");

            $data = $this->ci->policy->compriseList($now);

            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = [];
            }

            return $response->WithJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 현재 날짜를 포함 포인트 정책 상세내용
    public function getCompriseDetail($request, $response, $args)
    {
        try {
            
            $params = $this->ci->util->getParams($request);
            $point_card_code = $params['point_card_code'];    
            $now = date("Y-m-d");

            $data = $this->ci->policy->compriseDetail($now,$point_card_code);

            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = [];
            }

            return $response->WithJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 포인트 정책 추가
    public function postPolicyAdd($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $point_card_code = $params['point_card_code'];                                      //[필수] 포인트 코드
            $cooperation_start_date = $params['cooperation_start_date'];                        //[필수] 제휴시작일
            $cooperation_end_date = $params['cooperation_end_date']?? "9999-12-31";             //[필수] 제휴종료일 
            $point_card_name = $params['point_card_name'];                                      // 포인트 이름
            $purchase_max_avail_point = $params['purchase_max_avail_point'];                    // 상품 최대 사용 포인트
            $point_use_unit = $params['point_use_unit'];                                        // 상품 최대 사용 포인트 사용단위 (%,P)/(PCT/VAL) 
            $payment_min_point = $params['payment_min_point'];                                  // 상품 최소 사용 가능 포인트
            $is_parkingpass_sync = $params['is_parkingpass_sync'];                              // 파킹패스 여부 (Y/N)
            $parkingpass_max_avail_point = $params['parkingpass_max_avail_point'];              // 파킹패스 최대 사용 포인트
            $is_new = $params['is_new'];                                                        // 프로모션 마크 (Y/N)
            $description = $params['description'];                                              // 정책 문구(20byte)
            $usage_description = $params['usage_description'];                                  // 사용 문구
            $maintenance_start_datetime = $params['maintenance_start_datetime'];                // 점검문구 시작일자
            if($maintenance_start_datetime == "") $maintenance_start_datetime = null;
            $maintenance_end_datetime = $params['maintenance_end_datetime'];                    // 점검문구 종료일자
            if($maintenance_end_datetime == "") $maintenance_end_datetime = null;
            $maintenance_description = $params['maintenance_description'];                      // 점검문구
            $pub = $params['PUB'];
            $pri = $params['PRI'];

            if(!isset($point_card_code)) throw new Exception ("코드번호 3자리를 입력하세요.");
            if(!isset($point_card_name)) throw new Exception ("포인트명을 입력하세요.");
            if(!isset($purchase_max_avail_point)) throw new Exception ("상품 결제 최대사용 포인트를 입력하세요.");
            if(!isset($payment_min_point)) throw new Exception ("상품 결제 최소사용 포인트를 입력하세요.");
            if($is_parkingpass_sync == 'Y'){
                if(!isset($parkingpass_max_avail_point)) throw new Exception ("파킹패스 최대 사용포인트를 입력하세요.");
            }else if(($is_parkingpass_sync == 'N')){
                $parkingpass_max_avail_point = null;
            }

            // $pri = array(  
            //         "operation_method" =>  "PRI",
            //         "save_rate" => "0.30",
            //         "save_commission_rate" => "0.2",
            //         "use_commission_rate" => "0.1" 
            // ); 
            // $pub = array(
            //     "operation_method" =>  "PUB",
            //     "save_rate" => "0.30",
            //     "save_commission_rate" => "0.75",
            //     "use_commission_rate" => "0.55" 
            // );

            // 카드리스트 테이블에 포인트코드가 이미 등록되어있으면 UPDATE 없다면 INSERT
            $duplicate_card_check = $this->ci->policy->duplicateCardCheck($point_card_code); 
            if($duplicate_card_check >0) throw new Exception("이미 포인트카드가 등록되어있습니다.");

            if($duplicate_policy_cooperation_date_check == 0){ 
                if(!empty($pub) && $pub != null){
                    $operation_method = $pub['operation_method'];
                    $save_rate = $pub['save_rate'];
                    $use_commission_rate = $pub['use_commission_rate'];
                    $save_commission_rate = $pub['save_commission_rate'];
                    if(isset($save_rate) && isset($save_commission_rate) && isset($use_commission_rate)){ 
                        $policy_insert = $this->ci->policy->policyInsert($point_card_code,$cooperation_start_date,$cooperation_end_date,$point_use_unit,$purchase_max_avail_point,$parkingpass_max_avail_point,$payment_min_point,$is_parkingpass_sync,$operation_method,$save_rate,$save_commission_rate,$use_commission_rate);
                    }else{
                        throw new Exception ("PUB 적립율 %를 입력하세요.");
                    }
                }
                if(!empty($pri) && $pri != null){
                    $operation_method = $pri['operation_method'];
                    $save_rate = $pri['save_rate'];
                    $use_commission_rate = $pri['use_commission_rate'];
                    $save_commission_rate = $pri['save_commission_rate'];
                    if(isset($save_rate) && isset($save_commission_rate) && isset($use_commission_rate)){
                        $policy_insert = $this->ci->policy->policyInsert($point_card_code,$cooperation_start_date,$cooperation_end_date,$point_use_unit,$purchase_max_avail_point,$parkingpass_max_avail_point,$payment_min_point,$is_parkingpass_sync,$operation_method,$save_rate,$save_commission_rate,$use_commission_rate);
                    }else{
                        throw new Exception ("PRI 적립율 %를 입력하세요.");
                    } 
                }
                if($duplicate_card_check == 0){
                    $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_card_list', [[
                        'point_card_code' => $point_card_code,
                        'point_card_name' => $point_card_name,
                        'is_new' => $is_new,
                        'description' => $description,
                        'manage_description' => $manage_description,
                        'usage_description' => $usage_description,
                        'maintenance_start_datetime' => $maintenance_start_datetime,
                        'maintenance_end_datetime' => $maintenance_end_datetime,
                        'maintenance_description' => $maintenance_description
                    ]]);  
                }
            }else {
                throw new Exception ("중복된 코드번호가 있습니다. 다시 입력하세요.");
            }
        
                
            $msg = $this->ci->message->apiMessage['success']; 
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }



     // 포인트 정책 상세보기
    public function getPolicyDetail($request, $response, $args)
    {
        try {


            $params = $this->ci->util->getParams($request);

            $point_card_code = $params['point_card_code'];
             
            if(isset($point_card_code)){   
                $stmt = $this->ci->iparkingCloudDb->prepare('
                    SELECT
                        pcl.point_card_code AS point_card_code,
                        pcl.point_card_name AS point_card_name,
                        pcp.cooperation_start_date AS cooperation_start_date,
                        pcp.cooperation_end_date AS cooperation_end_date,
                        pcp.point_use_unit AS point_use_unit,
                        pcp.save_rate AS save_rate,
                        pcp.operation_method AS operation_method,
                        pcp.purchase_max_avail_point AS purchase_max_avail_point,
                        pcp.parkingpass_max_avail_point AS parkingpass_max_avail_point,
                        pcp.payment_min_point AS payment_min_point,
                        pcp.is_parkingpass_sync AS is_parkingpass_sync,
                        pcp.save_commission_rate AS save_commission_rate,
                        pcp.use_commission_rate AS use_commission_rate,
                        pcl.is_new AS is_new,
                        pcl.description AS description,
                        pcl.usage_description,
                        pcl.maintenance_start_datetime,
                        pcl.maintenance_end_datetime,
                        pcl.maintenance_description
                    FROM 
                        fdk_parkingcloud.point_card_list AS pcl
                    INNER JOIN fdk_parkingcloud.point_card_policy AS pcp ON pcp.point_card_code = pcl.point_card_code
                    WHERE 
                        pcl.point_card_code = :point_card_code
                ');
                $stmt -> execute(['point_card_code'=>$point_card_code]);
                
                $policyList = $stmt->fetchAll();

                foreach($policyList as $policyList_rows){  

                    $save_rate = $policyList_rows['save_rate'];
                    $save_commission_rate = $policyList_rows['save_commission_rate'];
                    $use_commission_rate = $policyList_rows['use_commission_rate'];
                    $operation_method = $policyList_rows['operation_method']; 
                    $point_card_code = $policyList_rows['point_card_code'];
                    $point_card_name = $policyList_rows['point_card_name'];
                    $cooperation_start_date = $policyList_rows['cooperation_start_date'];
                    $cooperation_end_date = $policyList_rows['cooperation_end_date'];
                    $point_use_unit = $policyList_rows['point_use_unit'];
                    $purchase_max_avail_point = $policyList_rows['purchase_max_avail_point'];
                    $parkingpass_max_avail_point = $policyList_rows['parkingpass_max_avail_point'];
                    $payment_min_point = $policyList_rows['payment_min_point'];
                    $is_parkingpass_sync = $policyList_rows['is_parkingpass_sync'];
                    $is_new = $policyList_rows['is_new'];
                    $description = $policyList_rows['description'];
                    $usage_description = $policyList_rows['usage_description'];
                    $maintenance_start_datetime = $policyList_rows['maintenance_start_datetime'];
                    $maintenance_end_datetime = $policyList_rows['maintenance_end_datetime'];
                    $maintenance_description = $policyList_rows['maintenance_description'];

                    if($operation_method == 'PRI'){
                        if($save_rate == null && $save_commission_rate == null && $use_commission_rate == null) {
                            $PRI = null;
                        } else {
                            $PRI = array(
                                'operation_method' => $operation_method,
                                'save_rate' => $save_rate,
                                'save_commission_rate' => $save_commission_rate,
                                'use_commission_rate' => $use_commission_rate,
                            );
                        }
                    }

                    if($operation_method == 'PUB'){
                        if($save_rate == null && $save_commission_rate == null && $use_commission_rate == null) {
                            $PUB = null;
                        } else {
                            $PUB = array(
                                'operation_method' => $operation_method,
                                'save_rate' => $save_rate,
                                'save_commission_rate' => $save_commission_rate,
                                'use_commission_rate' => $use_commission_rate,
                            );
                        }
                    }
    
                    $data = array(
                        "point_card_code" => $point_card_code,
                        "point_card_name" => $point_card_name,
                        "cooperation_start_date" => $cooperation_start_date,
                        "cooperation_end_date" => $cooperation_end_date,
                        "point_use_unit" => $point_use_unit,
                        "purchase_max_avail_point" => $purchase_max_avail_point,
                        "parkingpass_max_avail_point" => $parkingpass_max_avail_point,
                        "payment_min_point" => $payment_min_point,
                        "is_parkingpass_sync" => $is_parkingpass_sync,
                        "is_new" => $is_new,
                        "description" => $description,
                        "usage_description" => $usage_description,
                        "maintenance_start_datetime" => $maintenance_start_datetime,
                        "maintenance_end_datetime" => $maintenance_end_datetime,
                        "maintenance_description" => $maintenance_description,
                        "PRI" => $PRI,
                        "PUB" => $PUB 
                    );
                }
            }
            
            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = [];
            }
    
            return $response->withJson($msg); 

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 포인트 정책 수정
    public function putPolicyDetail($request, $response, $args)
    {
        try {


            $params = $this->ci->util->getParams($request);

            $point_card_code = $params['point_card_code']?? "TTT";                                              // 포인트 코드
            $point_card_name = $params['point_card_name'];                                              // 포인트 이름
            
            $cooperation_start_date = $params['cooperation_start_date']?? '2019-03-09';                                // 변경 제휴 시작일       
            $cooperation_end_date = $params['cooperation_end_date']?? '2020-01-31';                                    // 변경 제휴 종료일
            $point_use_unit = $params['point_use_unit'];                                                // 상품 최대 사용 포인트 사용단위 (%,P)/(PCT/VAL) 
            $purchase_max_avail_point = $params['purchase_max_avail_point'];                            // [상품] 최대 사용 포인트
            $payment_min_point = $params['payment_min_point'];                                          // [상품] 최소 사용 가능 포인트
            $is_parkingpass_sync = $params['is_parkingpass_sync'];                                      // 파킹패스 여부 (Y/N)
            $parkingpass_max_avail_point = $params['parkingpass_max_avail_point'];                      // [파킹패스] 최대 사용 포인트
            $is_new = $params['is_new'];                                                                // 프로모션 마크 (Y/N)
            $description = $params['description'];                                                      // 정책 문구(20byte)
            $usage_description = $params['usage_description'];                                          // 사용 문구
            $maintenance_start_datetime = $params['maintenance_start_datetime'];                        // 점검문구 시작일자
            $maintenance_end_datetime = $params['maintenance_end_datetime'];                            // 점검문구 종료일자
            if($maintenance_start_datetime == "") $maintenance_start_datetime = null;
            if($maintenance_end_datetime == "") $maintenance_end_datetime = null;
            $maintenance_description = $params['maintenance_description'];                              // 점검문구

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];

            $pub = $params['PUB'];                                                                      // 공영 정보
            $pri = $params['PRI'];                                                                      // 민영 정보

            $update_arr = $params['update_arr'];

            if(!isset($point_card_code)) throw new Exception ("코드번호 3자리를 입력하세요.");
            if(!isset($point_card_name)) throw new Exception ("포인트명을 입력하세요.");
            if(!isset($purchase_max_avail_point)) throw new Exception ("상품 결제 최대사용 포인트를 입력하세요.");
            if(!isset($payment_min_point)) throw new Exception ("상품 결제 최소사용 포인트를 입력하세요.");
            if($is_parkingpass_sync == 'Y'){
                if(!isset($parkingpass_max_avail_point)) throw new Exception ("파킹패스 최대 사용포인트를 입력하세요.");
            }else if(($is_parkingpass_sync == 'N')){
                $parkingpass_max_avail_point = null;
            }

            $binds['point_use_unit'] = $point_use_unit;
            $binds['purchase_max_avail_point'] = $purchase_max_avail_point;
            $binds['payment_min_point'] = $payment_min_point;
            $binds['is_parkingpass_sync'] = $is_parkingpass_sync;
            $binds['parkingpass_max_avail_point'] = $parkingpass_max_avail_point;

            if(isset($point_card_code) && isset($cooperation_start_date) && isset($cooperation_end_date)) {

                $binds['cooperation_start_date'] = $cooperation_start_date;
                $binds['cooperation_end_date'] = $cooperation_end_date;

                // PUB
                if(!empty($pub) && $pub != null){
                    $operation_method = $pub['operation_method'];
                    $save_rate = $pub['save_rate'];
                    $use_commission_rate = $pub['use_commission_rate'];
                    $save_commission_rate = $pub['save_commission_rate'];
                    
                    $binds['save_rate'] = $save_rate;
                    $binds['use_commission_rate'] = $use_commission_rate;
                    $binds['save_commission_rate'] = $save_commission_rate;
                    
                    if(isset($save_rate) && isset($save_commission_rate) && isset($use_commission_rate)){
                        $operation_method_count = $this->ci->policy->duplicatePolicyPointCode($point_card_code, $operation_method);
                        if($operation_method_count > 0) {
                            $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', $binds,[
                                'point_card_code' => $point_card_code,
                                'operation_method' => $operation_method
                            ]);
                        } else {
                            $binds['point_card_code'] = $point_card_code;
                            $binds['cooperation_start_date'] = $cooperation_start_date;
                            $binds['cooperation_end_date'] = $cooperation_end_date;
                            $binds['operation_method'] = $operation_method;
                            $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', [$binds]);
                        }
                    } else{
                        throw new Exception ("적립율 %를 입력하세요.");
                    } 
                } else {
                    $operation_method = 'PUB';
                    $operation_method_count = $this->ci->policy->duplicatePolicyPointCode($point_card_code, $operation_method);
 
                    if($operation_method_count > 0) {
                        $binds['operation_method'] = 'PUB';
                        $binds['save_rate'] = null;
                        $binds['use_commission_rate'] = null;
                        $binds['save_commission_rate'] = null;

                        $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', $binds,[
                            'point_card_code' => $point_card_code,
                            'operation_method' => $operation_method
                        ]);
                    }
                }

                // PRI
                if(!empty($pri) && $pri != null){
                    $operation_method = $pri['operation_method'];
                    $save_rate = $pri['save_rate'];
                    $use_commission_rate = $pri['use_commission_rate'];
                    $save_commission_rate = $pri['save_commission_rate'];

                    $binds['operation_method'] = $operation_method;
                    $binds['save_rate'] = $save_rate;
                    $binds['use_commission_rate'] = $use_commission_rate;
                    $binds['save_commission_rate'] = $save_commission_rate;

                    if(isset($save_rate) && isset($save_commission_rate) && isset($use_commission_rate)){
                        $operation_method_count = $this->ci->policy->duplicatePolicyPointCode($point_card_code, $operation_method);
                        if($operation_method_count > 0) {
                            $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', $binds,[
                                'point_card_code' => $point_card_code,
                                'operation_method' => $operation_method
                            ]);
                        } else {
                            $binds['point_card_code'] = $point_card_code;
                            $binds['cooperation_start_date'] = $cooperation_start_date;
                            $binds['cooperation_end_date'] = $cooperation_end_date;
                            $binds['operation_method'] = $operation_method;
                            $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', [$binds]);
                        }
                    }else{
                        throw new Exception ("적립율 %를 입력하세요.");
                    } 
                } else {
                    $operation_method = 'PRI';
                    $operation_method_count = $this->ci->policy->duplicatePolicyPointCode($point_card_code, $operation_method);
                    if($operation_method_count > 0) {
                        $binds['operation_method'] = 'PRI';
                        $binds['save_rate'] = null;
                        $binds['use_commission_rate'] = null;
                        $binds['save_commission_rate'] = null;
                        $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', $binds,[
                            'point_card_code' => $point_card_code,
                            'operation_method' => $operation_method
                        ]);
                    }
                }

                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_list', [
                    'point_card_name' => $point_card_name,
                    'is_new' => $is_new,
                    'description' => $description,
                    'manage_description' => $manage_description,
                    'usage_description' => $usage_description,
                    'maintenance_start_datetime' => $maintenance_start_datetime,
                    'maintenance_end_datetime' => $maintenance_end_datetime,
                    'maintenance_description' => $maintenance_description
                ],[
                    'point_card_code' => $point_card_code,
                ]); 

                $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_card_change_log', [[
                    'point_card_code' => $point_card_code,
                    'modify_datetime' => $now,
                    'modifier_id' => $user_id,
                    'change_log' => $update_arr,
                ]]);  

            } else {
                throw new Exception ("날짜를 확인해주세요.");
            }
           
            $msg = $this->ci->message->apiMessage['success']; 
            return $response->withJson($msg); 

            
        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }


    // 포인트 정책 테이블 삭제(제휴종료일을 현재보다 이전으로)
    public function deletePolicyDelete($request, $response, $args)
    {
        try {
            
            $params = $this->ci->util->getParams($request);
            
            $point_card_code = $params['point_card_code'];
            $cooperation_start_date = $params['cooperation_start_date'];                
            $cooperation_end_date = $params['cooperation_end_date'];
            $change_date = date("Y-m-d",strtotime("-1 day"));
              
            if(isset($point_card_code) && isset($cooperation_start_date) && isset($cooperation_end_date)){
                $policy_check = $this->ci->policy->duplicatePolicyPointCode($point_card_code,$cooperation_start_date,$cooperation_end_date, null);
                if($policy_check != 0){
                    $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', [
                        'cooperation_end_date' => $change_date
                    ], [
                        'point_card_code' => $point_card_code,
                        'cooperation_start_date' => $cooperation_start_date,
                        'cooperation_end_date' => $cooperation_end_date
                    ]);
                }else{
                    throw new Exception ("결과를 찾을 수 없습니다.");
                }
            }else{
                throw new Exception ("실패.");
            }

            $msg = $this->ci->message->apiMessage['success']; 
            return $response->withJson($msg); 

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}
?>