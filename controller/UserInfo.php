<?php

class UserInfo
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function executeCheck($query, $memb_seq) {

        $stmt = $this->ci->iparkingCloudDb->prepare($query);
        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;

    }  
     // 멤버스회원인지 체크
     public function membersCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                COUNT(1) as cnt
            FROM 
                fdk_parkingcloud.arf_b2bcore_member BTBM 
            INNER JOIN fdk_parkingcloud.acd_rpms_store STOR ON BTBM.cmpy_seq = STOR.cmpy_seq AND BTBM.bmem_del_ny = 0 
            WHERE 
                BTBM.memb_seq = :memb_seq 
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }
    // 등록한 차량 정보가 있는지 체크
    public function carPossessionCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                count(1) AS cnt
            FROM 
                fdk_parkingcloud.arf_b2ccore_car
            WHERE 
                memb_seq = :memb_seq 
            AND 
                bcar_del_ny = 0
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

    // push 정보가 있는지 체크
    public function appPushSeqCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                count(1) AS cnt
            FROM 
                fdk_parkingcloud.member_dm_policy_history 
            WHERE 
                mdph_memb_seq = :memb_seq  
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

    
    public function billkeyCheck($memb_seq){
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                count(1) AS cnt
            FROM 
                fdk_parkingcloud.arf_b2ccore_nesso
            WHERE 
                memb_seq = :memb_seq 
            AND 
                nspo_del_ny = 0
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }


    public function awsTokenCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                count(1) AS cnt
            FROM 
                fdk_parkingcloud.arf_b2ccore_member_mobile_aws_sns_registrationid_token
            WHERE 
                memso_memb_seq = :memb_seq 
            AND 
                memso_del_ny = 0
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }
    

    public function pointCardCheck($memb_seq){
            
        $stmt = $this->ci->iparkingCloudDb->prepare("
            SELECT 
                count(1) AS cnt 
            FROM 
                fdk_parkingcloud.member_point_card_info 
            WHERE 
                is_deleted = 'N'
            AND 
                memb_seq = :memb_seq 

        ");

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }
    
    // 제휴사정보가 있는지 체크
    public function allianceInfoCheck($memb_seq){
            
        $stmt = $this->ci->iparkingCloudDb->prepare("
            SELECT 
                count(1) AS cnt 
            FROM 
                fdk_parkingcloud.tbl_member_alliance_channel
            WHERE
                ma_del_ny = 0 
            AND 
                memb_seq =  :memb_seq

        ");

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

    // 구매한 상품중 사용중/사용예정 정기권이 있거나 미사용인 상품이 있는지 체크
    public function productSalesCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT
                COUNT(1) as cnt
            FROM
                fdk_parkingcloud.acd_rpms_parking_product_sales PPSL
            WHERE
                (
                    (
                        PPSL.ppsl_start_datetime > sysdate()
                        AND NOT PPSL.ppsl_pay_cd IN (0, 10)
                        AND PPSL.ppsl_del_ny = 0
                    )
                    OR
                    (
                        sysdate() BETWEEN PPSL.ppsl_start_datetime AND PPSL.ppsl_end_datetime
                        AND NOT PPSL.ppsl_pay_cd IN (0, 10)
                        AND PPSL.ppsl_del_ny = 0
                    )
                )
            AND
                PPSL.ppsl_del_ny = 0
            AND
                IFNULL(PPSL.ppsl_ticket_use_ny, 0) = 0
            AND
                PPSL.ppsl_buyer_seq = :memb_seq
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

    // CEO 가입이 되어있는지 체크
    public function CeoCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare("
            SELECT 
                count(1) as cnt
            FROM 
                fdk_parkingcloud.arf_b2bcore_member MEMB
            INNER JOIN fdk_parkingcloud.arf_b2bcore_company COMP ON COMP.cmpy_seq = MEMB.cmpy_seq AND COMP.cmpy_del_ny = 0
            INNER JOIN fdk_parkingcloud.arf_b2bcore_office O ON O.ofic_seq = MEMB.ofic_seq AND O.ofic_del_ny = 0 AND O.ofic_head_office_ny = '1'
            WHERE 
                cmpy_new_id is null and cmpy_id is not null
            AND 
                MEMB.bmem_join_approval_status_cd = 2
            AND  
                MEMB.bmem_login_approval_ny = 1
            AND 
                MEMB.bmem_employ_status_cd <> 3
            AND 
                MEMB.bmem_del_ny = 0
            AND 
                MEMB.memb_seq = :memb_seq
        ");

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }
    
    // 주차중인 차량이 있는지 체크 (파킹패스 ON)
    public function inCarCheck($memb_seq){
        
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                COUNT(1) AS cnt
            FROM 
                fdk_parkingcloud.acd_rpms_inout INOT
            INNER JOIN fdk_parkingcloud.arf_b2ccore_car BCAR ON INOT.inot_bcar_seq = BCAR.bcar_seq AND BCAR.bcar_del_ny = 0
            INNER JOIN fdk_parkingcloud.arf_b2ccore_member MEMB ON MEMB.memb_seq = BCAR.memb_seq
            WHERE 
                INOT.inot_del_ny = 0
            AND 
                INOT.inot_exit_datetime IS NULL
            AND 
                (INOT.inot_local_pay_machine_cd = 0 OR INOT.inot_local_pay_machine_cd IS NULL)
            AND 
                MEMB.memb_seq = :memb_seq   
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

    // 관리자 권한이 있는지 체크
    public function adminCheck($memb_seq){
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                COUNT(1) AS cnt
            FROM 
                fdk_parkingcloud.arf_b2ccore_member
            WHERE 
                memb_seq = :memb_seq
            AND 
                memb_del_ny = 0 
            AND 
                memb_admin_ny = 1
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;


    }

    // 감면 정보가 있는지 체크
    // redc_reduction_status_cd  1.대기 2.승인 3.불가 4.만료 5.취소

    public function userReductionCheck($memb_seq){
        $stmt = $this->ci->iparkingCloudDb->prepare('
            SELECT 
                COUNT(1) AS cnt
            FROM 
                fdk_parkingcloud.acd_rpms_member_reduction
            WHERE 
                redc_memb_seq = :memb_seq
            AND 
                redc_del_ny = 0 
            AND 
                redc_reduction_status_cd in (1,2) 
        
        ');

        $stmt->execute(['memb_seq' => $memb_seq]);
        $data = $stmt->fetch();
        return $data['cnt'] ?? 0;

    }

}


