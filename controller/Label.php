<?php

class Label
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public $공지사항 = array(
        'seq' => 'notice_seq',
        'title' => 'notice_title',
        'content' => 'notice_content',
        'on_off' => 'notice_on_off',
        'del_yn' => 'notice_del_yn',
        'hit' => 'notice_hit',
        'create_time' => 'create_time',
        'update_time' => 'update_time'
    );

    public $이벤트 = array(
        'seq' => 'event_seq',
        'add_type' => 'event_add_type',
        'title' => 'event_title',
        'content' => 'event_content',
        'content_url' => 'event_content_url',
        'thumbnail' => 'event_thumbnail',
        'start_date' => 'event_start_date',
        'end_date' => 'event_end_date',
        'app_main_image' => 'event_app_main_image',
        'like_banner_yn' => 'event_like_banner_yn',
        'like_banner' => 'event_like_banner',
        'hit' => 'event_hit',
        'update_time' => 'update_time',
        'del_yn' => 'event_del_yn',
        'on_off' => 'event_on_off',
        'event_end_datetime' => 'event_end_datetime',
        'event_start_datetime' => 'event_start_datetime',
        'create_time' => 'create_time',
        'event_app_main_order' => 'event_app_main_order',
        'event_like_banner_order' => 'event_like_banner_order'
    );

    public $첨부파일구분 = array(
        '공지사항' => array(
            '파일첨부' => 'cms/notice/attachments/'
        ),
        '이벤트' => array(
            '파일첨부' => 'cms/event/attachments/',
            '메인베너이미지' => 'cms/event/main_banner',
            '추천배너이미지' => 'cms/event/like_banner',
            '썸네일이미지' => 'cms/event/thumnail'
        ),
        '웹배너' => array(
            '배너이미지' => 'cms/web/banner/main',
            '상세템플릿' => 'cms/web/banner/detail_template'
        )    
    );

}