#!/usr/bin/php -q
<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 주기적인 작업을 하기 위한 크론실행 스크립트를 정의한다.
 *
 * @file /crons/index.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 8. 27.
 */
if (isset($_SERVER['SHELL']) == false) {
    echo 'SHELL ONLY!';
    exit();
}

$_SERVER['DOCUMENT_ROOT'] = str_replace('/crons/index.php', '', $_SERVER['SCRIPT_FILENAME']);
if (is_file($_SERVER['DOCUMENT_ROOT'] . '/configs/configs.php') == false) {
    echo 'NOT INSTALLED!';
    exit();
}

set_time_limit(0);

/**
 * 환경설정파일을 불러온다.
 */
$_CONFIGS = new stdClass();
$_CONFIGS->db = new stdClass();
require_once $_SERVER['DOCUMENT_ROOT'] . '/configs/configs.php';

/**
 * 환경설정을 불러오기위한 클래스를 초기화한다.
 */
require_once realpath($_SERVER['DOCUMENT_ROOT'] . '/classes/Configs.php');
Configs::init($_CONFIGS);

/**
 * 아이모듈 상수정의
 * 아이모듈에서 사용되는 상수는 상수명 앞뒤를 언더바 2개로 감싼 형태로 정의힌다. (__[상수명]__)
 *
 * __IM_CRON__ : 아이모듈 자동화작업이 실행되었는지 여부를 확인하는 상수
 * __IM_VERSION__ : 아이모듈 버전을 정의하는 상수. 빌드날짜는 포함되지 않음
 */
define('__IM_CRON__', true);
define('__IM_VERSION__', '4.0.0');

/**
 * 클래스파일을 자동으로 불러오기위한 오토로더클래스를 불러온다.
 */
require_once Configs::path() . '/classes/AutoLoader.php';
AutoLoader::init();
AutoLoader::register('/', '/');
AutoLoader::register('/', '/classes');
AutoLoader::register('/vendor', '/src');

/**
 * 자동화 작업을 시작한다.
 */
$cron = new Cron();
$cron->execute();

