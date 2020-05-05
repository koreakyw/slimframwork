<?php
class Policy
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function policyInsert($point_card_code,$cooperation_start_date,$cooperation_end_date,$point_use_unit,$purchase_max_avail_point,$parkingpass_max_avail_point,
    $payment_min_point,$is_parkingpass_sync,$operation_method,$save_rate,$save_commission_rate,$use_commission_rate)
    {
        $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.point_card_policy', [[
            'point_card_code' => $point_card_code,
            'cooperation_start_date' => $cooperation_start_date,
            'cooperation_end_date' => $cooperation_end_date,
            'point_use_unit' => $point_use_unit,
            'purchase_max_avail_point' => $purchase_max_avail_point,
            'parkingpass_max_avail_point' => $parkingpass_max_avail_point,
            'payment_min_point' => $payment_min_point,
            'is_parkingpass_sync' => $is_parkingpass_sync,
            'operation_method' => $operation_method,
            'save_rate' => $save_rate,
            'save_commission_rate' => $save_commission_rate,
            'use_commission_rate' => $use_commission_rate

        ]]);     
    }

    public function compriseList($now)
    {
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT
                pcl.point_card_code AS point_card_code,
                pcl.point_card_name AS point_card_name,
                pcp.cooperation_start_date AS cooperation_start_date,
                pcp.cooperation_end_date AS cooperation_end_date,
                pcp.operation_method AS operation_method,
                pcp.purchase_max_avail_point AS purchase_max_avail_point,
                pcp.parkingpass_max_avail_point AS parkingpass_max_avail_point,
                pcp.payment_min_point AS payment_min_point,
                pcp.is_parkingpass_sync AS is_parkingpass_sync,
                pcl.is_new AS is_new,
                pcl.manage_description,
                pcl.usage_description,
                pcl.maintenance_start_datetime,
                pcl.maintenance_end_datetime,
                pcl.maintenance_description
            FROM 
                fdk_parkingcloud.point_card_list AS pcl
            INNER JOIN fdk_parkingcloud.point_card_policy AS pcp ON pcp.point_card_code = pcl.point_card_code 
            WHERE 
                pcp.cooperation_start_date <= :now1 AND  pcp.cooperation_end_date >= :now2
            ORDER BY 
                pcp.cooperation_start_date ASC
        ');

        $stmt->execute(['now1'=>$now, 'now2'=> $now]);

        $data = $stmt->fetchAll();

        return $data;
    }
    // 현재 날짜를 포함 포인트 정책 상세내용
    public function compriseDetail($now,$point_card_code)
    {

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
                    pcl.manage_description,
                    pcl.usage_description,
                    pcl.maintenance_start_datetime,
                    pcl.maintenance_end_datetime,
                    pcl.maintenance_description
                FROM 
                    fdk_parkingcloud.point_card_list AS pcl
                INNER JOIN fdk_parkingcloud.point_card_policy AS pcp ON pcp.point_card_code = pcl.point_card_code
                WHERE 
                    pcl.point_card_code = :point_card_code
                AND 
                    pcp.cooperation_start_date <= :now1  
                AND 
                    pcp.cooperation_end_date >= :now2
            ');
            $stmt -> execute(['point_card_code'=>$point_card_code, 'now1'=>$now, 'now2'=> $now]);
            
            $policyList = $stmt->fetchAll();

            foreach($policyList as $policyList_rows){  
            
                if($policyList_rows['operation_method'] == 'PRI'){
                    $PRI = array(
                        'operation_method' => $policyList_rows['operation_method'],
                        'save_rate' => $policyList_rows['save_rate'],
                        'save_commission_rate' => $policyList_rows['save_commission_rate'],
                        'use_commission_rate' => $policyList_rows['use_commission_rate'],
                    );
                };
                if($policyList_rows['operation_method'] == 'PUB'){
                    $PUB = array(
                        'operation_method' => $policyList_rows['operation_method'],
                        'save_rate' => $policyList_rows['save_rate'],
                        'save_commission_rate' => $policyList_rows['save_commission_rate'],
                        'use_commission_rate' => $policyList_rows['use_commission_rate'],
                    );
                };

                $data = array(
                    "point_card_code" => $policyList_rows['point_card_code'],
                    "point_card_name" => $policyList_rows['point_card_name'],
                    "cooperation_start_date" => $policyList_rows['cooperation_start_date'],
                    "cooperation_end_date" => $policyList_rows['cooperation_end_date'],
                    "point_use_unit" => $policyList_rows['point_use_unit'],
                    "purchase_max_avail_point" => $policyList_rows['purchase_max_avail_point'],
                    "parkingpass_max_avail_point" => $policyList_rows['parkingpass_max_avail_point'],
                    "payment_min_point" => $policyList_rows['payment_min_point'],
                    "is_parkingpass_sync" => $policyList_rows['is_parkingpass_sync'],
                    "is_new" => $policyList_rows['is_new'],
                    "description" => $policyList_rows['description'],
                    "manage_description" => $policyList_rows['manage_description'],
                    "usage_description" => $policyList_rows['usage_description'],
                    "maintenance_start_datetime" => $policyList_rows['maintenance_start_datetime'],
                    "maintenance_end_datetime" => $policyList_rows['maintenance_end_datetime'],
                    "maintenance_descriptio" => $policyList_rows['maintenance_descriptio'],
                    "PRI" => $PRI,
                    "PUB" => $PUB
                );
            }
        }

        return $data;
    }

    
    public function duplicateCardCheck($point_card_code)
    {
        $stmt = $this->ci->iparkingCloudDb->prepare('           
            SELECT 
                count(*) as cnt 
            FROM 
                fdk_parkingcloud.point_card_list
            WHERE
                point_card_code = :point_card_code
        ');

        $stmt->execute(['point_card_code' => $point_card_code]);
        $data = $stmt->fetch();
        $count = $data['cnt'] ?? 0;

        return $count;
    }

   
    public function duplicatePolicyPointCode($point_card_code, $operation_method=null)
    {

        $binds['point_card_code'] = $point_card_code;
        
        $where = "";
        if($operation_method != null) {
            $where .= " AND operation_method = :operation_method ";
            $binds['operation_method'] = $operation_method;
        }

        $stmt = $this->ci->iparkingCloudDb->prepare('           
            SELECT 
                count(*) as cnt 
            FROM 
                fdk_parkingcloud.point_card_policy
            WHERE
                point_card_code = :point_card_code
        '.$where.'
        ');

        $stmt->execute($binds);

        $data = $stmt->fetch();
        $count = $data['cnt']?? 0;

        return $count;
    }

    public function duplicatePolicyDatePointCode($point_card_code, $cooperation_start_date, $cooperation_end_date, $operation_method=null)
    {

        $binds['point_card_code'] = $point_card_code;
        $binds['cooperation_start_date'] = $cooperation_start_date;
        $binds['cooperation_end_date'] = $cooperation_end_date;
        
        $where = "";
        if($operation_method != null) {
            $where .= " AND operation_method = :operation_method ";
            $binds['operation_method'] = $operation_method;
        }

        $stmt = $this->ci->iparkingCloudDb->prepare('           
            SELECT 
                count(*) as cnt 
            FROM 
                fdk_parkingcloud.point_card_policy
            WHERE
                point_card_code = :point_card_code
            AND cooperation_start_date = :cooperation_start_date
            AND cooperation_end_date = :cooperation_end_date
        '.$where.'
        ');

        $stmt->execute($binds);

        $data = $stmt->fetch();
        $count = $data['cnt']?? 0;

        return $count;
    }
  
}

