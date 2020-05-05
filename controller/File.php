<?php

class File
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    // 토스트 클라우드 토큰 생성
    public function toastCloudCreateToken()
    {   
        $requestBody = [
            'json' => [
                'auth' => [
                    'tenantName' => $this->ci->settings['objectStorage']['TenantName'],
                    'passwordCredentials' => [
                        'username' => 'account@parkingcloud.co.kr',
                        'password' => $this->ci->settings['objectStorage']['TenantPassword']
                    ]
                ]                                           
            ]
        ];

        $result = $this->ci->http->post(
            'https://api-compute.cloud.toast.com/identity/v2.0/tokens',
            $requestBody
        );

        return json_decode($result->getBody(), true);
    }

       // 토스트 클라우드 object storage 파일 업로드
       public function toastCloudObjectStorageUpload($file, $container=null, $attachment_type=null)
       {
           // 인증 토큰 발급
           $token_result = $this->toastCloudCreateToken();
   
           $token_id = $token_result['access']['token']['id'];  
           $account = $this->ci->settings['objectStorage']['Account'];
           $object = $file['file']['name'];
           $tmp_fileName = $file['file']['tmp_name'];
   
           // 확장자를 가져오기 위한 로직
           $info = new SplFileInfo($object);
           $extension = $info->getExtension();
           $size = $_FILES['file']['size'];
           $mime_content_type = mime_content_type($tmp_fileName);
        
           $requestBody = [
               'headers' => [
                   'X-Auth-Token' => $token_id,
                   'Content-Type' => $mime_content_type
               ],
               'body' => fopen($tmp_fileName, 'r'),
           ]; 
   
           $objectStorage_result = $this->ci->http->put(
               'https://api-storage.cloud.toast.com/v1/'.$account.'/'.$container.'/'.$object,
               $requestBody
           );
   
           $link = 'https://api-storage.cloud.toast.com/v1/'.$account.'/'.$container.'/'.$object;
           $result = array(
               'statusCode' => $objectStorage_result->getStatusCode(),
               'fileName' => $object,
               'attachment_type' => $attachment_type,
               'path' => $container,
               'size' => $size,
               'extension' => $extension,
               'link' => $link
           );
   
           return $result;
   
       }
   
       // 토스트 클라우드 object storage 파일 다운로드
       public function toastCloudObjectStorageDownload($link, $container=null)
       {
           // @ob_start();
   
           // 인증 토큰 발급
           $token_result = $this->toastCloudCreateToken();
   
           $token_id = $token_result['access']['token']['id'];  
   
           $account = $this->ci->settings['objectStorage']['Account'];
   
           list($url, $object) = explode($container."/", $link);
   
           $requestBody = [
               'headers' => [
                   'X-Auth-Token' => $token_id
               ]
           ];
   
           $url =  'https://api-storage.cloud.toast.com/v1/'.$account.'/'.$container.'/'.$object;
   
           $objectStorage_result = $this->ci->http->get(
               $url,
               $requestBody, ['stream' => true]
           );
   
           $body = $objectStorage_result->getBody()->getContents(); //->getMetadata()['uri'];
           print_r($body);
   
           $body_explode = explode('PK', $body);
           $header_info =  $body_explode[0];
   
           // send the headers
           header("Content-Disposition: attachment; filename=$object;");
           header('Content-Description: File Transfer');
           header('Content-Transfer-Encoding: binary');
           header('Cache-Control: public, must-revalidate, max-age=0');
           header('Pragma: public');
           header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
           header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
           header('Content-Type: application/force-download');
           header('Content-Type: application/octet-stream', false);
           header('Content-Type: application/download', false);
           header('Content-Type: application/pdf', false);   
       }

       public function makeExcelDownload($fileName, $dataList)
       {
          error_reporting(E_ALL);
          ini_set('display_errors', TRUE);
          ini_set('display_startup_errors', TRUE);
          ini_set('memory_limit','-1');
          ini_set('max_execution_time', 3600);
          ini_set('set_time_limit', 0);
  
          @ob_start();
          
          require_once '../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
          
          // Create new PHPExcel object
          $objPHPExcel = new PHPExcel();
  
          // Set document properties
          $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                                      ->setLastModifiedBy("Maarten Balliauw")
                                      ->setTitle("Office 2007 XLSX Test Document")
                                      ->setSubject("Office 2007 XLSX Test Document")
                                      ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                                      ->setKeywords("office 2007 openxml php")
                                      ->setCategory("Parking Calculate file");
          
          // Add some data
          foreach($dataList as $idx => $data) {
              
              if($idx == 0) {
                  $objPHPExcel->getActiveSheet($idx)->setTitle($data['name']);
              }
              else {
                  $myWorkSheet = new PHPExcel_WorkSheet($objPHPExcel, $data['name']);
                  $objPHPExcel->addSheet($myWorkSheet, $idx);
              }
  
              if($data['type'] == 'countHistory'){  
                  //COLUMN NAMES
                  $rowNumber = 1; 
                  $col = 'A'; 
                  $count = 0;
                  foreach($data['key'] as $i => $heading) { 
                      $objPHPExcel->setActiveSheetIndex($idx)->setCellValue($col.$rowNumber, $heading); 
                      $objPHPExcel->getActiveSheet($idx)->getColumnDimension($col)->setWidth($data['size'][$count]);                
                      $col++;
                      $count++;
                  } 
                  
                  //RESULTS
                  $rowNumber = 2;
                  foreach($data['data'] as $key => $val) {
                      $col = 'A'; 
                      foreach($data['column'] as $cols) {
                          if($cols == 'tid') {
                              $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $val[$cols]); 
                              $objPHPExcel->getActiveSheet()->getStyle($col.$rowNumber)->getNumberFormat()->setFormatCode('0000');
                          } else if($cols == 'appltime' || $cols == 'applcanceltime') {
                              $dateValue = explode(" ", $val[$cols]);
                              $val[$cols] = $dateValue[0];
                              if($val[$cols] == '0000-01-01') {
                                  $val[$cols] = '-';
                              }
                              $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $val[$cols]);
                          } else {
                              $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $val[$cols]); 
                          }
                          $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $val[$cols]); 
                          // $objPHPExcel->getActiveSheet($idx)->getStyle($col.$rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                          $col++;
                      }
                      $rowNumber++;
                  }
              }
              
              else if($data['type'] == 'dayTotal'){
  
                  $objPHPExcel->setActiveSheetIndex($idx)->mergeCells($data['columnTitle']['cell']);
                  $objPHPExcel->setActiveSheetIndex($idx)->setCellValue('A3', $data['columnTitle']['text']);
                  // $objPHPExcel->getActiveSheet($idx)->getStyle($data['columnTitle']['cell'])->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                  
                  // 상단 값 배경색 적용
                  $objPHPExcel->getActiveSheet()
                  ->getStyle('A3')
                  ->getFill()
                  ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                  ->getStartColor()
                  ->setARGB('FF00CFFF');
  
                  //COLUMN NAMES
                  $rowNumber = 4; 
                  $col = 'A'; 
                  foreach($data['key'] as $heading) { 
                      $objPHPExcel->setActiveSheetIndex($idx)->setCellValue($col.$rowNumber, $heading); 
                      $objPHPExcel->getActiveSheet($idx)->getColumnDimension($col)->setWidth(17);
                      // $objPHPExcel->getActiveSheet($idx)->getStyle($col.$rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                      
                      // 상단 헤더 배경색 적용
                      $objPHPExcel->getActiveSheet()
                      ->getStyle($col.$rowNumber)
                      ->getFill()
                      ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                      ->getStartColor()
                      ->setARGB('FF00CFFF');               
                      $col++;
                  } 
    
                  //RESULTS
                  $rowNumber = 5;
                  foreach($data['data'] as $dataIdx => $dataRows) {
  
                      foreach($dataRows as $typeKey => $typeRows) {
                          $col = 'A';                          
                          // 여기에 하나 삽입/
                          if($typeRows) {
                              $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $typeKey);
                          }
  
                          foreach($typeRows as $dateKey => $dateRows){
                              $col  = 'B';
                              $dateTime = explode(" ", $dateKey);
                              $date = $dateTime[0];
                              $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $date);
                              foreach($dateRows as $key => $val) {
                                  $col++;
                                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $val); 
                              }                 
                              $rowNumber++;
                          }                
                      }             
                  }
  
                  $mergeCol = 'A';
                  $col = 'A';
                  $mergeCol++;
                  $objPHPExcel->getActiveSheet($idx)->mergeCells($col.$rowNumber.":".$mergeCol.$rowNumber);
                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, '총합계');
                  $objPHPExcel->getActiveSheet($idx)->getStyle($col.$rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                  $col++;
                  $col++;
                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $data['totalPrice']);
                  $col++;
                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $data['totalCommission']);
                  $col++;
                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $data['totalShare']);
                  $col++;
                  $objPHPExcel->getActiveSheet($idx)->setCellValue($col.$rowNumber, $data['totalVillBePayment']);
  
                  // 총합계 배경색 적용
                  $objPHPExcel->getActiveSheet()
                  ->getStyle('A'.$rowNumber.':'.$col.$rowNumber)
                  ->getFill()
                  ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                  ->getStartColor()
                  ->setARGB('FF00CFFF');
                  
                  //$objPHPExcel->getActiveSheet($idx)->calculateColumnWidths();
              }
  
          }
  
          // Redirect output to a client’s web browser (Excel5)
          header('Content-Type: application/vnd.ms-excel');
          header('Content-Disposition: attachment;filename="'.$fileName.'"');
          header('Cache-Control: max-age=0');
          // If you're serving to IE 9, then the following may be needed
          header('Cache-Control: max-age=1');
  
          // If you're serving to IE over SSL, then the following may be needed
          header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
          header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
          header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
          header ('Pragma: public'); // HTTP/1.0
  
          $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
          $objWriter->save('php://output', 'w');
          $data = @ob_get_contents();
          @ob_end_clean();
  
          return $data;
      }


}