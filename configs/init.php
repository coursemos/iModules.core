<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈에서 사용되는 기본상수를 정의하고, 환경설정을 정의한다.
 *
 * @file /configs/init.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
if (is_file('./configs/configs.php') == false) {
    header('location: ./install');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', true);

/**
 * 환경설정파일을 불러온다.
 */
$_CONFIGS = new stdClass();
$_CONFIGS->db = new stdClass();
require_once realpath(__DIR__ . '/configs.php');

/**
 * 환경설정을 불러오기위한 클래스를 초기화한다.
 */
require_once realpath(__DIR__ . '/../classes/Configs.php');
Configs::init($_CONFIGS);

/**
 * 아이모듈 상수정의
 * 아이모듈에서 사용되는 상수는 상수명 앞뒤를 언더바 2개로 감싼 형태로 정의힌다. (__[상수명]__)
 *
 * __IM__ : 아이모듈 코어에 의해 PHP 파일이 실행되었는지 여부를 확인하는 상수
 * __IM_VERSION__ : 아이모듈 버전을 정의하는 상수. 빌드날짜는 포함되지 않음
 * __IM_DB_PREFIX__ : 아이모듈에서 생성되는 모든 DB 테이블앞에 붙는 PREFIX를 정의하는 상수
 * __IM_PATH__ : 아이모듈이 설치되어 있는 서버상의 경로 (./configs/configs.php 파일에 의해 정의)
 * __IM_DIR__ : 아이모듈이 설치되어 있는 웹브라우저상의 경로 (./configs/configs.php 파일에 의해 정의)
 */
define('__IM__', true);
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
 * 에러클래스를 초기화한다.
 */
ErrorHandler::init();

/**
 * 사이트 헤더 설정
 * 기본적인 HTTP보안설정 및 언어셋을 선언한다.
 */
header('X-UA-Compatible: IE=Edge');
header('X-XSS-Protection: 1');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');
?>
