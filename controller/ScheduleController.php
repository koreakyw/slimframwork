<?php
/**
 * PushController class
 *
 * @author    김영운<ywkim@parkingcloud.co.kr>
 * @brief     cron schedule 관련 클랙스
 * @date      2018/06/26 
 * @see       참고해야 할 사항을 작성
 * @todo      추가적으로 해야할 사항 기입
 */
class ScheduleController {
    protected $ci;

    public function __construct($ci) {
        $this->ci = $ci;
    }

    public function getEventAppOnOffUpdate($request, $response, $args){

        try{
            
            $params = $this->ci->util->getParams($request);

            $today = date('Y-m-d');
            $today_hour = date('Y-m-d H').':00:00';
            $now = date('Y-m-d H:i:s');

            // 1. 종료일시가 된 이벤트 찾기
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT *
                FROM iparking_cms.board_event
                WHERE 
                    event_del_yn = 0
                AND 
                    event_on_off = 1
                AND 
                    event_end_datetime < :today_hour
                AND
                    ( event_app_main_image_yn = 1 OR event_like_banner_yn = 1 )
            ');
            $stmt->execute(['today_hour' => $today_hour]);

            $exitList = $stmt->fetchAll();

            if(!empty($exitList)){
                foreach($exitList as $exitListRow){
                    $exit_idx = $exitListRow['event_seq'];

                    // 2. 종료일시가 된 이벤트 off 처리 
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [

                        'event_app_main_image_yn' => 0,
                        'event_like_banner_yn' => 0,
                        'event_app_main_order' => null,
                        'event_like_banner_order' => null,
                        'event_keep_app_main_order' => null,
                        'event_keep_like_banner_order' => null,
                        
                        'on_off_update_id' => 'scheduler',
                        'on_off_update_name' => 'scheduler',
                        'on_off_update_time' => $now
                    ], [
                        'event_seq' => $exit_idx
                    ]);

                    $parameter = [array(
                        'before' => '1',
                        'after' => '0'
                    )];
                    
                    // 3. off 처리한 이력 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                        'event_seq' => $exit_idx,
                        'method' => 'update',
                        'description' => 'scheduler에 의한 종료일시가 된 이벤트 OFF 처리 수행',
                        'parameter' => $parameter,
                        'create_id' => 'scheduler',
                        'create_name' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
            }


            // 4. 게시 ON 이며 배너 게시일시가 된 예약이벤트 리스트를 찾는다.
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT *
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_del_yn = 0
                AND 
                    event_on_off = 1
                AND 
                    keep_yn = :keep_yn
                AND 
                    event_start_datetime = :today_hour
                ORDER BY 
                    update_time DESC
            ');

            $stmt->execute([
                'keep_yn' => 'Y',
                'today_hour' => $today_hour
            ]);

            $start_list = $stmt->fetchAll();
            
            foreach($start_list as $start_list_rows){
                $event_seq = $start_list_rows['event_seq'];
                $event_keep_app_main_order = $start_list_rows['event_keep_app_main_order'];
                $event_keep_like_banner_order = $start_list_rows['event_keep_like_banner_order'];
                $keep_yn = $start_list_rows['keep_yn'];
            
                //예약순번에 중복된 순번이 있나 확인
                $event_app_main_order_cehck = $this->ci->eventBanner->duplicateWebEventAppMainPopOrder($event_keep_app_main_order); 
                $event_like_banner_order_check = $this->ci->eventBanner->duplicateWebEventAppLikeBannerOrder($event_keep_like_banner_order);

                // 5. 게시 on 상태로 변경되기전 순번 정렬작업 및 히스토리 생성 작업
                if($event_app_main_order_cehck > 0){
                    $this->ci->eventBanner->incrementEventAppMainPopOrder($event_seq, $event_keep_app_main_order);
                }
                if($event_like_banner_order_check > 0){
                    $this->ci->eventBanner->incrementEventAppLikeBannerOrder($event_seq, $event_keep_like_banner_order);
                }
                // 6. 게시 on 업데이트
                // 예약일 경우 예약 메인 순번은 메인 순번으로, 예약 추천 순번은 추천 순번으로
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                    'keep_yn' => 'N',
                    'event_app_main_order' => $event_keep_app_main_order,
                    'event_keep_app_main_order' => null,
                    'event_like_banner_order' => $event_keep_like_banner_order,
                    'event_keep_like_banner_order' => null,
                    'event_on_off' => 1,
                    'on_off_update_id' => 'scheduler',
                    'on_off_update_name' => 'scheduler',
                    'on_off_update_time' => $now
                ], [
                    'event_seq' => $event_seq
                ]);

                // 7. 게시 on 히스토리 생성
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'method' => 'update',
                    'event_seq' => $event_seq,
                    'description' => 'scheduler에 의한 시작일시가 된 이벤트 ON 처리 수행',
                    'parameter' => [array(
                        'key' => 'event_on_off',
                        'value' => array(
                            'before' => '0',
                            'after' => '1'
                        )
                    )],
                    'create_id' => 'scheduler',
                    'create_time' => $now
                ]]);
            }

            // 8. 스케쥴러에서 메인 순번을 순서대로 맞춰준다. (main_order =null값 제외 조건추가, 한자릿수 우선순위 조건 추가)
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    event_seq,
                    event_app_main_order
                FROM
                    iparking_cms.board_event
                WHERE
                    event_on_off = 1
                AND
                    event_del_yn = 0
                AND
                    event_app_main_order is not null
                AND
                    event_app_main_image_yn = 1
                ORDER BY
                    length(event_app_main_order),
                    event_app_main_order ASC
            ');

            $stmt->execute();

            $main_sort_list = $stmt->fetchAll();

            $count = 1;

            foreach($main_sort_list as $main_sort_list_row){
                $main_sort_list_row_idx = $main_sort_list_row['event_seq'];
                $main_sort_list_row_order = $main_sort_list_row['event_app_main_order'];
                
                // 9. count 숫자와 메인 순번이 일치하지 않을경우 count 숫자로 맞춰준다.
                if($count != $main_sort_list_row_order){
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                        'event_app_main_order' => $count,
                        'update_id' => 'scheduler',
                        'update_name' => 'scheduler',
                        'update_time' => $now
                    ], [
                        'event_seq' => $main_sort_list_row_idx                        
                    ]);
                    
                    // 10. 메인순번 순차정렬 히스토리 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                        'method' => 'update',
                        'event_seq' => $main_sort_list_row_idx,
                        'description' => '스케줄러에 의한 메인팝업순번 순차정렬 처리 수행',
                        'parameter' => [array(
                            'key' => 'event_app_main_order',
                            'value' => array(
                                'before' => $main_sort_list_row_order,
                                'after' => $count
                            )
                        )],
                        'create_id' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
                $count++;
            }
                
            
            // 11. 스케쥴러에서 추천 순번을 순서대로 맞춰준다. 
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    event_seq,
                    event_like_banner_order
                FROM
                    iparking_cms.board_event
                WHERE
                    event_on_off = 1
                AND
                    event_del_yn = 0
                AND
                    event_like_banner_order is not null
                AND
                    event_like_banner_yn = 1
                ORDER BY
                    length(event_like_banner_order),
                    event_like_banner_order ASC
            ');

            $stmt->execute();

            $like_sort_list = $stmt->fetchAll();

            $count = 1;

            foreach($like_sort_list as $like_sort_list_row){
                $like_sort_list_row_idx = $like_sort_list_row['event_seq'];
                $like_sort_list_row_order = $like_sort_list_row['event_like_banner_order'];
                
                // 12. count 숫자와 서비스 순번이 일치하지 않을경우 count 숫자로 맞춰준다.
                if($count != $like_sort_list_row_order){
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                        'event_like_banner_order' => $count,
                        'update_id' => 'scheduler',
                        'update_name' => 'scheduler',
                        'update_time' => $now
                    ], [
                        'event_seq' => $like_sort_list_row_idx                        
                    ]);
                    
                    // 13. 서비스 순번 순차정렬 히스토리 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                        'method' => 'update',
                        'event_seq' => $like_sort_list_row_idx,
                        'description' => '스케줄러에 의한 추천배너순번 순차정렬 처리 수행',
                        'parameter' => [array(
                            'key' => 'event_like_banner_order',
                            'value' => array(
                                'before' => $like_sort_list_row_order,
                                'after' => $count
                            )
                        )],
                        'create_id' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
                $count++;
            }    

        
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            // $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getWebEventBannerOnOffUpdate($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $today = date('Y-m-d');
            $today_hour = date('Y-m-d H').':00:00';
            $now = date('Y-m-d H:i:s');

            // 1. 배너 종료일시가 된 배너 찾기
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    *
                FROM
                    iparking_cms.web_event_banner
                WHERE 
                    del_yn = 0
                AND 
                    web_event_banner_on_off = 1
                AND 
                    web_event_banner_end_date < :today_hour
            ');
            $stmt->execute(['today_hour' => $today_hour]);

            $exitList = $stmt->fetchAll();

            if(!empty($exitList)){
                foreach($exitList as $exitListRow){
                    $exit_idx = $exitListRow['web_event_banner_idx'];

                    // 2. 종료일시가 된 배너 off 처리 
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                        'web_event_banner_on_off' => 0,
                        'on_off_update_id' => 'scheduler',
                        'on_off_update_name' => 'scheduler',
                        'on_off_update_time' => $now
                    ], [
                        'web_event_banner_idx' =>$exit_idx
                    ]);

                    $parameter = [array(
                        'before' => '1',
                        'after' => '0'
                    )];

                    // 3. off처리한 이력 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'web_event_banner_idx' => $exit_idx,
                        'method' => 'update',
                        'description' => 'scheduler에 의한 종료일시가 된 배너 OFF 처리 수행',
                        'parameter' => $parameter,
                        'create_id' => 'scheduler',
                        'create_name' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
            }

            // 4.게시 ON 이며 배너 게시일시가 된 예약배너 리스트를 찾는다.
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    * 
                FROM 
                    iparking_cms.web_event_banner 
                WHERE 
                    web_event_banner_on_off = 1
                AND
                    keep_yn = :keep_yn 
                AND 
                    del_yn = 0
                AND 
                    web_event_banner_start_date = :today_hour
                ORDER BY update_time DESC
            ');
            $stmt->execute([
                'keep_yn' => 'Y', 
                'today_hour' => $today_hour
            ]);

            $start_list = $stmt->fetchAll();

            foreach($start_list as $start_list_rows) {
                $web_event_banner_idx = $start_list_rows['web_event_banner_idx'];
                $web_event_banner_keep_main_order = $start_list_rows['web_event_banner_keep_main_order'];
                $web_event_banner_keep_service_order = $start_list_rows['web_event_banner_keep_service_order'];
                $keep_yn = $start_list_rows['keep_yn'];

                // 중복된 순번이 있나 확인
                $web_event_banner_main_order_check = $this->ci->eventBanner->duplicateWebEventBannerMainOrder($web_event_banner_keep_main_order);
                $web_event_banner_service_order_check = $this->ci->eventBanner->duplicateWebEventBannerServiceOrder($web_event_banner_keep_service_order);

                // 5. 게시 on 상태로 변경되기전 순번 정렬작업 및 히스토리 생성 작업
                if($web_event_banner_main_order_check > 0) {
                    $this->ci->eventBanner->updateScheduleWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_keep_main_order);
                }
                if($web_event_banner_service_order_check > 0) {
                    $this->ci->eventBanner->updateScheduleWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_keep_service_order);
                }
                // 6. 게시 on 업데이트
                // 예약일 경우 예약 메인 순번은 메인 순번으로, 예약 서비스 순번은 서비스 순번으로 
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                    'keep_yn' => 'N',
                    'web_event_banner_main_order' => $web_event_banner_keep_main_order,
                    'web_event_banner_keep_main_order' => null,
                    'web_event_banner_service_order' => $web_event_banner_keep_service_order,
                    'web_event_banner_keep_service_order' => null,
                    'web_event_banner_on_off' => 1,
                    'on_off_update_id' => 'scheduler',
                    'on_off_update_name' => 'scheduler',
                    'on_off_update_time' => $now
                ], [
                    'web_event_banner_idx' => $web_event_banner_idx
                ]);

                //7. 게시 on 히스토리 생성
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'method' => 'update',
                    'web_event_banner_idx' => $web_event_banner_idx,
                    'description' => 'scheduler에 의한 시작일시가 된 배너 ON 처리 수행',
                    'parameter' => [array(
                        'key' => 'web_event_banner_on_off',
                        'value' => array(
                            'before' => '0',
                            'after' => '1'
                        )
                    )],
                    'create_id' => 'scheduler',
                    'create_time' => $now
                ]]);
            }

            // 8.스케쥴러에서 메인 순번을 순서대로 맞춰준다. (main_order =null값 제외 조건추가, 한자릿수 우선순위 조건 추가)
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    web_event_banner_idx,
                    web_event_banner_main_order
                FROM 
                    iparking_cms.web_event_banner 
                WHERE 
                    web_event_banner_on_off = 1
                AND 
                    del_yn = 0
                AND 
                    web_event_banner_main_order is not null
                ORDER BY 
                    length(web_event_banner_main_order),
                    web_event_banner_main_order ASC
            ');
            $stmt->execute();

            $main_sort_list = $stmt->fetchAll();

            $count = 1;
            foreach($main_sort_list as $main_sort_list_row) {
                $main_sort_list_row_idx = $main_sort_list_row['web_event_banner_idx'];
                $main_sort_list_row_order = $main_sort_list_row['web_event_banner_main_order'];

                // 9. count 숫자와 메인 순번이 일치하지 않을경우 count 숫자로 맞춰준다.
                if($count != $main_sort_list_row_order) {
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                        'web_event_banner_main_order' => $count,
                        'update_id' => 'scheduler',
                        'update_name' => 'scheduler',
                        'update_time' => $now
                    ], [
                        'web_event_banner_idx' => $main_sort_list_row_idx
                    ]);

                    // 10. 메인순번 순차정렬 히스토리 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'method' => 'update',
                        'web_event_banner_idx' => $main_sort_list_row_idx,
                        'description' => '스케줄러에 의한 메인순번 순차정렬 처리 수행',
                        'parameter' => [array(
                            'key' => 'web_event_banner_main_order',
                            'value' => array(
                                'before' => $main_sort_list_row_order,
                                'after' => $count
                            )
                        )],
                        'create_id' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
                $count++;
            }

            // 11. 스케쥴러에서 메인 순번을 순서대로 맞춰준다. (main_order =null값 제외 조건추가, 한자릿수 우선순위 조건 추가)
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    web_event_banner_idx,
                    web_event_banner_service_order
                FROM 
                    iparking_cms.web_event_banner 
                WHERE 
                    web_event_banner_on_off = 1
                AND 
                    del_yn = 0
                AND 
                    web_event_banner_service_order is not null
                ORDER BY 
                    length(web_event_banner_service_order),
                    web_event_banner_service_order ASC
            ');
            $stmt->execute();

            $service_sort_list = $stmt->fetchAll();
            $count = 1;
            foreach($service_sort_list as $service_sort_list_row) {
                $service_sort_list_row_idx = $service_sort_list_row['web_event_banner_idx'];
                $service_sort_list_row_order = $service_sort_list_row['web_event_banner_service_order'];
                // 12. count 숫자와 서비스 순번이 일치하지 않을경우 count 숫자로 맞춰준다.
                if($count != $service_sort_list_row_order) {
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                        'web_event_banner_service_order' => $count,
                        'update_id' => 'scheduler',
                        'update_name' => 'scheduler',
                        'update_time' => $now
                    ], [
                        'web_event_banner_idx' => $service_sort_list_row_idx
                    ]);

                    // 13. 서비스 순번 순차정렬 히스토리 생성
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'method' => 'update',
                        'web_event_banner_idx' => $service_sort_list_row_idx,
                        'description' => '스케줄러에 의한 서비스 순번 순차정렬 처리 수행',
                        'parameter' => [array(
                            'key' => 'web_event_banner_service_order',
                            'value' => array(
                                'before' => $service_sort_list_row_order,
                                'after' => $count
                            )
                        )],
                        'create_id' => 'scheduler',
                        'create_time' => $now
                    ]]);
                }
                $count++;
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            // $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}
