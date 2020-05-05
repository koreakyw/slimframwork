<?php

class DbUtil
{

    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // query 사용방법.
    // placeholder 없음. SQL 인젝션의 위험이 있음.
    // $st = $pdo->("INSERT INTO table (col1, col2, col3) values ($val1, $val2, $val3)");
    //  
    // 이름 없는 placeholder. SQL 인젝션 방지.
    // $st = $pdo->('INSERT INTO table (col1, col2, col3) values (?, ?, ?)');
    //  값을 넘겨주고 실행
    // $st->execute(['val1', 'val2', 'val2']);
    //   
    // 이름 있는 placeholder. SQL 인젝션 방지
    // $st = $pdo->("INSERT INTO table (col1, col2, col3) value (:col1, :col2, :col3)");
    // 값을 넘겨주고 실행
    // $st->execute([':col1'=>'val1', ':col2'=>'val2', ':col3'=>'val3']);

    public function getDb($name) {
        if ($name=='iparkingCloudDb') return $this->ci->iparkingCloudDb;
        if ($name=='iparkingCmsDb') return $this->ci->iparkingCmsDb;
    }

    private function escapeTableName($str) {
        if ($str[0]=='"') return $str; // 이미 따옴표가 붙어있으면 그대로 패스
        else return '"'.str_replace('.', '"."', $str).'"';
    }

    public function paging($cmd)
    {

        $db = $this->getDb($cmd['db']);
 
        $countQuery = isset($cmd['countQuery']) ? $cmd['countQuery'] : $cmd['query'];
        $totalCount = $this->count(
            $cmd['db'],
            str_replace('%%', 'count(*)', $countQuery),
            $cmd['binds']
        );

        
        $stmt = $db->prepare(
            str_replace('%%', $cmd['select'], $cmd['query'])
            .( isset($cmd['orderby']) ? ' ORDER BY '.$cmd['orderby'] : '' )
            .' LIMIT '.$cmd['limit'].' OFFSET '.$cmd['offset']
        );
        $stmt->execute($cmd['binds']);

        $data = $this->fetchAllWithJson($stmt);

        $currentPage = ($cmd['offset']/$cmd['limit']) + 1;
        $pageCount = ceil( (int)$totalCount / $cmd['limit'] );
        $pageInfo = [
            'limit' => $cmd['limit'],
            'offset' => $cmd['offset'],
            'totalCount' => (int) $totalCount,
            'currentPage' => $currentPage,
            'pageCount' => $pageCount
        ];

        if ($data[0]===null) {
            $data = [];
        }

        return [ 'data' => $data, 'pageInfo'=> $pageInfo ];
        
    }

    public function count($db, $query, $binds = [])
    {
        $stmt = $this->getDb($db)->prepare($query);
        $stmt->execute($binds);

        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
              $count += $row[0];
        }

        return (int)$count;
    }

    /**
    * json 파싱하여 리스트 어레이를 리턴
    * 쿼리 결과 없으면 빈 어레이를 리턴한다.
    */
    public function fetchAllWithJson($stmt)
    {
        $list = [];
        while ($row = $stmt->fetch()) {
            foreach ($row as &$value) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                /////////////////////
                // json decode
                ////////////////////
                if ($this->isJson($value)) {
                    $value = json_decode($value, true);
                } 
                // ////////////////////////
                // // 숫자 저글링
                // ////////////////////////
                // else {
                //     $value = $this->ci->util->juggleNumber($value);
                // }
            }

            $list[] = $row;
        }

        if (!$list) {
            return []; // list($item) 과 같은 식으로 받을 수 있게
        } else {
            return $list;
        }
    }

    private function isJson($value)
    {
        return (
        ( substr($value, 0, 1) == '{' && substr($value, -1)=='}' )
        || ( substr($value, 0, 1) == '[' && substr($value, -1)==']' )
        );
    }

    public function insert($db, $table, $list) {

        $cols = [];
        $valsList = [];
        $binds = [];

        foreach($list as $data) {
            $vals = [];
            foreach($data as $col=>$val) {
                if ( !count($valsList) ) $cols[] = $col;
                $vals[] = '?';
                $binds = $this->bindValue($binds, $val);
            }
            $valsList[] = '('.implode(",", $vals).')';
        }

        $query = '
            INSERT INTO '.$table.'
            ('.implode(',', $cols).')
            VALUES '.implode(',',$valsList).'
        ';

        //return $binds;
        $stmt = $this->getDb($db)->prepare($query);
        $stmt->execute($binds);
    }

    public function insertUpdate($db, $table, $list, $duplicateUpdateList=[])
    {
        $cols = [];
        $valsList = [];
        $binds = [];

        foreach($list as $data) {
            $vals = [];
            foreach($data as $col=>$val) {
                if ( !count($valsList) ) $cols[] = $col;
                $vals[] = '?';
                $binds = $this->bindValue($binds, $val);
            }
            $valsList[] = '('.implode(",", $vals).')';
        }

        $duplicateList = [];
        foreach($duplicateUpdateList as $duplicateRow) {
            $duplicateList[] = $duplicateRow.' = VALUES('.$duplicateRow.')';
        }

        $query = '
            INSERT INTO '.$table.'
            ('.implode(',', $cols).')
            VALUES '.implode(',', $valsList).'
            ON DUPLICATE KEY UPDATE '.implode(', ', $duplicateList).'
        ';
        
        $stmt = $this->getDb($db)->prepare($query);
        $stmt->execute($binds);
    }

    public function update($db, $table, $data, $condition) {

        $binds = [];
        $set = [];
        $where = [];

        foreach($data as $key=>$val) {
            $set[] = $key . ' = ?';
            $binds = $this->bindValue($binds, $val);
        }

        foreach($condition as $key=>$val) {
            $where[] = $key . ' = ?';
            $binds = $this->bindValue($binds, $val);
        }

        $stmt = $this->getDb($db)->prepare("
            UPDATE ".$table."
            SET ".implode(', ', $set)."
            WHERE ".implode(' AND ', $where)."
        ");
        return $stmt->execute($binds);

    }

    private function bindValue($binds, $val) {
        if (is_array($val)) $binds[] = json_encode($val, JSON_UNESCAPED_UNICODE);
        else if ($val===true) $binds[] = 'true';
        else if ($val===false) $binds[] = 'false';
        else $binds[] = $val;

        return $binds;
    }

    public function listToArray($rows, $col) {
        $list = [];
        foreach($rows as $row) $list[] = $row[$col];
        return $list;
    }

    public function listToInQuery($rows, $col) {
        $array = $this->listToArray($rows, $col);
        return $this->arrayToInQuery($array);
    }

    public function arrayToInQuery($array) {
        return "'". implode("','", $array) ."'";
    }

    public function tableListQuery()
    {
        $result = 'SHOW Tables from business_note';

        return $result;
    }

    public function columnListQuery($tableName)
    {
        $result = 'SHOW FULL COLUMNS FROM business_note.' . $tableName;

        return $result;
    }

    public function delete($table, $condition)
    {
        $bind = [];

        foreach ($condition as $key => $val) {
            $where[] = $key . ' = ?';
            $binds = $this->bindValue($binds, $val);
        }
        
        $stmt = $this->ci->businessNoteDb->prepare('
            DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $where)
        );

        return $stmt->execute($binds);
    }


    public function fetch($db, $query, $binds) {
        $stmt = $this->getDb($db)->prepare($query);
        $stmt->execute($binds);
        $rows = $this->fetchAllWithJson($stmt);
        return $rows[0];
    }
    
    public function fetchAll($db, $query, $binds) {
        $stmt = $this->getDb($db)->prepare($query);
        $stmt->execute($binds);
        $rows = $this->fetchAllWithJson($stmt, false);
        return $rows;
    }

}
