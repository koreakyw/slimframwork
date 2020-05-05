<?php

class MarkUpController
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function postMarkUpList($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $northeastLat = $params['northeastLat'];
            $northeastLon = $params['northeastLon'];
            $southwestLat = $params['southwestLat'];
            $southwestLon = $params['southwestLon'];
            $search_vechicle = $params['search_vechicle'];
            $search_product = $params['search_product'];
            $memb_seq = $params['memb_seq'];

            $park_brand_kind = $params['park_brand_kind'];

            // 잘못된 회원코드 처리
            if($memb_seq == 0 || $memb_seq == "")
            {
                $memb_seq = null;
            }

            # 위도, 경도 받기(현위치 or 목적지)
            $dest_latitude = $params['dest_latitude'];
            $dest_longitude = $params['dest_longitude'];

            // $search_distance = $params['search_distance'];
            $markerIds = $params['markerIds'];
            $tx_operating_cmpy_cd = $params['tx_operating_cmpy_cd'];
            $tx_region_ct = $params['tx_region_ct'];

            $setting_fare_time = $params['setting_fare_time'];
            // 필수값 체크
            if($northeastLat == null || $northeastLat == ""
            || $northeastLon == null || $northeastLon == ""
            || $southwestLat == null || $southwestLat == ""
            || $southwestLon == null || $southwestLon == ""
            || $park_brand_kind == null || $park_brand_kind == "" || $park_brand_kind == 0
            || $search_vechicle == null || $search_vechicle == ""
            || $search_product == null || $search_product == "") 
            {
                $msg = $this->ci->message->apiMessage['required'];
                return $response->withJson($msg);
            }

            if ($search_product == 5 )
            {
                if ( $setting_fare_time == null || $setting_fare_time == ""){
                    $msg = $this->ci->message->apiMessage['required'];
                    return $response->withJson($msg);
                }
            }
            
            $park_brand_kind_str = "";
            $park_brand_kind_1 = false;
            $park_brand_kind_2 = false;
            $park_brand_kind_4 = false;
            $park_brand_kind_8 = false;
            $park_brand_kind_16 = false;

            $park_brand_kind_park = false;

            # park_brand_kind
            // 1	아이파킹존
            // 2	발렛
            // 4	파트너스존
            // 8	일반주차장
            // 16    제휴할인

            if( ($park_brand_kind & 1) > 0 ){
                $park_brand_kind_str = "1";
                $park_brand_kind_1 = true;
                $park_brand_kind_park = true;
            }
            if( ($park_brand_kind & 2) > 0 ){
                if($park_brand_kind_str == "")
                    $park_brand_kind_str = "3";
                else $park_brand_kind_str.=",3";
                                
                $park_brand_kind_2 = true;
                $park_brand_kind_park = true;
            }
            if( ($park_brand_kind & 4) > 0 ){
                if($park_brand_kind_str == "")
                    $park_brand_kind_str = "2";
                else $park_brand_kind_str.=",2";
                                
                $park_brand_kind_4 = true;
                $park_brand_kind_park = true;
            }

            if(($park_brand_kind & 8) > 0 )
                $park_brand_kind_8 = true;

            if(($park_brand_kind & 16) > 0 )
                $park_brand_kind_16 = true;

            // echo "\n";
            // echo $park_brand_kind."\n";
            // echo $park_brand_kind_1."\n";
            // echo $park_brand_kind_2."\n";
            // echo $park_brand_kind_4."\n";
            // echo $park_brand_kind_8."\n";
            // echo $park_brand_kind_16."\n";
            // echo $park_brand_kind_park."\n";
            // echo $park_brand_kind_str."\n";

            // return null;

            $sql = "
            SELECT 
                    MARK.table_name,
                    MARK.marker_id,
                    MARK.park_seq,
                    MARK.park_name,
                    MARK.park_latitude,
                    MARK.park_longitude,
                    MARK.park_division,
                    MARK.park_reveal_ny,

                    MARK.park_address_1,
                    ifnull(MARK.park_address_2, '') as park_address_2,

                    MARK.plla_seq,

                    MARK.lcad_name,
                    MARK.lcad_address_1,
                    ifnull(MARK.lcad_address_2, '') as lcad_address_2,

                    MARK.plla_discount_rate,
                    MARK.plla_discount_cd,
                    MARK.plla_discount,

                    CASE
                        WHEN MARK.park_interval_free_ny = 1 THEN null
                        ELSE MIN(MARK.price_normal)
                    END as price_normal,
                    CASE
                        WHEN MARK.park_interval_free_ny = 1 THEN null
                        ELSE MIN(MARK.price_sale)
                    END as price_sale,
                    
                    MIN(MARK.price_name) AS price_name,

                    MARK.park_regular_monthly_price,
                    MARK.park_oneday_price,
                    MARK.park_interval_free_ny,
                    getCode(MARK.park_basic_interval_minute_cd,125) AS park_basic_interval_minute,
                    MARK.park_basic_interval_price,
                    getCode(MARK.park_additional_interval_minute_cd ,125) AS park_additional_interval_minute,
                    MARK.park_additional_interval_price,
                    MARK.park_echarge_name,
                    MARK.park_echarge_ny,

                    hourPrice,

                    MARK.parking_pass_ny,
                    MARK.parkinglot_form,
                    MARK.alliance_discount_ny,
                    MARK.pay_sort,

                    TRUNCATE(distance, 2) as distance,

                    ";
                    if($memb_seq != null){
                        $sql.="
                    pafv_seq,
                        ";
                    } else {
                        $sql.="
                    null as pafv_seq,
                        ";
                    }

                    $sql.="
                    lcad_business_high,
                    lcad_business_low
            FROM (";
            
            if($park_brand_kind_park == true)
            {
                $sql.="
                    SELECT 
                            'PARK' AS table_name,
                            CONCAT('PARK_',PARK.park_seq) AS marker_id,
                            PARK.park_seq,
                            parkGetName(PARK.park_seq) as park_name,
                            PARK.park_latitude,
                            PARK.park_longitude,
                            PARK.park_division,
                            PARK.park_reveal_ny,
                            
                            park_address_1,
                            park_address_2,

                            0 AS plla_seq,

                            '' AS lcad_name,
                            '' AS lcad_address_1,
                            '' AS lcad_address_2,

                            0 AS plla_discount_rate,
                            0 AS plla_discount_cd,
                            0 AS plla_discount,

                            price_normal,
                            price_sale,
                            price_name,

                            CASE
                                WHEN park_division = 3 THEN null
                                ELSE PARK.park_regular_monthly_price
                            END as park_regular_monthly_price,

                            PARK.park_oneday_price,
                            PARK.park_interval_free_ny,
                            PARK.park_basic_interval_minute_cd,
                            PARK.park_basic_interval_price,
                            PARK.park_additional_interval_minute_cd,
                            PARK.park_additional_interval_price,
                            getCode(PARK.park_echarge_cd,159) AS park_echarge_name,
                            PARK.park_echarge_ny,
                            ";
                            if( $setting_fare_time != 0 || $setting_fare_time != null | $setting_fare_time != ""){
                                $sql.="
                                getHourPrice(".$setting_fare_time.",getCode(park_basic_interval_minute_cd,125), park_basic_interval_price , getCode(park_additional_interval_minute_cd ,125), park_additional_interval_price )
                                as hourPrice,
                                ";
                            } else {
                                $sql.= "
                                null as hourPrice,
                                ";
                            }
                            $sql.="
                            CASE WHEN PARK.park_app_vision_info & 8 = 8 THEN 1
                                 ELSE 0
                            END as parking_pass_ny,

                            CASE WHEN PARK.park_app_vision_info & 2 = 2 THEN PARK.park_shape_ct
                                 ELSE 0
                            END as parkinglot_form,

                            CASE WHEN PARK.park_app_vision_info & 32 = 32 THEN 1
                                 ELSE 0
                            END as alliance_discount_ny,

                            CASE WHEN PARK.park_app_vision_info & 16 = 16 THEN PARK.park_app_vision_pay_info
                                 ELSE 0
                            END as pay_sort,
                            ";
                        if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                            $sql.="
                            ROUND(6371 * acos( cos(radians(".$dest_latitude.")) * cos(radians(PARK.park_latitude)) * cos(radians(PARK.park_longitude) - radians(".$dest_longitude.")) +
                            sin(radians(".$dest_latitude.")) * sin(radians(PARK.park_latitude))), 2) AS distance,
                            ";
                        } else {
                            $sql.="
                            null AS distance,
                            ";
                        }

                        if($memb_seq != null){
                            $sql.="
                            pafv_seq,
                            ";
                        } else{
                            $sql.="
                            null as pafv_seq,
                            ";
                        }
        
                $sql.="
                            null AS lcad_business_high,
                            null AS lcad_business_low
                    FROM fdk_parkingcloud.acd_pms_parkinglot PARK 
                    LEFT JOIN 
                ";
            

            switch ($search_vechicle){
                case 1 : $sql.="
                        (
                            SELECT  PRDT.park_seq,
                                    IF( PPPR.pppr_price_normal_small > 0, PPPR.pppr_price_normal_small, NULL )  AS price_normal,
                                    IF( PPPR.pppr_price_sale_small > 0, PPPR.pppr_price_sale_small, NULL )  AS price_sale,
                                    prdt_hour_time AS price_name
                            FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                        break;
                case 2 : $sql.="
                        (
                            SELECT  PRDT.park_seq,
                                    IF( PPPR.pppr_price_normal_midsize > 0, PPPR.pppr_price_normal_midsize, NULL )  AS price_normal,
                                    IF( PPPR.pppr_price_sale_midsize > 0, PPPR.pppr_price_sale_midsize, NULL )  AS price_sale,
                                    prdt_hour_time AS price_name
                            FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                        break;
                case 3 : $sql.="
                        (
                            SELECT  PRDT.park_seq,   
                                    IF( PPPR.pppr_price_normal_van > 0, PPPR.pppr_price_normal_van, NULL )  AS price_normal,
                                    IF( PPPR.pppr_price_sale_van > 0, PPPR.pppr_price_sale_van, NULL )  AS price_sale,
                                    prdt_hour_time AS price_name
                            FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                        break;
                case 4 : $sql.="
                        (
                            SELECT  PRDT.park_seq,   
                                    IF( PPPR.pppr_price_normal_mid_truck > 0, PPPR.pppr_price_normal_mid_truck, NULL )  AS price_normal,
                                    IF( PPPR.pppr_price_sale_mid_truck > 0, PPPR.pppr_price_sale_mid_truck, NULL )  AS price_sale,
                                    prdt_hour_time AS price_name
                            FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                        break;
                case 5 : $sql.="
                        (
                            SELECT  PRDT.park_seq,   
                                    IF( PPPR.pppr_price_normal_big_truck > 0, PPPR.pppr_price_normal_big_truck, NULL )  AS price_normal,
                                    IF( PPPR.pppr_price_sale_big_truck > 0, PPPR.pppr_price_sale_big_truck, NULL )  AS price_sale,
                                    prdt_hour_time AS price_name
                            FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                        break;

                }
                $sql.="
                            INNER JOIN 
                            (
                                    SELECT *
                                    FROM acd_rpms_parking_product_price
                                    WHERE pppr_del_ny = 0
                            ) PPPR ON PRDT.prdt_seq = PPPR.prdt_seq 
									
                            WHERE PRDT.prdt_del_ny = 0
                                AND PRDT.prdt_use_ny = 1
                                AND PRDT.prdt_product_cd = ".$search_product."
                        ) PRDT2 ON PARK.park_seq = PRDT2.park_seq AND PARK.park_del_ny = 0 	
                        ";
                    
                        if($memb_seq != null){
                            $sql.="
                        LEFT JOIN
                            acd_rpms_parkinglot_favorite fav
                                on fav.pafv_partnership_cd = 1 and PARK.park_seq = fav.pafv_parkinglot_seq and fav.memb_seq = ".$memb_seq." and fav.pafv_del_ny = 0
                            ";
                        } 

                    $sql.="			  
                        WHERE PARK.park_del_ny = 0
                            AND PARK.park_reveal_ny = 1

                            AND PARK.park_latitude > ".$southwestLat."
                            AND PARK.park_latitude < ".$northeastLat."
                            AND PARK.park_longitude > ".$southwestLon."
                            AND PARK.park_longitude < ".$northeastLon;
                    if($park_brand_kind_str != "")
                    {
                        $sql.="
                            AND PARK.park_division in (".$park_brand_kind_str.")
                        ";
                    }
                    if($search_product == 1){
                        $sql.="
                            AND PARK.park_division <> 3
                        ";
                    }
            }
            
            if($park_brand_kind_8 == true)
            {
                if($park_brand_kind_park == true)
                {
                    $sql.="
                    UNION ALL
                ";
                }

                $sql.="
                        SELECT 
                                'PAGL' AS table_name,
                                CONCAT('PAGL_',pagl_seq) AS marker_id,
                                pagl_seq AS park_seq,
                                pagl_name as park_name,
                                pagl_latitude AS park_latitude,
                                pagl_longitude AS park_longitude,
                                null AS park_division,
                                null AS park_reveal_ny,

                                pagl_address_1 AS park_address_1,
                                pagl_address_2 AS park_address_2,

                                0 AS plla_seq,

                                0 AS lcad_name,
                                0 AS lcad_address_1,
                                0 AS lcad_address_2,

                                0 AS plla_discount_rate,
                                0 AS plla_discount_cd,
                                0 AS plla_discount,

                                null AS price_normal,
                                null AS price_sale,
                                null AS price_name,
                                
                                pagl_regular_monthly_price              AS park_regular_monthly_price,
                                pagl_oneday_price                       AS park_oneday_price,
                                pagl_interval_free_ny                   AS park_interval_free_ny,
                                pagl_basic_interval_minute_cd           AS park_basic_interval_minute_cd,
                                pagl_basic_interval_price               AS park_basic_interval_price,
                                pagl_additional_interval_minute_cd      AS park_additional_interval_minute_cd,
                                pagl_additional_interval_price          AS park_additional_interval_price,
                                getCode(pagl_echarge_cd,159)            AS park_echarge_name,
                                pagl_echarge_ny                         AS park_echarge_ny,
                                
                                ";

                if( $setting_fare_time != 0 || $setting_fare_time != null | $setting_fare_time != ""){
                    $sql.="
                                getHourPrice(".$setting_fare_time.",getCode(pagl_basic_interval_minute_cd,125), pagl_basic_interval_price , getCode(pagl_additional_interval_minute_cd ,125), pagl_additional_interval_price )
                                as hourPrice,
                                ";
                } else {
                    $sql.= "
                                null as hourPrice,
                                ";
                }
                                        
                $sql.="
                                0 as parking_pass_ny,

                                CASE WHEN pagl_app_vision_info & 2 = 2 THEN pagl_shape_ct
                                     ELSE 0
                                END as parkinglot_form,

                                0 as alliance_discount_ny,

                                CASE WHEN pagl_app_vision_info & 8 = 8 THEN pagl_app_vision_pay_info
                                     ELSE 0
                                END as pay_sort,
                                ";
                if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null )
                {
                    $sql.="
                                ROUND(6371 * acos( cos(radians(".$dest_latitude.")) * cos(radians(pagl_latitude)) * cos(radians(pagl_longitude) - radians(".$dest_longitude.")) +
                                sin(radians(".$dest_latitude.")) * sin(radians(pagl_latitude))), 2) AS distance,
                    ";    
                } else {
                    $sql.="
                                null AS distance,
                        ";
                }
                if($memb_seq != null){
                    $sql.="
                                pafv_seq,
                        ";
                } else{
                    $sql.="
                                null as pafv_seq,
                        ";
                }
                $sql.="
                                null AS lcad_business_high,
                                null AS lcad_business_low
                        FROM fdk_parkingcloud.acd_rpms_parkinglot_general
                        ";
                if($memb_seq != null){
                    $sql.="
                        LEFT JOIN
                            acd_rpms_parkinglot_favorite fav
                                on fav.pafv_partnership_cd = 2 and pagl_seq = fav.pafv_parkinglot_seq and fav.memb_seq = ".$memb_seq." and fav.pafv_del_ny = 0
                    ";
                }

                $sql.="
                        WHERE pagl_del_ny = 0
                            AND pagl_latitude > ".$southwestLat."
                            AND pagl_latitude < ".$northeastLat."
                            AND pagl_longitude > ".$southwestLon."
                            AND pagl_longitude < ".$northeastLon;
            }

            if($park_brand_kind_16 == true)
            {
                if($park_brand_kind_park == true || $park_brand_kind_8 == true)
                {
                    $sql.="
                    UNION ALL
                    ";
                }

                $sql.="
                        SELECT 
                                'PAAD' AS table_name,
                                CONCAT('PAAD_',plla_seq) AS marker_id,
                                PAAD.park_seq AS park_seq,
                                parkGetName(PAAD.park_seq) as park_name,
                                lcad_latitude AS park_latitude,
                                lcad_longitude AS park_longitude,
                                PARK.park_division,
                                PARK.park_reveal_ny,
                                
                                park_address_1,
                                park_address_2,

                                PAAD.plla_seq AS plla_seq,
                                
                                lcad_name,
                                lcad_address_1,
                                lcad_address_2,

                                plla_discount_rate,
                                plla_discount_cd,
                                getCode(PAAD.plla_discount_cd,152) AS plla_discount,

                                null AS price_normal,
                                null AS price_sale,
                                null AS price_name,

                                0 AS park_regular_monthly_price,
                                0 AS park_oneday_price,
                                0 AS park_interval_free_ny,
                                0 AS park_basic_interval_minute_cd,
                                0 AS park_basic_interval_price,
                                0 AS park_additional_interval_minute_cd,
                                0 AS park_additional_interval_price,
                                null AS  park_echarge_name,
                                null AS  park_echarge_ny,

                                null as hourPrice,

                                0 AS parking_pass_ny,
                                0 AS parkinglot_form,
                                0 AS alliance_discount_ny,
                                0 AS pay_sort,

                ";
                if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                    $sql.="
                                ROUND(6371 * acos( cos(radians(".$dest_latitude.")) * cos(radians(LOCA.lcad_latitude)) * cos(radians(LOCA.lcad_longitude) - radians(".$dest_longitude.")) +
                                sin(radians(".$dest_latitude.")) * sin(radians(LOCA.lcad_latitude))), 2) AS distance,
                ";    
                } else {
                    $sql.="
                                null AS distance,
                    ";
                }
                $sql.="
                            null as pafv_seq,

                            catePath((commSplit(lcad_business_ct, ';', 1)))          AS lcad_business_high,
                            catePath((commSplit(lcad_business_ct, ';', 2)))          AS lcad_business_low
                        FROM fdk_parkingcloud.acd_rpms_parkinglot_local_ad PAAD 
                        INNER JOIN fdk_parkingcloud.acd_rpms_local_ad LOCA on LOCA.lcad_seq = PAAD.lcad_seq
                        INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK on PAAD.park_seq = PARK.park_seq
                        WHERE lcad_del_ny = 0
                            AND plla_del_ny = 0
                            AND PARK.park_del_ny  = 0
                            AND PARK.park_reveal_ny = 1
                            AND lcad_latitude > ".$southwestLat."
                            AND lcad_latitude < ".$northeastLat."
                            AND lcad_longitude > ".$southwestLon."
                            AND lcad_longitude < ".$northeastLon;
            }
                
                            
        $sql.="
                    ) MARK
                    WHERE 1 = 1
            ";

            if(!empty($markerIds)){
                $markerIds_string = implode(", ", $markerIds);
                $sql.=" AND MARK.marker_id NOT IN ".$markerIds_string;
            }

            if($tx_operating_cmpy_cd != null || $tx_operating_cmpy_cd != ""){
                if($tx_operating_cmpy_cd != 1){
                    $sql.="AND a.park_operating_cmpy_cd = ".$tx_operating_cmpy_cd;
                }
            }

            if($tx_region_ct != null || $tx_region_ct != ""){
                $sql.="AND a.park_region_ct LIKE  = %".$tx_region_ct."%";
            }   

            if($search_product == 1){
                $sql.="AND MARK.park_regular_monthly_price IS NOT NULL
                ";
            } else if ($search_product == 2){
                $sql.="AND MARK.park_oneday_price IS NOT NULL
                ";
            }

            $sql.="GROUP BY MARK.park_seq, MARK.plla_seq";

            // echo $sql;
            // return null;
            $this->ci->iparkingCloudDb->exec('use fdk_parkingcloud');
            $stmt = $this->ci->iparkingCloudDb->prepare($sql);

            $stmt->execute();

            $data = $stmt->fetchall();

            

            foreach($data as &$data_row){
                $parkinglot_info = array();
                $parking_pass_ny = null;
                $parkinglot_form = null;
                $alliance_discount_ny = null;
                $pay_sort = null;

                if($data_row['parking_pass_ny'] == 1){
                    $parking_pass_ny = "파킹패스";
                }

                if($data_row['parkinglot_form'] != 0 || $data_row['parkinglot_form'] != null){
                    if( strpos($data_row['parkinglot_form'], "6;") !== false ){
                        $parkinglot_form = "기계식";
                    } else if( strpos($data_row['parkinglot_form'], "5;7")  !== false){
                        $parkinglot_form = "건축물";
                    } else if( strpos($data_row['parkinglot_form'], "5;8")  !== false){
                        $parkinglot_form = "철골구조";
                    } else if( strpos($data_row['parkinglot_form'], "5;9")  !== false){
                        $parkinglot_form = "나대지";
                    } else if( strpos($data_row['parkinglot_form'], "5;290")  !== false){
                        $parkinglot_form = "복합";
                    } 
                    // 6은 현재 값이 없다.
                    // else if( strpos($data_row['parkinglot_form'], "5;10") ){
                    //     $parkinglot_form = "6";
                    // }
                }

                if($data_row['alliance_discount_ny'] == 1){
                    $alliance_discount_ny = "제휴할인";
                }

                if($data_row['pay_sort'] == 1){
                    $pay_sort = "카드전용";
                } else if($data_row['pay_sort'] == 2){
                    $pay_sort = "카드,현금";
                } else if($data_row['pay_sort'] == 3){
                    $pay_sort = "현금전용";
                }
                
                if($parking_pass_ny != null ){
                    array_push($parkinglot_info, $parking_pass_ny);
                } 
                if($parkinglot_form != null ){
                    array_push($parkinglot_info, $parkinglot_form);
                } 
                if($alliance_discount_ny != null ){
                    array_push($parkinglot_info, $alliance_discount_ny);
                } 
                if($pay_sort != null ){
                    array_push($parkinglot_info, $pay_sort);
                }

                $data_row['parkinglot_info'] = $parkinglot_info;

                $data_row['search_product'] = $search_product;
                
            }
            
            $msg = $this->ci->message->apiMessage['success'];
            $msg['result'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            $msg = $this->ci->message->apiMessage['fail'];
            return $response->withJson($msg);
        }
    }


    public function postParkinglotList($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $thisPage = $params['thisPage'];
            $listRows = $params['listRows'];
            $onPaging = $params['onPaging'];
            $memb_seq = $params['memb_seq'];
            $park_brand_kind = $params['park_brand_kind'];
            // $search_radio = $params['search_radio'];

            $memb_seq = $params['memb_seq'];

            // 잘못된 회원코드 처리
            if($memb_seq == 0 || $memb_seq == ""){
                $memb_seq = null;
            }

            $dest_latitude = $params['dest_latitude'];
            $dest_longitude = $params['dest_longitude'];
            $search_product = $params['search_product'];
            $search_vechicle = $params['search_vechicle'];
            $search_distance = $params['search_distance'];
            $search_order = $params['search_order'];
            $setting_fare_time = $params['setting_fare_time'];

            // 필수값 체크
            if($search_order == null || $search_order == ""             // [정렬방식] PRICE:요금순, DISTANCE:거리순
            || $park_brand_kind == null || $park_brand_kind == ""       // [주차장 브랜드] 아이파킹존,발렛존,파트너스존,일반/무료,제휴할인
            || $park_brand_kind == "0"                               
            || $search_distance == null || $search_distance == ""       // [기준거리] 300m, 500, 1km, 1km이상
            || $setting_fare_time == null || $setting_fare_time == ""   // [기준요금] 30분,1시간,2시간,3시간
            || $search_product == null || $search_product == ""         // [주차상품] 정기권, 일일권, 시간권...
            || $search_vechicle == null || $search_vechicle == "")      
            {    
                $msg = $this->ci->message->apiMessage['required'];
                return $response->withJson($msg);
            }

            // 주차장리스트는 제휴할인은 제외하여 계산한다.
            if($park_brand_kind == 16){
                $msg = $this->ci->message->apiMessage['success'];
                $msg['result'] = null;
                return $response->withJson($msg);
            } else if($park_brand_kind > 16){
                $park_brand_kind = $park_brand_kind - 16;
            }

            $park_brand_kind_str = "";
            $park_brand_kind_1 = false;
            $park_brand_kind_2 = false;
            $park_brand_kind_4 = false;
            $park_brand_kind_8 = false;

            # park_brand_kind
            // 1	아이파킹존
            // 2	발렛
            // 4	파트너스존
            // 8	일반주차장
            if( ($park_brand_kind & 1) > 0 ){
                $park_brand_kind_str = "1";
                $park_brand_kind_1 = true;
            }
            if( ($park_brand_kind & 2) > 0 ){
                if($park_brand_kind_str == "")
                    $park_brand_kind_str = "3";
                else $park_brand_kind_str.=",3";
                                
                $park_brand_kind_2 = true;
            }
            if( ($park_brand_kind & 4) > 0 ){
                if($park_brand_kind_str == "")
                    $park_brand_kind_str = "2";
                else $park_brand_kind_str.=",2";
                                
                $park_brand_kind_4 = true;
            }

            if(($park_brand_kind & 8) > 0 )
                $park_brand_kind_8 = true;

            // echo "\n";
            // echo $park_brand_kind."\n";
            // echo $park_brand_kind_1."\n";
            // echo $park_brand_kind_2."\n";
            // echo $park_brand_kind_4."\n";
            // echo $park_brand_kind_8."\n";
            // echo $park_brand_kind_str."\n";

            $sql.="
                    FROM (
            ";
            if($park_brand_kind != 8)
            {
                $sql.="
                        SELECT	
                                'PARK' AS table_name,
                                PARK.park_seq,
                                PARK.park_name,
                                PARK.park_address_1,
                                PARK.park_address_2,

                                PARK.park_longitude,
                                PARK.park_latitude,

                                PARK.park_division,
                                PARK.park_region_ct,

                                min(price_normal) as price_normal,
                                min(price_sale) as price_sale,
                                MIN(price_name) AS price_name,

                                PARK.park_regular_monthly_price,
                                PARK.park_oneday_price,
                                PARK.park_interval_free_ny,
                                PARK.park_basic_interval_minute_cd,
                                PARK.park_basic_interval_price,
                                PARK.park_additional_interval_minute_cd,
                                PARK.park_additional_interval_price,
                            ";
                            if( $setting_fare_time != 0 || $setting_fare_time != null | $setting_fare_time != ""){
                                $sql.="
                                getHourPrice(".$setting_fare_time.",getCode(park_basic_interval_minute_cd,125), park_basic_interval_price , getCode(park_additional_interval_minute_cd ,125), park_additional_interval_price )
                                as hourPrice,
                                ";
                            } else {
                                $sql.= "
                                null as hourPrice,
                                ";
                            }
                            
                            $sql.="
                                CASE WHEN PARK.park_app_vision_info & 8 = 8 THEN 1
                                    ELSE 0
                                END as parking_pass_ny,

                                CASE WHEN PARK.park_app_vision_info & 2 = 2 THEN PARK.park_shape_ct
                                    ELSE 0
                                END as parkinglot_form,

                                CASE WHEN PARK.park_app_vision_info & 32 = 32 THEN 1
                                    ELSE 0
                                END as alliance_discount_ny,

                                CASE WHEN PARK.park_app_vision_info & 16 = 16 THEN PARK.park_app_vision_pay_info
                                    ELSE 0
                                END as pay_sort,
                                ";
                if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                    $sql.="
                                ROUND(6371 * acos( cos(radians(".$dest_latitude.")) * cos(radians(PARK.park_latitude)) * cos(radians(PARK.park_longitude) - radians(".$dest_longitude.")) +
                                sin(radians(".$dest_latitude.")) * sin(radians(PARK.park_latitude))), 2) AS distance,
                ";    
                } else {
                    $sql.="
                                null AS distance,
                    ";
                }

                if($memb_seq != null){
                    $sql.="
                                fav.pafv_seq
                    ";
                } else {
                    $sql.="
                                null as pafv_seq
                    ";
                }

                $sql.="
                        FROM acd_pms_parkinglot PARK
                        LEFT JOIN
                ";

                switch ($search_vechicle){
                    case 1 : $sql.="
                            (
                                SELECT  PRDT.park_seq,
                                        IF( PPPR.pppr_price_normal_small > 0, PPPR.pppr_price_normal_small, NULL )  AS price_normal,
                                        IF( PPPR.pppr_price_sale_small > 0, PPPR.pppr_price_sale_small, NULL )  AS price_sale,
                                        prdt_hour_time AS price_name
                                FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                            break;
                    case 2 : $sql.="
                            (
                                SELECT  PRDT.park_seq,
                                        IF( PPPR.pppr_price_normal_midsize > 0, PPPR.pppr_price_normal_midsize, NULL )  AS price_normal,
                                        IF( PPPR.pppr_price_sale_midsize > 0, PPPR.pppr_price_sale_midsize, NULL )  AS price_sale,
                                        prdt_hour_time AS price_name
                                FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                            break;
                    case 3 : $sql.="
                            (
                                SELECT  PRDT.park_seq,   
                                        IF( PPPR.pppr_price_normal_van > 0, PPPR.pppr_price_normal_van, NULL )  AS price_normal,
                                        IF( PPPR.pppr_price_sale_van > 0, PPPR.pppr_price_sale_van, NULL )  AS price_sale,
                                        prdt_hour_time AS price_name
                                FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                            break;
                    case 4 : $sql.="
                            (
                                SELECT  PRDT.park_seq,   
                                        IF( PPPR.pppr_price_normal_mid_truck > 0, PPPR.pppr_price_normal_mid_truck, NULL )  AS price_normal,
                                        IF( PPPR.pppr_price_sale_mid_truck > 0, PPPR.pppr_price_sale_mid_truck, NULL )  AS price_sale,
                                        prdt_hour_time AS price_name
                                FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                            break;
                    case 5 : $sql.="
                            (
                                SELECT  PRDT.park_seq,   
                                        IF( PPPR.pppr_price_normal_big_truck > 0, PPPR.pppr_price_normal_big_truck, NULL )  AS price_normal,
                                        IF( PPPR.pppr_price_sale_big_truck > 0, PPPR.pppr_price_sale_big_truck, NULL )  AS price_sale,
                                        prdt_hour_time AS price_name
                                FROM fdk_parkingcloud.acd_rpms_parking_product PRDT";
                            break;
                }

                $sql.="
                                INNER JOIN (
                                                            
                                    SELECT *
                                    FROM acd_rpms_parking_product_price
                                    WHERE pppr_del_ny = 0
                            ) PPPR ON PRDT.prdt_seq = PPPR.prdt_seq 
                                    
                            WHERE PRDT.prdt_del_ny = 0
                                AND PRDT.prdt_use_ny = 1
                                AND PRDT.prdt_product_cd = ".$search_product."
                        ) PRDT2 ON PARK.park_seq = PRDT2.park_seq AND PARK.park_del_ny = 0 	
                        ";
                    
                        if($memb_seq != null){
                            $sql.="
                        LEFT JOIN
                            acd_rpms_parkinglot_favorite fav
                                on fav.pafv_partnership_cd = 1 and PARK.park_seq = fav.pafv_parkinglot_seq and fav.memb_seq = ".$memb_seq." and fav.pafv_del_ny = 0
                            ";
                        } 

                    $sql.="				  
                        WHERE PARK.park_del_ny = 0
                            AND PARK.park_reveal_ny = 1
                ";

                if($park_brand_kind_str != ""){
                    $sql.="
                        AND PARK.park_division in (".$park_brand_kind_str.")
                    ";
                }
                
                if($search_product == 1){
                    $sql.="
                        AND PARK.park_division <> 3
                        AND PARK.park_regular_monthly_price is not null
                    ";
                }

                if($search_product == 2){
                    $sql.="
                        AND PARK.park_oneday_price is not null
                    ";
                }

                // if($southwestLat != null && $southwestLat != "" && !$searchButton){
                if($southwestLat != null && $southwestLat != ""){
                    $sql.="
                        AND PARK.park_latitude >".$southwestLat."
                        AND PARK.park_latitude <".$northeastLat."
                        AND PARK.park_longitude >".$southwestLon."
                        AND PARK.park_longitude <".$northeastLon."
                ";
                }

                if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                    if($search_distance != null || $search_distance != ""){
                        $sql.="
                        AND ROUND(6371 * acos( cos( radians(".$dest_latitude.") ) * cos( radians( PARK.park_latitude ) ) * cos( radians( PARK.park_longitude )
                        - radians(".$dest_longitude.") ) + sin( radians(".$dest_latitude.") ) * sin( radians( PARK.park_latitude ) ) ), 2) <= ".$search_distance."
                        ";
                    }
                }
                $sql.="
                        GROUP BY PARK.park_seq
                ";

            }

            if($park_brand_kind_8 == true){
                if($park_brand_kind != 8){
                    $sql.="
                    UNION ALL
                    ";
                }
                
                $sql.="
                SELECT 
                        'PAGL'                                                                    AS table_name,
                        PAGL.pagl_seq                                                             AS park_seq,
                        PAGL.pagl_name                                                            AS park_name,
                        PAGL.pagl_address_1                                                       AS park_address_1,
                        PAGL.pagl_address_2                                                       AS park_address_2,

                        PAGL.pagl_longitude AS park_longitude,
                        PAGL.pagl_latitude  AS park_latitude,

                        NULL                                                                      AS park_division,
                        PAGL.pagl_region_ct                                                       AS park_region_ct,

                        null AS price_normal,
                        null AS price_sale,
                        null AS price_name,

                        pagl_regular_monthly_price              AS park_regular_monthly_price,
                        pagl_oneday_price                       AS park_oneday_price,
                        pagl_interval_free_ny                   AS park_interval_free_ny,
                        pagl_basic_interval_minute_cd           AS park_basic_interval_minute_cd,
                        pagl_basic_interval_price               AS park_basic_interval_price,
                        pagl_additional_interval_minute_cd      AS park_additional_interval_minute_cd,
                        pagl_additional_interval_price          AS park_additional_interval_price,
                        ";

                    if( $setting_fare_time != 0 || $setting_fare_time != null | $setting_fare_time != ""){
                        $sql.="
                        getHourPrice(".$setting_fare_time.",getCode(pagl_basic_interval_minute_cd,125), pagl_basic_interval_price , getCode(pagl_additional_interval_minute_cd ,125), pagl_additional_interval_price )
                        as hourPrice,
                        ";
                    } else {
                        $sql.= "
                        null as hourPrice,
                        ";
                    }
                            
                $sql.="
                        0 as parking_pass_ny,

                        CASE WHEN pagl_app_vision_info & 2 = 2 THEN pagl_shape_ct
                            ELSE 0
                        END as parkinglot_form,

                        0 as alliance_discount_ny,

                        CASE WHEN pagl_app_vision_info & 8 = 8 THEN pagl_app_vision_pay_info
                            ELSE 0
                        END as pay_sort,
                ";

                if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                $sql.="
                        ROUND(6371 * acos( cos(radians(".$dest_latitude.")) * cos(radians(pagl_latitude)) * cos(radians(pagl_longitude) - radians(".$dest_longitude.")) +
                        sin(radians(".$dest_latitude.")) * sin(radians(pagl_latitude))), 2) AS distance,
                    ";
                } else {
                    $sql.="
                        null AS distance,
                    ";
                }

                if($memb_seq != null){
                    $sql.="
                            fav.pafv_seq
                    ";
                } else{
                    $sql.="
                            null as pafv_seq
                    ";
                }

                $sql.="
                FROM fdk_parkingcloud.acd_rpms_parkinglot_general PAGL
                ";
            if($memb_seq != null){
                $sql.="
                LEFT JOIN
                    acd_rpms_parkinglot_favorite fav
                        on fav.pafv_partnership_cd = 2 and PAGL.pagl_seq = fav.pafv_parkinglot_seq and fav.memb_seq = ".$memb_seq." and fav.pafv_del_ny = 0
                    ";
            } 
    
                $sql.="
                WHERE pagl_del_ny = 0
                ";

                if($southwestLat != null && $southwestLat != ""){
                    $sql.="
                AND PARK.park_latitude >".$southwestLat."
                AND PARK.park_latitude <".$northeastLat."
                AND PARK.park_longitude >".$southwestLon."
                AND PARK.park_longitude <".$northeastLon."
                    ";
                }
                if($search_product == 1){
                    $sql.="
                AND PAGL.pagl_regular_monthly_price is not null
                    ";
                } else if($search_product == 2){
                    $sql.="
                AND PAGL.pagl_oneday_price is not null
                    ";
                }

                if($search_order != null && $search_order != "" && $search_order == "PRICE"){
                    $sql.="
                AND PAGL.pagl_interval_free_ny = 0
                    ";
                }

                if($search_distance != null || $search_distance != ""){
                    if($dest_latitude != "" || $dest_latitude != null || $dest_longitude != "" || $dest_longitude != null ){
                        $sql.="
                AND ROUND(6371 * acos( cos( radians(".$dest_latitude.") ) * cos( radians( PAGL.pagl_latitude ) ) * cos( radians( PAGL.pagl_longitude )
                - radians(".$dest_longitude.") ) + sin( radians(".$dest_latitude.") ) * sin( radians( PAGL.pagl_latitude ) ) ), 2) <= ".$search_distance;
                    }
                }
            }
        $sql.="
        ) sub
         
        ";

        if($search_order == "DISTANCE"){
            $orderBy="
            (
                CASE park_division 
                    WHEN 1 THEN 1 
                    WHEN 3 THEN 2 
                    WHEN 2 THEN 3 
                    ELSE 4 
                END
            ), distance
            ";
        } else if ($search_order == "PRICE"){
            if($search_product == 5){
                $orderBy="
                (
                    CASE park_division 
                        WHEN 1 THEN 1 
                        WHEN 3 THEN 2 
                        WHEN 2 THEN 3 
                        ELSE 4 
                    END
                ), 
                hourPrice ASC ";
                    
                    
            } else if ($search_product == 1){
                $orderBy="
                (
                    CASE park_division 
                        WHEN 1 THEN 1 
                        WHEN 3 THEN 2 
                        WHEN 2 THEN 3 
                        ELSE 4 
                    END
                    ), 
                    park_regular_monthly_price
                ";
            } else if ($search_product == 2){
                $orderBy="
                (
                    CASE park_division 
                        WHEN 1 THEN 1 
                        WHEN 3 THEN 2 
                        WHEN 2 THEN 3 
                        ELSE 4 
                    END
                    ), 
                    park_oneday_price
                ";
            }
        }
            // $sql.="LIMIT 20, 10";

            // return null;
        
            // echo $sql;
            // return null;

            // echo $orderBy;
            // return null;

            $this->ci->iparkingCloudDb->exec('use fdk_parkingcloud');
            
            $limit = $listRows;
            $offset = 0;
            if($onPaging == "true"){
                if($thisPage == 1) {
                    $offset = 0;
                } else {
                    $offset = $listRows * ($thisPage-1);
                }
            }else {
                $limit = 999999;
                $offset = 0;
            }

            // echo $limit."\n";
            // echo $offset;

            // return null;

            $sel_query ="
                    table_name,
                    park_seq,
                    park_name,
                    park_address_1,
                    park_address_2,

                    park_longitude,
                    park_latitude,

                    park_division,
                    park_region_ct, 

                    CASE
                        WHEN park_interval_free_ny = 1 THEN null
                        ELSE price_normal
                    END as price_normal,
                    CASE
                        WHEN park_interval_free_ny = 1 THEN null
                        ELSE price_sale
                    END as price_sale,
                    price_name,

                    park_regular_monthly_price,
                    park_oneday_price,
                    park_interval_free_ny,
                    getCode(park_basic_interval_minute_cd,125) AS park_basic_interval_minute,
                    park_basic_interval_price,
                    getCode(park_additional_interval_minute_cd ,125) AS park_additional_interval_minute,
                    park_additional_interval_price,

                    hourPrice,

                    parking_pass_ny,
                    parkinglot_form,
                    alliance_discount_ny,
                    pay_sort,

                    TRUNCATE(distance, 2) as distance,
            ";
            if($memb_seq != null){
                $sel_query.="
                    pafv_seq
                ";
            } else {
                $sel_query.="
                    null as pafv_seq
                ";
            }

                // echo str_replace('%%', 'count(*)', "SELECT %% ".$sql);
            // echo"\n =================================================================================================================================================\n";
            // echo str_replace('%%',$sel_query
            // , " SELECT %% ".$sql).' ORDER BY '.$orderBy.' LIMIT '.$limit.' OFFSET '.$offset;
            // return null;


            $data = $this->ci->dbutil->paging([
                "db"=>"iparkingCloudDb",
                "select" =>
                    $sel_query,
                "query" => "
                    SELECT
                        %%
                    ".$sql
                ,
                "limit" => $limit,
                "offset" => $offset,
                "orderby" => $orderBy
            ]);

            // print_r($data);
            // return null;
            $data_item = $data["data"];
            foreach($data_item as &$data_row)
            {
                $parkinglot_info = array();
                $parking_pass_ny = null;
                $parkinglot_form = null;
                $alliance_discount_ny = null;
                $pay_sort = null;

                if($data_row['parking_pass_ny'] == 1){
                    $parking_pass_ny = "파킹패스";
                }

                if($data_row['parkinglot_form'] != 0 || $data_row['parkinglot_form'] != null){
                    if( strpos($data_row['parkinglot_form'], "6;") !== false ){
                        $parkinglot_form = "기계식";
                    } else if( strpos($data_row['parkinglot_form'], "5;7")  !== false){
                        $parkinglot_form = "건축물";
                    } else if( strpos($data_row['parkinglot_form'], "5;8")  !== false){
                        $parkinglot_form = "철골구조";
                    } else if( strpos($data_row['parkinglot_form'], "5;9")  !== false){
                        $parkinglot_form = "나대지";
                    } else if( strpos($data_row['parkinglot_form'], "5;290")  !== false){
                        $parkinglot_form = "복합";
                    } 
                    // 6은 현재 값이 없다.
                    // else if( strpos($data_row['parkinglot_form'], "5;10") ){
                    //     $parkinglot_form = "6";
                    // }
                }

                if($data_row['alliance_discount_ny'] == 1){
                    $alliance_discount_ny = "제휴할인";
                }

                if($data_row['pay_sort'] == 1){
                    $pay_sort = "카드전용";
                } else if($data_row['pay_sort'] == 2){
                    $pay_sort = "카드,현금";
                } else if($data_row['pay_sort'] == 3){
                    $pay_sort = "현금전용";
                }

                if($parking_pass_ny != null ){
                    array_push($parkinglot_info, $parking_pass_ny);
                } 
                if($parkinglot_form != null ){
                    array_push($parkinglot_info, $parkinglot_form);
                } 
                if($alliance_discount_ny != null ){
                    array_push($parkinglot_info, $alliance_discount_ny);
                } 
                if($pay_sort != null ){
                    array_push($parkinglot_info, $pay_sort);
                }

                $data_row['parkinglot_info'] = $parkinglot_info;

                $data_row['search_product'] = $search_product;
                
            }
            $data['data'] = $data_item;

            $msg = $this->ci->message->apiMessage['success'];
            $msg['result'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            $msg = $this->ci->message->apiMessage['fail'];
            return $response->withJson($msg);
        }
    }    


    public function postAllianceDiscountList($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);
            
            $thisPage = $params['thisPage'];
            $listRows = $params['listRows'];
            $onPaging = $params['onPaging'];
            // $memb_seq = $params['memb_seq'];

            $dest_latitude = $params['dest_latitude'];
            $dest_longitude = $params['dest_longitude'];
            $search_distance = $params['search_distance'];

            // 필수값 체크
            if($dest_latitude == null || $dest_latitude == ""            
            || $dest_longitude == null || $dest_longitude == "" ) 
            {    
                $msg = $this->ci->message->apiMessage['required'];
                return $response->withJson($msg);
            }

            // sql 만드는 작업을 먼저하고 페이징처리 쿼리 다시만들자

            $sql = "	
            FROM	
                (
                    SELECT 
                        LOCA.lcad_seq,
                        plla_seq,
                        parkGetName(PAAD.park_seq)                                    AS park_name,
                        PAAD.park_seq,
                        plla_discount_rate,
                        plla_discount_cd,
                        getCode(PAAD.plla_discount_cd, 152)                      AS plla_discount,
                        lcad_name,
                        lcad_zipcode,
                        lcad_address_1,
                        lcad_address_2,
                        lcad_latitude,
                        lcad_longitude,
                        lcad_region_ct,
                        catePath((commSplit(lcad_business_ct, ';', 1)))          AS lcad_business_high,
                        catePath((commSplit(lcad_business_ct, ';', 2)))          AS lcad_business_low,
                        PARK.park_division,
                        ";
                if($dest_latitude != null && $dest_latitude != ""){
                    $sql.="
                        ROUND(6371 * acos( cos( radians(".$dest_latitude.") ) * cos( radians( LOCA.lcad_latitude ) ) * cos( radians( LOCA.lcad_longitude ) 
                        - radians(".$dest_longitude.") ) + sin( radians(".$dest_latitude.") ) * sin( radians( LOCA.lcad_latitude ) ) ), 2
                        ) as distance
                    ";
                }else {
                    $sql.="
                        '--' AS distance
                    ";
                }
                $sql.="
                    FROM fdk_parkingcloud.acd_rpms_parkinglot_local_ad PAAD 
                    INNER JOIN fdk_parkingcloud.acd_rpms_local_ad LOCA on LOCA.lcad_seq = PAAD.lcad_seq
                    INNER JOIN fdk_parkingcloud.acd_pms_parkinglot PARK on PAAD.park_seq = PARK.park_seq
                    WHERE lcad_del_ny = 0
                        AND plla_del_ny = 0
                        AND PARK.park_del_ny  = 0
                        AND PARK.park_reveal_ny = 1

                ) sub
            WHERE 	1=1
            ";

            
            if($search_distance != null){
                $sql.="
            AND sub.distance <=  ".$search_distance;
            }


            $orderBy="
            sub.distance
                ";
        
            $this->ci->iparkingCloudDb->exec('use fdk_parkingcloud');

            $limit = $listRows;
            $offset = 0;
            if($onPaging == "true"){
                if($thisPage == 1) {
                    $offset = 0;
                } else {
                    $offset = $listRows * ($thisPage-1);
                }
            }else {
                $limit = 999999;
                $offset = 0;
            }
            
            $data = $this->ci->dbutil->paging([
                "db"=>"iparkingCloudDb",
                "select" =>"
                            sub.lcad_seq,
                            sub.plla_seq,
                            sub.park_name,
                            park_seq,
                            sub.plla_discount_rate,
                            sub.plla_discount_cd,
                            sub.plla_discount,
                            sub.lcad_name,
                            sub.lcad_zipcode,
                            sub.lcad_address_1,
                            sub.lcad_address_2,
                            sub.lcad_latitude,
                            sub.lcad_longitude,
                            sub.lcad_region_ct,
                            sub.lcad_business_high,
                            sub.lcad_business_low,
                            TRUNCATE(sub.distance, 2) as distance,
                            sub.park_division
                    ",
                "query" => "
                    SELECT
                        %%
                    ".$sql
                ,
                "limit" => $limit,
                "offset" => $offset,
                "orderby" => $orderBy
            ]);

            $msg = $this->ci->message->apiMessage['success'];
            $msg['result'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            $msg = $this->ci->message->apiMessage['fail'];
            return $response->withJson($msg);
        }
    }

}