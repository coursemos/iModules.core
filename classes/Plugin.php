<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 플러그인의 부모클래스를 정의한다.
 *
 * @file /classes/Plugin.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Plugin extends Component
{
    function __construct()
    {
    }

    /**
     * 플러그인명을 가져온다.
     *
     * @return string $module
     */
    public function getName(): string
    {
        return '';
    }

    /**
     * 플러그인의 기본경로를 가져온다.
     *
     * @return string $base
     */
    public function getBase(): string
    {
        return '';
    }

    /**
     * 플러그인의 상태경로를 가져온다.
     *
     * @return string $dir
     */
    public function getDir(): string
    {
        return '';
    }

    /**
     * 플러그인의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return '';
    }
}
