<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTTP Request 데이터를 관리하는 클래스를 정의한다.
 *
 * @file /classes/Request.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Request
{
    /**
     * 현재 호스트를 가져온다.
     *
     * @return string $host
     */
    public static function host(): string
    {
        return isset($_SERVER['HTTP_HOST']) == true ? strtolower($_SERVER['HTTP_HOST']) : 'localhost';
    }

    /**
     * 현재 URL을 가져온다.
     *
     * @param bool|array $query 쿼리스트링 포함 여부 (true : 전체, false : 포함하지 않음, array : 포함할 쿼리스트링)
     * @return string $url
     */
    public static function url(bool|array $query = true): string
    {
        $uri = explode('?', isset($_SERVER['REQUEST_URI']) == true ? $_SERVER['REQUEST_URI'] : '/');
        $url = $uri[0];
        if ($query === true && count($uri) == 2) {
            $url .= '?' . $uri[1];
        } elseif (is_array($query) == true) {
            $queries = [];
            foreach ($query as $key) {
                if (Request::get($key) !== null) {
                    $queries[] = $key . '=' . Request::get($key);
                }
            }
            if (count($queries) > 0) {
                $url .= '?' . implode('&', $queries);
            }
        }

        return $url;
    }

    /**
     * GET 으로 전달되는 QUERY_STRING(URL의 ? 이하부분)중 일부 파라매터값을 변경하고, 비어있거나 불필요한 QUERY_STRING 삭제한다.
     *
     * @param string[] $replacements array('GET 파라매터 KEY'=>'변경할 값, 해당값이 없으면 GET 파라매터를 지운다.')
     * @return string $queryString 정리된 GET 파라매터
     */
    public static function query(array $replacements = []): string
    {
        $queryString = $_SERVER['QUERY_STRING'];
        $replacements['route'] = '';
        $queries = strlen($queryString) > 0 ? explode('&', $queryString) : [];

        $queryStrings = [];
        foreach ($queries as $query) {
            list($key, $value) = explode('=', $query);
            if (isset($replacements[$key]) == true) {
                $value = $replacements[$key];
            }

            if (strlen($value) > 0) {
                $queryStrings[] = $key . '=' . $value;
            }
        }

        return implode('&', $queryStrings);
    }

    /**
     * URL 과 QUERY_STRING 를 결합한다.
     *
     * @param string $url
     * @param string $queryString
     * @return $url
     */
    public static function combine(string $url, string $queryString = ''): string
    {
        if (strlen($queryString) == 0) {
            return $url;
        }
        return strpos($url, '?') !== false ? $url . '&' . $queryString : $url . '?' . $queryString;
    }

    /**
     * GET 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @param bool $is_required 필수여부 (기본값 : false)
     * @return string|array|null $value
     */
    public static function get(string $name, bool $is_required = false): string|array|null
    {
        $value = isset($_GET[$name]) == true ? $_GET[$name] : null;
        if ($value === null) {
            if ($is_required == true) {
                ErrorHandler::print('REQUIRED', $name);
            }
            return null;
        }

        if (is_string($value) == true) {
            $value = trim($value);
        } else {
            foreach ($value as &$var) {
                $var = trim($var);
            }
        }

        return $value;
    }

    /**
     * POST 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @param bool $is_required 필수여부 (기본값 : false)
     * @return string|array|null $value
     */
    public static function post(string $name, bool $is_required = false): string|array|null
    {
        $value = isset($_POST[$name]) == true ? $_POST[$name] : null;
        if ($value === null) {
            if ($is_required == true) {
                ErrorHandler::print('REQUIRED', $name);
            }
            return null;
        }

        if (is_string($value) == true) {
            $value = trim($value);
        } else {
            foreach ($value as &$var) {
                $var = trim($var);
            }
        }

        return $value;
    }

    /**
     * REQUEST 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @param bool $is_required 필수여부 (기본값 : false)
     * @return string|array|null $value
     */
    public static function all(string $name, bool $is_required = false): string|array|null
    {
        $value = isset($_REQUEST[$name]) == true ? $_REQUEST[$name] : null;
        if ($value === null) {
            if ($is_required == true) {
                ErrorHandler::print('REQUIRED', $name);
            }
            return null;
        }

        if (is_string($value) == true) {
            $value = trim($value);
        } else {
            foreach ($value as &$var) {
                $var = trim($var);
            }
        }

        return $value;
    }

    /**
     * FILES 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @param bool $is_required 필수여부 (기본값 : false)
     * @return ?array $value
     */
    public static function file(string $name, bool $is_required = false): ?array
    {
        $value = isset($_FILES[$name]) == true ? $_FILES[$name] : null;
        if (
            $value === null ||
            isset($_FILES[$name]) == false ||
            strlen($_FILES[$name]['tmp_name']) == 0 ||
            is_file($_FILES[$name]['tmp_name']) == false
        ) {
            if ($is_required == true) {
                ErrorHandler::print('REQUIRED', $name);
            }
            return null;
        }

        return $_FILES[$name];
    }

    /**
     * SESSION 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @return ?string $value
     */
    public static function session(string $name): ?string
    {
        $value = isset($_SESSION[$name]) == true ? $_SESSION[$name] : null;
        if ($value === null) {
            return null;
        }

        return $value;
    }

    /**
     * COOKIE 변수데이터를 가져온다.
     *
     * @param string $name 변수명
     * @return ?string $value
     */
    public static function cookie(string $name): ?string
    {
        $value = isset($_COOKIE[$name]) == true ? $_COOKIE[$name] : null;
        if ($value === null) {
            return null;
        }

        return $value;
    }

    /**
     * HTTPS 접속여부를 확인한다.
     *
     * @return bool $isHttps
     */
    public static function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) == true && $_SERVER['HTTPS'] == 'on') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) == true && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) == true && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            return true;
        }

        return false;
    }

    /**
     * 디버깅을 위해 전달된 변수를 화면상에 출력한다.
     *
     * @param mixed $val
     */
    public static function debug(mixed $val): void
    {
        echo '<pre>';
        var_dump($val);
        echo '</pre>';
    }
}
?>
