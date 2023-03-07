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
    public static function code(int $code): void
    {
        if (headers_sent() == true) {
            return;
        }

        $codes = [
            200 => 'OK',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            413 => 'Content Too Large',
            414 => 'URI Too Long',
        ];

        header('HTTP/1.1 ' . $code . ' ' . $codes[$code]);
    }

    /**
     * 페이지를 이동한다.
     *
     * @param string $location 이동할 URL
     * @param bool $is_permanently 영구적인 이동인지 여부
     */
    public static function location(string $url, bool $is_permanently = false): void
    {
        if (headers_sent() == true) {
        } else {
            self::code($is_permanently == true ? 308 : 307);
            header('location: ' . $url);
            exit();
        }
    }

    /**
     * 콘텐츠 타입을 가져오거나 설정한다.
     *
     * @param ?string $type 설정할 콘텐츠 타입 (NULL 인 경우 현재 콘텐츠 타입을 가져온다.)
     * @return string|bool $type|$success 콘텐츠 타입이 설정되었을 경우 설정성공여부, 또는 현재 콘텐츠 타입
     */
    public static function type(?string $type = null): string|bool
    {
        if ($type === null) {
            $accept = isset($_SERVER['HTTP_ACCEPT']) == true ? $_SERVER['HTTP_ACCEPT'] : '';
            if (preg_match('/(html|json)/', $accept, $match) == true) {
                $accept = $match[1];
            }

            if ($accept == 'json' || self::$_type == 'json') {
                return 'json';
            }

            return self::$_type;
        } else {
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
}
