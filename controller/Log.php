<?php

class Log
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function relayHistoryInsert($url, $method, $param, $request_enc=null)
    {
        
        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.relay_history', [[
            'url' => $url,
            'method' => $method,
            'parameter' => $param,
            'request_enc' => $request_enc,
            'create_time' => date('Y-m-d H:i:s')
        ]]);

        $last_relay_index = $this->ci->iparkingCmsDb->lastInsertId();
        
        return $last_relay_index;
    }

    public function relayHistoryUpdate($idx, $res)
    {
        $this->ci->dbutil->update('iparkingCmsDb', 'iparking_cms.relay_history', [
            'response' => $res,
            'update_time' => date('Y-m-d H:i:s')
        ], 
        [
            'idx' => $idx
        ]);
    }

    public function decryptHistory($parameter, $res)
    {
        $this->ci->dbutil->insert('iparkingCmsDb', 'iparking_cms.description_history', [[
            'parameter' => $parameter,
            'res' => $res,
            'create_time' => date('Y-m-d H:i:s')
        ]]);
    }
}


