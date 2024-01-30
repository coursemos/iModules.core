<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 언어팩 클래스를 정의한다.
 *
 * @file /classes/Language.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 30.
 */
class Language
{
    /**
     * @var ?object $_texts 언어팩이 저장될 객체
     */
    private static ?object $_texts = null;

    /**
     * @var ?object $_customize 커스덤마이즈 언어팩이 저장될 객체
     */
    private static ?object $_customize;

    /**
     * 언어팩을 초기화한다.
     *
     * @param object $customize 커스터마이즈 언어팩
     */
    public static function init(?object $customize): void
    {
        if (isset(self::$_customize) == false) {
            self::$_customize = $customize;
            Router::add('/{type}/{name}/language/{language}.json', '#', 'blob', ['Language', 'customize']);
        }
    }

    /**
     * 언어팩을 불러온다.
     *
     * @param string $path 언어팩을 탐색할 경로
     * @param string $code 언어팩을 탐색할 언어코드
     * @return ?object $texts 언어팩
     */
    public static function load(string $path, string $code): ?object
    {
        if ((self::$_texts?->{$path}?->{$code} ?? null) === null) {
            self::$_texts ??= new stdClass();
            self::$_texts->{$path} ??= new stdClass();
            if (is_dir(self::getPath($path)) == false) {
                self::$_texts->{$path}->{$code} = false;
            }

            if (is_file(self::getPath($path) . '/' . $code . '.json') == false) {
                self::$_texts->{$path}->{$code} = false;
            } else {
                self::$_texts->{$path}->{$code} = json_decode(File::read(self::getPath($path) . '/' . $code . '.json'));
            }
        }

        return self::$_texts->{$path}->{$code} === false ? null : self::$_texts->{$path}->{$code};
    }

    /**
     * 루트폴더를 포함한 언어팩 경로를 가져온다.
     *
     * @param string $path 언어팩을 탐색할 경로
     * @return string $path 루트폴더를 포함한 언어팩 탐색 경로
     */
    public static function getPath(string $path): string
    {
        return Configs::path() . ($path == '/' ? '' : $path) . '/languages';
    }

    /**
     * 문자열 템플릿에서 치환자를 실제 데이터로 변환한다.
     *
     * @param string $text 문자열 템플릿
     * @param ?array $placeHolder 치환될 데이터
     * @return string $message 치환된 메시지
     */
    public static function replacePlaceHolder(string $text, ?array $placeHolder = null): string
    {
        if ($placeHolder === null || is_string($text) == false) {
            return $text;
        }

        if (preg_match_all('/\$\{(.*?)\}/', $text, $matches, PREG_SET_ORDER) == true) {
            foreach ($matches as $match) {
                $text = str_replace(
                    $match[0],
                    isset($placeHolder[$match[1]]) == true ? $placeHolder[$match[1]] : '',
                    $text
                );
            }
        }

        return $text;
    }

    /**
     * 언어팩을 불러온다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @param ?array $paths 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param ?array $codes 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return string|object $message 치환된 메시지
     */
    public static function getText(
        string $text,
        ?array $placeHolder = null,
        ?array $paths = null,
        ?array $codes = null
    ): string|object {
        $paths ??= ['/'];
        $codes ??= array_unique([Router::has()?->getLanguage() ?? Request::languages(true), ...Request::languages()]);
        $keys = explode('.', $text);
        $string = null;
        foreach ($paths as $path) {
            foreach ($codes as $code) {
                $string = self::load($path, $code);
                $customize = self::$_customize?->{$path}?->{$code} ?? null;
                foreach ($keys as $key) {
                    $customize = $customize?->{$key} ?? null;
                    $string = $string?->{$key} ?? null;
                }

                if ($customize !== null) {
                    return is_string($customize) == true
                        ? self::replacePlaceHolder($customize, $placeHolder)
                        : $customize;
                }

                if ($string !== null) {
                    return is_string($string) == true ? self::replacePlaceHolder($string, $placeHolder) : $string;
                }
            }
        }

        if ($string === null) {
            return $text;
        }

        return is_string($string) == true ? self::replacePlaceHolder($string, $placeHolder) : $string;
    }

    /**
     * 에러메시지를 불러온다.
     *
     * @param string $error 에러코드
     * @param ?array $placeHolder 치환자
     * @param ?array $paths 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param ?array $codes 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return string $message 치환된 메시지
     */
    public static function getErrorText(
        string $error,
        ?array $placeHolder = null,
        ?array $paths = null,
        ?array $codes = null
    ): string {
        $text = self::getText('errors.' . $error, $placeHolder, $paths, $codes);
        if (is_string($text) == true) {
            return $text;
        } else {
            return $error;
        }
    }

    /**
     * 커스터마이즈된 언어팩을 자바스크립트에서 불러오기 위한 라우터를 설정한다.
     *
     * @param \Route $route 라우트객체
     * @param string $type 컴포넌트타입
     * @param string $name 컴포넌트명
     * @param string $code 언어코드
     */
    public static function customize(\Route $route, string $type, string $name, string $code): void
    {
        Header::type('json');

        $component = '/' . $type . 's/' . $name;
        exit(Format::toJson(self::$_customize->{$component}?->{$code} ?? new stdClass()));
    }
}
