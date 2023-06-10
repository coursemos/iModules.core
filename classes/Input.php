<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * PHP INPUT 데이터를 처리하는 클래스를 정의한다.
 *
 * @file /classes/Input.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
class Input
{
    private mixed $input;
    private mixed $values;

    /**
     * 컨텍스트 데이터 구조체를 정의한다.
     *
     * @param object $context 컨텍스트정보
     */
    public function __construct(mixed $input, string $type = '')
    {
        $this->input = $input;

        if (strpos(strtolower($type), 'json') !== false) {
            $this->values = $this->normalizer(json_decode($this->input));
        } else {
            $this->values = null;
        }
    }

    /**
     * 전체 데이터를 가져온다.
     *
     * @return mixed $values
     */
    public function all(): mixed
    {
        return $this->values;
    }

    /**
     * BODY RAW 데이터를 가져온다.
     *
     * @return mixed $body
     */
    public function body(): mixed
    {
        return $this->input;
    }

    /**
     * JSON 데이터를 가지고 온다.
     *
     * @param string $key - 데이터를 가지고 올 키값
     * @param array &$errors 데이터가 존재하지 않을 경우 에러를 담을 배열
     * @param ?string $message 에러메시지 (NULL 인 경우 기본 메시지)
     * @return mixed $value
     */
    public function get(string $key, array &$errors = null, ?string $message = null): mixed
    {
        $value = isset($this->values?->$key) == true ? $this->values->$key : null;
        if ($value === null && $errors !== null) {
            $errors[$key] = $message ?? Language::getText('errors.REQUIRED');
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
    private function normalizer(mixed $data): mixed
    {
        if (is_string($data) == true) {
            return Format::normalizer($data);
        } elseif (is_iterable($data) == true) {
            foreach ($data as &$item) {
                $item = $this->normalizer($item);
            }

            return $data;
        } else {
            return $data;
        }
    }
}
