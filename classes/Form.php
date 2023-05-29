<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * FORM 태그 출력을 위한 클래스를 정의한다.
 *
 * @file /classes/Form.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 29.
 */
class Form
{
    /**
     * INPUT 태그를 생성한다.
     *
     * @param string $name 필드명
     * @param string $type 종류 (text, password, search 등)
     * @return FormElement $element
     */
    public static function input(string $name, string $type = 'text'): FormField
    {
        $element = new FormField('input', $name);
        $element->setAttribute('type', $type);

        return $element;
    }

    public static function check(string $name, string $boxLabel = ''): FormField
    {
        $element = new FormField('check', $name);
        $element->setAttribute('type', 'checkbox');
        $element->setBoxLabel($boxLabel);

        return $element;
    }
}

class FormField
{
    /**
     * @var string $_element 필드종류
     */
    private string $_field;

    /**
     * @var string $_name 필드명
     */
    private string $_name;

    /**
     * @var string $_boxLabel 박스라벨
     */
    private string $_boxLabel;

    /**
     * @var string[] $_arributes 태그속성
     */
    private array $_attributes = [];

    public function __construct(string $field, string $name)
    {
        $this->_field = $field;
        $this->_name = $name;

        $this->_attributes['name'] = $name;
    }

    /**
     * 폼 앨리먼트 속성을 정의한다.
     *
     * @param string $name 변수명
     * @param string $value 변수값
     * @return this $this
     */
    public function setAttribute(string $name, string $value): FormField
    {
        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * 체크박스 또는 라디오박스의 박스라벨을 설정한다.
     *
     * @param string $boxLabel
     * @return this $this
     */
    public function setBoxLabel(string $boxLabel = ''): FormField
    {
        $this->_boxLabel = $boxLabel;

        return $this;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    public function getField(): string
    {
        switch ($this->_field) {
            case 'input':
                return Html::element('input', $this->_attributes);

            case 'check':
                return Html::element(
                    'label',
                    [],
                    Html::element('input', $this->_attributes) . ($this->_boxLabel ?? '')
                );
                break;
        }
    }

    /**
     * 폼 앨리먼트 HTML를 가져온다.
     *
     * @return string $html
     */
    public function getLayout(): string
    {
        return Html::element(
            'div',
            ['data-role' => 'input', 'data-field' => $this->_field, 'data-name' => $this->_name],
            $this->getField()
        );
    }

    /**
     * 폼 앨리먼트를 출력한다.
     */
    public function doLayout(): void
    {
        echo $this->getLayout();
    }
}
