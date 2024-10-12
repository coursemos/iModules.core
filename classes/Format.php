<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Format.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 12.
 */
class Format
{
    /**
     * 문자열을 종류에 따라 변환한다.
     *
     * @param ?string $str 변환할 문자열
     * @param string $code 변환종류
     * @return string $str 변환된 문자열
     */
    public static function string(?string $str, string $code): string
    {
        $str ??= '';
        switch ($code) {
            /**
             * input 태그에 들어갈 수 있도록 <, >, " 문자열을 HTML 엔티티 문자열로 변환하고 ' 에 \ 를 추가한다.
             */
            case 'input':
                $str = str_replace('<', '&lt;', $str);
                $str = str_replace('>', '&gt;', $str);
                $str = str_replace('"', '&quot;', $str);
                $str = str_replace("'", '\'', $str);

                break;

            /**
             * HTML 태그를 HTML 엔티티 문자열로 변환한다.
             */
            case 'replace':
                $str = str_replace('<', '&lt;', $str);
                $str = str_replace('>', '&gt;', $str);
                $str = str_replace('"', '&quot;', $str);

                break;

            /**
             * XML 태그에 들어갈 수 있도록 &, <, >, ", ' 문자열을 HTML 엔티티 문자열로 변환한다.
             */
            case 'xml':
                $str = str_replace('&', '&amp;', $str);
                $str = str_replace('<', '&lt;', $str);
                $str = str_replace('>', '&gt;', $str);
                $str = str_replace('"', '&quot;', $str);
                $str = str_replace("'", '&apos;', $str);

                break;

            /**
             * 가장 일반적인 HTML 태그를 제외한 나머지 태그를 제거한다.
             */
            case 'default':
                $allow =
                    '<p>,<br>,<b>,<span>,<a>,<img>,<embed>,<i>,<u>,<strike>,<font>,<center>,<ol>,<li>,<ul>,<strong>,<em>,<div>,<table>,<tr>,<td>';
                $str = strip_tags($str, $allow);

                break;

            /**
             * \ 및 태그, HTML 엔티티를 제거한다.
             */
            case 'delete':
                $str = stripslashes($str);
                $str = strip_tags($str);
                $str = str_replace('&nbsp;', '', $str);
                $str = str_replace('"', '&quot;', $str);

                break;

            /**
             * 데이터베이스 인덱스에 사용할 수 있게 HTML태그 및 HTML엔티티, 그리고 불필요한 공백문자를 제거한다.
             */
            case 'index':
                $str = preg_replace('/<(P|p)>/', '', $str);
                $str = preg_replace('/<\/(P|p)>/', "\n", $str);
                $str = preg_replace('/<br(.*?)>/', "\n", $str);
                $str = preg_replace('/\r\n/', "\n", $str);
                $str = preg_replace('/[\n]+/', "\n", $str);
                $str = strip_tags($str);
                $str = preg_replace('/&[a-z]+;/', ' ', $str);
                $str = str_replace("\t", ' ', $str);
                $str = preg_replace('/[ ]+/', ' ', $str);

                break;
        }

        return trim(self::normalizer($str));
    }

    /**
     * 부분문자열을 위치에 따라 가져온다.
     *
     * @param string $string 문자열
     * @param int|array $length 부분문자열 길이 (양수:앞, 음수:뒤, [앞, 뒤)
     * @return string $substring 부분문자열
     */
    public static function substring(string $string, int|array $length): string
    {
        if (is_int($length) == true) {
            if ($length < 0) {
                $length = [0, $length * -1];
            } else {
                $length = [$length, 0];
            }
        }

        if (
            is_array($length) == false ||
            count($length) != 2 ||
            is_int($length[0]) == false ||
            is_int($length[1]) == false
        ) {
            return $string;
        }

        $string = trim($string);
        $origin = mb_strlen($string, 'utf-8');
        if ($origin <= $length[0] + $length[1]) {
            return $string;
        }

        $substring = trim(mb_substr($string, 0, $length[0], 'utf-8')) . '…';
        $substring .= trim(mb_substr($string, $origin - $length[1], $length[1], 'utf-8'));

        return $substring;
    }

    /**
     * 대상 배열에서 필터배열에 존재하지 않는 값을 필터링한다.
     *
     * @param array $array  대상배열
     * @param array $filters 필터배열
     * @return array $filtered
     */
    public static function filter(array $array, array $filters): array
    {
        return array_filter($array, function ($v) use ($filters) {
            return in_array($v, $filters);
        });
    }

    /**
     * 정규표현식에 사용되는 문자열을 치환한다.
     *
     * @param string $string 원본문자열
     * @param string $replace 치환된문자열
     */
    public static function reg(string $string): string
    {
        return preg_quote($string, '/');
    }

    /**
     * 유니코드 문자열을 정규화한다.
     *
     * @param string $string 대상문자열
     * @return string $string NFC 정규화 문자열
     */
    public static function normalizer(string $string): string
    {
        if (Normalizer::isNormalized($string) == true) {
            return $string;
        }

        $normalized = Normalizer::normalize($string);
        return $normalized === false ? $string : $normalized;
    }

    /**
     * 숫자형태를 언어별 포맷으로 가져온다.
     *
     * @param int|float $number
     * @param ?string $language 언어
     * @return string $formatted
     */
    public static function number(int|float $number, ?string $language = null): string
    {
        $language ??= \Router::getLanguage();

        // @todo 언어별 처리
        return number_format($number);
    }

    /**
     * UNIXTIMESTAMP 를 주어진 포맷에 따라 변환한다.
     *
     * @param string $format 변환할 포맷 (@see http://php.net/manual/en/function.date.php)
     * @param ?int $time UNIXTIMESTAMP (없을 경우 현재시각)
     * @param bool $is_moment momentjs 용 태그를 생성할 지 여부 (@see http://momentjs.com)
     * @return string $time 변환된 시각
     */
    public static function date(string $format, int $time = null, bool $is_moment = true): string
    {
        $time ??= time();

        /**
         * PHP date 함수 포맷텍스트를 momentjs 포맷텍스트로 치환하기 위한 배열정의
         */
        $replacements = [
            'd' => 'DD',
            'D' => 'ddd',
            'j' => 'D',
            'l' => 'dddd',
            'N' => 'E',
            'S' => 'o',
            'w' => 'e',
            'z' => 'DDD',
            'W' => 'W',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'MMM',
            'n' => 'M',
            't' => '', // no equivalent
            'L' => '', // no equivalent
            'o' => 'YYYY',
            'Y' => 'YYYY',
            'y' => 'YY',
            'a' => 'a',
            'A' => 'A',
            'B' => '', // no equivalent
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'u' => 'SSS',
            'e' => 'zz', // deprecated since version 1.6.0 of moment.js
            'I' => '', // no equivalent
            'O' => '', // no equivalent
            'P' => '', // no equivalent
            'T' => '', // no equivalent
            'Z' => '', // no equivalent
            'c' => '', // no equivalent
            'r' => '', // no equivalent
            'U' => 'X',
        ];
        $momentFormat = strtr($format, $replacements);

        if ($is_moment == true) {
            return '<time datetime="' .
                date('c', $time) .
                '" data-time="' .
                $time .
                '" data-format="' .
                $format .
                '" data-moment="' .
                $momentFormat .
                '">' .
                date($format, $time) .
                '</time>';
        } else {
            return date($format, $time);
        }
    }

    /**
     * 함수가 호출될 시점의 microtime 을 구한다.
     *
     * @param int $scale 소숫점 자리수
     * @return double $microtime
     */
    public static function microtime(int $scale = 8): string
    {
        $scale = min(8, $scale);
        return sprintf('%0.' . $scale . 'f', microtime(true));
    }

    /**
     * 랜덤문자열을 가져온다.
     *
     * @param int $length 랜덤문자열 길이
     * @return string $random
     */
    public static function random(int $length = 6): string
    {
        $characters = str_split('0123456789abcdefghijklmnopqrstuvwxyz');
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[array_rand($characters)];
        }
        return $randomString;
    }

    /**
     * byte 단위의 파일크기를 적절한 단위로 변환한다.
     *
     * @param int $size byte 단위 크기
     * @param bool $is_KiB KiB 단위 사용여부
     * @return string $size 단위를 포함한 파일크기
     */
    public static function size(int $size, bool $is_KiB = false): string
    {
        $depthSize = $is_KiB === true ? 1024 : 1000;
        if ($size / $depthSize / $depthSize / $depthSize > 1) {
            return sprintf('%0.2f', $size / $depthSize / $depthSize / $depthSize) . ($is_KiB === true ? 'GiB' : 'GB');
        } elseif ($size / $depthSize / $depthSize > 1) {
            return sprintf('%0.2f', $size / $depthSize / $depthSize) . ($is_KiB === true ? 'MiB' : 'MB');
        } elseif ($size / $depthSize > 1) {
            return sprintf('%0.2f', $size / $depthSize) . ($is_KiB === true ? 'KiB' : 'KB');
        }
        return $size . 'B';
    }

    /**
     * 키코드값을 가져온다.
     *
     * @param string $str 변환할 텍스트
     * @return string $keycode 키코드
     */
    public static function keycode(?string $str = null): string
    {
        if ($str === null) {
            return '';
        }

        $chos = explode(',', 'ㄱ,ㄲ,ㄴ,ㄷ,ㄸ,ㄹ,ㅁ,ㅂ,ㅃ,ㅅ,ㅆ,ㅇ,ㅈ,ㅉ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ');
        $jungs = explode(',', 'ㅏ,ㅐ,ㅑ,ㅒ,ㅓ,ㅔ,ㅕ,ㅖ,ㅗ,ㅘ,ㅙ,ㅚ,ㅛ,ㅜ,ㅝ,ㅞ,ㅟ,ㅠ,ㅡ,ㅢ,ㅣ');
        $jongs = explode(',', ',ㄱ,ㄲ,ㄳ,ㄴ,ㄵ,ㄶ,ㄷ,ㄹ,ㄺ,ㄻ,ㄼ,ㄽ,ㄾ,ㄿ,ㅀ,ㅁ,ㅂ,ㅄ,ㅅ,ㅆ,ㅇ,ㅈ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ');
        $unicode = [];
        $values = [];
        $index = 1;

        for ($i = 0, $loop = strlen($str); $i < $loop; $i++) {
            $code = ord($str[$i]);

            if ($code < 128) {
                $unicode[] = $code;
            } else {
                if (count($values) == 0) {
                    $index = $code < 224 ? 2 : 3;
                }
                $values[] = $code;
                if (count($values) == $index) {
                    $number =
                        $index == 3
                            ? ($values[0] % 16) * 4096 + ($values[1] % 64) * 64 + ($values[2] % 64)
                            : ($values[0] % 32) * 64 + ($values[1] % 64);
                    $unicode[] = $number;
                    $values = [];
                    $index = 1;
                }
            }
        }

        $keycode = '';
        foreach ($unicode as $code) {
            if ($code >= 44032 && $code <= 55203) {
                $temp = $code - 44032;
                $cho = intval($temp / 21 / 28, 10);
                $jung = intval(($temp % (21 * 28)) / 28, 10);
                $jong = intval($temp % 28, 10);

                $keycode .= $chos[$cho] . $jungs[$jung] . $jongs[$jong];
            } else {
                if ($code < 128) {
                    $keycode .= chr($code);
                } elseif ($code < 2048) {
                    $keycode .= chr(192 + ($code - ($code % 64)) / 64);
                    $keycode .= chr(128 + ($code % 64));
                } else {
                    $keycode .= chr(224 + ($code - ($code % 4096)) / 4096);
                    $keycode .= chr(128 + (($code % 4096) - ($code % 64)) / 64);
                    $keycode .= chr(128 + ($code % 64));
                }
            }
        }

        return strtolower(str_replace(' ', '', $keycode));
    }

    /**
     * 이메일이 형식에 맞는지 확인한다.
     *
     * @param ?string $email 이메일
     * @return bool $isValid
     */
    public static function checkEmail(?string $email = null): bool
    {
        if ($email === null) {
            return false;
        }
        return preg_match('/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/', $email) === 1;
    }

    /**
     * 닉네임이 형식에 맞는지 확인한다.
     *
     * @param ?string $nickname 닉네임
     * @return bool $isValid
     */
    public static function checkNickname(?string $nickname = null): bool
    {
        if ($nickname === null) {
            return false;
        }
        if (
            preg_match(
                '/[~!@#\$%\^&\*\(\)\-_\+\=\[\]\<\>\/\?\'":;\{\}\x{25a0}-\x{25ff}\x{2600}-\x{26ff}[:space:]]+/u',
                $nickname
            ) == true
        ) {
            return false;
        }
        if (mb_strlen($nickname, 'utf-8') < 2 || mb_strlen($nickname, 'utf-8') > 12) {
            return false;
        }
        return true;
    }

    /**
     * 패스워드가 형식에 맞는지 확인한다.
     *
     * @param ?string $password 패스워드
     * @return bool $isValid
     */
    public static function checkPassword(?string $password = null): bool
    {
        if ($password === null) {
            return false;
        }
        $pattern = self::reg('!@#$%^&*()+=-[];,./{}|:<>?~');
        return preg_match('/^[A-Za-z\d' . $pattern . ']{6,}$/', $password) === 1;
    }

    /**
     * 사용자의 IP 를 특정형식으로 가져온다.
     *
     * @return string $ip
     */
    public static function ip(string $ip = null, bool $is_mask = true): string
    {
        $ip ??= Request::ip();
        if ($is_mask == true) {
            $ips = explode('.', $ip);
            $ips[2] = str_repeat('*', strlen($ips[2]));
            $ip = implode('.', $ips);
        }

        return $ip;
    }

    /**
     * 사용자의 브라우저정보를 특정형식으로 가져온다.
     *
     * @return string $agent
     */
    public static function agent(): string
    {
        return isset($_SERVER['HTTP_USER_AGENT']) == true ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * JSON 으로 변경한다.
     *
     * @param mixed $data 변경할 데이터
     * @param bool $is_nullable 변경할 데이터가 NULL 인 경우 NULL 반환여부
     * @return ?string $json
     */
    public static function toJson(mixed $data, bool $is_nullable = false): ?string
    {
        if ($data === null && $is_nullable == true) {
            return null;
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 두개의 데이터가 동일한지 비교한다.
     *
     * @param mixed $left
     * @param mixed $right
     * @return bool $is_equal - 동일한지 여부
     */
    public static function isEqual(mixed $left, mixed $right): bool
    {
        if (is_null($left) != is_null($right)) {
            return false;
        }

        if (gettype($left) != gettype($right)) {
            return false;
        }

        if (is_array($left) == true || is_array($right) == true) {
            if (is_array($left) != is_array($right)) {
                return false;
            }

            if (array_is_list($left) != array_is_list($right)) {
                return false;
            }

            if (array_is_list($left) == false) {
                return Format::isEqual((object) $left, (object) $right);
            }

            if (count($left) != count($right)) {
                return false;
            }

            foreach ($left as $v) {
                $matched = false;
                foreach ($right as $c) {
                    if (Format::isEqual($v, $c) == true) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched == false) {
                    return false;
                }
            }

            foreach ($right as $v) {
                $matched = false;
                foreach ($left as $c) {
                    if (Format::isEqual($v, $c) == true) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched == false) {
                    return false;
                }
            }

            return true;
        }

        if (is_object($left) == true || is_object($right) == true) {
            if (is_object($left) != is_object($right)) {
                return false;
            }

            $checked = [];
            foreach ($left as $k => $v) {
                if (property_exists($right, $k) == false) {
                    return false;
                }

                if (Format::isEqual($v, $right->{$k}) == false) {
                    return false;
                }

                $checked[] = $k;
            }

            foreach ($right as $k => $v) {
                if (in_array($k, $checked) == true) {
                    continue;
                }

                if (property_exists($left, $k) == false) {
                    return false;
                }

                if (Format::isEqual($v, $left->{$k}) == false) {
                    return false;
                }
            }

            return true;
        }

        return $left == $right;
    }
}
