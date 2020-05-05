<?php

class BoardController
{

    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // 공지사항 리스트
    public function getNoticeList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 100,
                'offset' => 0
            ]);

            $search_type = $params['search_type'];
            $search_content = $params['search_content'];
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'] ?? 'desc';
            $orderByColumn = $params['orderByColumn'] ?? 'create_time';

            $where = "";
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
                
            $orderByColumn = $this->ci->label->공지사항[$orderByColumn];
                    
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    notice_seq as seq,
                    notice_title as title,
                    notice_content as content,
                    notice_content_url as content_url,
                    create_time
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.board_notice 
                    WHERE 1=1 AND notice_del_yn = 0 AND notice_on_off = 1 '.$where.'
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

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
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
                    notice_title as title,
                    notice_content as content,
                    notice_content_url as content_url,
                    notice_attachment as attchment,
                    notice_hit as hit,
                    create_time
                FROM 
                    iparking_cms.board_notice
                WHERE notice_seq = :notice_seq
            ');

            $stmt->execute(['notice_seq' => $notice_seq]);

            $data = $stmt->fetch();

            $hit = (int) $data['hit'] + 1;

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_notice', [
                'notice_hit' => $hit
            ], [
                'notice_seq' => $notice_seq
            ]);

            // 모바일에서 뷰를 들어가게되면 조회수가 선 카운트가 되어서 보여주어야 한다.
            $data['hit'] = $hit;
            
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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 추천배너 리스트
    public function getEventList($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 100,
                'offset' => 0
            ]);
            
            $device_type = $params['device_type'] ?? 'android';
            $add_type = $params['add_type'];
            $search_type = $params['search_type'];
            $search_content = $params['search_content'];
            $limit = $params['limit'];
            $offset = $params['offset'];
            $orderBy = $params['orderBy'] ?? 'desc';
            $orderByColumn = $params['orderByColumn'] ?? 'seq';
            // 히스토리 정렬조건 시작일자,시컨스로 수정
            $multiColumnOrderBy = $params['multiColumnOrderBy'] ?? 'event_start_datetime DESC, event_seq DESC';
            
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

            if($device_type == 'ios') {
                $where .= " AND event_add_type != 2 ";
            }

            $orderByColumn = $this->ci->label->이벤트[$orderByColumn];

            $queryOrderBy = $orderByColumn.' '.$orderBy;

            // 멀티 orderby면 적용해줘야한다.
            if ($multiColumnOrderBy) {
                $queryOrderBy = $multiColumnOrderBy;
            }

            // order by 절을 app에서 어떻게 보내는지 확인이 안되어 픽스함
            // $queryOrderBy = 'create_time DESC, event_seq DESC';

            // order by 절 등록일시로
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    event_seq as seq,
                    event_add_type as add_type,
                    (
                        CASE
                            WHEN event_add_type = 0 THEN \'EVENT\'
                            WHEN event_add_type = 1 THEN \'OPEN\'
                            WHEN event_add_type = 2 THEN \'EVENT\'
                            WHEN event_add_type = 3 THEN \'NEWS\'
                        END
                    ) as add_type_name,
                    event_title as title,
                    event_content as content,
                    event_content_url as content_url,
                    event_thumbnail as thumbnail,
                    DATE_FORMAT(event_start_datetime, "%Y-%m-%d") as start_date,
                    DATE_FORMAT(event_end_datetime, "%Y-%m-%d") as end_date,
                    event_app_main_image as app_main_image,
                    event_like_banner_yn as like_banner_yn,
                    event_like_banner as like_banner,
                    event_hit as hit,
                    create_time
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.board_event 
                    WHERE 1=1 AND event_on_off =1 AND event_del_yn = 0 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => $queryOrderBy
            ]);
                        
            // 추천배너 리스트 
            $nowDate = date('Y-m-d');
            $like_banner_where = "";  
            if($device_type == 'ios') {
                $like_banner_where .= " AND event_add_type != 2 ";
            }

            //상단에 노출되는 추천배너
            // order by 절 순서값으로 수정
            // limit 5  추가
            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    event_seq as seq,
                    event_like_banner as like_banner
                FROM 
                    iparking_cms.board_event
                WHERE 
                    event_like_banner_yn = 1 
                AND
                    event_del_yn = 0
                AND
                    event_on_off = 1
                AND
                    event_like_banner_order is not null
                AND
                    event_end_datetime >= :nowDate
                '.$like_banner_where.'
                ORDER BY 
                    length(event_like_banner_order),
                    event_like_banner_order ASC
                LIMIT 5
            ');

            $stmt->execute(['nowDate' => $nowDate]);

            $banner_result = $stmt->fetchAll();
            $result['bannerList'] = $banner_result;

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

    // 이벤트 뷰
    public function getEventDetail($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $event_seq = $args['event_seq'];

            $stmt = $this->ci->iparkingCmsDb->prepare('
                SELECT 
                    event_seq as seq,
                    event_add_type as add_type,
                    event_title as title,
                    event_content as content,
                    event_content_url as content_url,
                    event_thumbnail as thumbnail,
                    event_start_datetime as start_date,
                    event_end_datetime as end_date,
                    event_app_main_image as app_main_image,
                    event_like_banner_yn as like_banner_yn,
                    event_like_banner as like_banner,
                    event_hit as hit,
                    create_time
                FROM 
                    iparking_cms.board_event
                WHERE event_seq = :event_seq
            ');

            $stmt->execute(['event_seq' => $event_seq]);

            $data = $stmt->fetch();

            $hit = (int) $data['hit'] + 1;

            $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.board_event', [
                'event_hit' => $hit
            ], [
                'event_seq' => $event_seq
            ]);
            
            // 모바일에서 뷰를 들어가게되면 조회수가 선 카운트가 되어서 보여주어야 한다.
            $data['hit'] = $hit;

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
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    // 이벤트 메인팝업 이미지 
    public function getEventMainPopUp($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 5,
                'offset' => 0
            ]);

            $device_type = $params['device_type'] ?? 'android';
            $offset = $params['offset'];
            $limit = $params['limit'];

            $where = "";
            $binds = [];
            if($device_type == 'ios') {
                $where .= 'AND event_add_type != 2 ';
            }
            
            // order by 절 
            // 순번조건 추가
            // limit 5 추가 
            $limit = 5;

            $where .= " AND event_end_datetime >= :nowDate ";
            $binds['nowDate'] = date('Y-m-d');
            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    event_seq as seq,
                    event_app_main_image as app_main_image    
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.board_event 
                    WHERE 1=1 
                    AND 
                        event_del_yn = 0 
                    AND 
                        event_app_main_image_yn = 1 
                    AND
                        event_app_main_order is not null
                    AND
                        event_end_datetime >= :nowDate
                    AND 
                        event_on_off = 1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => 'length(event_app_main_order), event_app_main_order ASC'
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
 
    // 배너게시리스트
    public function getBannerPostingList($request, $response, $args){
        try{

            $params = $this->ci->util->getParams($request);

            $positionCheckVal = (int)$args['position_check_val'];

            $now = date('Y-m-d H:i:s');

            // $positionCheckVal = 1 메인배너
            if($positionCheckVal == 1){
                $stmt = $this->ci->iparkingCmsDb->prepare('
                    SELECT
                        web_event_banner_idx,
                        web_event_banner_image,
                        web_event_banner_detail_type,
                        web_event_banner_detail_method,
                        web_event_banner_detail_content,
                        web_event_banner_main_order,
                        web_event_banner_detail_html
                    FROM
                        iparking_cms.web_event_banner
                    WHERE 
                        del_yn = 0
                    AND 
                        web_event_banner_on_off = 1
                    AND 
                        web_event_banner_start_date <= :web_event_banner_start_date
                    AND 
                        web_event_banner_end_date >= :web_event_banner_end_date
                    AND 
                        web_event_banner_main_order is not null
                    ORDER BY
                        length(web_event_banner_main_order),
                        web_event_banner_main_order ASC
                    LIMIT 7
                ');

                $stmt->execute([
                    'web_event_banner_start_date' => $now,
                    'web_event_banner_end_date' => $now
                ]);
            }

            // $positionCheckVal = 2 서비스배너
            else if($positionCheckVal == 2){
                $stmt = $this->ci->iparkingCmsDb->prepare('
                    SELECT
                        web_event_banner_idx,
                        web_event_banner_image,
                        web_event_banner_detail_type,
                        web_event_banner_detail_method,
                        web_event_banner_detail_content,
                        web_event_banner_service_order,
                        web_event_banner_detail_html
                    FROM
                        iparking_cms.web_event_banner
                    WHERE 
                        del_yn = 0
                    AND 
                        web_event_banner_on_off = 1
                    AND 
                        web_event_banner_start_date <= :web_event_banner_start_date
                    AND 
                        web_event_banner_end_date >= :web_event_banner_end_date
                    AND 
                        web_event_banner_service_order is not null
                    ORDER BY
                        length(web_event_banner_service_order),
                        web_event_banner_service_order ASC
                    LIMIT 7
                ');

                $stmt->execute([
                    'web_event_banner_start_date' => $now,
                    'web_event_banner_end_date' => $now
                ]);
            
            } else throw new Exception ("포지션 값을 확인해주세요.");
                        
            $result['data'] = $stmt->fetchAll();
                        
            $msg = $this->ci->message->apiMessage['success'];

            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (ErrorException $e) {
            return $response->withJson(['error'=>$e->getMessage()]);
        } catch (Exception $e) {
            $this->ci->logger->debug($e); 
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    //배너 조회수 증가
    public function postBannerHitIncrement($request, $response, $args){
        try{
            $params = $this->ci->util->getParams($request);

            $web_event_banner_idx = $params['web_event_banner_idx'];

            if(!isset($web_event_banner_idx) && empty($web_event_banner_idx)) throw new ErrorException('순번값이 없습니다.');

            $stmt = $this->ci->iparkingCmsDb->prepare('
            SELECT
                web_event_banner_idx,
                web_event_banner_hit
            FROM
                iparking_cms.web_event_banner
            WHERE web_event_banner_idx = :web_event_banner_idx
            ');

            $stmt->execute(['web_event_banner_idx' => $web_event_banner_idx]);

            $hit_data = $stmt->fetch();

            $stmt = $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.web_event_banner',[
                'web_event_banner_hit' => $hit_data['web_event_banner_hit']+1
            ], [
                'web_event_banner_idx' => $web_event_banner_idx
            ]);

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
    
}