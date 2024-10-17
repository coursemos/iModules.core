<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 컴포넌트간 데이터교환을 위한 규약 클래스를 정의한다.
 *
 * @file /classes/Protocol.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 14.
 */
class Protocol
{
    /**
     * @var Component $_owner 프로토콜 소유 컴포넌트
     */
    private Component $_owner;

    /**
     * @var Component $_target 프로토콜 재정의 컴포넌트
     */
    private Component $_target;

    /**
     * 컴포넌트간 데이터교환을 위한 규약 클래스를 정의한다.
     *
     * @param Component $owner 프로토콜 소유 컴포넌트
     * @param Component $target 프로토콜 재정의 컴포넌트
     */
    public function __construct(Component $owner, Component $target)
    {
        $this->_owner = $owner;
        $this->_target = $target;
    }

    /**
     * 프로토콜 소유자 클래스를 가져온다.
     *
     * @return \modules\push\Push $target
     */
    public function getOwner(): Component
    {
        return $this->_owner;
    }

    /**
     * 프로토콜 재정의 클래스를 가져온다.
     *
     * @return Component $target
     */
    public function getTarget(): Component
    {
        return $this->_target;
    }
}
