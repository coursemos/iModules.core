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
    private static ?int $_length = null;

    /**
     * 기본헤더를 초기화한다.
     */
    public static function init(): void
    {
        header('X-Powered-By: iModules (https://www.imodules.io)', true);
        header('X-XSS-Protection: 1; mode=block', true);

        self::cache(0);
    }

    /**
     * 모든 HTTP 요청 헤더를 가져온다.
     *
     * @return array $headers
     */
    public static function all(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (preg_match('/^(HTTP_|CONTENT_)/', $name, $match) == true) {
                $headers[
                    str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', preg_replace('/^HTTP_/', '', $name))))
                    )
                ] = $value;
            }
        }
        return $headers;
    }

    /**
     * HTTP 헤더값을 가져온다.
     *
     * @param string $name 가져올 헤더명
     * @return ?string $value
     */
    public static function get(string $name): ?string
    {
        $name = str_replace(' ', '-', ucwords(strtolower(preg_replace('/(_|-)/', ' ', $name))));
        $headers = Header::all();
        return isset($headers[$name]) == true ? $headers[$name] : null;
    }

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

        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $codes[$code], true);
    }

    /**
     * 캐시설정을 지정한다.
     *
     * @param int $age 캐시유지시간
     * @param ?int $modified 수정시각
     */
    public static function cache(int $age = 0, ?int $modified = null): void
    {
        if ($age == 0) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true);
            header('Cache-Control: no-cache, pre-check=0, post-check=0, max-age=0', true);
            header('Expires: 0', true);
        } else {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified ?? time()) . ' GMT', true);
            header('Cache-Control: max-age=' . $age, true);
            header('Expires: ' . gmdate('D, d M Y H:i:s', ($modified ?? time()) + $age) . ' GMT', true);
        }
    }

    /**
     * 다운로드를 위한 헤더를 지정한다.
     *
     * @param string $name 다운로드될 파일명
     */
    public static function attachment(string $name): void
    {
        header(
            'Content-Disposition: attachment; filename="' .
                rawurlencode($name) .
                '"; filename*=UTF-8\'\'' .
                rawurlencode($name),
            true
        );
        header('Content-Transfer-Encoding: binary', true);
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

                    case 'javascript':
                    case 'css':
                        $mime = 'text/' . $type;
                        $charset = 'utf-8';
                        break;

                    case 'jpg':
                        $mime = 'image/jpeg';
                        break;

                    case 'webp':
                    case 'gif':
                    case 'png':
                    case 'jpeg':
                        $mime = 'image/' . $type;
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

    /**
     * 콘텐츠 크기를 지정한다.
     *
     * @param int $length
     * @param ?int $length 설정할 콘텐츠 길이 (NULL 인 경우 현재 콘텐츠 길이를 가져온다.)
     * @return ?int $length
     */
    public static function length(?int $length = null): ?int
    {
        if ($length === null) {
            return self::$_length;
        }

        if (headers_sent() == false) {
            self::$_length = $length;
            header('Content-Length: ' . self::$_length, true);
        }

        return null;
    }

    /**
     * 크로스사이트 헤더를 설정한다.
     */
    public static function cors(): void
    {
        if (headers_sent() == false) {
            header('Access-Control-Allow-Origin:' . (self::get('origin') ?? '*'));
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept-Language, Accept, X-Method');
            header('Access-Control-Allow-Methods: *');
        }
    }
}
