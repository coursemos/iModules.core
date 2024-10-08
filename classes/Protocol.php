<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 컴포넌트간 데이터교환을 위한 규약 클래스를 정의한다.
 *
 * @file /classes/Protocol.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 8.
 */
class Protocol
{
    /**
     * @var Component $_origin 호출한 컴포넌트 객체
     */
    private Component $_origin;

    /**
     * @var Component $_target 호출대상 컴포넌트 객체
     */
    private Component $_target;

    /**
     * 컴포넌트간 데이터교환을 위한 규약 클래스를 정의한다.
     *
     * @param Component $origin
     * @param Component $target
     */
    public function __construct(Component $origin, Component $target)
    {
        $this->_origin = $origin;
        $this->_target = $target;
    }

    /**
     * 호출한 클래스를 가져온다.
     *
     * @return Component $origin
     */
    public function getOrigin(): Component
    {
        return $this->_origin;
    }

    /**
     * 호출대상 클래스를 가져온다.
     *
     * @return Component $target
     */
    public function getTarget(): Component
    {
        return $this->_target;
    }
}
