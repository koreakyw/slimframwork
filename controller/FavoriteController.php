<?php

class FavoriteController
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function postFavoriteAdd($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $parkinglot_seq = $params['parkinglot_seq'];
            $parkinglot_cd = $params['parkinglot_cd'];
            $memb_seq = $params['memb_seq'];
            
            // 잘못된 회원코드 처리
            if($memb_seq == 0 || $memb_seq == ""){
                $memb_seq = null;
            }

            // $pafv_seq = $params['pafv_seq'];

            // 필수값 체크
            if($parkinglot_seq == null || $parkinglot_seq == ""
            || $parkinglot_cd == null || $parkinglot_cd == ""
            || $memb_seq == null || $memb_seq == "" ) 
            {
                $msg = $this->ci->message->apiMessage['required'];
                return $response->withJson($msg);
            }

            // 10개 맥스 체크 
            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    *
                FROM 
                    fdk_parkingcloud.acd_rpms_parkinglot_favorite
                WHERE 
                    memb_seq = :memb_seq
                AND 
                    pafv_del_ny = 0
                GROUP BY 
                    pafv_partnership_cd, pafv_parkinglot_seq
            ');

            $stmt->execute([ 'memb_seq' => $memb_seq ]);

            $max_check = $stmt->fetchAll();

            if( count($max_check) >= 10 ){
                $msg = $this->ci->message->apiMessage['max_check'];
                return $response->withJson($msg);
            }

            $now = date('Y-m-d H:i:s');

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT *
                FROM fdk_parkingcloud.acd_rpms_parkinglot_favorite
                WHERE memb_seq = :memb_seq
                    AND pafv_partnership_cd = :parkinglot_cd
                    AND pafv_parkinglot_seq = :parkinglot_seq
                    AND pafv_del_ny = 0
            ');
            $stmt->execute([ 
                            'memb_seq' => $memb_seq,
                            'parkinglot_cd' => $parkinglot_cd, 
                            'parkinglot_seq' => $parkinglot_seq
                            ]);
            $duple_check = $stmt->fetch();
            
            // 중복 등록 방지코드
            if(empty($duple_check)){
                
                $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.acd_rpms_parkinglot_favorite', [[
                    'pafv_partnership_cd' => $parkinglot_cd,
                    'pafv_parkinglot_seq' => $parkinglot_seq,
                    'pafv_reg_ip' => $_SERVER['REMOTE_ADDR'],
                    'pafv_reg_seq' => $memb_seq,
                    'pafv_reg_device_cd' => 3,
                    'pafv_reg_datetime' => $now,
                    'pafv_mod_ip' => $_SERVER['REMOTE_ADDR'],
                    'pafv_mod_seq' => $memb_seq,
                    'pafv_mod_device_cd' => 3,
                    'pafv_mod_datetime' => $now,
                    'pafv_del_ny' => 0,
                    'memb_seq' => $memb_seq
                ]]);  
    
                $pafv_seq = $this->ci->iparkingCloudDb->lastInsertId();

            } else {
                $pafv_seq = $duple_check['pafv_seq'];
            }

            $msg = $this->ci->message->apiMessage['success'];
            $msg['result'] = array('pafv_seq' => $pafv_seq);
            return $response->withJson($msg);

        } catch (Exception $e) {
            $msg = $this->ci->message->apiMessage['fail'];
            return $response->withJson($msg);
        }
    }    



    public function postFavoriteDelete($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $parkinglot_seq = $params['parkinglot_seq'];
            $parkinglot_cd = $params['parkinglot_cd'];
            $memb_seq = $params['memb_seq'];
            
            // 잘못된 회원코드 처리
            if($memb_seq == 0 || $memb_seq == ""){
                $memb_seq = null;
            }

            $pafv_seq = $params['pafv_seq'];

            // 필수값 체크
            if($pafv_seq == null || $pafv_seq == "" || $pafv_seq == 0){

                if($parkinglot_seq == null || $parkinglot_seq == ""
                || $parkinglot_cd == null || $parkinglot_cd == ""
                || $memb_seq == null || $memb_seq == "" ) 
                {
                    $msg = $this->ci->message->apiMessage['required'];
                    return $response->withJson($msg);
                }

            }else {

                $stmt = $this->ci->iparkingCloudDb->prepare('
                    SELECT *
                    FROM fdk_parkingcloud.acd_rpms_parkinglot_favorite
                    WHERE pafv_seq = :pafv_seq
                ');
                $stmt->execute([ 'pafv_seq' => $pafv_seq ]);
                $data = $stmt->fetch();

                $parkinglot_cd = $data['pafv_partnership_cd'];
                $parkinglot_seq = $data['pafv_parkinglot_seq'];
                $memb_seq = $data['memb_seq'];
                
            }
            

            $now = date('Y-m-d H:i:s');

            $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.acd_rpms_parkinglot_favorite', [
                'pafv_del_ny' => 1,
                'pafv_mod_ip' => $_SERVER['REMOTE_ADDR'],
                'pafv_mod_seq' => $memb_seq,
                'pafv_mod_device_cd' => 3,
                'pafv_mod_datetime' => $now
            ],[
                'pafv_partnership_cd' => $parkinglot_cd,
                'pafv_parkinglot_seq' => $parkinglot_seq,
                'memb_seq' => $memb_seq
            ]);


            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (Exception $e) {
            $msg = $this->ci->message->apiMessage['fail'];
            return $response->withJson($msg);
        }
    }



}