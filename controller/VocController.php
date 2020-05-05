<?php

class VocController
{

    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function getRelayUrl($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request);

            $stmt = $this->ci->iparkingCmsDb->prepare("
                SELECT 
                    url as text,
                    url as value
                FROM 
                    iparking_cms.relay_history
                GROUP BY url
            ");

            $stmt->execute();
            $data = $stmt->fetchAll();

            $msg = $this->ci->message->apiMessage['success'];
            $msg['data'] = $data;
            return $response->withJson($msg);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getRelayHistory($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);

            $create_date = $params['create_date'] ?? "";
            $create_hour = $params['create_hour'] ?? ""; 
            $url = $params['url'] ?? "";
            $memb_seq = $params['memb_seq'] ?? "";
            $park_seq = $params['park_seq'] ?? "";
            $prdt_seq = $params['prdt_seq'] ?? "";
            $search_keyword = $params['search_keyword'] ?? "";  
            $limit = $params['limit'];
            $offset = $params['offset'];

            $where = "";
            $binds = [];
            if($create_date != "" && $create_hour != "") {
                $where .= " AND create_time BETWEEN :start_create_date_time AND :end_create_time ";
                $start_create_date_time = $create_date." ".$create_hour.":00:00";
                $end_create_date_time = date('Y-m-d H', strtotime($start_create_date_time. ' + 1 hour')).":00:00";
                $binds['start_create_date_time'] = $start_create_date_time;
                $binds['end_create_time'] = $end_create_date_time;
            } else if($create_date != "") {
                $where .= " AND create_time BETWEEN :start_create_time AND :end_create_time ";
                $binds['start_create_time'] = $create_date;
                $binds['end_create_time'] = date('Y-m-d', strtotime($create_date. ' + 1 days'));;
            }
            if($url != "") {
                $where .= " AND url = :url ";
                $binds['url'] = $url;
            }
            if($memb_seq != "") {
                $where .= " AND JSON_EXTRACT(parameter, '$.memb_seq') like :memb_seq ";
                $binds['memb_seq'] = '%'.$memb_seq.'%';
            }
            if($park_seq != "") {
                $where .= " AND JSON_EXTRACT(parameter, '$.park_seq') like :park_seq ";
                $binds['park_seq'] = '%'.$park_seq.'%';
            }
            if($prdt_seq != "") {
                $where .= " AND JSON_EXTRACT(parameter, '$.prdt_seq') like :prdt_seq ";
                $binds['prdt_seq'] = '%'.$prdt_seq.'%';
            }
            if($search_keyword != ""){
                $where .= " AND response like :search_keyword ";
                $binds['search_keyword'] = '%'.$search_keyword.'%';
            }



            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    *
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.relay_history 
                    WHERE 1=1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => 'idx desc'
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }

    public function getDescriptionHistory($request, $response, $args)
    {
        try {

            $params = $this->ci->util->getParams($request, [
                'limit' => 20,
                'offset' => 0
            ]);

            $create_date = $params['create_date'] ?? "";
            $create_hour = $params['create_hour'] ?? ""; 
            $search_keyword = $params['search_keyword'] ?? "";  
            $limit = $params['limit'];
            $offset = $params['offset'];

            $where = "";
            $binds = [];
            if($create_date != "" && $create_hour != "") {
                $where .= " AND create_time BETWEEN :start_create_date_time AND :end_create_time ";
                $start_create_date_time = $create_date." ".$create_hour.":00:00";
                $end_create_date_time = date('Y-m-d H', strtotime($start_create_date_time. ' + 1 hour')).":00:00";
                $binds['start_create_date_time'] = $start_create_date_time;
                $binds['end_create_time'] = $end_create_date_time;
            } else if($create_date != "") {
                $where .= " AND create_time BETWEEN :start_create_time AND :end_create_time ";
                $binds['start_create_time'] = $create_date;
                $binds['end_create_time'] = date('Y-m-d', strtotime($create_date. ' + 1 days'));
            }
            if($search_keyword != ""){
                $where .= " AND res like :search_keyword ";
                $binds['search_keyword'] = '%'.$search_keyword.'%';
            }

            $result = $this->ci->dbutil->paging([
                'db'=>'iparkingCmsDb',
                'select' => '
                    *
                ',
                'query' => '
                    SELECT
                        %%
                    FROM 
                        iparking_cms.description_history 
                    WHERE 1=1 '.$where.'
                ',
                'binds' => $binds,
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => 'description_history_idx desc'
            ]);

            if ($result) {
                $msg = $this->ci->message->apiMessage['success'];
            } else {
                $msg = $this->ci->message->apiMessage['notFound'];
            }  
            
            $result = array_merge($result, $msg);

            return $response->withJson($result);

        } catch (Exception $e) {
            return $response->withJson(['error' => $e->getMessage()]);
        }
    }
}