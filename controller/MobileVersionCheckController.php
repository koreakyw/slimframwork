<?php
/**
 * MobileVersionCheckController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     모바일 버전 체크 클랙스
 * @date      2018/05/15
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class MobileVersionCheckController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    /**
     * getMobileVersionCheck function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     모바일 버전 체크 가져오는 함수
     * @date      2018/05/15
     * @see       기존 api정보 : /web/fdk/rpms/mobileversion/mobileVersionChekAug.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getMobileVersionCheck($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);

            $apvr_mobile_os = $params['mobileversionItem_apvr_mobile_os_cd'];
            $apvr_app_name_cd = $params['mobileversionItem_apvr_app_name_cd'];

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT  apvr_version_name
                    ,apvr_version_number
                    ,apvr_update_ny
                FROM 	
                    fdk_parkingcloud.arf_core_app_version
                WHERE	
                    apvr_use_ny = 1
                    AND apvr_del_ny = 0
                    AND apvr_mobile_os_cd = :apvr_mobile_os
                    AND apvr_app_name_cd = :apvr_app_name_cd
                ORDER BY 
                    apvr_seq DESC
                LIMIT 1
            ");

            $stmt->execute(['apvr_mobile_os' => $apvr_mobile_os, 'apvr_app_name_cd' => $apvr_app_name_cd]);
			$result = $stmt->fetch();
            
            $msg['result'] = $this->ci->message->oldMessage['success'];
            $msg['desc']['items'] = $result;
            
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
}