<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTTP 헤더 형식을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Header.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class Header
{
    private static string $_type = 'text/html';

    /**
     * 콘텐츠 타입을 가져온다.
     *
     * @return string $type
     */
    public static function getType(): string
    {
        return self::$_type;
    }

    /**
     * 콘텐츠 타입을 지정한다.
     *
     * @param string $type 콘텐츠 타입
     * @return bool $success 헤더설정여부
     */
    public static function setType(string $type): bool
    {
        if (headers_sent() == false) {
            $type = strtolower($type);
            self::$_type = $type;

            $charset = null;
            switch ($type) {
                case 'html':
                    $mime = 'text/html';
                    $charset = 'utf-8';
                    break;

                case 'json':
                    $mime = 'application/json';
                    $charset = 'utf-8';
                    break;

                default:
                    $mime = $type;
            }

            $header = 'Content-type: ' . $mime;
            if ($charset !== null) {
                $header .= '; charset=utf-8';
            }
            header($header, true);

            return true;
        }

        return false;
    }
}
