<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 사이트 최초 접속시 실행되는 파일로 기본설정을 불러오고 아이모듈 코어 클래스를 선언하여 요청된 페이지를 반환한다.
 * 
 * @file index.php
 * @author Arzz
 * @license MIT License
 * @modified 2022. 1. 30.
 */
require_once './configs/init.php';

/**
 * 아이모듈코어를 선언하고, 레이아웃을 불러온다.
 * 
 * @see /classes/iModules.class.php
 */
$IM = new iModules();
$IM->route();
?>