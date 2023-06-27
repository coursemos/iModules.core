<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 플러그인의 부모클래스를 정의한다.
 *
 * @file /classes/Plugin.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 27.
 */
abstract class Plugin extends Component
{
    /**
     * @var bool $_init 플러그인 클래스가 초기화되었는지 여부
     */
    private static bool $_init = false;

    /**
     * 플러그인 설정을 초기화한다.
     */
    public function init(): void
    {
        if (self::$_init == false) {
            self::$_init = true;
        }
    }
}
