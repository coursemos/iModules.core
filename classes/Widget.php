<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 위젯의 부모클래스를 정의한다.
 *
 * @file /classes/Widget.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Widget extends Component
{
    function __construct()
    {
    }

    /**
     * 위젯명을 가져온다.
     *
     * @return string $module
     */
    public function getName(): string
    {
        return '';
    }

    /**
     * 위젯의 기본경로를 가져온다.
     *
     * @return string $base
     */
    public function getBase(): string
    {
        return '';
    }

    /**
     * 위젯의 상태경로를 가져온다.
     *
     * @return string $dir
     */
    public function getDir(): string
    {
        return '';
    }

    /**
     * 위젯의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return '';
    }
}
