<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * PHP INPUT 데이터를 처리하는 클래스를 정의한다.
 *
 * @file /classes/Input.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 18.
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
            $this->values = json_decode($this->input);
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
     * JSON 데이터를 가지고 온다.
     *
     * @param string $key - 데이터를 가지고 올 키값
     * @param array &$errors 데이터가 존재하지 않을 경우 에러를 담을 배열
     * @return mixed $value
     */
    public function get(string $key, array &$errors = null): mixed
    {
        $value = isset($this->values?->$key) == true ? $this->values->$key : null;
        if ($value === null && $errors !== null) {
            $errors[$key] = Language::getText('errors/REQUIRED');
        }
        return $value;
    }
}
