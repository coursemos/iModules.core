<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈, 플러그인, 위젯의 인터페이스 추상 클래스를 정의한다.
 *
 * @file /classes/Component.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
abstract class Component
{
    /**
     * 컴포넌트명을 가져온다.
     *
     * @return string $module
     */
    abstract public function getName(): string;

    /**
     * 컴포넌트의 기본경로를 가져온다.
     *
     * @return string $base
     */
    abstract public function getBase(): string;

    /**
     * 컴포넌트의 상태경로를 가져온다.
     *
     * @return string $dir
     */
    abstract function getDir(): string;

    /**
     * 컴포넌트의 절대경로를 가져온다.
     *
     * @return string $path
     */
    abstract function getPath(): string;
}
