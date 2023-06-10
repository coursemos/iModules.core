<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * FORM 태그 출력을 위한 클래스를 정의한다.
 *
 * @file /classes/Form.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
require_once __DIR__ . '/FormElement.php';
class Form
{
    /**
     * INPUT 태그를 생성한다.
     *
     * @param string $name 필드명
     * @param string $type 종류 (text, password, search 등)
     * @return \FormElement\Input $element
     */
    public static function input(string $name, string $type = 'text'): \FormElement\Input
    {
        $element = new \FormElement\Input($name, $type);
        return $element;
    }

    /**
     * 체크박스 태그를 생성한다.
     *
     * @param string $name 필드명
     * @param string $value 필드값
     * @param string $boxLabel 체크박스 라벨텍스트
     * @return \FormElement\Check $element
     */
    public static function check(string $name, string $value = '', string $boxLabel = ''): \FormElement\Check
    {
        $element = new \FormElement\Check($name, $value, $boxLabel);
        return $element;
    }

    /**
     * 체크박스 태그를 생성한다.
     *
     * @param string $name 필드명
     * @param string $options 선택항목 [VALUE=>DISPLAY, ...]
     * @return \FormElement\Select $element
     */
    public static function select(string $name, array $options = []): \FormElement\Select
    {
        $element = new \FormElement\Select($name, $options);
        return $element;
    }

    /**
     * 텍스트영역 태그를 생성한다.
     *
     * @param string $name 필드명
     * @param int $rows 라인수
     * @return \FormElement\Textarea $element
     */
    public static function textarea(string $name, int $rows = 5): \FormElement\Textarea
    {
        $element = new \FormElement\Textarea($name, $rows);

        return $element;
    }
}
