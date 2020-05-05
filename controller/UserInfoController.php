<?php
/**
 * UserInfoController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     유저정보 클랙스
 * @date      2018/05/17
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class UserInfoController
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    
    /**
     * getFindId function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     아이디찾기 함수
     * @date      2018/05/17
     * @see       기존 api정보 : /app/arf/core/sms/smsFindIdSendAug.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getFindId($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $smcf_memb_name = $params['arfSmsItem_smcf_memb_name'];
            $smcf_phone_number = $params['arfSmsItem_smcf_phone_number'];

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT
                    memb_alliance_cd AS allianceCd
                    ,memb_alliance_uri AS allianceUri
                    ,memb_name AS membName
                    ,memb_seq AS membSeq
                    ,memb_level_bit AS levelBit
                    ,memb_id AS membId
                FROM 
                    fdk_parkingcloud.arf_b2ccore_member 
                WHERE 
                    memb_del_ny  <>  '1'
                    AND CONCAT(memb_mobile_1,memb_mobile_2,memb_mobile_3) = :smcf_phone_number
                    AND memb_name = :smcf_memb_name
            ");

            $stmt->execute(['smcf_phone_number' => $smcf_phone_number, 'smcf_memb_name' => $smcf_memb_name]);
			$result = $stmt->fetch();

            if ($result) {
                $msg['result'] = $this->ci->message->oldMessage['success'];
                $msg['result'] += $result;
            } else {
                $msg['result'] = $this->ci->message->oldMessage['error'];
            }
            
            
            return $response->withJson($msg);
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }  
    
    /**
     * getCarList function
     *
     * @param [type] $request
     * @param [type] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     등록차량 리스트 함수
     * @date      2018/06/26
     * @see       
     * @todo      추후 차량번호 이외에 오토업을 통해 취득한 정보도 함께 보여줄수 있도록 처리가 필요할듯
     */
    public function getCarList($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];    //회원고유번호
            $ppsl_vehicle_cd = $params['ppsl_vehicle_cd'];  //차종정보
            $big_data = $params['big_data'];    //오토업 정보

            if ($ppsl_vehicle_cd != 0) {
                $ppsl_vehicle_cd = ' AND bcar_vehicle_cd = '. $ppsl_vehicle_cd;
            } else {
                $ppsl_vehicle_cd = '';
            }

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT
                    bcar_num
                    ,bcar_seq
                    ,bcar_vehicle_cd
                    ,bcar_default_ny
                    ,bcar_parking_pass_ny
                    ,bcar_auto_point_ny
                    ,bcar_reg_datetime
                FROM
                    fdk_parkingcloud.arf_b2ccore_car
                WHERE
                    bcar_del_ny = 0
                    AND memb_seq = :memb_seq
                    '. $ppsl_vehicle_cd .'
                ORDER BY
                    bcar_reg_datetime DESC
            ');

            $stmt->execute(['memb_seq' => $memb_seq]);
            $result = $stmt->fetchAll();
            
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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }

    }

    public function getPaymentCardList($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];    //회원고유번호

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT
                    nspo_seq
                    ,nspo_default_ny
                    ,nspo_cardcode
                    ,nspo_cardcmpy
                    ,nspo_cardalias
                    ,nspo_auto_point_ny
                    ,nspo_reg_datetime
                FROM
                    fdk_parkingcloud.arf_b2ccore_nesso
                WHERE
                    nspo_del_ny = 0
                    AND memb_seq = :memb_seq
                ORDER BY
                    nspo_reg_datetime, nspo_default_ny DESC
            ');

            $stmt->execute(['memb_seq' => $memb_seq]);
            $result = $stmt->fetchAll();
            
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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }

    }

    // public function getPointCardList($request, $response, $args)
    // {
    //     try {
    //         $params = $this->ci->util->getParams($request);

    //         $smcf_memb_name = $params[''];
    //         $smcf_phone_number = $params[''];

    //         $stmt = $this->ci->iparkingCloudDb->prepare("
                
    //         ");

    //         $stmt->execute(['' => $]);
	// 		$result = $stmt->fetch();

    //         if ($result) {
    //             $msg['result'] = $this->ci->message->oldMessage['success'];
    //             $msg['result'] += $result;
    //         } else {
    //             $msg['result'] = $this->ci->message->oldMessage['error'];
    //         }
        
    //         return $response->withJson($msg);
    //     } catch (ErrorException $e) {
    //         return $response->withJson(['error'=>$e->getMessage()]);
    //     } catch (Exception $e) {
    //         $this->ci->logger->debug($e); 
    //         return $response->withJson(['error' => $e->getMessage()]);
    //     }

    // }

    public function getCouponList($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];    //회원고유번호


            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT
                    a.cp_hist_seq,
                    a.cp_hist_ex_end_time,
                    b.cp_name,
                    b.cp_price,
                    b.cp_min_price,
                    b.cp_use_condition,
                    b.cp_payco_yn
                FROM fdk_parkingcloud.arf_basis_coupon_history a
                     JOIN fdk_parkingcloud.arf_basis_coupon b ON b.cp_seq = a.cp_seq
                WHERE a.cp_memb_seq = :memb_seq
                    AND a.cp_use_ny = 0
                    AND b.cp_parking_pass_ny = 0
                    AND a.cp_hist_ex_start_time <= NOW()
                    AND a.cp_hist_ex_end_time >= NOW() 
                    ORDER BY cp_hist_seq desc
            ');

            $stmt->execute(['memb_seq' => $memb_seq]);
            $result = $stmt->fetchAll();
            
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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function postCheckMemeber($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_id = $params['memb_id'];
            $memb_pwd = $params['memb_pwd'];
            $memb_bit = $params['memb_bit'];
            $memb_name = $params['memb_name'];
            $memb_seq = $params['memb_seq'];

     
            // TODO : 향후 비밀번호 연동이 되었을 경우 복호화 처리 필요 
            // TODO : 회원 로그인은 총 3종류 1. 일반 회원, 2. 간편 로그인 회원, 3. 게스트 회원 확인 필요

            if($memb_bit == null || $memb_bit == "" || $memb_bit == 'null'){
                $memb_bit = 0; 
            }
            /*
            Iparking User Encrypt
            */
            
            if($memb_bit == 1){ // 일반로그인회원

                $memb_pwd = $this->ci->util->iparkingCloudUserPasswordEncrypt($memb_pwd);

                $stmt = $this->ci->iparkingCloudDb->prepare("
                    SELECT 
                        count(*) as cnt
                    FROM 
                        fdk_parkingcloud.arf_b2ccore_member 
                    WHERE 
                        memb_del_ny = 0
                        AND memb_id = :memb_id
                        AND memb_pwd = :memb_pwd
                        AND memb_seq = :memb_seq
                ");

                $stmt->execute(['memb_id' => $memb_id, 'memb_pwd' => $memb_pwd, 'memb_seq' => $memb_seq]);
                $result = $stmt->fetch();
                $user_count = $result['cnt'] ?? 0;
                
                if( $user_count > 0 ) {
                    $msg = $this->ci->message->apiMessage['success'];
                } else {
                    $msg = $this->ci->message->apiMessage['noneMember'];
                }

            }else if($memb_bit == 0){ // 간편로그인회원
                $stmt = $this->ci->iparkingCloudDb->prepare("
                    SELECT 
                        count(*) as cnt
                    FROM 
                        fdk_parkingcloud.arf_b2ccore_member 
                    WHERE 
                        memb_del_ny = 0
                        AND memb_name = :memb_name
                        AND memb_seq = :memb_seq
                ");

                $stmt->execute(['memb_name' => $memb_name, 'memb_seq' => $memb_seq]);
                $result = $stmt->fetch();
                $user_count = $result['cnt'] ?? 0;

                if( $user_count > 0 ) {
                    $msg = $this->ci->message->apiMessage['success'];
                } else{
                    $msg = $this->ci->message->apiMessage['fail'];
                }

            }


            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e);
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function putDropMember ($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $memb_seq = $args['memb_seq'];
            $now = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'];
            $memb_withdraw_cd = $params['memb_withdraw_cd']?? null;
            $memb_withdraw_reason = $params['memb_withdraw_reason']?? null;

          
            // 1. 관리자 권한
            $admin_check = $this->ci->userInfo->adminCheck($memb_seq);    
            if($admin_check > 0) {
                $msg = $this->ci->message->apiMessage['adminImpossible']; 
                return $response->withJson($msg);
            }

            // 2. CEO 가입 정보 체크
            $ceo_check = $this->ci->userInfo->CeoCheck($memb_seq);
            if($ceo_check > 0) {
                $msg = $this->ci->message->apiMessage['ceoImpossibleMobile']; 
                return $response->withJson($msg);
            }
            
            // 3. 멤버스 회원인지 체크
            $members_check = $this->ci->userInfo->membersCheck($memb_seq);    
            if($members_check > 0) {
                $msg = $this->ci->message->apiMessage['membersImpossible']; 
                return $response->withJson($msg);
            }
      
            // 4. 파킹패스 온 입차 상태
            $in_car_check = $this->ci->userInfo->inCarCheck($memb_seq); 
            if($in_car_check  > 0) {
                $msg = $this->ci->message->apiMessage['inCar'];
                return $response->withJson($msg);
            } 

            // 5. 유효한 상품 체크
            $product_sales_check = $this->ci->userInfo->productSalesCheck($memb_seq);
            if($product_sales_check  > 0) {
                $msg = $this->ci->message->apiMessage['existProduct'];
                return $response->withJson($msg);
            } 
            
  

          
            /* 회원 삭제처리 */
            // TODO : 회원 탈퇴는 한 트랜젝션안에서 이루어져야 한다.

      
            $this->ci->iparkingCloudDb->beginTransaction();  

            $car_possession_check = $this->ci->userInfo->carPossessionCheck($memb_seq);   
            if($car_possession_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_car', [
                    'bcar_del_ny' => 1,
                    'bcar_mod_device_cd' => 3,
                    'bcar_mod_datetime' => $now,
                    'bcar_mod_seq' => $memb_seq,
                    'bcar_mod_ip' => $ip
                ],
                [
                    'memb_seq' => $memb_seq
                ]);
            }
    

            // TODO : 파킹패스 빌링키 삭제 추가
            $billkey_check = $this->ci->userInfo->billkeyCheck($memb_seq);
            if($billkey_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_nesso', [
                    'nspo_del_ny' => 1,
                    'nspo_mod_device_cd' => 3,
                    'nspo_mod_datetime' => $now,
                    'nspo_mod_seq' => $memb_seq,
                    'nspo_mod_ip' => $ip
                ],
                [
                    'memb_seq' => $memb_seq
                ]);
            }

            // TODO : FCM 토큰 데이터 삭제 추가
            // 앱 푸시 정보 삭제
            $app_push_seq_check = $this->ci->userInfo->appPushSeqCheck($memb_seq);
            if($app_push_seq_check != 0){
            $this->ci->dbutil->insert('iparkingCloudDb', 'fdk_parkingcloud.member_dm_policy_history', [[
                    'mdph_memb_seq' => $memb_seq,
                    'mdph_service_cd' => 1,
                    'mdph_marketing_agree_ny' => 0,
                    'mdph_alarm_agree_ny' => 0,
                    'mdph_reg_datetime' => $now
                ]]);
            }

            $aws_token_check = $this->ci->userInfo->awsTokenCheck($memb_seq);
            if($aws_token_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_member_mobile_aws_sns_registrationid_token', [
                    'memso_del_ny' => 1,
                    'memso_mod_datetime' => $now,
                    'memso_mod_seq' => $memb_seq,
                    'memso_mod_ip' => $ip,
                    'memso_mod_device_cd' => 3
                ],
                [
                    'memso_memb_seq' => $memb_seq
                ]);
            }
                        

            // 감면정보가 있으면 삭제
            $user_reduction_check = $this->ci->userInfo->userReductionCheck($memb_seq);
            if($user_reduction_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.acd_rpms_member_reduction', [
                    'redc_reduction_status_cd' => 5,
                    'redc_del_ny' => 1,
                    'redc_mod_device_cd' => 3,
                    'redc_mod_datetime' => $now,
                    'redc_mod_seq' => $memb_seq,
                    'redc_mod_ip' => $ip
                ],
                [
                    'redc_memb_seq' => $memb_seq
                ]);
            }
            
            // 등록된 포인트 카드가 있으면 삭제
            $point_card_check = $this->ci->userInfo->pointCardCheck($memb_seq);
            if($point_card_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.member_point_card_info', [
                    'is_deleted' => 'Y',
                    'mod_sequence' => 0,
                    'mod_device_code' => 3,
                    'mod_datetime' => $now,
                    'mod_ip' => $ip
                ],
                [
                    'memb_seq' => $memb_seq
                ]);
            }

            // 제휴회원 정보 삭제 
            $alliance_info_check = $this->ci->userInfo->allianceInfoCheck($memb_seq);
            if($alliance_info_check != 0){
                $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.tbl_member_alliance_channel', [
                    'ma_del_ny' => 1,
                    'ma_mod_seq' => $memb_seq,
                    'ma_mod_device_cd' => 3,
                    'ma_mod_datetime' => $now,
                    'ma_mod_ip' => $ip
                ],
                [
                    'memb_seq' => $memb_seq
                ]);
            }

            // 회원 삭제
            $result = $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_b2ccore_member', [
                'memb_del_ny' => 1,
                'memb_mod_device_cd' => 3,
                'memb_mod_datetime' => $now,
                'memb_mod_seq' => $memb_seq,
                'memb_mod_ip' => $ip,
                'memb_withdraw_cd' => $memb_withdraw_cd,
                'memb_withdraw_reason' => $memb_withdraw_reason
            ],
            [
                'memb_seq' => $memb_seq
            ]);

            // 회원삭제까지 완료 된 후 커밋을 날린다.
            $this->ci->iparkingCloudDb->commit();

            if( $result ) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['fail'];
            }

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            $this->ci->iparkingCloudDb->rollBack();
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->iparkingCloudDb->rollBack();
            return $response->withJson(['error' => $e->getMessage()]);
        } catch(PDOException $pdoe) {
            $this->ci->iparkingCloudDb->rollBack();
            return $response->withJson(['error' => $pdoe->getMessage()]);
        }

    }

    public function getUserInfo($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $memb_seq = $params['memb_seq'];
            
            $stmt = $this->ci->iparkingCloudDb->prepare("
            SELECT 
                memb_seq,
                memb_id,
                memb_name
            FROM 
                fdk_parkingcloud.arf_b2ccore_member 
            WHERE 
                memb_del_ny = 0
                AND memb_seq = :memb_seq
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}