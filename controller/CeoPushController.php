<?php

use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use paragraph1\phpFCM\Notification;


class CeoPushController {

    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // fcm 안드
    public function postErrorPushCeo($request, $response, $args)
    {
        try {
            
            $params = $this->ci->util->getParams($request);

            $apiKey = $params['apiKey'] ?? null;
            $token = $params['token'] ?? null;
            $title = $params['title'];
            $msg = $params['msg'];

            // if( $apiKey == null ) throw new Exception ("서버키가 없습니다.");
            // if( $token == null ) throw new Exception ("토큰이 없습니다.");
            $apiKey = 'ceo';
            $apiKey =$this->ci->settings['serverKey'][$apiKey];
                     
            // 아이파킹 토큰
            // $token = 'djg9ROSEYlc:APA91bHCmhhkj2Kw8rInut5Vk1NhYnNoYtafoSwHqCBUTMIb_ulBVxg9F0YHj0X-JzI4QfNr9_LTKdp71iiFPALQ32mJL7bTUdxKpgjAEWYo_oS__H0eO0ddKI3301yApQKdeMQ6ivZUV65U67dYAumCTnTDrtfk6A';
            // ceo 토큰
            // $token = 'APA91bHowXIvPb_zSO5erV0anCsZS-5Xfirf42FjCQR6S49pKj-f32xZ8bhEtN13P3Sm7UAWta9eT1FbG8uw8DYvht3KWLf0fJwb9UH2kP5dKeNRFXhsJMtFDThUmHIAuTGsPB50Uorc';
            //ios
            $token = 'fktevb0zH00:APA91bE0tWEbnNFaDJMrjp2xNcJcgWpR2w-n8d3Wu7WRvJ1VwwYoLJIZfWmlQ65-1yFJ0eK-_qDB4PGMMM4mjGDITLJ4ALL47NnMvktq5R40U80766XKuE61FjYi5H_uw_C4Ebxit2CW';
            $client = new Client();
    
            $client->setApiKey($apiKey);
            $client->injectHttpClient($this->ci->http);
            $note = new Notification($title, $msg);
            $note->setIcon('notification_icon_resource_name')
                ->setColor('#ffffff')
                ->setBadge(1);
            
            // $data['notification'] = $note;
            // return print_r($data);

            $message = new Message();
            $message->addRecipient(new Device($token));
            $message->setNotification($note);
            // $message['notification']['title']= 'title';
            
            // return print_r($message);
            $response = $client->send($message);
    
            
            $push_result = json_decode($response->getBody(), true);
            
            return print_r($push_result);
    
            
        } catch(Exception $e) {
            print_r($e->getMessage());
        }
    }

    
    //fcm ios
    public function postErrorPushCeo2($request, $response, $args)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        // $token = 'fktevb0zH00:APA91bE0tWEbnNFaDJMrjp2xNcJcgWpR2w-n8d3Wu7WRvJ1VwwYoLJIZfWmlQ65-1yFJ0eK-_qDB4PGMMM4mjGDITLJ4ALL47NnMvktq5R40U80766XKuE61FjYi5H_uw_C4Ebxit2CW';
        // $token = 'c4qaE-9suek:APA91bEnhTSCwtSJ5E8SJqjjRGKcSaYoZtyLg7MXKtU6GLPqsyWwfEZLL2EWoj4ByWVxW4pvGmG2RWr2MhPa2doDFQPSnr5-iQh1wFP8BoxLNfrmGPX2GJ3tgf9GbO8dAgYZEKkhkY6F';
        $token = '0e5903f6a79fa5260fc882e6757fa94e9eda7c82ddf31c7a3ebccb4aa8f675ca';
        
        // $apiKey = 'ceo';
        // $apiKey =$this->ci->settings['serverKey'][$apiKey];
        $apiKey = 'ceo';
        $apiKey =$this->ci->settings['serverKey'][$apiKey];
        $serverKey = $apiKey;
        $title = "Title";
        $body = "Body of the message";
        $notification = array('title' =>$title , 'text' => $body, 'sound' => 'default', 'badge' => '1');
        $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');
        $json = json_encode($arrayToSend);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key='. $serverKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,
        
        "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        //Send the request
        $response = curl_exec($ch);
        //Close request
        if ($response === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
           
    }

    //fcm ios2
    public function postErrorPushCeo3($request, $response, $args)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';



        $tokens = array();
        // ceo 토큰
        // $tokens = 'APA91bHowXIvPb_zSO5erV0anCsZS-5Xfirf42FjCQR6S49pKj-f32xZ8bhEtN13P3Sm7UAWta9eT1FbG8uw8DYvht3KWLf0fJwb9UH2kP5dKeNRFXhsJMtFDThUmHIAuTGsPB50Uorc';
            //ios
        $tokens[0] = 'fktevb0zH00:APA91bE0tWEbnNFaDJMrjp2xNcJcgWpR2w-n8d3Wu7WRvJ1VwwYoLJIZfWmlQ65-1yFJ0eK-_qDB4PGMMM4mjGDITLJ4ALL47NnMvktq5R40U80766XKuE61FjYi5H_uw_C4Ebxit2CW';


        $myMessage = "Message Test"; 

        $message = array("message" => $myMessage);
        $message_status = send_notification($tokens, $message);
        echo $message_status;


        $fields = array(
            'registration_ids' => $tokens,
            'data' => $message
        );
        $apiKey = 'ceo';
        $apiKey =$this->ci->settings['serverKey'][$apiKey];
        $key = $apiKey;
        $headers = array(
            'Authorization:key =' . $key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);           
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
	}
	
    
    //apns 푸시 
	public function postErrorPushCeo4($request, $response, $args)
    {
        // set time limit to zero in order to avoid timeout
        set_time_limit(0);
        
        
        // this is the pass phrase you defined when creating the key
        $passphrase = 'fdk2075';
        
        // you can post a variable to this string or edit the message here
        if (!isset($_POST['msg'])) {
        $_POST['msg'] = "Notification message here!";
        }
        
        // tr_to_utf function needed to fix the Turkish characters
        $message = $_POST['msg'];
        
        // load your device ids to an array
        $deviceIds = array(
        '1595b78fab328e8e76d7a4fcd1abd00ae73f5865f79bbe022c43cfa45dd1b3cb'
        );
        
        // this is where you can customize your notification
        $payload = '{"aps":{"alert":"' . $message . '","sound":"default"}}';
        
        $result = 'Start' . '<br />';
        
        ////////////////////////////////////////////////////////////////////////////////
        // start to create connection
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', '/Users/park/Desktop/apns_pro.p12');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        
        echo count($deviceIds) . ' devices will receive notifications.<br />';
        
        foreach ($deviceIds as $item) {
            // wait for some time
            sleep(1);
            
            // Open a connection to the APNS server
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        
            if (!$fp) {
                exit("Failed to connect: $err $errstr" . '<br />');
            } else {
                echo 'Apple service is online. ' . '<br />';
            }
        
            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $item) . pack('n', strlen($payload)) . $payload;
            
            // Send it to the server
            $result = fwrite($fp, $msg, strlen($msg));
            
            if (!$result) {
                echo 'Undelivered message count: ' . $item . '<br />';
            } else {
                echo 'Delivered message count: ' . $item . '<br />';
            }
        
            if ($fp) {
                fclose($fp);
                echo 'The connection has been closed by the client' . '<br />';
            }
        }
        
        echo count($deviceIds) . ' devices have received notifications.<br />';
        
        // function for fixing Turkish characters
        function tr_to_utf($text) {
            $text = trim($text);
            $search = array('Ü', 'Þ', 'Ð', 'Ç', 'Ý', 'Ö', 'ü', 'þ', 'ð', 'ç', 'ý', 'ö');
            $replace = array('Ãœ', 'Åž', '&#286;ž', 'Ã‡', 'Ä°', 'Ã–', 'Ã¼', 'ÅŸ', 'ÄŸ', 'Ã§', 'Ä±', 'Ã¶');
            $new_text = str_replace($search, $replace, $text);
            return $new_text;
        }
        
        // set time limit back to a normal value
        set_time_limit(30);
	}

    //apns 푸시  2
	public function postErrorPushCeo5($request, $response, $args)
    {
        $deviceToken = '1595b78fab328e8e76d7a4fcd1abd00ae73f5865f79bbe022c43cfa45dd1b3cb';  // masked for security reason
        // Passphrase for the private key (ck.pem file)
        // $pass = '';

        // Get the parameters from http get or from command line
        $message = $_GET['message'] or $message = $argv[1] or $message = 'test msg';
        $badge = (int)$_GET['badge'] or $badge = (int)$argv[2];
        $sound = $_GET['sound'] or $sound = $argv[3];

        // Construct the notification payload
        $body = array();
        $body['aps'] = array('notification' => $message);
        if ($badge)
        $body['aps']['badge'] = $badge;
        if ($sound)
        $body['aps']['sound'] = $sound;


        /* End of Configurable Items */

        $ctx = stream_context_create();
        
        // 상대경로
        // stream_context_set_option($ctx, 'ssl', 'local_cert', '../push_keychain/apns_pro.p12');

        // 절대경로
        stream_context_set_option($ctx, 'ssl', 'local_cert', '/home/work/cms-server/push_keychain/apns_pro.p12');
        // assume the private key passphase was removed.
        // stream_context_set_option($ctx, 'ssl', 'passphrase', 'fdk2075');

        
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        // for production change the server to ssl://gateway.push.apple.com:2195
        if (!$fp) {
        print "Failed to connect $err $errstr\n";
        return;
        }
        else {
        print "Connection OK\n";
        }

        $payload = json_encode($body);
        
        print_r($body);

        $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
        print "sending message :" . $payload . "\n";
        // print_r($msg);

        fwrite($fp, $msg);
        fclose($fp);
        
    }
    
}