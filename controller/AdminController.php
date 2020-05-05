<?php

class AdminController
{

    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    /////////////////////////// 공지사항 ///////////////////////////
    // 공지사항 리스트
    public function getNoticeList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);

            $search_type = $params['search_type'];
            $search_content = $params['search_content'];
            $del_yn = $params['del_yn'] ?? '0';
            $on_off = $params['on_off'];
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'];
            $orderByColumn = $params['orderByColumn'];

            $where  = "";
            $binds = [];
            if(isset($search_type) && $search_type == "전체") {
                $where .= " AND ( notice_title like :notice_title OR notice_content like :notice_content )";
                $binds['notice_title'] = '%'.$search_content.'%';
                $binds['notice_content'] = '%'.$search_content.'%';
            } 
            else if (isset($search_type) && $search_type != "전체") {
                if($search_type == 'title') {
                    $where .= " AND notice_title like :notice_title ";
                    $binds['notice_title'] = '%'.$search_content.'%';
                } else if ($search_type == 'content') {
                    $where .= " AND notice_content like :notice_content ";
                    $binds['notice_content'] = '%'.$search_content.'%';
                }
            }

            if($del_yn != null) {
                $where .= " AND notice_del_yn = :del_yn ";
                $binds['del_yn'] = $del_yn;
            }
    
            if($on_off != null) {
                $where .= " AND notice_on_off = :on_off ";
                $binds['on_off'] = $on_off;
            }
    
            $orderByColumn = $this->ci->label->공지사항[$orderByColumn];
                    
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    notice_seq as seq,
                    notice_on_off as on_off,
                    notice_title as title,
                    notice_content as content,
                    notice_content_url as content_url,
                    notice_attachment as attachment,
                    notice_hit as hit,
                    notice_del_yn,
                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time
                ',
                // 'query' => '
                //     SELECT
                //         %%
                //     FROM 
                //         iparking_cms.board_notice 
                //    '

                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.board_notice 
                    WHERE 1=1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $orderByColumn.' '.$orderBy
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            // echo 'st where =';
            // return $where;
            // return $orderByColumn;
            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 공지사항 뷰
    public function getNoticeDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $notice_seq = $args['notice_seq'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    notice_seq as seq,
                    notice_on_off,
                    notice_title as title,
                    notice_content as content,
                    notice_content_url as content_url,
                    notice_attachment as attachment,
                    notice_hit as hit,
                    notice_del_yn,
                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time
                FROM 
                    iparking_cms.board_notice
                WHERE notice_seq = :notice_seq
            ');

            $stmt->execute(['notice_seq' => $notice_seq]);

            $data = $stmt->fetch();

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    *
                FROM 
                    iparking_cms.board_notice_history
                WHERE notice_seq = :notice_seq
            ');

            $stmt->execute(['notice_seq' => $notice_seq]);

            $history_result = $stmt->fetchAll();
            $data['update_history'] = $history_result;
            
            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = json_decode('{}');
            }

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 공지사항 등록
    public function postNoticeAdd($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $title = $params['title'];
            $content = $params['content'];
            $content_url = $params['content_url'];
            $attachment = $params['attachment'];
            
            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_notice', [[
                'notice_title' => $title,
                'notice_content' => $content,
                'notice_content_url' => $content_url,
                'notice_attachment' => $attachment,
                'create_id' => $user_id,
                'create_name' => $user_name,
                'create_time' => $now,
                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ]]);

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 공지사항 수정
    public function putNoticeDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $notice_seq = $args['notice_seq'];
            $title = $params['title'];
            $content = $params['content'];
            $content_url = $params['content_url'];
            $attachment = $params['attachment'];
            
            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_notice', [
                'notice_title' => $title,
                'notice_content' => $content,
                'notice_content_url' => $content_url,
                'notice_attachment' => $attachment,
                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ], [
                'notice_seq' => $notice_seq
            ]);

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_notice_history', [[
                'notice_seq' => $notice_seq,
                'method' => 'update',
                'description' => '공지사항 수정',
                'notice_title' => $title,
                'notice_content' => $content,
                'notice_content_url' => $content_url,
                'notice_attachment' => $attachment,
                'create_id' => $user_id,
                'create_name' => $user_name,
                'create_time' => $now,
                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ]]);

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    } 

    // 공지사항 삭제
    public function deleteNoticeDelete($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $notcie_seq = $params['seq'];  

            $where = "";
            $binds = [
                'delete_id' => $user_id,
                'delete_name' => $user_name,
                'delete_time' => $now
            ];

            if(!isset($notcie_seq) && empty($notcie_seq)) throw new ErrorException('파라메터가 잘못 되었습니다.');

            $stmt = $this->ci->iparkingCmsDb->prepare('
                UPDATE
                    iparking_cms.board_notice
                SET
                    notice_del_yn = 1,
                    delete_id = :delete_id,
                    delete_name = :delete_name,
                    delete_time = :delete_time
                WHERE 1=1 AND notice_seq IN ('.$this->ci->dbutil->arrayToInQuery($notcie_seq).')
            ');
            $stmt->execute($binds);

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);            

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 공지사항 게시 여부 업데이트
    public function patchNoticeOnOff($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $notice_seq = $args['notice_seq'];
            $on_off = $params['on_off'];

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_notice', [
                'notice_on_off' => $on_off,
                'on_off_update_id' => $user_id,
                'on_off_update_name' => $user_name,
                'on_off_update_time' => $now
            ], [
                'notice_seq' => $notice_seq
            ]);

            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_notice_history', [[
                'notice_seq' => $notice_seq,
                'method' => 'update',
                'description' => '공지사항 개시여부 수정',
                'notice_on_off' => $on_off,
                'create_id' => $user_id,
                'create_name' => $user_name,
                'create_time' => $now,
                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ]]);

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ///////////////////////////// 배너 /////////////////////////////
    // 배너 리스트
    public function getBannerList($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);

            $search_content = $params['search_content'];
            $del_yn = $params['del_yn'] ?? '0';
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'];
            $orderByColumn = $params['orderByColumn'];

            //초기 정렬조건= 게시on, 메인순번 null이 아닌경우, 메인순번 작은 자릿수, 메인순번, 서비스 순번 동일,최근 업데이트 일시
            if ($orderByColumn == null && $orderBy == null){
                $orderByQuery = 'web_event_banner_on_off DESC,
                                 web_event_banner_main_order is null ASC,
                                 length(web_event_banner_main_order),
                                 web_event_banner_main_order ASC,
                                 web_event_banner_service_order is null ASC,
                                 length(web_event_banner_service_order),
                                 web_event_banner_service_order ASC,
                                 update_time DESC';
            } else if ($orderByColumn == 'web_event_banner_main_order' && $orderBy == 'asc'){
                $orderByQuery = 'web_event_banner_on_off DESC,
                                 web_event_banner_main_order is null ASC,
                                 length(web_event_banner_main_order),
                                 web_event_banner_main_order ASC,
                                 web_event_banner_service_order is null ASC,
                                 length(web_event_banner_service_order),
                                 web_event_banner_service_order ASC,
                                 update_time DESC';
            } else if ($orderByColumn == 'web_event_banner_main_order' && $orderBy == 'desc'){
                $orderByQuery = 'web_event_banner_on_off DESC,
                                 web_event_banner_main_order is null ASC,
                                 length(web_event_banner_main_order) DESC,
                                 web_event_banner_main_order DESC,
                                 web_event_banner_service_order is null ASC,
                                 length(web_event_banner_service_order) DESC,
                                 web_event_banner_service_order DESC,
                                 update_time DESC';
            } else if ($orderByColumn == 'web_event_banner_service_order' && $orderBy == 'asc'){
                $orderByQuery = 'web_event_banner_on_off DESC,
                                 web_event_banner_service_order is null ASC,
                                 length(web_event_banner_service_order),
                                 web_event_banner_service_order ASC,
                                 web_event_banner_main_order is null ASC,
                                 length(web_event_banner_main_order),
                                 web_event_banner_main_order ASC,
                                 update_time DESC';
            } else if ($orderByColumn == 'web_event_banner_service_order' && $orderBy == 'desc'){
                $orderByQuery = 'web_event_banner_on_off DESC,
                                 web_event_banner_service_order is null ASC,
                                 length(web_event_banner_service_order) DESC,
                                 web_event_banner_service_order DESC,
                                 web_event_banner_main_order is null ASC,
                                 length(web_event_banner_main_order) DESC,
                                 web_event_banner_main_order DESC,
                                 update_time DESC';
            } else {
                $orderByQuery = $orderByColumn.' '.$orderBy;
            }
            $where = "";
            $binds = [];
            if($search_content != null) {
                $where .=" AND web_event_banner_title like :web_event_banner_title ";
                $binds['web_event_banner_title'] = '%'.$search_content.'%';
            } 

            if($del_yn != null) {
                $where .= " AND del_yn = :del_yn ";
                $binds['del_yn'] = $del_yn;
            }

            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    web_event_banner_idx,
                    web_event_banner_title,
                    web_event_banner_position,
                    web_event_banner_image,
                    web_event_banner_start_date,
                    web_event_banner_end_date,
                    web_event_banner_detail_type,
                    web_event_banner_detail_content,
                    web_event_banner_detail_html,
                    web_event_banner_detail_method,
                    web_event_banner_on_off,
                    web_event_banner_main_order,
                    web_event_banner_service_order,
                    web_event_banner_hit,
                    keep_yn,
                    web_event_banner_keep_main_order,
                    web_event_banner_keep_service_order,
                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time,
                    on_off_update_id,
                    on_off_update_name,
                    on_off_update_time,
                    del_yn
                ',
                'query' => '
                    SELECT
                        %%
                    FROM
                        iparking_cms.web_event_banner
                    WHERE 1=1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $orderByQuery
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }

            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e);
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getBannerMaxOrder($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                IFNULL( 
                    MAX(
                        CASE 
                            WHEN web_event_banner_position like "%web_event_banner_main_order%" THEN web_event_banner_main_order 
                            ELSE 0 
                        END
                        )
                    ,0
                ) AS web_event_banner_main_order,
                IFNULL(
                    MAX(
                        CASE 
                            WHEN web_event_banner_position like "%web_event_banner_service_order%" THEN web_event_banner_service_order 
                            ELSE 0 
                        END
                    )
                ,0) AS web_event_banner_service_order
                FROM
                    iparking_cms.web_event_banner
                WHERE 
                    del_yn = 0 AND web_event_banner_on_off = 1
            ');

            $stmt->execute();

            $result = $stmt->fetchAll();

            $data = [];
            if(!empty($result)) {

                foreach($result as $key => $rows) {
                    $web_event_banner_main_order = (int)$rows['web_event_banner_main_order'] ?? 0;
                    $web_event_banner_service_order = (int)$rows['web_event_banner_service_order'] ?? 0;

                    if($key == 'web_event_banner_main_order') {
                        array_push($data, array(
                            'type' => 'web_event_banner_main_order',
                            'title' => 'IPARKING MAIN',
                            'maxCount' => $web_event_banner_main_order
                        ));
                    }
                    if($key == 'web_event_banner_service_order') {
                        array_push($data, array(
                            'type' => 'web_event_banner_service_order',
                            'title' => '서비스 안내 메인',
                            'maxCount' => $web_event_banner_service_order
                        ));
                    }
                }
            }
            

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e);
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //배너 뷰
    public function getBannerDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $web_event_banner_idx = $args['web_event_banner_idx'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    web_event_banner_idx,
                    web_event_banner_title,
                    web_event_banner_position,
                    web_event_banner_image,
                    web_event_banner_start_date,
                    web_event_banner_end_date,
                    web_event_banner_detail_type,
                    web_event_banner_detail_content,
                    web_event_banner_detail_html,
                    web_event_banner_detail_method,
                    web_event_banner_on_off,
                    web_event_banner_main_order,
                    web_event_banner_service_order,
                    web_event_banner_hit,
                    keep_yn,
                    web_event_banner_keep_main_order,
                    web_event_banner_keep_service_order,
                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time,
                    on_off_update_id,
                    on_off_update_name,
                    on_off_update_time,
                    del_yn
                FROM
                    iparking_cms.web_event_banner
                WHERE web_event_banner_idx = :web_event_banner_idx
            ');

            $stmt->execute(['web_event_banner_idx' => $web_event_banner_idx]);

            $data = $stmt->fetch();

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    web_event_banner_idx,
                    method,
                    description,
                    parameter,
                    create_id as update_id,
                    create_name as update_name,
                    create_time as update_time
                FROM
                    iparking_cms.web_event_banner_history
                WHERE web_event_banner_idx = :web_event_banner_idx
            ');

            $stmt->execute(['web_event_banner_idx' => $web_event_banner_idx]);

            $history_result = $stmt ->fetchAll();

            $data['update_history'] = $history_result;

            if($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
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

    // 배너 등록
    public function postBannerAdd($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $web_event_banner_title = $params['web_event_banner_title'];
            $web_event_banner_position = $params['web_event_banner_position'];
            $web_event_banner_image = $params['web_event_banner_image'];
            $web_event_banner_start_date = $params['web_event_banner_start_date'];
            $web_event_banner_end_date = $params['web_event_banner_end_date'];
            $web_event_banner_detail_type = $params['web_event_banner_detail_type'];
            $web_event_banner_detail_content = $params['web_event_banner_detail_content'];
            $web_event_banner_detail_html = $params['web_event_banner_detail_html'];
            $web_event_banner_detail_method = $params['web_event_banner_detail_method'];
            
            $web_event_banner_main_order = $params['web_event_banner_main_order'];            
            $web_event_banner_service_order = $params['web_event_banner_service_order'];
            // 예약순번
            $keep_yn = $params['keep_yn']; // 예약일 경우 Y, 예약이 아닐 경우 N
            $web_event_banner_keep_main_order = $params['web_event_banner_keep_main_order'];
            $web_event_banner_keep_service_order = $params['web_event_banner_keep_service_order'];   

            //게시여부 프론트에서 받음
            $web_event_banner_on_off = $params['web_event_banner_on_off'];
            
            // 필수값 체크 로직 
            if($web_event_banner_title == null || $web_event_banner_title == '' ) throw new Exception ("타이틀을 입력하세요.");
            if(!$web_event_banner_start_date) throw new Exception ("배너 게시시작일을 입력하세요.");
            if($web_event_banner_on_off == null || $web_event_banner_on_off == '' ) throw new Exception ("배너 게시여부를 선택하세요.");
            
            // login user 가져오기
            $now = date('Y-m-d H:i:s');
            $now_hour = date('Y-m-d H');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            // 예약이 아닐경우
            if($keep_yn == 'N') {
                //새로등록한 메인순번이 입력됨에 따라 새로등록할 순번보다 크거나 같은 순번은 +1씩 업데이트
                if($web_event_banner_on_off == 1){
                    $stmt = $this->ci->iparkingCmsDb->prepare('
                        SELECT
                            IFNULL( 
                                MAX(
                                    CASE 
                                        WHEN web_event_banner_position like "%web_event_banner_main_order%" THEN web_event_banner_main_order 
                                        ELSE 0 
                                    END
                                    )
                                ,0 ) +1 
                            AS web_event_banner_main_order,
                            IFNULL(
                                MAX(
                                    CASE 
                                        WHEN web_event_banner_position like "%web_event_banner_service_order%" THEN web_event_banner_service_order 
                                        ELSE 0 
                                    END
                                    )
                                ,0) +1 
                            AS web_event_banner_service_order                      
                        FROM
                            iparking_cms.web_event_banner
                        WHERE del_yn = 0
                        AND web_event_banner_on_off = 1
                    ');
                    $stmt->execute();
                    $limitOrder = $stmt->fetch();

                    $limit_web_event_banner_main_order = $limitOrder['web_event_banner_main_order'];
                    $limit_web_event_banner_service_order = $limitOrder['web_event_banner_service_order'];
                    
                    if($web_event_banner_main_order > $limit_web_event_banner_main_order) $web_event_banner_main_order = $limit_web_event_banner_main_order;                      
                    if($web_event_banner_service_order > $limit_web_event_banner_service_order) $web_event_banner_service_order = $limit_web_event_banner_service_order;
                    
                    if($web_event_banner_main_order != null){
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
                        $update_main_order_list = [];
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
                                        $update_main_order_list[] = array(
                                            'web_event_banner_idx' => $target_idx,
                                            'web_event_banner_main_order' => $target_main_order
                                        );

                                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기 때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                                            'web_event_banner_main_order' => $target_main_order+1
                                        ], [
                                            'web_event_banner_idx' => $target_idx
                                        ]);
                                    }   
                                }  
                                $main_order_count++;
                            }
                        }
                    }
                    
                    //새로등록한 서비스순번이 입력됨에 따라 새로등록할 순번보다 크거나 같은 순번은 +1씩 업데이트
                    if($web_event_banner_service_order != null){
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
                        $update_service_order_list = [];
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
                                        $update_service_order_list[] = array(
                                            'web_event_banner_idx' => $target_idx,
                                            'web_event_banner_service_order' => $target_service_order
                                        );
                                    
                                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                                            'web_event_banner_service_order' => $target_service_order+1
                                        ], [
                                            'web_event_banner_idx' => $target_idx
                                        ]);
                                    }
                                }
                                $service_order_count++;
                            }
                        }
                    }
                }
            }
            
            //배너 등록
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner', [[
                'web_event_banner_title'          => $web_event_banner_title,      
                'web_event_banner_position'       => $web_event_banner_position,          
                'web_event_banner_image'          => $web_event_banner_image,      
                'web_event_banner_start_date'     => $web_event_banner_start_date,          
                'web_event_banner_end_date'       => $web_event_banner_end_date,          
                'web_event_banner_detail_type'    => $web_event_banner_detail_type,      
                'web_event_banner_detail_content' => $web_event_banner_detail_content,          
                'web_event_banner_detail_html'    => $web_event_banner_detail_html,          
                'web_event_banner_detail_method'  => $web_event_banner_detail_method,          
                'web_event_banner_on_off'         => $web_event_banner_on_off,      
                'web_event_banner_main_order'     => $web_event_banner_main_order,          
                'web_event_banner_service_order'  => $web_event_banner_service_order, 
                'web_event_banner_keep_main_order' => $web_event_banner_keep_main_order,
                'web_event_banner_keep_service_order' => $web_event_banner_keep_service_order,
                'keep_yn' => $keep_yn,
                'create_id' => $user_id, 
                'create_name' => $user_name,
                'create_time' => $now,
                'update_id' => $user_id, 
                'update_name' => $user_name,
                'update_time' => $now
            ]]);

            // 등록한 배너의 seq 셋팅
            $web_event_banner_idx = $this->ci->iparkingCmsDb->lastInsertId();

            // 등록된 순번으로 인해 +1 변경된 메인순번 히스토리 생성
            if(!empty($update_main_order_list)) {
                foreach($update_main_order_list as $update_main_order_list_rows){
                    $target_idx = $update_main_order_list_rows['web_event_banner_idx'];
                    $target_main_order = $update_main_order_list_rows['web_event_banner_main_order'];

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

            if(!empty($update_service_order_list)) {
                foreach($update_service_order_list as $update_service_order_list_rows){
                    $target_idx = $update_service_order_list_rows['web_event_banner_idx'];
                    $target_service_order = $update_service_order_list_rows['web_event_banner_service_order'];

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
            }

            //등록시에 등록한 게시물의 히스토리는 생성하지 않음

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e){
            $this->ci->logger->debug($e);
            return $response->withJson(['error'=>$e->getMessage()]);
        }
    }

    // 배너 수정
    public function putBannerDetail($request, $response, $args)
    {
        try {
            $params = $this->ci->util->getParams($request);

            $web_event_banner_idx = $args['web_event_banner_idx'];

            $web_event_banner_title                 = $params['web_event_banner_title'];
            $web_event_banner_position_before       = $params['web_event_banner_position_before'];
            $web_event_banner_position_after        = $params['web_event_banner_position_after'];
            $web_event_banner_image                 = $params['web_event_banner_image'];
            $web_event_banner_start_date            = $params['web_event_banner_start_date'];
            $web_event_banner_end_date              = $params['web_event_banner_end_date'];
            $web_event_banner_detail_type           = $params['web_event_banner_detail_type'];
            $web_event_banner_detail_content        = $params['web_event_banner_detail_content'];
            $web_event_banner_detail_html           = $params['web_event_banner_detail_html'];
            $web_event_banner_detail_method         = $params['web_event_banner_detail_method'];
            $web_event_banner_on_off_before         = $params['web_event_banner_on_off_before'];
            $web_event_banner_on_off_after          = $params['web_event_banner_on_off_after'];

            // 이전 이후 순서값 받기
            $web_event_banner_main_order_before     = $params['web_event_banner_main_order_before']; 
            $web_event_banner_main_order_after      = $params['web_event_banner_main_order_after'];
            $web_event_banner_service_order_before  = $params['web_event_banner_service_order_before']; 
            $web_event_banner_service_order_after   = $params['web_event_banner_service_order_after']; 

            // 예약 순번
            $keep_yn = $params['keep_yn']; // 예약일 경우 Y, 예약이 아닐 경우 N
            $keep_before_yn = $params['keep_before_yn'];
            $keep_after_yn = $params['keep_after_yn'];
            $web_event_banner_keep_main_order_before = $params['web_event_banner_keep_main_order_before']; 
            $web_event_banner_keep_main_order_after = $params['web_event_banner_keep_main_order_after'];
            $web_event_banner_keep_service_order_before = $params['web_event_banner_keep_service_order_before'];   
            $web_event_banner_keep_service_order_after = $params['web_event_banner_keep_service_order_after'];    

            $now = date('Y-m-d H:i:s');
            $now_hour = date('Y-m-d H');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            // 게시하지 않던 포지션에 등록할경우 등록과 같이 맥스+1 이 마지막 순번이 된다.
            $positionMainNew = 0;
            $positionServiceNew = 0;

            // 이후에 게시하지 않는다면 맥스 체크를 할 필요가 없다.
            if($web_event_banner_position_after != null || $web_event_banner_position_after != '' || $web_event_banner_position_before != $web_event_banner_position_after){

                $positionBeforeDataArr = $this->ci->eventBanner->getBannerPositionVal($web_event_banner_position_before);
                $positionBeforeMain = $positionBeforeDataArr['main'];
                $positionBeforeService = $positionBeforeDataArr['service'];
                
                $positionAfterDataArr = $this->ci->eventBanner->getBannerPositionVal($web_event_banner_position_after);
                $positionAfterMain = $positionAfterDataArr['main'];
                $positionAfterService = $positionAfterDataArr['service'];

                if($positionBeforeMain == 0 && $positionAfterMain == 1) {
                    $positionMainNew = 1;
                } else if ($positionBeforeMain == 1 && $positionAfterMain == 1) {
                    $positionMainNew = 1;
                }
                if($positionBeforeService == 0 && $positionAfterService == 1) {
                    $positionServiceNew = 1;
                } else if($positionBeforeService == 1 && $positionAfterService == 1) {
                    $positionServiceNew = 1;
                }
                
            }


            // order 값 맥스 체크
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    IFNULL( 
                        MAX(
                            CASE 
                                WHEN web_event_banner_position like "%web_event_banner_main_order%" THEN web_event_banner_main_order 
                                ELSE 0 
                            END
                            )
                        ,0 ) 
                    AS web_event_banner_main_order,
                    IFNULL(
                        MAX(
                            CASE 
                                WHEN web_event_banner_position like "%web_event_banner_service_order%" THEN web_event_banner_service_order 
                                ELSE 0 
                            END
                        )
                    ,0)  
                    AS web_event_banner_service_order
                
                FROM
                    iparking_cms.web_event_banner
                WHERE del_yn = 0
                AND web_event_banner_on_off = 1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();

            $limit_web_event_banner_main_order = $limitOrder['web_event_banner_main_order'];
            $limit_web_event_banner_service_order = $limitOrder['web_event_banner_service_order'];

            //메인 게시유무 off -> on
            if($positionBeforeMain == 0 && $positionAfterMain == 1) {
                $limit_web_event_banner_main_order_keepN = $limit_web_event_banner_main_order + 1;
            } 
            // 게시유무 off -> on
            else if ($web_event_banner_on_off_before == 0 && $web_event_banner_on_off_after==1){
                $limit_web_event_banner_main_order_keepN = $limit_web_event_banner_main_order + 1;
            //예약이었다가 게시상태로 변경시 맥스값 +1 
            } else if ($keep_before_yn == "Y" && $keep_after_yn =="N"){
                $limit_web_event_banner_main_order_keepN = $limit_web_event_banner_main_order + 1;
            } else {
                $limit_web_event_banner_main_order_keepN = $limit_web_event_banner_main_order;
            }

            if($positionBeforeService == 0 && $positionAfterService == 1) {
                $limit_web_event_banner_service_order_keepN = $limit_web_event_banner_service_order +1;
            } else if ($web_event_banner_on_off_before == 0 && $web_event_banner_on_off_after==1){
                $limit_web_event_banner_service_order_keepN = $limit_web_event_banner_service_order +1;
            } else if ($keep_before_yn == "Y" && $keep_after_yn =="N"){
                $limit_web_event_banner_service_order_keepN = $limit_web_event_banner_service_order +1;
            } else {
                $limit_web_event_banner_service_order_keepN = $limit_web_event_banner_service_order;
            }



            if($keep_after_yn == 'N'){
                if($web_event_banner_main_order_after > $limit_web_event_banner_main_order_keepN) throw new Exception ("현재 등록가능한 IPARKING MAIN의 마지막 순번값은 ".$limit_web_event_banner_main_order_keepN."입니다. 순번값을 확인해주세요.");                       
                if($web_event_banner_service_order_after > $limit_web_event_banner_service_order_keepN) throw new Exception ("현재 등록가능한 서비스 안내 메인의 마지막 순번값은 ".$limit_web_event_banner_service_order_keepN."입니다. 순번값을 확인해주세요.");                 
            }

            // 예약순번 맥스 체크
            if($keep_after_yn == 'Y'){
                $limit_web_event_banner_main_order = $limit_web_event_banner_main_order+1;
                $limit_web_event_banner_service_order = $limit_web_event_banner_service_order+1;

                if($web_event_banner_keep_main_order_after > $limit_web_event_banner_main_order) throw new Exception ("현재 등록가능한 IPARKING MAIN의 마지막 예약순번값은 ".$limit_web_event_banner_main_order."입니다. 순번값을 확인해주세요.");                       
                if($web_event_banner_keep_service_order_after > $limit_web_event_banner_service_order) throw new Exception ("현재 등록가능한 서비스 안내 메인의 마지막 예약순번값은 ".$limit_web_event_banner_service_order."입니다. 순번값을 확인해주세요.");                 
            }


            /*
            * 정렬 로직
            */

            // 1. 게시 ON -> 게시 OFF
            if($web_event_banner_on_off_before == 1 && $web_event_banner_on_off_after == 0 ){

                // 예약off -> 예약on  뒷순번 -1
                if($keep_before_yn == 'N' && $keep_after_yn == 'Y'){
                    //메인 게시 on -> off 이전메인순번의 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 0 ){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    //메인 게시 on -> on  이전메인순번의 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1 ){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    //메인 게시 off->off X
                    //메인 게시 off->on X
                    //서비스 게시 on -> off 이전 서비스 순번의 뒷순번 -1
                    if( $positionBeforeService == 1 && $positionAfterService == 0 ){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 on -> on 뒷순번 -1
                    if( $positionBeforeService == 1 && $positionAfterService == 1 ){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 off->off
                    //서비스 게시 off->on
                    
                    
                }
                // 예약off -> 예약off 뒷순번 - 1
                if($keep_before_yn == 'N' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off 이전메인순번의 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 0 ){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    //메인 게시 on -> on 이전메인순번의 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1 ){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    //메인 게시 off->off X
                    //메인 게시 off->on X
                    //서비스 게시 on -> off 이전서비스순번의 뒷순번 -1
                    if( $positionBeforeService == 1 && $positionAfterService == 0 ){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 on -> on 이전서비스순번의 뒷순번 -1
                    if( $positionBeforeService == 1 && $positionAfterService == 1 ){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 off->off X
                    //서비스 게시 off->on X
                }
            }

            // 2. 게시 ON -> 게시 ON
            if($web_event_banner_on_off_before == 1 && $web_event_banner_on_off_after == 1 ){
                // 예약on -> 예약off 같거나 큰순번 +1
                if($keep_before_yn == 'Y' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off X
                    //메인 게시 on -> on 이후 메인순번 같거나 큰순번 +1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1 ){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }
                    //메인 게시 off->off X
                    //메인 게시 off->on 이후 메인순번 같거나 큰순번 +1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1 ){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }
                    //서비스 게시 on -> off X
                    //서비스 게시 on -> on
                    if( $positionBeforeService == 1 && $positionAfterService == 1 ){
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                    //서비스 게시 off->off X
                    //서비스 게시 off->on
                    if( $positionBeforeService == 1 && $positionAfterService == 1 ){
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                }
                // 예약off -> 예약off 크기비교로직 수행
                if($keep_before_yn == 'N' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off 이전메인순번 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 0){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    //메인 게시 on -> on 크기비교 로직
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1){
                        if($web_event_banner_main_order_before == null) throw new Exception ("이전 메인배너 순번 값이 없습니다."); 
                        if($web_event_banner_main_order_after == null) throw new Exception ("이후 메인배너 순번 값이 없습니다."); 
                        // 이전 값이 이후 순번 보다 작을 경우 ex ) 3 -> 5
                        if($web_event_banner_main_order_before < $web_event_banner_main_order_after) {
                            $this->ci->eventBanner->decrementWebEventBannerMainOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_main_order_before ,$web_event_banner_main_order_after);                              
                        }
                        // 이전 값이 이후 순번 보다 클 경우  ex) 5 -> 3 
                        else if($web_event_banner_main_order_before > $web_event_banner_main_order_after) { 
                            $this->ci->eventBanner->incrementWebEventBannerMainOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_main_order_before ,$web_event_banner_main_order_after);                              
                        }
                    }
                    //메인 게시 off->off X
                    //메인 게시 off->on 이후 메인순번 같거나 큰순번 +1
                    if( $positionBeforeMain == 0 && $positionAfterMain == 1){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }

                    //서비스 게시 on -> off
                    if( $positionBeforeService == 1 && $positionAfterService == 0 ){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 on -> on
                    if( $positionBeforeService == 1 && $positionAfterService == 1 ){
                        if($web_event_banner_service_order_before == null) throw new Exception ("이전 서비스배너 순번 값이 없습니다."); 
                        if($web_event_banner_service_order_after == null) throw new Exception ("이후 서비스배너 순번 값이 없습니다."); 
                        // 이전 값이 바뀔 순번 보다 작을 경우 ex ) 3 -> 5
                        if($web_event_banner_service_order_before < $web_event_banner_service_order_after) {
                            $this->ci->eventBanner->decrementWebEventBannerServiceOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_service_order_before ,$web_event_banner_service_order_after);                    
                        }
                        // 이전 값이 바뀔 순번 보다 클 경우  ex) 5 -> 3 
                        else if($web_event_banner_service_order_before > $web_event_banner_service_order_after) {
                            $this->ci->eventBanner->incrementWebEventBannerServiceOrderBetweenFrontAndBack($web_event_banner_idx, $web_event_banner_service_order_before ,$web_event_banner_service_order_after);
                        }    
                    }
                    //서비스 게시 off->off X
                    //서비스 게시 off->on 이후 서비스 순번 보다 같거나 큰순번 +1
                    if( $positionBeforeService == 0 && $positionAfterService == 1 ){
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                }
                // 예약off -> 예약on 뒷순번-1
                if($keep_before_yn == 'N' && $keep_after_yn == 'Y'){
                    //메인 게시 on -> off 이전 메인순번 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 0){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    
                    //메인 게시 on -> on 이전 메인순번 뒷순번 -1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1){
                        $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_before);
                    }
                    
                    //메인 게시 off->off X
                    //메인 게시 off->on X

                    
                    //서비스 게시 on -> off
                    if( $positionBeforeService == 1 && $positionBeforeService == 0){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 on -> on
                    if( $positionBeforeService == 1 && $positionBeforeService == 1){
                        $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_before);
                    }
                    //서비스 게시 off->off X
                    //서비스 게시 off->on X
                }
            }

            // 3. 게시여부가 OFF -> ON
            if($web_event_banner_on_off_before == 0 && $web_event_banner_on_off_after == 1 ){

                // 예약on -> 예약off 같거나 큰순번 + 1
                if($keep_before_yn == 'Y' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off X
                    //메인 게시 on -> on 이후값 보다 같거나 큰 값 +1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }
                    
                    //메인 게시 off->off X
                    //메인 게시 off->on 이후 메인순번 같거나 큰 순번 +1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }

                    //서비스 게시 on -> off X
                    //서비스 게시 on -> on
                    if( $positionBeforeService == 1 && $positionAfterService == 1){
                        //이후값 보다 같거나 큰 값 +1
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                    
                    //서비스 게시 off->off X
                    //서비스 게시 off->on
                    if( $positionBeforeService == 0 && $positionAfterService == 1){
                        //이후값 보다 같거나 큰 값 +1
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                }

                // 예약off -> 예약off 
                if($keep_before_yn == 'N' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off
                    //메인 게시 on -> on 이후 메인순번 같거나 큰 순번 +1
                    if( $positionBeforeMain == 1 && $positionAfterMain == 1){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }

                    //메인 게시 off->off X
                    //메인 게시 off->on 이후 메인순번 같거나 큰 순번 +1
                    if( $positionBeforeMain == 0 && $positionAfterMain == 1){
                        $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $web_event_banner_main_order_after);
                    }                   
                    //서비스 게시 on -> off X
                    //서비스 게시 on -> on 이후 서비스순번 같거나 큰 순번 +1
                    if( $positionBeforeService == 1 && $positionAfterService == 1){
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                    
                    //서비스 게시 off->off X
                    //서비스 게시 off->on 이후 서비스순번 같거나 큰 순번 +1
                    if( $positionBeforeService == 0 && $positionAfterService == 1){
                        $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $web_event_banner_service_order_after);
                    }
                }
                
            }


            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                'web_event_banner_title'          => $web_event_banner_title,      
                'web_event_banner_position'       => $web_event_banner_position_after,          
                'web_event_banner_image'          => $web_event_banner_image,      
                'web_event_banner_start_date'     => $web_event_banner_start_date,          
                'web_event_banner_end_date'       => $web_event_banner_end_date,          
                'web_event_banner_detail_type'    => $web_event_banner_detail_type,      
                'web_event_banner_detail_content' => $web_event_banner_detail_content,          
                'web_event_banner_detail_method'  => $web_event_banner_detail_method,
                'web_event_banner_detail_html'    => $web_event_banner_detail_html,          
                'web_event_banner_on_off'         => $web_event_banner_on_off_after,      
                'web_event_banner_main_order'     => $web_event_banner_main_order_after,          
                'web_event_banner_service_order'  => $web_event_banner_service_order_after, 
                'keep_yn' => $keep_after_yn,
                'web_event_banner_keep_main_order' => $web_event_banner_keep_main_order_after,
                'web_event_banner_keep_service_order' => $web_event_banner_keep_service_order_after,
                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ], [ 
                'web_event_banner_idx' => $web_event_banner_idx
            ]);
            
            $update_arr = $params['update_arr'];
            if(!empty($update_arr)) {
                if(gettype($update_arr) == 'string') {
                    $update_arr = json_decode($update_arr, true);
                }
                foreach($update_arr as $update_arr_rows){
                    $description = $update_arr_rows['key'];
                    $parameter = $update_arr_rows['value'];

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'web_event_banner_idx' => $web_event_banner_idx,
                        'method' => 'update',
                        'description' => $description.' 수정',      
                        'parameter' => $parameter,          
                        'create_id' => $user_id, 
                        'create_name' => $user_name,
                        'create_time' => $now
                    ]]);
                }
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);
            
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e){
            $this->ci->logger->debug($e);
            return $response->withJson(['error'=>$e->getMessage()]);
        }
    }

    //배너 삭제
    public function deleteBanner($request, $response, $args)
    {
        try{
            $params = $this->ci->util->getParams($request);

            $web_event_banner_idx = $params['web_event_banner_idx'];

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');
            
            if(!isset($web_event_banner_idx) && empty($web_event_banner_idx)) throw new ErrorException('삭제순번 파라메터가 잘못 되었습니다.');

            // 기삭제된 배너의 순번을 다시계산하지 않도록 삭제처리가 안된 배너인지 확인
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    web_event_banner_idx,
                    web_event_banner_main_order,
                    web_event_banner_service_order,
                    web_event_banner_on_off,
                    keep_yn
                FROM
                    iparking_cms.web_event_banner
                WHERE del_yn = 0
                AND web_event_banner_idx IN ('.$this->ci->dbutil->arrayToInQuery($web_event_banner_idx).')
            ');
            $stmt->execute();
            $deleteList = $stmt->fetchAll();

            if(!empty($deleteList)){
                foreach($deleteList as $deleteListRow){
                    $target_idx = $deleteListRow['web_event_banner_idx'];
                    $web_event_banner_main_order = $deleteListRow['web_event_banner_main_order'];
                    $web_event_banner_service_order = $deleteListRow['web_event_banner_service_order'];             
                    $keep_yn = $deleteListRow['keep_yn'];
                    $web_event_banner_on_off = $deleteListRow['web_event_banner_on_off'];
                    
                    // 예약이지 않고 게시여부가 1인 경우에만 순번에 영향을 주어야한다.
                    // 메인과 서비스에 게시하지않으면 순번은 널이다.
                    if($keep_yn == 'N' && $web_event_banner_on_off == 1) {
                        if($web_event_banner_main_order != null) {
                            $this->ci->eventBanner->decrementWebEventBannerMainOrder($target_idx, $web_event_banner_main_order);
                        }
                        if($web_event_banner_service_order != null) {
                            $this->ci->eventBanner->decrementWebEventBannerServiceOrder($target_idx, $web_event_banner_service_order);
                        }
                    } 
                    
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                        'del_yn' => 1,
                        'delete_id' => $user_id,
                        'delete_name' => $user_name,
                        'delete_time' => $now
                    ], [
                        'web_event_banner_idx' => $target_idx
                    ]);

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                        'web_event_banner_idx' => $target_idx,
                        'method' => 'delete',
                        'description' => $target_idx.' 번 배너 삭제',
                        'parameter' => null,
                        'create_id' => $user_id,
                        'create_name' => $user_name,
                        'create_time' => $now
                    ]]);
                    
                }
            }
            
            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //배너 게시순서 저장
    public function postBannerSetOrder($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $orderList = $params['orderList'];

            $main_order_dupl_check = array();
            $service_order_dupl_check = array();
            
            foreach($orderList as $orderListRow){
                if($orderListRow['web_event_banner_main_order_after'] != '' || $orderListRow['web_event_banner_main_order_after'] != null)
                    array_push($main_order_dupl_check, $orderListRow['web_event_banner_main_order_after']);
                if($orderListRow['web_event_banner_service_order_after'] != '' || $orderListRow['web_event_banner_service_order_after'] != null)
                    array_push($service_order_dupl_check, $orderListRow['web_event_banner_service_order_after']);
            }

            // 순서값이 정렬되어있다는 보장이 없기때문에 sort함수를 이용하여 올림차순 정렬을 수행한다. 
            sort($main_order_dupl_check);
            sort($service_order_dupl_check);

            // 중복체크 수행 후 빠진순번 체크
            // 메인순번에 중복된 값을 제외하고 원본과 카운터 비교
            if ( count($main_order_dupl_check) != count(array_unique($main_order_dupl_check)) )
                throw new Exception ("변경요청한 메인순번에 중복된 순번 값이 존재합니다.");

            // 서비스순번에 중복된 값을 제외하고 원본과 카운터 비교
            if ( count($service_order_dupl_check) != count(array_unique($service_order_dupl_check)) )
                throw new Exception ("변경요청한 서비스순번에 중복된 순번 값이 존재합니다.");

            // 빠진순번 체크
            $check_val_for_main = 1;
            $check_val_for_service = 1;
            
            if(!empty($main_order_dupl_check)){
                foreach($main_order_dupl_check as $main_order_dupl_check_row => $main_order_dupl_check_row_val){
                    if($check_val_for_main != $main_order_dupl_check_row_val) 
                        throw new Exception ('메인순번에 '.$check_val_for_main."번 순번이 존재하지 않습니다. 확인 후 다시 저장해주세요.");
                    $check_val_for_main++;
                }
            }
            
            if(!empty($service_order_dupl_check)){
                foreach($service_order_dupl_check as $service_order_dupl_check_row => $service_order_dupl_check_row_val){
                    if($check_val_for_service != $service_order_dupl_check_row_val) 
                    throw new Exception ('서비스순번에 '.$check_val_for_service."번 순번이 존재하지 않습니다. 확인 후 다시 저장해주세요.");
                    $check_val_for_service++;
                }
            }

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            foreach($orderList as $orderListRow){
                $target_idx = $orderListRow['web_event_banner_idx'];
                $target_main_order_after =  $orderListRow['web_event_banner_main_order_after'];
                $target_main_order_before =  $orderListRow['web_event_banner_main_order_before'];
                $target_service_order_after = $orderListRow['web_event_banner_service_order_after'];
                $target_service_order_before = $orderListRow['web_event_banner_service_order_before'];

                if($orderListRow['web_event_banner_main_order_after'] != '' || $orderListRow['web_event_banner_main_order_after'] != null)
                {
                    if( $target_main_order_before != $target_main_order_after ){
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                            'web_event_banner_main_order' => $target_main_order_after,
                            'update_id' => $user_id,
                            'update_name' => $user_name,
                            'update_time' => $now
                        ], [
                            'web_event_banner_idx' => $target_idx
                        ]);
    
                        $parameter = [array(
                            'before' => $target_main_order_before,
                            'after' => $target_main_order_after
                        )];
        
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                            'web_event_banner_idx' => $target_idx,
                            'method' => 'update',
                            'description' => $target_idx.' 번 배너 메인 게시순번 수정',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }
                }
                
                if($orderListRow['web_event_banner_service_order_after'] != '' || $orderListRow['web_event_banner_service_order_after'] != null)
                {
                    if( $target_service_order_before != $target_service_order_after ){
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                            'web_event_banner_service_order' => $target_service_order_after,
                            'update_id' => $user_id,
                            'update_name' => $user_name,
                            'update_time' => $now
                        ], [
                            'web_event_banner_idx' => $target_idx
                        ]);
    
                        $parameter = [array(
                            'before' => $target_service_order_before,
                            'after' => $target_service_order_after
                        )];
        
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                            'web_event_banner_idx' => $target_idx,
                            'method' => 'update',
                            'description' => $target_idx.' 번 배너 서비스 게시순번 수정',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }
                }
            }    

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //배너 게시 on/off
    public function patchBannerOnoff($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $web_event_banner_idx = $args['web_event_banner_idx'];
            $web_event_banner_on_off = (int)$params['web_event_banner_on_off'];

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT
                    web_event_banner_idx,
                    web_event_banner_on_off,
                    web_event_banner_main_order,
                    web_event_banner_service_order,
                    keep_yn,
                    web_event_banner_start_date,
                    web_event_banner_end_date,
                    web_event_banner_position
                FROM
                    iparking_cms.web_event_banner
                WHERE del_yn = 0
                AND web_event_banner_idx = :web_event_banner_idx
             ');

            $stmt->execute(['web_event_banner_idx' =>$web_event_banner_idx]);

            $data = $stmt->fetch();

            $keep_yn = $data['keep_yn'];

            $positionData =$data['web_event_banner_position'];

            $positionDataArr = $this->ci->eventBanner->getBannerPositionVal($positionData);

            $positionMain = $positionDataArr['main'];
            $positionService = $positionDataArr['service'];;
            
            if($web_event_banner_on_off == $data['web_event_banner_on_off'] && $web_event_banner_on_off == 1) throw new Exception ("배너의 게시상태가 이미 ON 입니다.");
            if($web_event_banner_on_off == $data['web_event_banner_on_off'] && $web_event_banner_on_off == 0) throw new Exception ("배너의 게시상태가 이미 OFF 입니다.");
            

            //게시 off로 수정 시 갖고있던 순번의 뒷 순번들은 -1 처리
            if($web_event_banner_on_off == 0){
                // 예약이 아닌경우에만 
                if($keep_yn == 'N') {
                    // 메인 게시여부 확인
                    if($positionMain == 1){
                        if($data['web_event_banner_main_order'] != null){
                            $this->ci->eventBanner->decrementWebEventBannerMainOrder($web_event_banner_idx, $data['web_event_banner_main_order']);
                        }
                    }
                    // 서비스 게시여부 확인
                    if($positionService == 1){
                        if($data['web_event_banner_service_order'] != null){
                            $this->ci->eventBanner->decrementWebEventBannerServiceOrder($web_event_banner_idx, $data['web_event_banner_service_order']);
                        }
                    }
                }
                
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', [
                    'web_event_banner_on_off' => 0,
                    'update_id' => $user_id,
                    'update_name' => $user_name,
                    'update_time' => $now
                ], [ 
                    'web_event_banner_idx' => $web_event_banner_idx
                ]);

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $web_event_banner_idx,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 게시 OFF',
                    'parameter' => null,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }

            // 게시 on 처리
            if($web_event_banner_on_off == 1){
                
                if($data['web_event_banner_end_date']<= $now ) throw new Exception ("게시 종료일이 지난 배너는 게시상태를 변경할 수 없습니다.");

                // 기존 셋팅
                $binds = [
                    'web_event_banner_on_off' => 1,
                    'update_id' => $user_id,
                    'update_name' => $user_name,
                    'update_time' => $now
                ];

                if($keep_yn == 'N') {
                    // 메인 게시여부 확인
                    if($positionMain == 1){
                        // 게시 on 요청한 배너 메인순번부터 마지막 메인순번까지 +1
                        if($data['web_event_banner_main_order'] != null){
                            $binds['web_event_banner_main_order'] = $data['web_event_banner_main_order'];
                            $this->ci->eventBanner->incrementWebEventBannerMainOrder($web_event_banner_idx, $data['web_event_banner_main_order']);
                        }
                    }
                    if($positionService == 1){
                        if($data['web_event_banner_service_order'] != null){
                            $binds['web_event_banner_service_order'] = $data['web_event_banner_service_order'];
                            $this->ci->eventBanner->incrementWebEventBannerServiceOrder($web_event_banner_idx, $data['web_event_banner_service_order']);
                        }
                    }
                }       

                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner', $binds, [ 
                    'web_event_banner_idx' => $web_event_banner_idx
                ]);

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.web_event_banner_history', [[
                    'web_event_banner_idx' => $web_event_banner_idx,
                    'method' => 'update',
                    'description' => $web_event_banner_idx.' 번 배너 게시 ON',
                    'parameter' => null,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
                
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ///////////////////////////// 이벤트 /////////////////////////////
    // 이벤트 리스트
    public function getEventList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);
            
            $add_type = $params['add_type'];
            $search_type = $params['search_type'];
            $search_content = $params['search_content'];
            $on_off = $params['on_off'];
            $del_yn = $params['del_yn'] ?? '0';
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'];
            $orderByColumn = $params['orderByColumn'];
             
            $where = "";
            $binds = [];

            if(isset($add_type) && $add_type != '전체') {
                $where .= " AND event_add_type = :add_type ";
                $binds['add_type'] = $add_type;
            }

            if(isset($search_type) && $search_type == "전체") {
                $where .= " AND ( event_title like :event_title OR event_content like :event_content )";
                $binds['event_title'] = '%'.$search_content.'%';
                $binds['event_content'] = '%'.$search_content.'%';
            } 
            else if (isset($search_type) && $search_type != "전체") {
                if($search_type == 'title') {
                    $where .= " AND event_title like :event_title ";
                    $binds['event_title'] = '%'.$search_content.'%';
                } else if ($search_type == 'content') {
                    $where .= " AND event_content like :event_content ";
                    $binds['event_content'] = '%'.$search_content.'%';
                }
            }

            if($del_yn != null) {
                $where .= " AND event_del_yn = :del_yn ";
                $binds['del_yn'] = $del_yn;
            }

            if($on_off != null) {
                $where .= " AND event_on_off = :on_off ";
                $binds['on_off'] = $on_off;
            }

            $orderByColumn = $this->ci->label->이벤트[$orderByColumn];

            $queryOrderBy = $orderByColumn.' '.$orderBy;

            if($orderByColumn == null && $orderBy == null){
                $queryOrderBy = 'event_on_off DESC,
                                event_app_main_order is null ASC,
                                length(event_app_main_order),
                                event_app_main_order ASC,
                                event_like_banner_order is null ASC,
                                length(event_like_banner_order),
                                event_like_banner_order ASC,
                                update_time DESC';
            } else if($orderByColumn == 'event_app_main_order' && $orderBy == 'asc'){
                $queryOrderBy = 'event_on_off DESC,
                                event_app_main_order is null ASC,
                                length(event_app_main_order),
                                event_app_main_order ASC,
                                event_like_banner_order is null ASC,
                                length(event_like_banner_order),
                                event_like_banner_order ASC,
                                update_time DESC';
            } else if($orderByColumn == 'event_like_banner_order' && $orderBy == 'asc'){
                $queryOrderBy = 'event_on_off DESC,
                                event_like_banner_order is null ASC,
                                length(event_like_banner_order),
                                event_like_banner_order ASC,
                                event_app_main_order is null ASC,
                                length(event_app_main_order),
                                event_app_main_order ASC,
                                update_time DESC';
            }

            // 리스트 리턴시 순번, 예약순번, 예약여부, 데이트타임 추가
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    event_seq as seq,
                    event_add_type as add_type,
                    event_title as title,
                    event_content as content,
                    event_content_url as content_url,
                    event_thumbnail as thumbnail,
                    event_start_date as start_date,
                    event_end_date as end_date,
                    event_app_main_image_yn as app_main_image_yn,
                    event_app_main_image as app_main_image,
                    event_like_banner_yn as like_banner_yn,
                    event_like_banner as like_banner,
                    event_hit as hit,
                    event_on_off as on_off,
                    event_del_yn as del_yn,
                    event_order_no as order_no,

                    event_app_main_order, 
                    event_like_banner_order,
                    event_start_datetime,
                    event_end_datetime,

                    event_keep_app_main_order,
                    event_keep_like_banner_order,
                    keep_yn,

                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.board_event 
                    WHERE 1=1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $queryOrderBy
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);


        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 뷰
    public function getEventDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $event_seq = $args['event_seq'];

            // 상세 리턴시 순번, 예약순번, 예약여부, 데이트타임 추가
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    event_seq as seq,
                    event_add_type as add_type,
                    event_title as title,
                    event_content as content,
                    event_content_url as content_url,
                    event_thumbnail as thumbnail,
                    event_start_date as start_date,
                    event_end_date as end_date,
                    event_app_main_image_yn as app_main_image_yn,
                    event_app_main_image as app_main_image,
                    event_like_banner_yn as like_banner_yn,
                    event_like_banner as like_banner,
                    event_hit as hit,
                    event_on_off as on_off,
                    event_order_no as order_no,
                    event_del_yn as del_yn,
                    
                    event_app_main_order, 
                    event_like_banner_order,

                    event_start_datetime,
                    event_end_datetime,

                    event_keep_app_main_order,
                    event_keep_like_banner_order,
                    keep_yn,

                    create_id,
                    create_name,
                    create_time,
                    update_id,
                    update_name,
                    update_time,
                    delete_id,
                    delete_name,
                    delete_time
                FROM 
                    iparking_cms.board_event
                WHERE event_seq = :event_seq
            ');

            $stmt->execute(['event_seq' => $event_seq]);

            $data = $stmt->fetch();

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    idx, 
                    method, 
                    description, 
                    parameter, 
                    event_seq, 
                    create_id as update_id, 
                    create_name as update_name, 
                    create_time as update_time
                FROM 
                    iparking_cms.board_event_history
                WHERE event_seq = :event_seq
            ');

            $stmt->execute(['event_seq' => $event_seq]);

            $history_result = $stmt->fetchAll();
            $data['update_history'] = $history_result;
            
            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = json_decode('{}');
            }

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 등록
    public function postEventAdd($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');
            
            $add_type = $params['add_type'];
            $title = $params['title'];
            $content = $params['content'];
            $content_url = $params['content_url'];
            $thumbnail = $params['thumbnail'];
            $start_date = $params['start_date'];
            $end_date = $params['end_date'];
            $app_main_image_yn = $params['app_main_image_yn'];
            $app_main_image = $params['app_main_image'];
            $like_banner_yn = $params['like_banner_yn'];
            $like_banner = $params['like_banner'];
            $order_no = $params['order_no'] ?? 1;
            
            // 메인팝업, 추천배너 순번 추가
            $event_app_main_order = $params['event_app_main_order'];
            $event_like_banner_order = $params['event_like_banner_order'];

            // 예약순번
            $keep_yn = $params['keep_yn']; // 예약일 경우 Y, 예약이 아닐 경우 N
            $event_keep_app_main_order = $params['event_keep_app_main_order'];
            $event_keep_like_banner_order = $params['event_keep_like_banner_order'];   

            // 데이트타임
            $event_start_datetime = $params['event_start_datetime'];
            $event_end_datetime = $params['event_end_datetime'];

            // $event_on_off = $params['event_on_off'] ?? 0;
            $event_on_off = 0;

            // 게시 on 이고, 예약이 아닐경우
            if($event_on_off == 1){
                if($keep_yn == 'N') {
                    if( $event_app_main_order == '' ) $event_app_main_order == null;
                    if( $app_main_image_yn == 1 && $event_app_main_order == null) throw new ErrorException('메인팝업 순번값이 없습니다.');
                    if( $event_like_banner_order == '' ) $event_like_banner_order == null;
                    if( $like_banner_yn == 1 && $event_like_banner_order == null) throw new ErrorException('추천배너 순번값이 없습니다.');
                }
            }

            // 맥스값 체크 로직
            // 게시상태가 off일지라도 메인과 추천배너에 게시한다면 맥스값체크는 해야한다.
            // app main order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_app_main_order)
                    ,0 ) + 1
                    AS event_app_main_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_app_main_image_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();

            // 메인팝업 리밋값 가져오기
            $limit_event_app_main_order = $limitOrder['event_app_main_order_max'];


            // like banner order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_like_banner_order)
                    ,0 ) + 1
                    AS event_like_banner_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_like_banner_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();
            
            // 추천배너 리밋값 가져오기
            $limit_event_like_banner_order = $limitOrder['event_like_banner_order_max'];
            
            // 메인, 추천 게시여부가 on일때 리밋 초과시 순번값에 리밋값 셋팅
            if ($app_main_image_yn == 1){
                if($event_app_main_order > $limit_event_app_main_order) $event_app_main_order = $limit_event_app_main_order;                      
            }
            if ($like_banner_yn == 1){
                if($event_like_banner_order > $limit_event_like_banner_order) $event_like_banner_order = $limit_event_like_banner_order;
            }

            /*============================ 등록시 on_off가 무조건 null이므로 탈경우는 없지만 신규 요청을 대비하여 남겨둠 ================================*/
            // 게시 on 이고, 예약이 아닐경우
            if($event_on_off == 1){
                if($keep_yn == 'N') {
                    
                    // 등록할 순번값 이상의 순번값은 +1 처리한다.
                    if($app_main_image_yn == 1){
                        $stmt = $this->ci->iparkingCmsDb->prepare('
                        SELECT
                            event_seq, 
                            event_app_main_order
                        FROM
                            iparking_cms.board_event
                        WHERE 
                            event_del_yn = 0
                        AND 
                            event_on_off =1
                        AND 
                            event_app_main_image_yn = 1
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

                        // +1 씩 업데이트
                        $main_order_count = 0;
                        $main_order_update_flag = false;
                        $update_main_order_list = [];
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
                                        $update_main_order_list[] = array(
                                            'event_seq' => $target_idx,
                                            'event_app_main_order' => $target_main_order
                                        );

                                        //해당이벤트의 직접적인수정이 아닌 추가등록된 이벤트에 의한 수정이기 때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                                            'event_app_main_order' => $target_main_order+1
                                        ], [
                                            'event_seq' => $target_idx
                                        ]);
                                    }   
                                }  
                                $main_order_count++;
                            }
                        }
                    }
                    
                    //새로등록한 추천배너순번이 입력됨에 따라 새로등록할 순번보다 크거나 같은 순번은 +1씩 업데이트
                    if ($like_banner_yn == 1){
                        $stmt = $this->ci->iparkingCmsDb->prepare('
                        SELECT
                            event_seq, 
                            event_like_banner_order
                        FROM
                            iparking_cms.board_event
                        WHERE 
                            event_del_yn = 0
                        AND 
                            event_on_off =1
                        AND 
                            event_like_banner_yn = 1
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
                        $update_like_order_list = [];
                        if(!empty($like_order_list)) {
                            foreach($like_order_list as $like_order_list_rows){
                                $target_idx = $slike_order_list_rows['event_seq'];
                                $target_like_order = $like_order_list_rows['event_like_banner_order'];
                                
                                if($like_order_count == 0) {
                                    if($event_like_banner_order == $target_like_order) {
                                        $like_order_update_flag = true;
                                    }
                                }
                                if($like_order_update_flag) {
                                    if($event_like_banner_order+$like_order_count == $target_like_order) {
                                        $update_like_order_list[] = array(
                                            'event_seq' => $target_idx,
                                            'event_like_banner_order' => $target_like_order
                                        );
                                    
                                        //해당배너의 직접적인수정이 아닌 추가등록된 배너의 의한 수정이기때문에 수정자의 정보는 남기지 않지만 히스토리에는 남긴다.
                                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                                            'event_like_banner_order' => $target_like_order+1
                                        ], [
                                            'event_seq' => $target_idx
                                        ]);
                                    }
                                }
                                $like_order_count++;
                            }
                        }
                    }
                }
            }
            // 순번정렬로직 사전작업 끝

            // 이벤트 등록 - 신규 데이트타임, 순번, 예약순번 추가, 게시여부도 현재 무조건 0이지만 파람에서 받아서 넣도록 수정함
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event', [[
                'event_add_type' => $add_type,
                'event_title' => $title,
                'event_content' => $content,
                'event_content_url' => $content_url,
                'event_thumbnail' => $thumbnail,
                'event_start_date' => $start_date,
                'event_end_date' => $end_date,
                'event_app_main_image_yn' => $app_main_image_yn,
                'event_app_main_image' => $app_main_image,
                'event_like_banner_yn' => $like_banner_yn,
                'event_like_banner' => $like_banner,
                'event_order_no' => $order_no,
                
                'event_app_main_order' => $event_app_main_order,
                'event_like_banner_order' => $event_like_banner_order,
                
                'event_keep_app_main_order' => $event_keep_app_main_order,
                'event_keep_like_banner_order' => $event_keep_like_banner_order,
                'keep_yn' => $keep_yn,

                'event_start_datetime' => $event_start_datetime,
                'event_end_datetime' => $event_end_datetime, 

                'event_on_off' => $event_on_off,

                'create_id' => $user_id,
                'create_name' => $user_name,
                'create_time' => $now,
                'update_id' => $user_name,
                'update_name' => $user_name,
                'update_time' => $now
            ]]);
            
            /*============================ 등록시 on_off가 무조건 null이므로 탈경우는 없지만 신규 요청을 대비하여 남겨둠 ================================*/
            // 예약이 아닐경우
            if($event_on_off == 1){
                if($keep_yn == 'N') {

                    // 등록한 배너의 seq 셋팅
                    $event_seq = $this->ci->iparkingCmsDb->lastInsertId();
                    
                    // 등록된 순번으로 인해 +1 변경된 메인순번 히스토리 생성
                    if(!empty($update_main_order_list)) {
                        foreach($update_main_order_list as $update_main_order_list_rows){
                            $target_idx = $update_main_order_list_rows['event_seq'];
                            $target_main_order = $update_main_order_list_rows['event_app_main_order'];

                            $parameter = [array(
                                'before' => $target_main_order,
                                'after' => $target_main_order+1
                            )];

                            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                                'idx' => $target_idx,
                                'method' => 'update',
                                'description' => $event_seq.' 번 이벤트 등록에 따른 App 메인팝업 순번 +1',
                                'parameter' => $parameter,
                                'create_id' => $user_id,
                                'create_name' => $user_name,
                                'create_time' => $now
                            ]]);
                        }
                    }

                    if(!empty($update_like_order_list)) {
                        foreach($update_like_order_list as $update_like_order_list_rows){
                            $target_idx = $update_like_order_list_rows['event_seq'];
                            $target_like_order = $update_like_order_list_rows['event_like_banner_order'];

                            $parameter = [array(
                                'before' => $target_like_order,
                                'after' => $target_like_order+1
                            )];

                            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                                'idx' => $target_idx,
                                'method' => 'update',
                                'description' => $event_seq.' 번 이벤트 등록에 따른 추천배너 순번 +1',
                                'parameter' => $parameter,
                                'create_id' => $user_id,
                                'create_name' => $user_name,
                                'create_time' => $now
                            ]]);
                        }
                    }
                    //등록시에 등록한 게시물의 히스토리는 생성하지 않음
                }
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);
             
        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getEventMaxOrder($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);
            
            // app main order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_app_main_order)
                    ,0 )
                    AS event_app_main_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_app_main_image_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();

            // 메인팝업 리밋값 가져오기
            $limit_event_app_main_order = $limitOrder['event_app_main_order_max'];


            // like banner order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_like_banner_order)
                    ,0 )
                    AS event_like_banner_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_like_banner_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();
            
            // 추천배너 리밋값 가져오기
            $limit_event_like_banner_order = $limitOrder['event_like_banner_order_max'];

            $data = [];
            
            array_push($data, array(
                'type' => 'event_app_main_order',
                'title' => '메인 팝업',
                'maxCount' => $limit_event_app_main_order
            ));
            
            array_push($data, array(
                'type' => 'event_like_banner_order',
                'title' => '추천 배너',
                'maxCount' => $limit_event_like_banner_order
            ));            

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e);
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 수정
    public function putEventDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $event_seq = $args['event_seq'];
            $add_type = $params['add_type'];

            $title = $params['title'];
            $content = $params['content'];
            $content_url = $params['content_url'];
            $thumbnail = $params['thumbnail'];
            $start_date = $params['start_date'];
            $end_date = $params['end_date'];
            
            //메인팝업 게시유무 전후값 받기
            $app_main_image_yn_after = $params['app_main_image_yn_after'];
            $app_main_image_yn_before = $params['app_main_image_yn_before'];
            
            $app_main_image = $params['app_main_image'];
            
            // 추천배너 게시유무 전후값 받기
            $like_banner_yn_before = $params['like_banner_yn_before'];
            $like_banner_yn_after = $params['like_banner_yn_after'];
            
            $like_banner = $params['like_banner'];
            $order_no = $params['order_no'];

            // 메인팝업, 추천배너 순번 추가
            $event_app_main_order_after = $params['event_app_main_order_after'];
            $event_app_main_order_before = $params['event_app_main_order_before'];
            $event_like_banner_order_after = $params['event_like_banner_order_after'];
            $event_like_banner_order_before = $params['event_like_banner_order_before'];

            // 예약순번
            $keep_before_yn = $params['keep_before_yn']; // 예약일 경우 Y, 예약이 아닐 경우 N
            $keep_after_yn = $params['keep_after_yn']; // 예약일 경우 Y, 예약이 아닐 경우 N
            $event_keep_app_main_order_after = $params['event_keep_app_main_order_after'];
            $event_keep_app_main_order_before = $params['event_keep_app_main_order_before'];
            $event_keep_like_banner_order_after = $params['event_keep_like_banner_order_after'];
            $event_keep_like_banner_order_before = $params['event_keep_like_banner_order_before'];

            // 데이트타임
            $event_start_datetime = $params['event_start_datetime'];
            $event_end_datetime = $params['event_end_datetime'];

            $event_on_off = (int)$params['event_on_off'];
            
            // 맥스값 체크 로직
            // 게시상태가 off일지라도 메인과 추천배너에 게시한다면 맥스값체크는 해야한다.
            // app main order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_app_main_order)
                    ,0 )
                    AS event_app_main_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_app_main_image_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();

            // 메인팝업 리밋값 가져오기
            $limit_event_app_main_order = $limitOrder['event_app_main_order_max'];


            // like banner order max
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    IFNULL(
                        MAX(event_like_banner_order)
                    ,0 )
                    AS event_like_banner_order_max
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_like_banner_yn = 1
                AND 
                    event_del_yn = 0
                AND 
                    event_on_off =1
            ');
            $stmt->execute();
            $limitOrder = $stmt->fetch();
            
            // 추천배너 리밋값 가져오기
            $limit_event_like_banner_order = $limitOrder['event_like_banner_order_max'];

        
            // 맥스값 체크 로직
            //메인팝업에 게시하지 않다가 게시한다면 맥스값 +1
            if ($app_main_image_yn_before == 0 && $app_main_image_yn_after == 1){
                $limit_event_app_main_order = $limit_event_app_main_order +1;
            }
            //예약이였다가 게시한다면 맥스값 +1
            else if ( $keep_before_yn == 'Y' && $keep_after_yn == 'N'){
                $limit_event_app_main_order = $limit_event_app_main_order +1;
            }
            //게시상태가 아니라면 순번체크에 포함되지 않은상태이므로 맥스값 +1
            else if( $event_on_off == "0"){
                $limit_event_app_main_order = $limit_event_app_main_order +1;
            }
           

            //추천배너에 게시하지 않다가 게시한다면 맥스값 +1
            if ($like_banner_yn_before == 0 && $like_banner_yn_after == 1){
                $limit_event_like_banner_order = $limit_event_like_banner_order +1;
            }
            //예약이였다가 게시한다면 맥스값 +1
            else if ( $keep_before_yn == 'Y' && $keep_after_yn == 'N'){
                $limit_event_like_banner_order = $limit_event_like_banner_order +1;
            }
            else if( $event_on_off == "0"){
                $limit_event_like_banner_order = $limit_event_like_banner_order +1;
            }


            if($keep_after_yn == 'N'){
                if($event_app_main_order_after > $limit_event_app_main_order) throw new Exception ("현재 등록가능한 메인 팝업의 마지막 순번값은 ".$limit_event_app_main_order."입니다. 순번값을 확인해주세요.");                       
                if($event_like_banner_order_after > $limit_event_like_banner_order) throw new Exception ("현재 등록가능한 추천 배너의 마지막 순번값은 ".$limit_event_like_banner_order."입니다. 순번값을 확인해주세요.");                 
            }

            //예약순번 맥스 체크
            if($keep_after_yn == 'Y'){
                $limit_event_app_main_order_keepY = $limit_event_app_main_order +1;
                $limit_event_like_banner_order_keepY = $limit_event_like_banner_order +1;

                if($event_keep_app_main_order_after > $limit_event_app_main_order_keepY) throw new Exception ("현재 등록가능한 메인 팝업의 마지막 순번값은 ".$limit_event_like_banner_order_keepY."입니다. 순번값을 확인해주세요.");                       
                if($event_kepp_like_banner_order_after > $limit_event_like_banner_order_keepY) throw new Exception ("현재 등록가능한 추천 배너의 마지막 순번값은 ".$limit_event_like_banner_order_keepY."입니다. 순번값을 확인해주세요.");                 
            }


            // 정렬로직
            // 2. 게시 ON -> 게시 ON
            if( $event_on_off == 1){
                // 예약 -> 게시중
                if ($keep_before_yn == 'Y' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off X
                    //메인 게시 on -> on 이후 메인순번 같거나 큰순번 +1
                    if($app_main_image_yn_before == 1 && $app_main_image_yn_after == 1 ){
                        $this->ci->eventBanner->incrementEventAppMainPopOrder($event_seq, $event_app_main_order_after);
                    }
                    //메인 게시 off -> off X
                    //메인 게시 off -> on 이후 메인순번 같거나 큰순번 +1
                    if($app_main_image_yn_before == 0 && $app_main_image_yn_after == 1 ){
                        $this->ci->eventBanner->incrementEventAppMainPopOrder($event_seq, $event_app_main_order_after);
                    }

                    //추천 게시 on -> off X
                    //추천 게시 on -> on 이후 추천순번 같거나 큰순번 +1
                    if($like_banner_yn_before == 1 && $like_banner_yn_after ==1 ){
                        $this->ci->eventBanner->incrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_after);
                    }
                    //추천 게시 off -> off X
                    //추천 게시 off -> on 이후 추천순번 같거나 큰순번 +1
                    if($like_banner_yn_before == 0 && $like_banner_yn_after ==1 ){
                        $this->ci->eventBanner->incrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_after);
                    }
                }
                //게시중 -> 게시중
                if ($keep_before_yn == 'N' && $keep_after_yn == 'N'){
                    //메인 게시 on -> off 이전메인순번 뒷순번 -1
                    if($app_main_image_yn_before == 1 && $app_main_image_yn_after == 0 ){
                        $this->ci->eventBanner->decrementEventAppMainPopOrder($event_seq, $event_app_main_order_before);
                    }
                    //메인 게시 on -> on 크기비교로직
                    if($app_main_image_yn_before == 1 && $app_main_image_yn_after == 1 ){
                        if($event_app_main_order_before == null) throw new Exception ("이전 메인팝업 순번 값이 없습니다."); 
                        if($event_app_main_order_after == null) throw new Exception ("이후 추천메인팝업배너 순번 값이 없습니다."); 

                        // 이전 값이 이후 순번 보다 작을 경우 ex ) 3 -> 5
                        if($event_app_main_order_before < $event_app_main_order_after){
                            $this->ci->eventBanner->decrementEventAppMainPopOrderBetweenFrontAndBack($event_seq, $event_app_main_order_before, $event_app_main_order_after);
                        }
                        // 이전 값이 이후 순번 보다 클 경우  ex) 5 -> 3 
                        else if($event_app_main_order_before > $event_app_main_order_after){
                            $this->ci->eventBanner->incrementEventAppMainPopOrderBetweenFrontAndBack($event_seq, $event_app_main_order_before, $event_app_main_order_after);
                        }
                    }
                    //메인 게시 off -> off X
                    //메인 게시 off -> on 이후 메인순번 같거나 큰 순번 +1
                    if($app_main_image_yn_before == 0 && $app_main_image_yn_after == 1 ){
                        $this->ci->eventBanner->incrementEventAppMainPopOrder($event_seq, $event_app_main_order_after);
                    }

                    //추천 게시 on -> off 이전추천순번 뒷순번 -1
                    if($like_banner_yn_before == 1 && $like_banner_yn_after == 0 ){
                        $this->ci->eventBanner->decrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_before);
                    }
                    //추천 게시 on -> on 크기비교로직
                    if($like_banner_yn_before == 1 && $like_banner_yn_after == 1 ){
                        if($event_like_banner_order_before == null) throw new Exception ("이전 추천배너 순번 값이 없습니다."); 
                        if($event_like_banner_order_after == null) throw new Exception ("이후 추천배너 순번 값이 없습니다."); 
                        
                        // 이전 값이 바뀔 순번 보다 작을 경우 ex ) 3 -> 5
                        if($event_like_banner_order_before < $event_like_banner_order_after){
                            $this->ci->eventBanner->decrementEventAppLikeBannerOrderBetweenFrontAndBack($event_seq, $event_like_banner_order_before, $event_like_banner_order_after);
                        }
                        // 이전 값이 바뀔 순번 보다 클 경우  ex) 5 -> 3 
                        if($event_like_banner_order_before > $event_like_banner_order_after){
                            $this->ci->eventBanner->incrementEventAppLikeBannerOrderBetweenFrontAndBack($event_seq, $event_like_banner_order_before, $event_like_banner_order_after);
                        }
                    }
                    //추천 게시 off -> off X
                    //추천 게시 off -> on 이후 추천 순번 보다 같거나 큰순번 +1
                    if($like_banner_yn_before == 0 && $like_banner_yn_after == 1 ){
                        $this->ci->eventBanner->incrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_after);
                    }
                }
                //게시중 -> 예약
                if ($keep_before_yn == 'N' && $keep_after_yn == 'Y'){
                    //메인 게시 on -> off 이전 메인순번 뒷순번 -1
                    if($app_main_image_yn_before == 1 && $app_main_image_yn_after == 0 ){
                        $this->ci->eventBanner->decrementEventAppMainPopOrder($event_seq, $event_app_main_order_before);
                    }
                    //메인 게시 on -> on 이전 메인순번 뒷순번 -1
                    if($app_main_image_yn_before == 1 && $app_main_image_yn_after == 1 ){
                        $this->ci->eventBanner->decrementEventAppMainPopOrder($event_seq, $event_app_main_order_before);
                    }
                    //메인 게시 off -> off X
                    //메인 게시 off -> on X

                    //추천 게시 on -> off 이전 메인순번 뒷순번 -1
                    if($like_banner_yn_before == 1 && $like_banner_yn_after == 0 ){
                        $this->ci->eventBanner->decrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_before);
                    }
                    //추천 게시 on -> on 이전 추천순번 뒷순번 -1
                    if($like_banner_yn_before == 1 && $like_banner_yn_after == 1 ){
                        $this->ci->eventBanner->decrementEventAppLikeBannerOrder($event_seq, $event_like_banner_order_before);
                    }
                    //추천 게시 off -> off X
                    //추천 게시 off -> on X
                }
            }

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                'event_add_type' => $add_type,
                'event_title' => $title,
                'event_content' => $content,
                'event_content_url' => $content_url,
                'event_thumbnail' => $thumbnail,
                'event_start_date' => $start_date,
                'event_end_date' => $end_date,
                'event_app_main_image_yn' => $app_main_image_yn_after,
                'event_app_main_image' => $app_main_image,
                'event_like_banner_yn' => $like_banner_yn_after,
                'event_like_banner' => $like_banner,
                'event_order_no' => $order_no,

                'event_app_main_order' => $event_app_main_order_after,
                'event_like_banner_order' => $event_like_banner_order_after,

                'keep_yn' => $keep_after_yn,
                'event_keep_app_main_order' => $event_keep_app_main_order_after,
                'event_keep_like_banner_order' => $event_keep_like_banner_order_after,

                'event_start_datetime' => $event_start_datetime,
                'event_end_datetime' => $event_end_datetime,

                'update_id' => $user_id,
                'update_name' => $user_name,
                'update_time' => $now
            ], [
                'event_seq' => $event_seq
            ]);


            $update_arr = $params['update_arr'];
            if(!empty($update_arr)){
                if(gettype($update_arr) == 'string') {
                    $update_arr = json_decode($update_arr, true);
                }
                foreach($update_arr as $update_arr_rows){
                    $description = $update_arr_rows['key'];
                    $parameter = $update_arr_rows['value'];

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                        'event_seq' => $event_seq,
                        'method' => 'update',
                        'description' => $description.' 수정',
                        'parameter' => $parameter,
                        'create_id' => $user_id,
                        'create_name' => $user_name,
                        'create_time' => $now
                    ]]);
                }
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e){
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 삭제
    public function deleteEventDelete($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            $event_seq = $params['seq'];  

            $where = "";
            $binds = [
                'delete_id' => $user_id,
                'delete_name' => $user_name,
                'delete_time' => $now
            ];

            if(!isset($event_seq) && empty($event_seq)) throw new ErrorException('삭제할 게시물의 순번이 없습니다.');

            //기삭제된 배너를 재삭제 처리하지 않도록 삭제처리가 안된 배너인지 확인
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT *
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_del_yn = 0
                AND 
                    event_seq IN ('.$this->ci->dbutil->arrayToInQuery($event_seq).')
            ');
            $stmt->execute();
            $deleteList = $stmt->fetchAll();

            //삭제가안된 해당순번데이터 가져오기
            //삭제처리할 데이터가 있다면
            if(!empty($deleteList)){
                foreach($deleteList as $deleteListRow){
                    
                    $target_idx = $deleteListRow['event_seq'];

                    $event_app_main_order = $deleteListRow['event_app_main_order'];
                    $event_like_banner_order = $deleteListRow['event_like_banner_order'];    

                    $keep_yn = $deleteListRow['keep_yn'];
                    $event_on_off = $deleteListRow['event_on_off'];

                    $event_app_main_image_yn = $deleteListRow['event_app_main_image_yn'];
                    $event_like_banner_yn = $deleteListRow['event_like_banner_yn'];


                    if($event_app_main_order == '' ) $event_app_main_order = null;
                    if($event_like_banner_order == '' ) $event_like_banner_order = null;
                     
                     //예약이 아니고 메인, on/off 가 1인지 체크
                    if($keep_yn == 'N' && $event_on_off == 1) {
                        //메인 게시여부가 1인지, 순번값이 널이아닌지 체크
                        if($event_app_main_image_yn == 1 && $event_app_main_order != null ) {
                            //해당순번 보다 큰순번은 -1
                            $this->ci->eventBanner->decrementEventAppMainPopOrder($target_idx, $event_app_main_order);
                        }
                        if($event_app_main_image_yn == 1 && $event_like_banner_order != null) {
                            //해당순번 보다 큰순번은 -1
                            $this->ci->eventBanner->decrementEventAppLikeBannerOrder($target_idx, $event_like_banner_order);
                        }
                    } 
                    
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                        'event_del_yn' => 1,
                        'delete_id' => $user_id,
                        'delete_name' => $user_name,
                        'delete_time' => $now
                    ], [
                        'event_seq' => $target_idx
                    ]);

                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                        'event_seq' => $target_idx,
                        'method' => 'delete',
                        'description' => $target_idx.' 번 배너 삭제',
                        'parameter' => null,
                        'create_id' => $user_id,
                        'create_name' => $user_name,
                        'create_time' => $now
                    ]]);
                    
                }
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e){
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //이벤트 게시 순번 저장
    public function postEventSetOrder($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $orderList = $params['orderList'];

            $main_order_dupl_check = array();
            $like_order_dupl_check = array();

            foreach($orderList as $orderListRow){
                if($orderListRow['event_app_main_order_after'] != '' || $orderListRow['event_app_main_order_after'] != null)
                    array_push($main_order_dupl_check, $orderListRow['event_app_main_order_after']);
                if($orderListRow['event_like_banner_order_after'] != '' || $orderListRow['event_like_banner_order_after'] != null )
                    array_push($like_order_dupl_check, $orderListRow['event_like_banner_order_after']);
            }

            // 순서값이 정렬되어있다는 보장이 없기때문에 sort함수를 이용하여 올림차순 정렬을 수행한다.
            sort($main_order_dupl_check);
            sort($like_order_dupl_check);
            
            // 중복체크 수행 후 빠진순번 체크
            // 메인팝업순번에 중복된 값을 제외하고 원본과 카운터 비교
            if( count($main_order_dupl_check) != count(array_unique($main_order_dupl_check)) )
                throw new Exception ("변경요청한 메인팝업순번에 중복된 순번 값이 존재합니다.");

            // 추천배너순번에 중복된 값을 제외하고 원본과 카운터 비교
            if( count($like_order_dupl_check) != count(array_unique($like_order_dupl_check)) )
                throw new Exception ("변경요청한 추천배너순번에 중복된 순번 값이 존재합니다.");

            // 빠진순번 체크
            $check_val_for_main = 1;
            $check_val_for_like = 1;

            if(!empty($main_order_dupl_check)){
                foreach($main_order_dupl_check as $main_order_dupl_check_row => $main_order_dupl_check_row_val){
                    if($check_val_for_main != $main_order_dupl_check_row_val)
                        throw new Exception ('메인팝업순번에 '.$check_val_for_main.'번 순번이 존재하지 않습니다. 확인 후 다시 저장해주세요.');
                    $check_val_for_main++;
                }
            }

            if(!empty($like_order_dupl_check)){
                foreach($like_order_dupl_check as $like_order_dupl_check_row => $like_order_dupl_check_row_val){
                    if($check_val_for_like != $like_order_dupl_check_row_val)
                        throw new Exception ('추천배너순번에 '.$check_val_for_like.'번 순번이 존재하지 않습니다. 확인 후 다시 저장해주세요.');
                    $check_val_for_like++;
                }
            }

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            foreach($orderList as $orderListRow){
                $target_idx = $orderListRow['event_seq'];
                $target_main_order_after = $orderListRow['event_app_main_order_after'];
                $target_main_order_before = $orderListRow['event_app_main_order_before'];
                $target_like_order_after = $orderListRow['event_like_banner_order_after'];
                $target_like_order_before = $orderListRow['event_like_banner_order_before'];

                if($orderListRow['event_app_main_order_after'] != '' || $orderListRow['event_app_main_order_after'] != null )
                {
                    if($target_main_order_before != $target_main_order_after){
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                            'event_app_main_order' => $target_main_order_after,
                            'update_id' => $user_id,
                            'update_name' => $user_name,
                            'update_time' => $now
                        ], [
                            'event_seq' => $target_idx
                        ]);

                        $parameter = [array(
                            'before' => $target_main_order_before,
                            'after' => $target_main_order_after
                        )];

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                            'event_seq' => $target_idx,
                            'method' => 'update',
                            'description' => $target_idx.' 번 배너 메인팝업 게시순번 수정',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);

                    }
                }

                if($orderListRow['event_like_banner_order_after'] != '' || $orderListRow['event_like_banner_order_after'] != null)
                {
                    if( $target_like_order_before != $target_like_order_after){
                        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                            'event_like_banner_order' => $target_like_order_after,
                            'update_id' => $user_id,
                            'update_name' => $user_name,
                            'update_time' => $now
                        ], [
                            'event_seq' => $target_idx
                        ]);
                        
                        $parameter = [array(
                            'before' => $target_like_order_before,
                            'after' => $target_like_order_after
                        )];

                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                            'event_seq' => $target_idx,
                            'method' => 'update',
                            'description' => 'target_idx'.' 번 추천 배너 게시순번 수정',
                            'parameter' => $parameter,
                            'create_id' => $user_id,
                            'create_name' => $user_name,
                            'create_time' => $now
                        ]]);
                    }
                }
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 게시 on/off
    public function patchEventOnOff($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $event_seq = $args['event_seq'];
            $on_off = (int)$params['on_off'];

            // 해당게시물의 상태값 가져오기
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT *
                FROM iparking_cms.board_event
                WHERE event_del_yn = 0
                AND event_seq = :event_seq
            ');

            $stmt->execute(['event_seq' => $event_seq]);

            $data = $stmt->fetch();

            $keep_yn = $data['keep_yn'];

            $event_app_main_image_yn = $data['event_app_main_image_yn'];

            $event_like_banner_yn = $data['event_like_banner_yn'];

            if($on_off == $data['event_on_off'] && $on_off == 1) throw new Exception ("이벤트 게시물의 게시상태가 이미 ON 입니다.");
            if($on_off == $data['event_on_off'] && $on_off == 0) throw new Exception ("이벤트 게시물의 게시상태가 이미 OFF 입니다.");
            
            //변경할 on/off값이 off일때 
            if($on_off == 0)
            {
                // 예약이 아닌경우에만 
                if($keep_yn == 'N')
                {
                    // 메인 게시여부 on 확인
                    if($event_app_main_image_yn == 1)
                    {
                        // 갖고있던 순번의 뒷 순번들은 -1 처리
                        if($data['event_app_main_order'] != null){
                            $this->ci->eventBanner->decrementEventAppMainPopOrder($event_seq, $data['event_app_main_order']);
                        }
                    }
                    // 추천 게시여부 확인
                    if($event_like_banner_yn == 1){
                        // 갖고있던 순번의 뒷 순번들은 -1 처리
                        if($data['event_like_banner_order'] != null){
                            $this->ci->eventBanner->decrementEventAppLikeBannerOrder($event_seq, $data['event_like_banner_order']);
                        }
                    }
                }        

                //게시상태업데이트, 이력생성
                //게시 OFF 시 순번값 리셋
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                    'event_on_off' => 0,

                    'event_app_main_image_yn' => 0,
                    'event_like_banner_yn' => 0,
                    'event_app_main_order' => null,
                    'event_like_banner_order' => null,
                    'event_keep_app_main_order' => null,
                    'event_keep_like_banner_order' => null,

                    'on_off_update_id' => $user_id,
                    'on_off_update_name' => $user_name,
                    'on_off_update_time' => $now
                ], [
                    'event_seq' => $event_seq
                ]);

                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $event_seq,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 게시 OFF',
                    'parameter' => null,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }
            //변경할 on/off값이 on 일때
            if($on_off == 1)
            {
                // 예약이 아닌경우에만 
                if($keep_yn == 'N')
                {
                    // 메인팝업 게시여부 확인
                    if($event_app_main_image_yn == 1)
                    {
                        //요청한 이벤트 메인팝업순번부터 마지막 메인팝업순번까지 +1
                        if($data['event_app_main_order'] != null){
                            $this->ci->eventBanner->incrementEventAppMainPopOrder($event_seq, $data['event_app_main_order']);
                        }
                    }
                    // 추천 게시여부 확인
                    if($event_like_banner_yn == 1){
                        //요청한 이벤트 추천순번부터 마지막 추천순번까지 +1
                        if($data['event_like_banner_order'] != null){
                            $this->ci->eventBanner->incrementEventAppLikeBannerOrder($event_seq, $data['event_like_banner_order']);
                        }
                    }
                }

                //게시상태업데이트, 이력생성
                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                    'event_on_off' => 1,
                    'on_off_update_id' => $user_id,
                    'on_off_update_name' => $user_name,
                    'on_off_update_time' => $now
                ], [
                    'event_seq' => $event_seq
                ]);
    
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.board_event_history', [[
                    'event_seq' => $event_seq,
                    'method' => 'update',
                    'description' => $event_seq.' 번 이벤트 게시 ON',
                    'parameter' => null,
                    'create_id' => $user_id,
                    'create_name' => $user_name,
                    'create_time' => $now
                ]]);
            }

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    ///////////////////////// 이벤트 배너 /////////////////////////
    // 이벤트 프로젝트 리스트 SELECT
    public function getEventBannerProjectList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    project_idx as value,
                    project_name as text
                FROM 
                    iparking_cms.event_banner_project_list
            ');
            $stmt->execute();
            $event_banner_project_list = $stmt->fetchAll();

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $event_banner_project_list;
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 링크 타입 SELECT
    public function getEventBannerLinkTypeList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    code as value,
                    name as text
                FROM iparking_cms.event_banner_link_type
            ');
            $stmt->execute();
            $event_banner_link_type_list = $stmt->fetchAll();

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $event_banner_link_type_list;
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 리스트
    public function getEventBannerList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);
            
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'] ?? 'desc';
            $orderByColumn = $params['orderByColumn'] ?? 'banner_idx';
             
            $queryOrderBy = $orderByColumn.' '.$orderBy;

            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    banner_idx, 
                    project_idx, 
                    description, 
                    create_id, 
                    create_name, 
                    create_time, 
                    update_id, 
                    update_name, 
                    update_time
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.event_banner 
                    WHERE 1=1 AND del_ny = 0
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $queryOrderBy
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    // 이벤트 배너 상세
    public function getEventBannerDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $banner_idx = $args['banner_idx'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    banner_idx,
                    project_idx,
                    description
                FROM 
                    iparking_cms.event_banner
                WHERE 
                    banner_idx = :banner_idx
            ');

            $stmt->execute(['banner_idx' => $banner_idx]);

            $event_banner_list = $stmt->fetch();
            $data['banner_idx'] = $event_banner_list['banner_idx'];
            $data['project_idx'] = $event_banner_list['project_idx'];
            $data['description'] = $event_banner_list['description'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    *
                FROM 
                    iparking_cms.event_banner_detail
                WHERE banner_idx = :banner_idx AND del_ny = 0
                ORDER BY order_no ASC
            ');

            $stmt->execute(['banner_idx' => $banner_idx]);

            $event_banner_detail_list = $stmt->fetchAll();

            // 배너 이미지에 따라 주차장 선택을 할 수가 있다.
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    *
                FROM 
                    iparking_cms.event_banner_parkinglot
                WHERE banner_idx = :banner_idx AND del_ny = 0
            ');

            $stmt->execute(['banner_idx' => $banner_idx]);

            $event_banner_parkinglot_list = $stmt->fetchAll();

            foreach($event_banner_detail_list as &$event_banner_detail_rows) {
                $banner_detail_idx = $event_banner_detail_rows['banner_detail_idx'];
                $event_banner_detail_rows['parkinglot_arr'] = [];
                if(!empty($event_banner_parkinglot_list)) {
                    foreach($event_banner_parkinglot_list as $event_banner_parkinglot_rows) {
                        $parkinglot_banner_detail_idx = $event_banner_parkinglot_rows['banner_detail_idx'];
                        $park_seq = $event_banner_parkinglot_rows['park_seq'];
                        $park_name = $event_banner_parkinglot_rows['park_name'];
                        if($banner_detail_idx == $parkinglot_banner_detail_idx) {
                            $event_banner_detail_rows['parkinglot_arr'][] = array(
                                'park_seq' => $park_seq,
                                'park_name' => $park_name
                            );
                        }
                    }
                }
            }

            $data['event_banner_detail_arr'] = $event_banner_detail_list;
            
            if ($data) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = $data;
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
                $msg['data'] = json_decode('{}');
            }

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 등록
    public function postEventBannerAdd($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');
            
            // 이벤트 배너 초기 데이터
            $project_idx = $params['project_idx'];
            $description = $params['description'];

            // 이벤트 배너 상세
            $event_banner_detail_arr = $params['event_banner_detail_arr'];
        
            // 이벤트 배너 초기 데이터 셋팅
            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner', [[
                'project_idx' => $project_idx,
                'description' => $description,
                'create_id' => $user_id,
                'create_name' => $user_name,
                'create_time' => $now
            ]]);

            $last_banner_idx = $this->ci->iparkingCmsDb->lastInsertId();

            foreach($event_banner_detail_arr as $detail_rows) {
                $link_type = $detail_rows['link_type'];
                $link_uri = $detail_rows['link_uri'];
                $link_detail_uri = $detail_rows['link_detail_uri'];
                $order_no = $detail_rows['order_no'];
                $event_name = $detail_rows['event_name'];
                $image_path = $detail_rows['image_path'];
                $start_date = $detail_rows['start_date'];
                $end_date = $detail_rows['end_date'];
                $parkinglot_apply_type = $detail_rows['parkinglot_apply_type'];
                
                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_detail', [[
                    'banner_idx' => $last_banner_idx,
                    'project_idx' => $project_idx,
                    'link_type' => $link_type,
                    'link_uri' => $link_uri,    
                    'link_detail_uri' => $link_detail_uri,  
                    'order_no' => $order_no,
                    'event_name' => $event_name,
                    'image_path' => $image_path,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'parkinglot_apply_type' => $parkinglot_apply_type
                ]]);

                $last_banner_detail_idx = $this->ci->iparkingCmsDb->lastInsertId();

                if($parkinglot_apply_type == 1 || $parkinglot_apply_type == 2) {
                    $parkinglot_arr = $detail_rows['parkinglot_arr'];
                    if(!empty($parkinglot_arr)) {
                        foreach($parkinglot_arr as $parkinglot_rows) {
                            $park_seq = $parkinglot_rows['park_seq'];
                            $park_name = $parkinglot_rows['park_name'];
                            $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_parkinglot', [[
                                'banner_idx' => $last_banner_idx,
                                'banner_detail_idx' => $last_banner_detail_idx,
                                'park_seq' => $park_seq,
                                'park_name' => $park_name,
                                'type' => $parkinglot_apply_type
                            ]]);
                        }
                    }
                }
            }
                        
            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 수정
    public function putEventBannerDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $now = date('Y-m-d H:i:s');
            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];

            // 이벤트 배너 초기 데이터
            $banner_idx = $args['banner_idx'];
            $project_idx = $params['project_idx'];
            $description = $params['description'];
            $update_arr = $params['update_arr']; // 프로젝트명, 배너위치명 수정된 정보 array

            if(!empty($update_arr)) {

                $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.event_banner', [
                    'project_idx' => $project_idx,
                    'description' => $description,
                    'update_id' => $user_id,
                    'update_name' => $user_name,
                    'update_time' => $now
                ], [
                    'banner_idx' => $banner_idx
                ]);
                foreach($update_arr as $update_arr_rows) {

                    $key = $update_arr_rows['key'];
                    $value = $update_arr_rows['value'];
                    if($key == 'project_idx') {
                        $history_description = '프로젝트 수정';
                    } else if ($key == 'description') {
                        $history_description = '배너 위치명 수정';
                    }
  
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_detail_history', [[
                        'method' => 'update',
                        'banner_idx' => $banner_idx,
                        'project_idx' => $project_idx,
                        'description' => $history_description,
                        'parameter' => $value,
                        'create_id' => $user_id,
                        'create_time' => $now
                    ]]);

                }
            }
    
            // 이벤트 배너 상세
            $event_banner_detail_arr = $params['event_banner_detail_arr'];

            foreach($event_banner_detail_arr as $detail_rows) {
                $method = $detail_rows['method'];
                $banner_detail_idx = $detail_rows['banner_detail_idx'];
                $link_type = $detail_rows['link_type'];
                $link_uri = $detail_rows['link_uri'];
                $link_detail_uri = $detail_rows['link_detail_uri'];
                $order_no = $detail_rows['order_no'];
                $event_name = $detail_rows['event_name'];
                $image_path = $detail_rows['image_path'];
                $start_date = $detail_rows['start_date'];
                $end_date = $detail_rows['end_date'];
                $parkinglot_apply_type = $detail_rows['parkinglot_apply_type'];
                $parkinglot_arr = $detail_rows['parkinglot_arr'];
                $update_detail_arr = $detail_rows['update_detail_arr'];  
                $parkinglot_count = count($parkinglot_arr);
                if($method == 'insert') {
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_detail', [[
                        'banner_idx' => $banner_idx,
                        'project_idx' => $project_idx,
                        'link_type' => $link_type,
                        'link_uri' => $link_uri,  
                        'link_detail_uri' => $link_detail_uri,    
                        'order_no' => $order_no,
                        'event_name' => $event_name,
                        'image_path' => $image_path,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'parkinglot_apply_type' => $parkinglot_apply_type
                    ]]);

                    $last_banner_detail_idx = $this->ci->iparkingCmsDb->lastInsertId();
    
                    if($parkinglot_apply_type == 1 || $parkinglot_apply_type == 2) {
                        $parkinglot_arr = $detail_rows['parkinglot_arr'];
                        if(!empty($parkinglot_arr)) {
                            foreach($parkinglot_arr as $parkinglot_rows) {
                                $park_seq = $parkinglot_rows['park_seq'];
                                $park_name = $parkinglot_rows['park_name'];
                                $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_parkinglot', [[
                                    'banner_idx' => $banner_idx,
                                    'banner_detail_idx' => $last_banner_detail_idx,
                                    'park_seq' => $park_seq,
                                    'park_name' => $park_name,
                                    'type' => $parkinglot_apply_type
                                ]]);
                            }
                        }
                    }
                } else if($method == 'update') {
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.event_banner_detail', [
                        'project_idx' => $project_idx,
                        'link_type' => $link_type,
                        'link_uri' => $link_uri,   
                        'link_detail_uri' => $link_detail_uri,   
                        'order_no' => $order_no,
                        'event_name' => $event_name,
                        'image_path' => $image_path,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'parkinglot_apply_type' => $parkinglot_apply_type
                    ], [
                        'banner_detail_idx' => $banner_detail_idx
                    ]);

                    if($parkinglot_apply_type == 1 || $parkinglot_apply_type == 2) {                   
                        if(!empty($parkinglot_arr)) {
                            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.event_banner_parkinglot', [
                                'del_ny' => 1
                            ], [
                                'banner_idx' => $banner_idx,
                                'banner_detail_idx' => $banner_detail_idx
                            ]);
                            foreach($parkinglot_arr as $parkinglot_rows) {
                                $park_seq = $parkinglot_rows['park_seq'];
                                $park_name = $parkinglot_rows['park_name'];
                                $stmt = $this->ci->iparkingCmsDb->prepare('
                                    INSERT INTO iparking_cms.event_banner_parkinglot (
                                        banner_idx, banner_detail_idx, type, park_seq, park_name, del_ny
                                    ) VALUES 
                                    (
                                        :banner_idx, :banner_detail_idx, :type, :park_seq, :park_name, 0  
                                    ) ON DUPLICATE KEY UPDATE banner_idx = :banner_idx1, banner_detail_idx = :banner_detail_idx1, type = :type1, park_seq = :park_seq1, park_name = :park_name1, del_ny = 0
                                ');
                                $stmt->execute([
                                    'banner_idx' => $banner_idx,
                                    'banner_detail_idx' => $banner_detail_idx, 
                                    'type' => $parkinglot_apply_type, 
                                    'park_seq' => $park_seq, 
                                    'park_name' => $park_name, 
                                    'banner_idx1' => $banner_idx,
                                    'banner_detail_idx1' => $banner_detail_idx,
                                    'type1' => $parkinglot_apply_type, 
                                    'park_seq1' => $park_seq,
                                    'park_name1' => $park_name
                                ]);
                            }             
                        }
                    }
                    foreach($update_detail_arr as $update_detail_arr_rows) {
                        $detail_key = $update_detail_arr_rows['key'];
                        $detail_value = $update_detail_arr_rows['value'];
                        if($detail_key == 'image_path') {
                            $history_description = '이미지 변경';
                        } else if($detail_key == 'start_date') {
                            $history_description = '게시일 시작일 변경';
                        } else if($detail_key == 'end_date') {
                            $history_description = '게시일 종료일 변경';
                        } else if($detail_key == 'event_name') {
                            $history_description = '이벤트명 변경';
                        } else if($detail_key == 'link_type') {
                            $history_description = '링크 타입 변경';
                        } else if($detail_key == 'link_uri') {
                            $history_description = '링크 전달 방식 변경';
                        } else if ($detail_key == 'link_detail_uri') {
                            $history_description = '링크 상세 페이지 uri 변경';
                        } else if($detail_key == 'order_no') {
                            $history_description = '순서 변경';
                        } else if($detail_key == 'parkinglot_apply_type') {
                            $history_description = '노출 주차장 타입 변경';
                        } else if($detail_key == 'parkinglot_arr') {
                            $history_description = '주차장 선택 '.$parkinglot_count.'개로 변경';
                        }
                        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_detail_history', [[
                            'method' => 'update',
                            'banner_idx' => $banner_idx,
                            'project_idx' => $project_idx,
                            'banner_detail_idx' => $banner_detail_idx,
                            'description' => $history_description,
                            'parameter' => $detail_value,
                            'create_id' => $user_id,
                            'create_time' => $now
                        ]]);
                    }
                    
                } else if ($method == 'delete') {
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.event_banner_detail', [
                        'del_ny' => 1
                    ], [
                        'banner_detail_idx' => $banner_detail_idx
                    ]);
                    $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.event_banner_parkinglot', [
                        'del_ny' => 1
                    ], [
                        'banner_idx' => $banner_idx,
                        'banner_detail_idx' => $banner_detail_idx
                    ]);
                    $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.event_banner_detail_history', [[
                        'method' => 'delete',
                        'banner_idx' => $banner_idx,
                        'project_idx' => $project_idx,
                        'banner_detail_idx' => $banner_detail_idx,
                        'description' => $event_name.' 이미지 삭제',
                        'parameter' => array(
                            'link_type' => $link_type, 
                            'order_no' => $order_no, 
                            'event_name' => $event_name, 
                            'image_path' => $image_path, 
                            'start_date' => $start_date,
                            'end_date' => $end_date, 
                            'parkinglot_apply_type' => $parkinglot_apply_type,
                            'parkinglot_arr' => $parkinglot_arr
                        ),
                        'create_id' => $user_id,
                        'create_time' => $now
                    ]]);
                }               
            }
                        
            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 삭제
    public function deleteEventBannerList($request, $response, $args)
    {
        try{

            $params = $this->ci->util->getParams($request);

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');

            $banner_idx = $params['banner_idx'];  

            $where = "";
            $binds = [
                'delete_id' => $user_id,
                'delete_name' => $user_name,
                'delete_time' => $now
            ];

            if(!isset($banner_idx) && empty($banner_idx)) throw new ErrorException('파라메터가 잘못 되었습니다.');

            $stmt = $this->ci->iparkingCmsDb->prepare('
                UPDATE
                    iparking_cms.event_banner
                SET
                    del_ny = 1,
                    delete_id = :delete_id,
                    delete_name = :delete_name,
                    delete_time = :delete_time
                WHERE 1=1 AND banner_idx IN ('.$this->ci->dbutil->arrayToInQuery($banner_idx).')
            ');
            $stmt->execute($binds);

            $msg = $this->ci->message->apiMessage['success'];
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 배너 상세 삭제이력 보기
    public function getEventBannerDetailDeleteHistorys($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');

            $banner_idx = $args['banner_idx'];
            $limit = $params['limit'];
            $offset = $params['offset'];

            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    *
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.event_banner_detail_history 
                    WHERE 1=1 AND method = \'DELETE\' AND banner_idx = :banner_idx
                ',
                'binds' => ['banner_idx' => $banner_idx],
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => 'create_time DESC'
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getFindParkinglot($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $user_id = $this->ci->settings['userInfo']['id'];
            $user_name = $this->ci->settings['userInfo']['name'];
            $now = date('Y-m-d H:i:s');

            $park_name = $params['park_name'];

            if(!$park_name) throw new Exception("주차장명을 입력해주세요.");

            $stmt = $this->ci->iparkingCloudDb->prepare('
                SELECT 
                    park_seq,
                    park_name
                FROM 
                    fdk_parkingcloud.acd_pms_parkinglot
                WHERE park_name like :park_name
            ');
            $stmt->execute(['park_name' => '%'.$park_name.'%']);
            $data = $stmt->fetchAll();    
            
            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
    ///////////////////////// 이미지 이벤트 끝 /////////////////////////

    ////////////////////////////// FAQ //////////////////////////////

    ////////////////////////////// SMS //////////////////////////////

    ////////////////////////////// 공통 //////////////////////////////
        
    // 첨부파일 업로드
    public function postBoardFileUpload($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $type = $params['type'];
            $main_type = $params['main_type'];

            // 확장자를 가져오기 위한 로직
            // $info = new SplFileInfo($_FILES['file']['name']);
            // $extension = $info->getExtension();
            // $size = $_FILES['file']['size'];
            $path = $this->ci->label->첨부파일구분[$main_type][$type];

            // 업로드 보류
            $result = $this->ci->file->toastCloudObjectStorageUpload($_FILES, $path, $main_type);         
            
            if($result['statusCode'] == 201) {
                $msg = $this->ci->message->apiMessage['success'];
                $msg['data'] = [
                    'fileName' => $result['fileName'],
                    'attachment_type' => $result['attachment_type'],
                    'path' => $result['path'],
                    'size' => $result['size'],
                    'extension' => $result['extension'],
                    'link' => $result['link']
                ];
            } else if ($result['statusCode'] == 408) {
                $msg = $this->ci->message->apiMessage['notRequestTime'];
            } else if ($result['statusCode'] == 411) {
                $msg = $this->ci->message->apiMessage['notUploadHeader'];
            } else {
                $msg = $this->ci->message->apiMessage['notUpload'];
            }

            return $response->withJson($msg);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 첨부파일 다운로드
    public function getBoardFileDownload($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $link = $params['link'];
            $path = $params['path'];

            $result = $this->ci->file->toastCloudObjectStorageDownload($link, $path);

            return $result;

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
           //  $this->ci->logger->debug($e); 
            return $response->withJson(['error'=>$e->getMessage()]);
        }
    }


}