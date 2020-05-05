<?php
/**
 * PushController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     PUSH 관련 클랙스
 * @date      2018/06/17 
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class PushController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    /**
     * getPushSearch function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     PUSH 설정 정보 가져오는 함수
     * @date      2018/06/17
     * @see       기존 api정보 : /iparking/push/search.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getPushSearch($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);
            $memb_seq = $params['memb_seq'];

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT 
                    mdpo_service_cd
                    ,mdpo_alarm_agree_ny
                    ,DATE_FORMAT(mdpo_alarm_reg_datetime, '%Y-%m-%d %H:%i') as mdpo_alarm_reg_datetime
                    ,mdpo_marketing_agree_ny
                    ,DATE_FORMAT(mdpo_marketing_reg_datetime, '%Y-%m-%d %H:%i') as mdpo_marketing_reg_datetime
                FROM 
                fdk_parkingcloud.member_dm_policy
                WHERE 
                    mdpo_memb_seq = :memb_seq
                    AND mdpo_service_cd = '1'
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
            $result = $stmt->fetch();
            
            $msg['pushReceptSearch'] = $this->ci->message->oldMessage['success'];
            $msg['pushReceptSearch']['result'] = "0";
            $msg['pushReceptSearch']['totalCnt'] = 0;
            $msg['pushReceptSearch']['resultData'] = $result;

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
}