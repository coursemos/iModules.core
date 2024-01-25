<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * PHP INPUT 데이터를 처리하는 클래스를 정의한다.
 *
 * @file /classes/Input.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 */
class Input
{
    private static mixed $_input;
    private static mixed $_values;

    /**
     * 컨텍스트 데이터 구조체를 정의한다.
     *
     * @param object $context 컨텍스트정보
     */
    public static function init(): void
    {
        if (isset(self::$_input) == false) {
            if (in_array(Request::method(), ['GET', 'DELETE']) == true) {
                self::$_input = null;
            } else {
                self::$_input = file_get_contents('php://input');
            }
        }

        if (isset(self::$_values) == false) {
            if (Header::type() == 'json' && self::$_input !== null) {
                self::$_values = self::normalizer(json_decode(self::$_input));
            } else {
                self::$_values = null;
            }
        }
    }

    /**
     * 전체 데이터를 가져온다.
     *
     * @return mixed $values
     */
    public static function all(): mixed
    {
        self::init();
        return self::$_values;
    }

    /**
     * 로그기록용 전체 데이터를 가져온다.
     *
     * @return string $log
     */
    public static function log(): string
    {
        self::init();
        if (self::$_values === null) {
            return Header::type();
        } else {
            Format::toJson(self::$_input);
        }
    }

    /**
     * BODY RAW 데이터를 가져온다.
     *
     * @return mixed $body
     */
    public static function body(): mixed
    {
        self::init();
        return self::$_input;
    }

    /**
     * JSON 데이터를 가지고 온다.
     *
     * @param string $key - 데이터를 가지고 올 키값
     * @param array &$errors 데이터가 존재하지 않을 경우 에러를 담을 배열
     * @param ?string $message 에러메시지 (NULL 인 경우 기본 메시지)
     * @return mixed $value
     */
    public static function get(string $key, array &$errors = null, ?string $message = null): mixed
    {
        self::init();
        $value = isset(self::$_values?->$key) == true ? self::$_values->$key : null;
        if ($value === null && $errors !== null) {
            $errors[$key] = $message ?? Language::getErrorText('REQUIRED');
            if (strlen($errors[$key]) == 0) {
                $errors[$key] = null;
            }
        }
        return $value;
    }

    /**
     * 유니코드 문자열을 정규화한다.
     *
     * @param string $string
     * @return string $string
     */
    private static function normalizer(mixed $data): mixed
    {
        if (is_string($data) == true) {
            return Format::normalizer($data);
        } elseif (is_iterable($data) == true) {
            foreach ($data as &$item) {
                $item = self::normalizer($item);
            }

            return $data;
        } else {
            return $data;
        }
    }
}
