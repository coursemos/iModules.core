<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTTP 헤더 형식을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Header.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 2. 25.
 */
class Header
{
    private static string $_type = 'html';

    /**
     * HTTP 응답코드를 설정한다.
     *
     * @param int $code HTTP 응답코드
     */
    public static function setCode(int $code): void
    {
        if (headers_sent() == true) {
            return;
        }

        switch ($code) {
            case 200:
                break;

            case 403:
                header('HTTP/1.0 403 Forbidden');
                break;

            case 404:
                header('HTTP/1.0 404 Not Fount');
                break;
        }
    }

    /**
     * 콘텐츠 타입을 가져온다.
     *
     * @return string $type
     */
    public static function getType(): string
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) == true ? $_SERVER['HTTP_ACCEPT'] : '';
        if (preg_match('/(html|json)/', $accept, $match) == true) {
            $accept = $match[1];
        }

        if ($accept == 'json' || self::$_type == 'json') {
            return 'json';
        }

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
