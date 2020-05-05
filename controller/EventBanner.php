<?php

class EventBanner
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // 웹 이벤트 배너 메인 순번 중복 체크
    public function duplicateWebEventBannerMainOrder($web_event_banner_main_order) 
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                count(*) as cnt
            FROM 
                iparking_cms.web_event_banner 
            WHERE 
                web_event_banner_on_off = 1 
            AND 
                web_event_banner_main_order = :web_event_banner_main_order
            AND
                del_yn = 0
        ');
        $stmt->execute(['web_event_banner_main_order' => $web_event_banner_main_order]);

        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;
    }

    // 웹 이벤트 배너 메인 순번 조정
    public function updateScheduleWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                * 
            FROM 
                iparking_cms.web_event_banner 
            WHERE 
                web_event_banner_on_off = 1 
            AND 
                web_event_banner_main_order >= :web_event_banner_main_order
            AND
                del_yn = 0
            AND web_event_banner_main_order is not null
            ORDER BY
                    length(web_event_banner_main_order),
                    web_event_banner_main_order ASC
        ');
        $stmt->execute(['web_event_banner_main_order' => $web_event_banner_main_order]);

        $web_event_banner_list = $stmt->fetchAll();

        if(!empty($web_event_banner_list)){
            foreach($web_event_banner_list as $web_event_banner_list_rows) {
                $rows_web_event_banner_idx = $web_event_banner_list_rows['web_event_banner_idx'];
                $rows_web_event_banner_main_order = $web_event_banner_list_rows['web_event_banner_main_order'];
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                    'web_event_banner_main_order' => $rows_web_event_banner_main_order+1
                ], [
                    'web_event_banner_idx' => $rows_web_event_banner_idx
                ]);
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'method' => 'update',
                    'web_event_banner_idx' => $rows_web_event_banner_idx,
                    'description' => $web_event_banner_idx.'번 배너 게시 on 상태변경에 의한 메인순번 +1',
                    'parameter' => [array(
                        'key' => 'web_event_banner_main_order',
                        'value' => array(
                            'before' => $rows_web_event_banner_main_order,
                            'after' => $rows_web_event_banner_main_order+1
                        )
                    )],
                    'create_id' => 'scheduler',
                    'create_time' => date('Y-m-d H:i:s')
                ]]);
            }
        }
    }

    // 웹 이벤트 배너 서비스 안내 중복 체크
    public function duplicateWebEventBannerServiceOrder($web_event_banner_service_order)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                count(*) as cnt
            FROM 
                iparking_cms.web_event_banner 
            WHERE 
                web_event_banner_on_off = 1 
            AND 
                web_event_banner_service_order = :web_event_banner_service_order
            AND
                del_yn = 0
        ');
        $stmt->execute(['web_event_banner_service_order' => $web_event_banner_service_order]);

        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;
    }
    
    // 웹 이벤트 배너 서비스 안내 순번 조정
    public function updateScheduleWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                * 
            FROM 
                iparking_cms.web_event_banner 
            WHERE 
                web_event_banner_on_off = 1 
            AND 
                web_event_banner_service_order >= :web_event_banner_service_order
            AND 
                del_yn = 0
            AND web_event_banner_service_order is not null
            ORDER BY
                length(web_event_banner_service_order),
                web_event_banner_service_order ASC
        ');
        $stmt->execute(['web_event_banner_service_order' => $web_event_banner_service_order]);

        $web_event_banner_list = $stmt->fetchAll();

        if(!empty($web_event_banner_list)){
            foreach($web_event_banner_list as $web_event_banner_list_rows) {
                $rows_web_event_banner_idx = $web_event_banner_list_rows['web_event_banner_idx'];
                $rows_web_event_banner_service_order = $web_event_banner_list_rows['web_event_banner_service_order'];
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                    'web_event_banner_service_order' => $rows_web_event_banner_service_order+1
                ], [
                    'web_event_banner_idx' => $rows_web_event_banner_idx
                ]);
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'method' => 'update',
                    'web_event_banner_idx' => $rows_web_event_banner_idx,
                    'description' => $web_event_banner_idx.'번 배너 게시 on 상태변경에 의한 서비스순번 +1',
                    'parameter' => [array(
                        'key' => 'web_event_banner_detail_service_order',
                        'value' => array(
                            'before' => $rows_web_event_banner_service_order,
                            'after' => $rows_web_event_banner_service_order+1
                        )
                    )],
                    'create_id' => 'scheduler',
                    'create_time' => date('Y-m-d H:i:s')
                ]]);
            }
        }
    }

    public function getBannerPositionVal($positionData){

        if(gettype($positionData) == 'string') {
            $positionData = json_decode($positionData,true);
        }

        $positionMain = 0;
        $positionService = 0;
    
        foreach($positionData as $positionDataRow=>$positionDataRowVal){
            if($positionDataRowVal['web_event_banner_position'] == 'web_event_banner_main_order'){
                $positionMain = 1;
            }
            if($positionDataRowVal['web_event_banner_position'] == 'web_event_banner_service_order'){
                $positionService = 1;
            }
        }

        $positionDataArr['main'] = $positionMain;
        $positionDataArr['service'] = $positionService;

        return $positionDataArr;
    }

    // 메인배너 순번 +1 증가 
    public function incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                web_event_banner_idx, 
                web_event_banner_main_order
            FROM iparking_cms.web_event_banner
            WHERE del_yn = 0 
            AND web_event_banner_on_off = 1
            AND keep_yn = :keep_yn
            AND web_event_banner_main_order >= :web_event_banner_main_order
            AND web_event_banner_main_order is not null
            ORDER BY
                length(web_event_banner_main_order),
                web_event_banner_main_order ASC
        ');
        $stmt->execute([
            'keep_yn' => 'N', 
            'web_event_banner_main_order' => $web_event_banner_main_order
        ]);
        $main_order_list = $stmt->fetchAll();

        //+1씩 업데이트
        $main_order_count = 0;
        $main_order_update_flag = false;
        if(!empty($main_order_list)) {
            foreach($main_order_list as $main_order_list_rows){
                $target_idx = $main_order_list_rows['web_event_banner_idx'];
                $target_main_order = $main_order_list_rows['web_event_banner_main_order'];
                if($main_order_count == 0) {
                    if($web_event_banner_main_order == $target_main_order) {
                        $main_order_update_flag = true;
                    }
                }
                if($main_order_update_flag) {
                    if($web_event_banner_main_order+$main_order_count == $target_main_order) {
                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기 때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                            'web_event_banner_main_order' => $target_main_order+1
                        ], [
                            'web_event_banner_idx' => $target_idx
                        ]);

                        $parameter = [array(
                            'before' => $target_main_order,
                            'after' => $target_main_order+1
                        )];

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                            'web_event_banner_idx' => $target_idx,
                            'method' => 'update',
                            'description' => $web_event_banner_idx.' 번 배너 등록에 따른 메인배너 순번 +1',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }   
                    
                }  
                $main_order_count++;
            }
        }
    }
    
    // 메인배너 순번 이전 값이 바뀔 순번 보다 작을 경우 사이값 +1 증가 
    public function incrementWebEventBannerMainOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_main_order_before ,$web_event_banner_main_order_after)
    {
        // main_order beforeLarge case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_main_order 
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            AND web_event_banner_main_order < :web_event_banner_main_order_before
            AND web_event_banner_main_order >= :web_event_banner_main_order_after
            AND web_event_banner_main_order is not null
            ORDER BY
                length(web_event_banner_main_order),
                web_event_banner_main_order ASC
        ');
        $stmt->execute([
            'web_event_banner_main_order_before' => $web_event_banner_main_order_before,
            'web_event_banner_main_order_after' => $web_event_banner_main_order_after
        ]);

        $mainBeforeLargeList = $stmt->fetchAll();
        
        if(!empty($mainBeforeLargeList)){
            foreach($mainBeforeLargeList as $mainBeforeLargeListRow){
                $target_idx_main_beforeLarge = $mainBeforeLargeListRow['web_event_banner_idx'];
                $target_order_main_beforeLarge = $mainBeforeLargeListRow['web_event_banner_main_order'];
               
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_main_order' => $target_order_main_beforeLarge+1
                ], [ 
                    'web_event_banner_idx' => $target_idx_main_beforeLarge
                ]);
                
                $parameter = [array(
                    'before' => $target_order_main_beforeLarge,
                    'after' => $target_order_main_beforeLarge+1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx_main_beforeLarge,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 메인순번 수정에 따른 메인배너 순번 +1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            } 
        }
    }

    // 메인배너 순번 -1 감소 
    public function decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_main_order
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            AND keep_yn = :keep_yn
            AND web_event_banner_main_order > :web_event_banner_main_order
            AND web_event_banner_main_order is not null
            ORDER BY
                length(web_event_banner_main_order),
                web_event_banner_main_order ASC
        ');
        $stmt->execute([
            'keep_yn' => 'N',
            'web_event_banner_main_order' => $web_event_banner_main_order
        ]);
        $mainOrderList = $stmt->fetchAll();

        if(!empty($mainOrderList)){
            $foreachCount = 0;
            foreach($mainOrderList as $mainOrderListRow){
                $target_idx = $mainOrderListRow['web_event_banner_idx'];
                $target_main_order = $mainOrderListRow['web_event_banner_main_order'];       
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_main_order' => $web_event_banner_main_order+$foreachCount
                ], [ 
                    'web_event_banner_idx' => $target_idx
                ]);
    
                $parameter = [array(
                    'before' => $target_main_order,
                    'after' => $web_event_banner_main_order+$foreachCount
                )];
    
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 메인순번 수정에 따른 메인배너 순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);

                $foreachCount++;
            }
        }
    }




    // 메인배너 순번 이전 값이 바뀔 순번 보다 클 경우 사이값 -1 감소
    public function decrementWebEventBannerMainOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_main_order_before ,$web_event_banner_main_order_after)
    {
        // main_order beforeSmall case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_main_order 
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            AND web_event_banner_main_order > :web_event_banner_main_order_before
            AND web_event_banner_main_order <= :web_event_banner_main_order_after
            AND web_event_banner_main_order is not null
            ORDER BY
                length(web_event_banner_main_order),
                web_event_banner_main_order ASC
        ');
        $stmt->execute([
            'web_event_banner_main_order_before' => $web_event_banner_main_order_before,
            'web_event_banner_main_order_after' => $web_event_banner_main_order_after
        ]);

        $mainBeforeSmallList = $stmt->fetchAll();

        if(!empty($mainBeforeSmallList)){
            foreach($mainBeforeSmallList as $mainBeforeSmallListRow){
                $target_idx_main_beforeSmall = $mainBeforeSmallListRow['web_event_banner_idx'];
                $target_order_main_beforeSmall = $mainBeforeSmallListRow['web_event_banner_main_order'];
                
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_main_order'     => $target_order_main_beforeSmall-1
                ], [ 
                    'web_event_banner_idx' => $target_idx_main_beforeSmall
                ]);
                
                $parameter = [array(
                    'before' => $target_order_main_beforeSmall,
                    'after' => $target_order_main_beforeSmall-1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx_main_beforeSmall,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 메인순번 수정에 따른 메인배너 순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
        }
    }

    // 서비스배너 순번 +1 증가
    public function incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT 
                web_event_banner_idx, 
                web_event_banner_service_order
            FROM iparking_cms.web_event_banner
            WHERE del_yn = 0 
            AND keep_yn = :keep_yn
            AND web_event_banner_on_off = 1
            AND web_event_banner_service_order >= :web_event_banner_service_order
            AND web_event_banner_service_order is not null
            ORDER BY
                length(web_event_banner_service_order),
                web_event_banner_service_order ASC
        ');

        $stmt->execute([
            'keep_yn' => 'N',
            'web_event_banner_service_order' => $web_event_banner_service_order
        ]);
        $service_order_list = $stmt->fetchAll();

        //+1씩 업데이트
        $service_order_count = 0;
        $service_order_update_flag = false;
        if(!empty($service_order_list)) {
            foreach($service_order_list as $service_order_list_rows){
                $target_idx = $service_order_list_rows['web_event_banner_idx'];
                $target_service_order = $service_order_list_rows['web_event_banner_service_order'];
                
                if($service_order_count == 0) {
                    if($web_event_banner_service_order == $target_service_order) {
                        $service_order_update_flag = true;
                    }
                }
                if($service_order_update_flag) {
                    if($web_event_banner_service_order+$service_order_count == $target_service_order) {
                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                            'web_event_banner_service_order' => $target_service_order+1
                        ], [
                            'web_event_banner_idx' => $target_idx
                        ]);
                    }

                    $parameter = [array(
                        'before' => $target_service_order,
                        'after' => $target_service_order+1
                    )];

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'web_event_banner_idx' => $target_idx,
                        'method' => 'update',
                        'description' => $web_event_banner_idx.' 번 배너 등록에 따른 서비스배너 순번 +1',
                        'parameter' => $parameter,
                        'create_id' => $user_id,
                        'create_name' => $user_name,
                        'create_time' => $now
                    ]]);
                }
                $service_order_count++;
            }
        }
    }

    // 서비스배너 이전 값이 바뀔 순번 보다 작을 경우 사이값 +1 증가 
    public function incrementWebEventBannerServiceOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_service_order_before ,$web_event_banner_service_order_after)
    {
        // service_order beforeLarge case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_service_order 
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            AND web_event_banner_service_order < :web_event_banner_service_order_before
            AND web_event_banner_service_order >= :web_event_banner_service_order_after
            AND web_event_banner_service_order is not null
            ORDER BY
                length(web_event_banner_service_order),
                web_event_banner_service_order ASC
        ');
        $stmt->execute([
            'web_event_banner_service_order_before' => $web_event_banner_service_order_before,
            'web_event_banner_service_order_after' => $web_event_banner_service_order_after
        ]);

        //담는변수명 변경
        $serviceBeforeLargeList = $stmt->fetchAll();

        if (!empty($serviceBeforeLargeList)){
            
            foreach($serviceBeforeLargeList as $serviceBeforeLargeListRow){
                $target_idx_service_beforeLarge = $serviceBeforeLargeListRow['web_event_banner_idx'];
                $target_order_service_beforeLarge = $serviceBeforeLargeListRow['web_event_banner_service_order'];
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_service_order'     => $target_order_service_beforeLarge+1         
                ], [ 
                    'web_event_banner_idx' => $target_idx_service_beforeLarge
                ]);

                $parameter = [array(
                    'before' => $target_order_service_beforeLarge,
                    'after' => $target_order_service_beforeLarge+1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx_service_beforeLarge,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 서비스순번 수정에 따른 서비스 순번 +1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
        }
    }

    // 서비스배너 순번 -1 감소
    public function decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_service_order
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            AND keep_yn = :keep_yn
            AND web_event_banner_service_order > :web_event_banner_service_order
            AND web_event_banner_service_order is not null
            ORDER BY
                length(web_event_banner_service_order),
                web_event_banner_service_order ASC
        ');
        
        $stmt->execute([
            'keep_yn' => 'N',
            'web_event_banner_service_order' => $web_event_banner_service_order
        ]);

        $serviceOrderList = $stmt->fetchAll();

        if(!empty($serviceOrderList)){
            $foreachCount = 0;
            foreach($serviceOrderList as $serviceOrderListRow){
                $target_idx = $serviceOrderListRow['web_event_banner_idx'];
                $target_service_order = $serviceOrderListRow['web_event_banner_service_order'];
    
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_service_order' => $web_event_banner_service_order+$foreachCount
                ], [ 
                    'web_event_banner_idx' => $target_idx
                ]);
    
                $parameter = [array(
                    'before' => $target_service_order,
                    'after' => $web_event_banner_service_order+$foreachCount
                )];
    
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 서비스순번 수정에 따른 서비스배너 순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
                $foreachCount++;
            }
        }
    }

    // 서비스배너 순번 이전 값이 바뀔 순번 보다 클 경우 사이값 -1 감소
    public function decrementWebEventBannerServiceOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_service_order_before ,$web_event_banner_service_order_after)
    {
        
        // service_order beforeSmall case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_service_order 
            FROM
                iparking_cms.web_event_banner
            WHERE del_yn = 0
            AND web_event_banner_on_off = 1
            and web_event_banner_service_order > :web_event_banner_service_order_before
            and web_event_banner_service_order <= :web_event_banner_service_order_after
            AND web_event_banner_service_order is not null
            ORDER BY
                length(web_event_banner_service_order),
                web_event_banner_service_order ASC
        ');
        $stmt->execute([
            'web_event_banner_service_order_before' => $web_event_banner_service_order_before,
            'web_event_banner_service_order_after' => $web_event_banner_service_order_after
        ]);

        $serviceBeforeSmallList = $stmt->fetchAll();
        
        if(!empty($serviceBeforeSmallList)){
            foreach($serviceBeforeSmallList as $serviceBeforeSmallListRow){
                $target_idx_service_beforeSmall = $serviceBeforeSmallListRow['web_event_banner_idx'];
                $target_order_service_beforeSmall = $serviceBeforeSmallListRow['web_event_banner_service_order'];

                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                    'web_event_banner_service_order'     => $target_order_service_beforeSmall-1
                ], [ 
                    'web_event_banner_idx' => $target_idx_service_beforeSmall
                ]);
                $parameter = [array(
                    'before' => $target_order_service_beforeSmall,
                    'after' => $target_order_service_beforeSmall-1
                )];
                
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $target_idx_service_beforeSmall,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 서비스순번 수정에 따른 서비스 순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
        }
    }

    // 메인팝업 순번 +1 증가 
    public function incrementEventAppMainPopOrder($event_seq, $event_app_main_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        if($user_id == '' || $user_id == null){
            $user_id = 'scheduler';
            $user_name = 'scheduler';
        }

        $stmt = $this->ci->iparkingCmsDb->prepare('
        SELECT
            event_seq,
            event_app_main_order
        FROM iparking_cms.board_event
        WHERE 
            event_del_yn = 0
        AND 
            event_on_off = 1
        AND 
            keep_yn = :keep_yn
        AND 
            event_app_main_order >= :event_app_main_order
        AND 
            event_app_main_order is not null
        ORDER BY
            length(event_app_main_order),
            event_app_main_order ASC
        ');
        $stmt->execute([
            'keep_yn' => 'N', 
            'event_app_main_order' => $event_app_main_order
        ]);
        $main_order_list = $stmt->fetchAll();

        //+1씩 업데이트
        $main_order_count = 0;
        $main_order_update_flag = false;
        if(!empty($main_order_list)) {
            foreach($main_order_list as $main_order_list_rows){
                $target_idx = $main_order_list_rows['event_seq'];
                $target_main_order = $main_order_list_rows['event_app_main_order'];
                if($main_order_count == 0) {
                    if($event_app_main_order == $target_main_order) {
                        $main_order_update_flag = true;
                    }
                }
                if($main_order_update_flag) {
                    if($event_app_main_order+$main_order_count == $target_main_order) {
                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기 때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                            'event_app_main_order' => $target_main_order+1
                        ], [
                            'event_seq' => $target_idx
                        ]);

                        $parameter = [array(
                            'before' => $target_main_order,
                            'after' => $target_main_order+1
                        )];

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                            'event_seq' => $target_idx,
                            'method' => 'update',
                            'description' => $event_seq.' 번 메인팝업 게시순번 수정에 따른 메인팝업 게시순번 +1',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }   
                    
                }  
                $main_order_count++;
            }
        }
    }


    // 추천배너 순번 +1 증가 
    public function incrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        if($user_id == '' || $user_id == null){
            $user_id = 'scheduler';
            $user_name = 'scheduler';
        }

        $stmt = $this->ci->iparkingCmsDb->prepare('
        SELECT
            event_seq,
            event_like_banner_order
        FROM iparking_cms.board_event
        WHERE 
            event_del_yn = 0
        AND 
            event_on_off = 1
        AND 
            keep_yn = :keep_yn
        AND 
            event_like_banner_order >= :event_like_banner_order
        AND 
            event_like_banner_order is not null
        ORDER BY
            length(event_like_banner_order),
            event_like_banner_order ASC
        ');
        $stmt->execute([
            'keep_yn' => 'N', 
            'event_like_banner_order' => $event_like_banner_order
        ]);
        $like_order_list = $stmt->fetchAll();

        //+1씩 업데이트
        $like_order_count = 0;
        $like_order_update_flag = false;
        if(!empty($like_order_list)) {
            foreach($like_order_list as $like_order_list_rows){
                $target_idx = $like_order_list_rows['event_seq'];
                $target_like_order = $like_order_list_rows['event_like_banner_order'];
                if($like_order_count == 0) {
                    if($event_like_banner_order == $target_like_order) {
                        $like_order_update_flag = true;
                    }
                }
                if($like_order_update_flag) {
                    if($event_like_banner_order+$like_order_count == $target_like_order) {
                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기 때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                            'event_like_banner_order' => $target_like_order+1
                        ], [
                            'event_seq' => $target_idx
                        ]);

                        $parameter = [array(
                            'before' => $target_like_order,
                            'after' => $target_like_order+1
                        )];

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                            'event_seq' => $target_idx,
                            'method' => 'update',
                            'description' => $event_seq.' 번 추천배너 게시순번 수정에 따른 추천배너 게시순번 +1',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }   
                    
                }  
                $like_order_count++;
            }
        }
    }


    // 앱 메인팝업 순번 -1 감소 
    public function decrementEventAppMainPopOrder($event_seq, $event_app_main_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_app_main_order
            FROM
                iparking_cms.board_event
            WHERE 
                event_del_yn = 0
            AND 
                event_on_off = 1
            AND 
                event_app_main_image_yn = 1
            AND 
                keep_yn = :keep_yn
            AND 
                event_app_main_order > :event_app_main_order
            AND 
                event_app_main_order is not null
            ORDER BY
                length(event_app_main_order),
                event_app_main_order ASC
        ');
        $stmt->execute([
            'keep_yn' => 'N',
            'event_app_main_order' => $event_app_main_order
        ]);
        $mainOrderList = $stmt->fetchAll();

        if(!empty($mainOrderList)){
            $foreachCount = 0;
            foreach($mainOrderList as $mainOrderListRow){
                $target_idx = $mainOrderListRow['event_seq'];
                $target_main_order = $mainOrderListRow['event_app_main_order'];       
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_app_main_order' => $event_app_main_order+$foreachCount
                ], [ 
                    'event_seq' => $target_idx
                ]);
    
                $parameter = [array(
                    'before' => $target_main_order,
                    'after' => $event_app_main_order+$foreachCount
                )];
    
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 메인팝업순번 수정에 따른 메인팝업순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);

                $foreachCount++;
            }
        }
    }


    // 추천배너 순번 -1 감소
    public function decrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order)
    {
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_like_banner_order
            FROM
                iparking_cms.board_event
            WHERE 
                event_del_yn = 0
            AND 
                event_on_off = 1
            AND 
                event_like_banner_yn = 1
            AND 
                keep_yn = :keep_yn
            AND 
                event_like_banner_order > :event_like_banner_order
            AND 
                event_like_banner_order is not null
            ORDER BY
                length(event_like_banner_order),
                event_like_banner_order ASC
        ');

        $stmt->execute([
            'keep_yn' => 'N',
            'event_like_banner_order' => $event_like_banner_order
        ]);

        $likeOrderList = $stmt->fetchAll();

        if(!empty($likeOrderList)){
            $foreachCount = 0;
            foreach($likeOrderList as $likeOrderListRow){
                $target_idx = $likeOrderListRow['event_seq'];
                $target_like_order = $likeOrderListRow['event_like_banner_order'];
    
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_like_banner_order' => $event_like_banner_order+$foreachCount
                ], [ 
                    'event_seq' => $target_idx
                ]);
    
                $parameter = [array(
                    'before' => $target_like_order,
                    'after' => $event_like_banner_order+$foreachCount
                )];
    
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 추천배너순번 수정에 따른 추천배너순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
                $foreachCount++;
            }
        }
    }

    // 메인팝업 순번 이전 값이 바뀔 순번 보다 클 경우 사이값 -1 감소
    public function decrementEventAppMainPopOrderBetweenFrontAndBack($event_seq, $event_app_main_order_before, $event_app_main_order_after)
    {
        // main_order beforeSmall case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_app_main_order 
            FROM
                iparking_cms.board_event
            WHERE event_del_yn = 0
            AND event_app_main_image_yn = 1
            AND event_on_off = 1
            AND event_app_main_order > :event_app_main_order_before
            AND event_app_main_order <= :event_app_main_order_after
            AND event_app_main_order is not null
            ORDER BY
                length(event_app_main_order),
                event_app_main_order ASC
        ');
        $stmt->execute([
            'event_app_main_order_before' => $event_app_main_order_before,
            'event_app_main_order_after' => $event_app_main_order_after
        ]);

        $mainBeforeSmallList = $stmt->fetchAll();

        if(!empty($mainBeforeSmallList)){
            foreach($mainBeforeSmallList as $mainBeforeSmallListRow){
                $target_idx_main_beforeSmall = $mainBeforeSmallListRow['event_seq'];
                $target_order_main_beforeSmall = $mainBeforeSmallListRow['event_app_main_order'];
                
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_app_main_order'     => $target_order_main_beforeSmall-1
                ], [ 
                    'event_seq' => $target_idx_main_beforeSmall
                ]);
                
                $parameter = [array(
                    'before' => $target_order_main_beforeSmall,
                    'after' => $target_order_main_beforeSmall-1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx_main_beforeSmall,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 메인팝업 게신순서 수정에 따른 메인팝업 게시순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
        }
    }




    // 메인팝업 순번 이전 값이 바뀔 순번 보다 작을 경우 사이값 +1 증가 
    public function incrementEventAppMainPopOrderBetweenFrontAndBack($event_seq, $event_app_main_order_before, $event_app_main_order_after)
    {
        // main_order beforeLarge case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        // 순번정렬로직에서 예약은 순번컬림이 비어있기때문에 의미가 없다.
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_app_main_order 
            FROM
                iparking_cms.board_event
            WHERE event_del_yn = 0
            AND event_on_off = 1
            AND event_app_main_image_yn = 1
            AND event_app_main_order < :event_app_main_order_before
            AND event_app_main_order >= :event_app_main_order_after
            AND event_app_main_order is not null
            ORDER BY
                length(event_app_main_order),
                event_app_main_order ASC
        ');
        $stmt->execute([
            'event_app_main_order_before' => $event_app_main_order_before,
            'event_app_main_order_after' => $event_app_main_order_after
        ]);

        $mainBeforeLargeList = $stmt->fetchAll();

        if(!empty($mainBeforeLargeList)){
            foreach($mainBeforeLargeList as $mainBeforeLargeListRow){
                $target_idx_main_beforeLarge = $mainBeforeLargeListRow['event_seq'];
                $target_order_main_beforeLarge = $mainBeforeLargeListRow['event_app_main_order'];
               
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_app_main_order' => $target_order_main_beforeLarge+1
                ], [ 
                    'event_seq' => $target_idx_main_beforeLarge
                ]);
                
                $parameter = [array(
                    'before' => $target_order_main_beforeLarge,
                    'after' => $target_order_main_beforeLarge+1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx_main_beforeLarge,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 메인팝업순번 수정에 따른 메인팝업순번 +1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            } 
        }
    }



    // 메인팝업 순번 이전 값이 바뀔 순번 보다 클 경우 사이값 -1 감소
    public function decrementEventAppLikeBannerOrderBetweenFrontAndBack($event_seq, $event_like_banner_order_before, $event_like_banner_order_after)
    {
        // like_order beforeSmall case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_like_banner_order 
            FROM
                iparking_cms.board_event
            WHERE event_del_yn = 0
            AND event_like_banner_yn = 1
            AND event_on_off = 1
            AND event_like_banner_order > :event_like_banner_order_before
            AND event_like_banner_order <= :event_like_banner_order_after
            AND event_like_banner_order is not null
            ORDER BY
                length(event_like_banner_order),
                event_like_banner_order ASC
        ');
        $stmt->execute([
            'event_like_banner_order_before' => $event_like_banner_order_before,
            'event_like_banner_order_after' => $event_like_banner_order_after
        ]);

        $likeBeforeSmallList = $stmt->fetchAll();

        if(!empty($likeBeforeSmallList)){
            foreach($likeBeforeSmallList as $likeBeforeSmallListRow){
                $target_idx_like_beforeSmall = $likeBeforeSmallListRow['event_seq'];
                $target_order_like_beforeSmall = $likeBeforeSmallListRow['event_like_banner_order'];
                
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_like_banner_order' => $target_order_like_beforeSmall-1
                ], [ 
                    'event_seq' => $target_idx_like_beforeSmall
                ]);
                
                $parameter = [array(
                    'before' => $target_order_like_beforeSmall,
                    'after' => $target_order_like_beforeSmall-1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx_like_beforeSmall,
                    'method' => 'update',
                    'description' => $event_seq.' 번 추천배너 게신순서 수정에 따른 추천배너 게시순번 -1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
        }
    }




    // 추천배너 순번 이전 값이 바뀔 순번 보다 작을 경우 사이값 +1 증가 
    public function incrementEventAppLikeBannerOrderBetweenFrontAndBack($event_seq, $event_like_banner_order_before, $event_like_banner_order_after)
    {
        // like_order beforeLarge case
        $now = date('Y-m-d H:i:s');
        $user_id = $this->ci->settings['userInfo']['id'];
        $user_name = $this->ci->settings['userInfo']['name'];

        // 순번정렬로직에서 예약은 순번컬림이 비어있기때문에 의미가 없다.
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                event_seq,
                event_like_banner_order 
            FROM
                iparking_cms.board_event
            WHERE event_del_yn = 0
            AND event_on_off = 1
            AND event_like_banner_yn = 1
            AND event_like_banner_order < :event_like_banner_order_before
            AND event_like_banner_order >= :event_like_banner_order_after
            AND event_like_banner_order is not null
            ORDER BY
                length(event_like_banner_order),
                event_like_banner_order ASC
        ');
        $stmt->execute([
            'event_like_banner_order_before' => $event_like_banner_order_before,
            'event_like_banner_order_after' => $event_like_banner_order_after
        ]);

        $likeBeforeLargeList = $stmt->fetchAll();
        
        if(!empty($likeBeforeLargeList)){
            foreach($likeBeforeLargeList as $likeBeforeLargeListRow){
                $target_idx_like_beforeLarge = $likeBeforeLargeListRow['event_seq'];
                $target_order_like_beforeLarge = $likeBeforeLargeListRow['event_like_banner_order'];
               
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event',[
                    'event_like_banner_order' => $target_order_like_beforeLarge+1
                ], [ 
                    'event_seq' => $target_idx_like_beforeLarge
                ]);
                
                $parameter = [array(
                    'before' => $target_order_like_beforeLarge,
                    'after' => $target_order_like_beforeLarge+1
                )];

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $target_idx_like_beforeLarge,
                    'method' => 'update',
                    'description' => $event_seq.' 번 추천배너순번 수정에 따른 추천배너순번 +1',
                    'parameter' => $parameter,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            } 
        }
    }


    // 앱 이벤트 메인팝업 순번 중복 체크
    public function duplicateWebEventAppMainPopOrder($event_app_main_order) 
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                count(*) as cnt
            FROM
                iparking_cms.board_event
            WHERE
                event_on_off = 1
            AND
                event_app_main_order = :event_app_main_order
            AND
                event_del_yn = 0
        ');
        $stmt->execute(['event_app_main_order' => $event_app_main_order]);

        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;
    }

    // 앱 이벤트 추천배너 안내 중복 체크
    public function duplicateWebEventAppLikeBannerOrder($event_like_banner_order)
    {
        $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                count(*) as cnt
            FROM
                iparking_cms.board_event
            WHERE
                event_on_off = 1
            AND
                event_like_banner_order = :event_like_banner_order
            AND
                event_del_yn = 0
        ');
        $stmt->execute(['event_like_banner_order' => $event_like_banner_order]);

        $data = $stmt->fetch();

        return $data['cnt'] ?? 0;
    }
}