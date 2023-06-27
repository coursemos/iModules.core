<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * FORM 태그 출력을 위한 클래스를 정의한다.
 *
 * @file /classes/FormElement.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 24.
 */
namespace FormElement;
class Base
{
    /**
     * @var string $_name 필드명
     */
    protected string $_name;

    /**
     * @var string $_name 필드값
     */
    protected string $_value = '';

    /**
     * @var string[] $_arributes 태그속성
     */
    protected array $_attributes = [];

    /**
     * @var string $_helpText 도움말
     */
    protected ?string $_helpText = null;

    /**
     * 폼 앨리먼트 속성을 정의한다.
     *
     * @param string $name 변수명
     * @param string $value 변수값
     * @return this $this
     */
    public function attribute(string $name, string $value): self
    {
        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * placeholder 를 설정한다.
     *
     * @param string $placeholder placeholder (줄바꿈이 필요할 경우 &#10; 를 사용합니다.)
     * @return this $this
     */
    public function placeholder(string $placeholder): self
    {
        $this->_attributes['placeholder'] = $placeholder;
        return $this;
    }

    /**
     * 필드 도움말을 설정한다.
     *
     * @param string $helpText 도움말
     * @return this $this
     */
    public function helpText(string $helpText): self
    {
        $this->_helpText = $helpText;
        return $this;
    }

    /**
     * 필드값을 설정한다.
     *
     * @param string $value 필드값
     * @return this $this
     */
    public function value(string $value): self
    {
        $this->_value = $value;
        return $this;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    protected function getField(): string
    {
        return '';
    }

    /**
     * 도움말 태그를 가져온다.
     *
     * @return string $field
     */
    private function getHelpText(): string
    {
        if ($this->_helpText !== null) {
            return \Html::element('div', ['data-role' => 'help'], $this->_helpText);
        }

        return '';
    }

    /**
     * 폼 앨리먼트 HTML를 가져온다.
     *
     * @return string $html
     */
    public function getLayout(): string
    {
        $field = explode('\\', get_called_class());
        $field = strtolower(end($field));
        return \Html::element(
            'div',
            ['data-role' => 'form', 'data-type' => 'field', 'data-field' => $field, 'data-name' => $this->_name],
            \Html::tag($this->getField(), $this->getHelpText())
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

class Input extends \FormElement\Base
{
    /**
     * 폼 필드를 생성한다.
     *
     * @param string $name 필드명
     * @param string $type 필드타입
     */
    public function __construct(string $name, string $type)
    {
        $this->_name = $name;
        $this->_attributes['type'] = $type;
        $this->_attributes['name'] = $name;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    protected function getField(): string
    {
        return \Html::element('input', $this->_attributes);
    }
}

class Check extends \FormElement\Base
{
    /**
     * @var string $_boxLabel 박스라벨
     */
    private string $_boxLabel;

    /**
     * 폼 필드를 생성한다.
     *
     * @param string $name 필드명
     * @param string $type 필드타입
     */
    public function __construct(string $name, string $value, string $boxLabel = '')
    {
        $this->_name = $name;
        $this->_boxLabel = $boxLabel;
        $this->_attributes['type'] = 'checkbox';
        $this->_attributes['name'] = $name;
        $this->_attributes['value'] = $value;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    protected function getField(): string
    {
        return \Html::element('label', [], \Html::element('input', $this->_attributes) . ($this->_boxLabel ?? ''));
    }
}

class Select extends \FormElement\Base
{
    /**
     * @var array $_options 선택항목
     */
    private array $_options = [];

    /**
     * 폼 필드를 생성한다.
     *
     * @param string $name 필드명
     * @param array $options 선택항목
     */
    public function __construct(string $name, array $options = [])
    {
        $this->_name = $name;
        $this->_options = $options;
        $this->_attributes['name'] = $name;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    protected function getField(): string
    {
        $options = [];
        foreach ($this->_options as $value => $display) {
            $options[] = \Html::element('option', ['value' => $value], $display);
        }
        return \Html::element('select', $this->_attributes, \Html::tag(...$options));
    }
}

class Textarea extends \FormElement\Base
{
    /**
     * 폼 필드를 생성한다.
     *
     * @param string $name 필드명
     * @param array $options 선택항목
     */
    public function __construct(string $name, int $rows = 5)
    {
        $this->_name = $name;
        $this->_attributes['name'] = $name;
        $this->_attributes['rows'] = $rows;
    }

    /**
     * placeholder 를 설정한다.
     *
     * @param string $placeholder placeholder
     * @return this $this
     */
    public function placeholder(string $placeholder): self
    {
        $this->_attributes['placeholder'] = preg_replace('/(\n|\r\n)/', '&#10;', $placeholder);
        return $this;
    }

    /**
     * 실제 폼필드 태그를 가져온다.
     *
     * @return string $field
     */
    protected function getField(): string
    {
        return \Html::element('textarea', $this->_attributes, $this->_value);
    }
}
