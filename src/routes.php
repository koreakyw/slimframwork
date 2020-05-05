<?php
$app->group('/cron/', function () use ($app) {
    $app->get('webEventBannerScheduler', '\ScheduleController:getWebEventBannerOnOffUpdate');
    $app->get('appEventScheduler', '\ScheduleController:getEventAppOnOffUpdate');
});

$app->group('/payment/', function () use ($app) {
    $app->get('inicis', '\ParkingProductController:getPaymentInicis');
    $app->get('point', '\ParkingProductController:getPaymentPoint');
    $app->get('inicisExcelDownload', '\ParkingProductController:getPaymentInicisExcelDownload');
    $app->get('pointExcelDownload', '\ParkingProductController:getPaymentPointExcelDownload');
    $app->get('variableValue', '\ParkingProductController:getVariableValue');
});

// Routes
$app->group('/admin/', function () use ($app) {
    $app->group('board/', function () use ($app) {
        $app->group('notice/', function () use ($app) {
            $app->get('list', '\AdminController:getNoticeList');
            $app->get('detail/{notice_seq}', '\AdminController:getNoticeDetail');
            $app->post('add', '\AdminController:postNoticeAdd');
            $app->put('detail/{notice_seq}', 'AdminController:putNoticeDetail');
            $app->delete('delete', '\AdminController:deleteNoticeDelete');
            $app->patch('onOff/{notice_seq}', '\AdminController:patchNoticeOnOff');
        });
        $app->group('event/', function () use ($app) {
            $app->get('list', '\AdminController:getEventList');
            $app->get('detail/{event_seq}', '\AdminController:getEventDetail');
            $app->post('add', '\AdminController:postEventAdd');
            $app->put('detail/{event_seq}', '\AdminController:putEventDetail');
            $app->delete('delete', '\AdminController:deleteEventDelete');
            $app->patch('onOff/{event_seq}', '\AdminController:patchEventOnOff');
            $app->post('setOrder', '\AdminController:postEventSetOrder');
            $app->get('maxOrder', '\AdminController:getEventMaxOrder');
            
            // 이벤트 배너 
            // $app->group('banner/', function () use ($app) {
            //     $app->get('projectList', '\AdminController:getEventBannerProjectList');
            //     $app->get('linkTypeList', '\AdminController:getEventBannerLinkTypeList');
            //     $app->get('list', '\AdminController:getEventBannerList');
            //     $app->get('detail/{banner_idx}', '\AdminController:getEventBannerDetail');
            //     $app->post('add', '\AdminController:postEventBannerAdd');
            //     $app->put('detail/{banner_idx}', '\AdminController:putEventBannerDetail');
            //     $app->delete('delete', '\AdminController:deleteEventBannerList');
            //     $app->get('detail/{banner_idx}/deleteHistorys', '\AdminController:getEventBannerDetailDeleteHistorys');
            // });

        });
        $app->group('banner/', function () use ($app){
            $app->group('web/', function () use ($app){
                $app->get('list', '\AdminController:getBannerList');
                $app->get('detail/{web_event_banner_idx}', '\AdminController:getBannerDetail');
                $app->get('typesMaxOrder', '\AdminController:getBannerMaxOrder');
                $app->post('add', '\AdminController:postBannerAdd');
                $app->put('detail/{web_event_banner_idx}', '\AdminController:putBannerDetail');
                $app->delete('delete', '\AdminController:deleteBanner');
                $app->patch('onOff/{web_event_banner_idx}', '\AdminController:patchBannerOnoff');
                $app->post('setOrder', '\AdminController:postBannerSetOrder');
            });
        });

        $app->post('fileUpload', '\AdminController:postBoardFileUpload');
        $app->get('fileDownload', '\AdminController:getBoardFileDownload');
    });

    $app->get('findParkinglot', '\AdminController:getFindParkinglot');
    // $app->group('/', function () use ($app) {
    //     $app->group('event/', function () use ($app) {
    //         $app->get('list', '\AdminController:getWebEventList');
    //         $app->get('detail/{web_event_seq}', '\AdminController:getWebEventDetail');
    //         $app->post('add', '\AdminController:postWebEventAdd');
    //         $app->put('detail/{web_event_seq}', '\AdminController:putWebEventDetail');
    //         $app->delete('delete', '\AdminController:deleteWebEventDelete');
    //         $app->patch('onOff/{web_event_seq}', '\AdminController:patchWebEventOnOff');
    //     });   
    // });

    $app->put('changePassword', '\AuthController:putChangePassword');    
    $app->get('getSession', '\AuthController:getSession');

})->add($onlyMember);



$app->group('/api/', function () use ($app) { 
    $app->post('meassageTest', '\CeoPushController:postErrorPushCeo');
    $app->post('meassageTest2', '\CeoPushController:postErrorPushCeo2');
    $app->post('meassageTest3', '\CeoPushController:postErrorPushCeo3');
    $app->post('meassageTest4', '\CeoPushController:postErrorPushCeo4');
    $app->post('meassageTest5', '\CeoPushController:postErrorPushCeo5');
 
    $app->group('board/', function () use ($app) {
        $app->group('notice/', function () use ($app) {
            $app->get('list', '\BoardController:getNoticeList');
            $app->get('detail/{notice_seq}', '\BoardController:getNoticeDetail');
        });
        $app->group('event/', function () use ($app) {
            $app->get('list', '\BoardController:getEventList');
            $app->get('detail/{event_seq}', '\BoardController:getEventDetail');
            $app->get('mainPopUp', '\BoardController:getEventMainPopUp');  
        });
        $app->group('banner/', function () use ($app){
            $app->post('hitIncrement', '\BoardController:postBannerHitIncrement');  
            $app->get('postingList/{position_check_val}', '\BoardController:getBannerPostingList'); 
        });
    });
    
    $app->group('markUp/', function () use ($app) {
        $app->post('list', '\MarkUpController:postMarkUpList');
        $app->post('parkinglotList', '\MarkUpController:postParkinglotList');
        $app->post('allianceDiscountList', '\MarkUpController:postAllianceDiscountList');
    });

    $app->group('favorite/', function () use ($app){
        $app->post('add', '\FavoriteController:postFavoriteAdd');
        $app->post('delete', '\FavoriteController:postFavoriteDelete');
    });

    $app->group('coupon/', function () use ($app) { //쿠폰관련
        $app->get('couponListData', 'CouponController:getCoponList');
    });

    $app->group('version/', function () use ($app) {    //버전체크
        $app->get('mobileVersionChek', 'MobileVersionCheckController:getMobileVersionCheck');
    });

    $app->group('userinfo/', function () use ($app) {    //유저관련
        $app->post('findId', 'UserInfoController:getFindId');    //아이디찾기
        $app->get('carList', 'UserInfoController:getCarList');    //차량리스트
        $app->get('paymentCardList', 'UserInfoController:getPaymentCardList');    //결제카드리스트
        $app->get('couponList', 'UserInfoController:getCouponList');    //쿠폰리스트
        $app->get('info', 'UserInfoController:getUserInfo');    //회원정보
        $app->post('checkMemb', '\UserInfoController:postCheckMemeber');    // 회원체크
        $app->put('dropMemb/{memb_seq}', '\UserInfoController:putDropMember');     //회원탈퇴 
    
    });

    $app->group('product/', function () use ($app) {    //상품관련
        $app->get('productInfo', 'ParkingProductController:getProductInfo');
    });


    $app->group('point/', function () use ($app) {
        // $app->get('list', '\PointController:getPointCardList');
        // $app->post('add', '\PointController:postPointCardAdd');
        // $app->put('isDeleted', '\PointController:putPointCardIsDeleted');
        $app->get('limit', '\PointController:getPointLimitCheck');
        $app->post('reservation', '\PointController:postPointReservation');
        $app->group('bluePoint/', function () use ($app) {
            $app->get('info', '\PointController:getBluePointInfo');
            $app->post('auth', '\PointController:postBluePointAuth');
            $app->post('payment', '\PointController:postBluePointPayment');
            $app->post('paymentCancel', '\PointController:postBluePointPaymentCancel');
        });
        $app->group('lotte/', function () use ($app) {
            $app->get('info', '\PointController:getLPointInfo');
            $app->post('use', '\PointController:postLPointUse');
            $app->post('cancel', '\PointController:postLPointCancel');
            $app->post('reverseCancel', '\PointController:postLPointUseReverseCancel');
            $app->post('accmulate', '\PointController:postLPointAccumulate');
            $app->post('accmulateCancel', '\PointController:postLPointAccumulateCancel');
            $app->post('accmulateReverseCancel', '\PointController:postLPointAccmulateReverseCancel');
        });
        $app->group('gs/', function () use ($app) {
            $app->get('info', '\PointController:getGsPointInfo');
            $app->post('use', '\PointController:postGsPointUse');
            $app->post('cancel', '\PointController:postGsPointCancel');
            $app->post('reverseCancel', '\PointController:postGsPointReserveCancel');
            $app->post('accmulate', '\PointController:postGsPointAccumulate');
            $app->post('accmulateCancel', '\PointController:postGsPointAccumulateCancel');
        });
        $app->group('policy/', function () use ($app) {
            $app->get('list', '\PointPolicyController:getPolicyList');
            $app->get('compriseList', '\PointPolicyController:getCompriseList');
            $app->get('compriseDetail', '\PointPolicyController:getCompriseDetail');
            $app->post('add', '\PointPolicyController:postPolicyAdd');
            $app->get('detail', '\PointPolicyController:getPolicyDetail');
            $app->put('update', '\PointPolicyController:putPolicyDetail');
            $app->put('delete', '\PointPolicyController:deletePolicyDelete');
        });
    });

    $app->get('badgeList', '\BadgeController:getBadgeList');

    $app->post('logout', '\AuthController:postIparkingLogout');
    
    // 파킹패스건만 처리할수 있게 처리. TestController에다가 넣어놈. 일회성이므로 노출되면 안되서 라우터를 test로 함.
    $app->post('test/bluePointDirectCancel', '\TestController:postTestBluePointDirectCancel');
    $app->post('test/gsPointAccumulateCancel', '\TestController:postTestGsPointAccumulateCancel');
    $app->post('test/paycoReflect', '\TestController:postPayCoReflect');
    $app->post('test/payConfirm', '\TestController:postTestPayConfirm');
});

$app->group('/relay/', function () use ($app) {
    $app->get('descrypt', '\RelayController:getDescryptData');
    $app->post('encrypt', '\RelayController:postEncryptData');
    $app->group('product/', function () use ($app) {
        $app->get('sales', '\RelayController:getProductSalesInfo');
        $app->post('sales', '\RelayController:postProductSales');
        $app->get('sales/history', '\RelayController:getProductSalesHistory');
        $app->get('sales/calc', '\RelayController:getProductSalesCalc');
        $app->post('sales/assign', '\RelayController:postProductSalesAssign');
    });  
    $app->group('point/', function () use ($app) {
        $app->get('dec', '\RelayController:getPointCardDecrypt');
        $app->post('card/add', '\RelayController:postPointCardAdd');
        $app->get('card/list', '\RelayController:getPointCardList');
        $app->put('card/delete', '\RelayController:putPointCardIsDeleted');
    });
    $app->group('payment/', function () use ($app) {
        $app->post('iparkingPay', '\RelayController:postIparkingPayPayment');
        $app->post('iparkingPayCancel', '\RelayController:postIparkingPayPaymentCancel');
    });
    $app->post('point/paymentCancel', '\RelayController:postPointCardCancel');
    $app->post('point/onlyCancelRelay', '\RelayController:postOnlyPointCardCancelRelay');
    
})->add($normal);

$app->group('/relay/payco/', function () use ($app) {
    $app->post('reserve', '\PaycoController:postPaycoReserve');
    $app->post('callBack', '\PaycoController:postPaycoCallback');
    $app->post('cancel', '\PaycoController:postPayCoCancel');
})->add($normal)->add($env_set);

// $app->group('/payco/', function () use ($app) {
//     $app->post('reserve', '\PaycoController:postPaycoReserve');
//     $app->post('callBack', '\PaycoController:postPaycoCallback');
//     $app->post('cancel', '\PaycoController:postPayCoCancel');
// });

$app->put('/admin/resetPassword', '\AuthController:putResetPassword');
$app->post('/admin/signUp', '\AuthController:signUp');
$app->post('/admin/signIn', '\AuthController:signIn');

$app->group('/app/', function () use ($app) {
    $app->group('arf/', function () use ($app) {
        $app->group('b2ccore/', function () use ($app) {
            $app->group('loginout/', function () use ($app) {
                $app->get('loginChekAug.do', 'LoginController:getLoginCheck');  //로그인체크 - /app/arf/b2ccore/loginout/loginChekAug.do
            });
        });

        $app->group('basis/', function () use ($app) {
            $app->group('legalpolicy/', function () use ($app) {
                $app->get('legalPolicyViewAug.do', 'LegalPolicyViewController:getLegalPolicyView'); //이용약관 - /app/arf/basis/legalpolicy/legalPolicyViewAug.do
            });
        });

    });
});

$app->group('/iparking/', function () use ($app) {
    $app->group('push/', function () use ($app) {
        $app->post('search.do', 'PushController:getPushSearch');  //PUSH 설정 - /iparking/push/search.do
    });
});

$app->post('/xx', '\TestController:sttest');
// $app->get('/xx', '\PaycoController:postPaycoReserve');

$app->group('/voc/', function () use ($app) {
    $app->get('relayUrls', '\VocController:getRelayUrl');
    $app->get('relayHistory', '\VocController:getRelayHistory');
    $app->get('descriptionHistory', '\VocController:getDescriptionHistory');
})->add($onlyMember);

