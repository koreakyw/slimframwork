<?php
/**
 * LegalPolicyViewController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     이용약관
 * @date      2018/05/16
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class LegalPolicyViewController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    /**
     * getLegalPolicyView function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     이용약관 상세보기
     * @date      2018/05/16
     * @see       기존 api정보 : /app/arf/basis/legalpolicy/legalPolicyViewAug.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getLegalPolicyView($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);

            $lepo_legal_policy_cd = $params['legalPolicyItem_lepo_legal_policy_cd'];
            $lepo_operating_cmpy_cd = 1;

            $stmt = $this->ci->iparkingCloudDb->("
                SELECT lepo_seq
                    ,lepo_legal_policy_cd
                    ,lepo_contents
                    ,lepo_mod_datetime
                FROM 
                fdk_parkingcloud.arf_basis_legal_policy
                WHERE 
                    lepo_del_ny = 0
                    AND lepo_legal_policy_cd = :lepo_legal_policy_cd
                    AND lepo_operating_cmpy_cd = :lepo_operating_cmpy_cd
                LIMIT 1
            ");

            $stmt->execute(['lepo_legal_policy_cd' => $lepo_legal_policy_cd, 'lepo_operating_cmpy_cd' => $lepo_operating_cmpy_cd]);
			$result = $stmt->fetch();

            $msg['result'] = $this->ci->message->oldMessage['success'];
            $msg['desc'] = $result;
            
            return $response->withJson($msg);
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    
}