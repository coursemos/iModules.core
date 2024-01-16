<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 위젯의 부모클래스를 정의한다.
 *
 * @file /classes/Widget.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 27.
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
     * 위젯 설정을 초기화한다.
     */
    public function init(): void
    {
        if ($this->_init == false) {
            $this->_init = true;
        }
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
     * 위젯을 출력하는데 사용하는 데이터를 할당한다.
     */
    abstract public function setValues(): void;

    /**
     * 위젯 레이아웃을 가져온다.
     *
     * @return string $layout
     */
    final public function getLayout(): string
    {
        $template = $this->getTemplate();
        $this->setValues();

        return Html::element(
            'div',
            [
                'data-role' => 'widget',
                'data-name' => $this->getName(),
                'data-template' => $template->getName(),
                'data-module' => $this->getParentModule()?->getName() ?? 'core',
            ],
            $template->getLayout()
        );
    }

    final public function doLayout(): void
    {
        echo $this->getLayout();
    }
}
