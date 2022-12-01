/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 자바스크립트 클래스의 부모클래스를 정의한다.
 *
 * @file /scripts/Module.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Module {
    dom?: Dom;

    /**
     * 모듈 클래스를 생성한다.
     *
     * @param {Dom} dom - 모듈 컨텍스트의 Dom 객체
     */
    constructor(dom?: Dom) {
        if (dom instanceof Dom === true) {
            this.dom = dom;
        } else {
            this.dom = null;
        }
    }

    /**
     * 해당 모듈의 Dom 객체를 초기화한다.
     */
    init(): void {}
}
