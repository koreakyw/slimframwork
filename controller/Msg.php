<?php

class Msg
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public $oldMessage = [
        'success' => [
            'message' => 'success',
            'resultcode' => "1"
        ],
        'error' => [
            'message' => 'error',
            'resultcode' => "0"
        ]
    ];
    
    public $apiMessage = [
        'point_cancel_success' => [
            'code' => '00',
            'message' => '포인트 취소 성공'
        ],
        'point_cancel_fail' => [
            'code' => '99',
            'message' => '포인트 취소 실패'
        ],
        'point_cancel_relay_fail' => [
            'code' => '98',
            'message' => '포인트 취소 성공 후 B2B 연동 오류'
        ],
        'fail' => [
            'code' => 40000,
            'message' => '실패'
        ],
        'send_fail' => [
            'code' => 40001,
            'message' => '네트워크 장애로 인하여 관리자에게 문의 해주세요.'
        ],
        'exceeded_count' => [
            'code' => 40002,
            'message' => '하루 최대 5회까지 발송할 수 있습니다.'
        ],
        'required' => [
            'code' => 40003,
            'message' => '필수 값 오류'
        ],
        'max_check' => [
            'code' => 40004,
            'message' => '즐겨찾기는 최대 10개까지'.PHP_EOL.' 추가 할 수 있습니다.'
        ],
        'success' => [
            'code' => 20000,
            'message' => '성공'
        ],
        'duplicateFalse' => [
            'code' => 20000,
            'message' => '중복 없음'
        ],
        'loginSuccess'=>[
            'code' => 20000,
            'message' => '로그인 성공'
        ],
        'newUserSuccess'=>[
            'code' => 20001,
            'message' => '기본 회원정보 입력 필요합니다.'
        ],
        'loginWaitSuccess'=>[
            'code' => 20002,
            'message' => '관리자의 승인 대기중인 사용자입니다.'
        ],
        'noGoogleId' => [
            'code' => 20002,
            'message' => '토큰정보를 확인해주세요.'
        ],
        'duplicateTrue'=>[
            'code' => 40000,
            'message' => '중복된 내용이 있습니다.'
        ],
        'notFound'=>[
            'code' => 40000,
            'message' => '결과를 찾을 수 없습니다.'
        ],
        'userNotConfirm'=>[
            'code' => 40000,
            'message' => '미승인 사용자입니다.'
        ],
        'notFoundParams'=>[
            'code' => 40001,
            'message' => '검색어가 없습니다.'
        ],
        'notSendEmail' =>[
            'code' => 40002,
            'message' => '이메일 발송을 실패하였습니다.'
        ],
        'banner_main_count_over' =>[
            'code' => 40003,
            'message' => '메인 베너는 10개를 초과할수 업습니다.'
        ],
        'banner_service_count_over' =>[
            'code' => 40004,
            'message' => '이메일 발송을 실패하였습니다.'
        ],
        'ceoCaution'=>[ 
            'code' => 90001,
            'message' => 'CEO 계정의 경우 회원 삭제 시 더 이상'.PHP_EOL.'CEO 서비스를 이용하실 수 없습니다.'.PHP_EOL.'회원정보를 삭제 하시겠습니까?'
        ],
        'noneMember'=>[
            'code' => 90002,
            'message' => '비밀번호가 일치하지 않습니다.'
        ],
        'ceoImpossibleMobile'=>[ 
            'code' => 90003,
            'message' => 'CEO 계정의 경우 모바일에서'.PHP_EOL.'탈퇴가 불가능합니다.'.PHP_EOL.'웹사이트를 이용해주세요.'
        ],
        'existProduct'=>[
            'code' => 90004,
            'message' => '보유중인 주차 상품이 있어 탈퇴가 불가능합니다.'.PHP_EOL.'사용 완료 후 탈퇴가 가능 합니다.'
        ],
        'inCar'=>[
            'code' => 90005,
            'message' => '현재 등록하신 차량이 주차장에 입차 상태입니다.'.PHP_EOL.'출차 하신 후 탈퇴가 가능 합니다.'
        ],
        'adminImpossible'=>[
            'code' => 90006,
            'message' => '관리자 권한을 갖고 있는 계정입니다.'.PHP_EOL.'관리자 권한을 삭제 후 탈퇴가 가능합니다.'
        ],
        'membersImpossible'=>[
            'code' => 90007,
            'message' => 'members로 가입된 회원의 경우 탈퇴가 불가능 합니다.'.PHP_EOL.'고객센터(1588-5783)로 문의해주세요.'
        ]
    ];

}