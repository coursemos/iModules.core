<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 설치화면을 구성한다.
 *
 * @file /install/index.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
if (version_compare(PHP_VERSION, '8.0.0', '<') == true) {
    exit('PHP version 8.0 or higher is required.');
}

/**
 * 클래스파일을 자동으로 불러오기위한 오토로더클래스를 불러온다.
 */
require_once '../classes/AutoLoader.php';
AutoLoader::init('..');
AutoLoader::register('/', '/');
AutoLoader::register('/', '/classes');
AutoLoader::register('/vendor', '/src');

Configs::setPath('..');
Html::language(Request::get('language') ?? Request::languages(true));
Html::title(Configs::package()->title . ' Installer');
Html::viewport('width=1000');
Html::script('//www.moimz.tools/installer/scripts/script.js');
Html::style('//www.moimz.tools/installer/styles/style.css');

Html::print(Html::header(), Html::footer());
