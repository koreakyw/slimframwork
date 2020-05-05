<?php

class BadgeController
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function getBadgeList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');

            $memb_seq = $params['memb_seq'] ?? null;

            $separatorDevice = $request->getHeaderLine('separatorDevice');
            $memo_mobile_os_cd = ($separatorDevice == 'IOS') ? 2 : 1;

            $mobile_parking_result = array();
            $product_sales_result = array();
            $coupon_count = 0;

            if($memb_seq != null) {
                // 모바일 주차권
                $stmt = $this->ci->iparkingCloudDb->prepare('
                    SELECT 
                        INOT.inot_seq as seq,
                        INOT.inot_enter_datetime as create_time
                    FROM fdk_parkingcloud.acd_rpms_inout INOT
                        LEFT JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON INOT.inot_memb_seq = BCAR.memb_seq AND INOT.inot_car_number = BCAR.bcar_num AND BCAR.bcar_del_ny = 0
                        INNER JOIN fdk_parkingcloud.inicis_payment_result  ipr ON ipr.icpr_seq = INOT.inot_icpr_seq
                    WHERE 
                        INOT.inot_memb_seq = :inot_memb_seq
                    AND 
                        INOT.inot_product_cd = 5    
                    AND 
                        INOT.inot_del_ny = 0
                    ORDER BY INOT.inot_seq DESC
                    limit 1
                ');

                $stmt->execute(['inot_memb_seq' => $memb_seq]);
                $mobile_parking_result = $stmt->fetch() ?? array(); 

                // 주차권 구매내역
                $stmt = $this->ci->iparkingCloudDb->prepare('
                    SELECT 
                        ipr.icpr_seq as seq,
                        ipr.insert_datetime as create_time
                    FROM 
                        fdk_parkingcloud.acd_rpms_parking_product_sales arpps
                    INNER JOIN fdk_parkingcloud.inicis_payment_result ipr ON arpps.ppsl_seq = ipr.icpr_product_seq AND ipr.icpr_product_cd = 2 AND ipr.pg_cd NOT IN(7, 10, 11) 
                    WHERE arpps.ppsl_buyer_seq = :ppsl_buyer_seq
                    ORDER BY ipr.icpr_seq DESC
                    limit 1
                ');

                $stmt->execute(['ppsl_buyer_seq' => $memb_seq]);
                $product_sales_result = $stmt->fetch(); 

                // 쿠폰 (숫자)
                $stmt = $this->ci->iparkingCloudDb->prepare('
                    SELECT
                        count(*) as cnt
                    FROM 
                        fdk_parkingcloud.arf_basis_coupon_history a
                    INNER JOIN fdk_parkingcloud.arf_basis_coupon b ON b.cp_seq = a.cp_seq
                    WHERE 
                        a.cp_memb_seq = :cp_memb_seq
                    AND 
                        a.cp_use_ny = 0 
                    AND
                        a.cp_hist_ex_end_time >= :cp_hist_ex_end_time
                ');

                $stmt->execute([
                    'cp_memb_seq' => $memb_seq,
                    'cp_hist_ex_end_time' => $now
                ]);
                $coupon_history_result = $stmt->fetch(); 
                $coupon_count = $coupon_history_result['cnt'] ?? 0;
            }
            

            $day_ago = date('Y-m-d H:i:s', strtotime($now.'- 14days'));
                // 아이파킹 이용안내
            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    notc_seq as seq,
                    notc_reg_datetime as create_time
                FROM 
                    fdk_parkingcloud.arf_basis_notice
                Where 
                    notc_del_ny = 0
                AND 
                    notc_post_ny = 1
                AND 
                    notc_view_device_cd  = 3
                AND 
                    notc_view_cd = 1
                AND
                    notc_reg_datetime BETWEEN :notc_reg_start_datetime AND :notc_reg_end_datetime
                ORDER BY notc_reg_datetime desc
                limit 1
            ');

            $stmt->execute([
                'notc_reg_start_datetime' => $day_ago,
                'notc_reg_end_datetime' => $now
            ]);
            $iparking_board_result = $stmt->fetch(); 
            
            // 공지사항
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    notice_seq as seq,
                    create_time
                FROM 
                    iparking_cms.board_notice
                WHERE 
                    notice_del_yn = 0 
                AND
                    notice_on_off = 1
                AND
                    create_time BETWEEN :create_start_datetime AND :create_end_datetime
                ORDER BY 
                    create_time desc
                LIMIT 1
            ');

            $stmt->execute([
                'create_start_datetime' => $day_ago,
                'create_end_datetime' => $now
            ]);
            $notice_result = $stmt->fetch(); 

            $event_where = "";
            if($memo_mobile_os_cd == 2) {
                $event_where .= " AND event_add_type != 2 ";
            }

            // 이벤트
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    event_seq as seq,
                    create_time
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_del_yn = 0 
                AND
                    event_on_off = 1
                AND
                    create_time BETWEEN :create_start_datetime AND :create_end_datetime
                '.$event_where.'
                ORDER BY 
                    create_time desc
                LIMIT 1
            ');

            $stmt->execute([
                'create_start_datetime' => $day_ago,
                'create_end_datetime' => $now
            ]);
            $event_result = $stmt->fetch(); 
           
            if(empty($notice_result)) {
                $notice_result = (object)[];
            } else {
                $notice_result['seq'] = (int) $notice_result['seq'];
            }

            if(empty($event_result)) {
                $event_result = (object)[];
            } else {
                $event_result['seq'] = (int) $event_result['seq'];
            }

            if(empty($iparking_board_result)) {
                $iparking_board_result = (object)[];
            } else {
                $iparking_board_result['seq'] = (int) $iparking_board_result['seq'];
            }

            if(empty($product_sales_result)) {
                $product_sales_result = (object)[];
            } else {
                $product_sales_result['seq'] = (int) $product_sales_result['seq'];
            }

            if(empty($mobile_parking_result)) {
                $mobile_parking_result = (object)[];
            } else {
                $mobile_parking_result['seq'] = (int) $mobile_parking_result['seq'];
            }

            $data = array(
                'notice' => $notice_result,
                'event' => $event_result,
                'iparking_board' => $iparking_board_result,
                'product_sales' => $product_sales_result,
                'mobile_parking' => $mobile_parking_result,
                'coupon_count' => $coupon_count
            );
            
            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;

            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson([
                'code' => 40000,
                'error' => $e->getMessage()
            ]);
        }
    }
}   