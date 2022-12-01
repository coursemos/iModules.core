<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 사이트 최초 접속시 실행되는 파일로 기본설정을 불러오고 아이모듈 코어 클래스를 선언하여 요청된 페이지를 반환한다.
 *
 * @file /index.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
require_once './configs/init.php';

/**
 * 요청에 따라 응답한다.
 */
iModules::respond();
