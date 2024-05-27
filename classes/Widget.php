<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 위젯의 부모클래스를 정의한다.
 *
 * @file /classes/Widget.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 27.
 */
abstract class Widget extends Component
{
    /**
     * @var bool $_init 위젯 클래스가 초기화되었는지 여부
     */
    private bool $_init = false;

    /**
     * @var Template $_template 템플릿
     */
    private Template $_template;

    /**
     * @var array $_attributes 위젯컨테이너속성
     */
    private array $_attributes = [];

    /**
     * @var ?object $_configs 위젯설정
     */
    private ?object $_configs = null;

    /**
     * 위젯 설정을 초기화한다.
     */
    public function init(): void
    {
        if ($this->_init == false) {
            $this->_init = true;
        }
    }

    /**
     * 위젯의 데이터를 가져온다.
     *
     * @param string $key 가져올 데이터키
     * @return mixed $value 데이터값
     */
    final public function getData(string $key): mixed
    {
        return Widgets::getData($this->getName(), $key);
    }

    /**
     * 위젯의 데이터를 저장한다.
     *
     * @param string $key 저장할 데이터키
     * @param mixed $value 저장할 데이터값
     * @return bool $success
     */
    final public function setData(string $key, mixed $value): bool
    {
        return Widgets::setData($this->getName(), $key, $value);
    }

    /**
     * 템플릿을 가져온다.
     *
     * @return Template $template
     */
    final public function getTemplate(): Template
    {
        /**
         * 위젯 템플릿이 지정되지 않은 경우 기본 템플릿을 반환한다.
         */
        if (isset($this->_template) == false) {
            $this->_template = new Template($this, (object) ['name' => 'default', 'configs' => null]);
        }

        return $this->_template;
    }

    /**
     * 템플릿을 설정한다.
     *
     * @param string $name 템플릿명
     * @param array $configs 템플릿설정
     * @return Widget $widget
     */
    final public function setTemplate(string $name, array $configs = []): Widget
    {
        $template = (object) ['name' => $name, 'configs' => (object) $configs];
        $this->_template = new Template($this, $template);
        return $this;
    }

    /**
     * 위젯컨테이너속성을 설정한다.
     *
     * @param string $key 설정값키
     * @param mixed $value 설정값
     * @return Widget $widget
     */
    final public function setAttribute(string $key, int|string|null $value = null): Widget
    {
        $this->_attributes[$key] = $value;
        return $this;
    }

    /**
     * 위젯설정값을 설정한다.
     *
     * @param string $key 설정값키
     * @param mixed $value 설정값
     * @return Widget $widget
     */
    final public function setConfig(string $key, mixed $value = null): Widget
    {
        $this->_configs ??= new \stdClass();
        $this->_configs->$key = $value;
        return $this;
    }

    /**
     * 위젯설정값을 가져온다..
     *
     * @param string $key 설정값키
     * @return mixed $value 설정값
     */
    final public function getConfig(string $key): mixed
    {
        return $this->_configs?->$key ?? null;
    }

    /**
     * 위젯을 출력하는데 사용하는 데이터를 할당한다.
     *
     * @param \Template $template 위젯템플릿 객체
     */
    abstract public function setTemplateValues(\Template $template): void;

    /**
     * 위젯 레이아웃을 가져온다.
     *
     * @return string $layout
     */
    final public function getLayout(): string
    {
        $template = $this->getTemplate();
        $this->setTemplateValues($template);

        $attributes = array_merge(
            [
                'data-role' => 'widget',
                'data-widget' => $this->getName(),
                'data-template' => $template->getName(),
                'data-module' => $this->getParentModule()?->getName() ?? 'core',
            ],
            $this->_attributes
        );

        return Html::element('div', $attributes, $template->getLayout());
    }

    final public function doLayout(): void
    {
        echo $this->getLayout();
    }
}
