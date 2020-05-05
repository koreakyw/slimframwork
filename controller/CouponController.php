<?php
/**
 * CouponController class
 *
 * @author    이창민<cmlee@parkingcloud.co.kr>
 * @brief     쿠폰 관련 클랙스
 * @date      2018/05/15
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class CouponController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    /**
     * getCoponList function
     *
     * @param [type] $request
     * @param [none] $response
     * @param [type] $args
     * @return void
     * @author    이창민<cmlee@parkingcloud.co.kr>
     * @brief     보유중인 쿠폰 리스트 가져오는 함수
     * @date      2018/05/15
     * @see       기존 api정보 : /app/arf/basis/coupon/couponListDataAug.do
     * @todo      추가적으로 해야할 사항 기입
     */
    public function getCoponList($request, $response, $args) 
	{

		try {
            $params = $this->ci->util->getParams($request);
            $memb_seq = $params['couponItem_cp_memb_seq'];

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT
                    count(*) AS totalCount
                FROM
                    fdk_parkingcloud.arf_basis_coupon_history a
                    INNER JOIN fdk_parkingcloud.arf_basis_coupon b on b.cp_seq = a.cp_seq
                WHERE
                    a.cp_use_ny = 0
                    AND a.cp_memb_seq = :memb_seq
                    AND a.cp_hist_ex_start_time <= NOW()
                    AND a.cp_hist_ex_end_time >= NOW()
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
            $totalCnt = $stmt->fetch();

            $stmt = $this->ci->iparkingCloudDb->prepare("
                SELECT
                    DATEDIFF(a.cp_hist_ex_end_time,NOW()) +1 as countdown
                    ,b.cp_type_cd
		            ,b.cp_name
		            ,b.cp_price
		            ,a.cp_hist_ex_end_time
                    ,b.cp_use_info
                    ,b.cp_min_price
                    ,a.cp_hist_seq
                FROM 
                    fdk_parkingcloud.arf_basis_coupon_history a
                    INNER JOIN fdk_parkingcloud.arf_basis_coupon b on b.cp_seq = a.cp_seq
                WHERE 
                    a.cp_use_ny = 0
                    AND a.cp_memb_seq = :memb_seq
                    AND a.cp_hist_ex_start_time <= NOW()
                    AND a.cp_hist_ex_end_time >= NOW() 
                ORDER BY
                    a.cp_hist_seq DESC
            ");

            $stmt->execute(['memb_seq' => $memb_seq]);
            $result = $stmt->fetchall();
            
            $msg['result'] = $this->ci->message->oldMessage['success'];
            $msg['result']['total'] = $totalCnt['totalCount'];
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