/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * Html Dom Node 를 확장한다.
 *
 * @file /scripts/DomList.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 4.
 */
class DomList {
    domList;
    dataValues = {};
    /**
     * Dom 목록객체를 생성한다.
     *
     * @param {NodeList} elements - HTML 엘리먼트
     */
    constructor(elements) {
        this.domList = [];
        if (elements instanceof NodeList) {
            elements.forEach((element) => {
                this.domList.push(new Dom(element));
            });
        }
    }
    /**
     * 목록에서 특정위치의 Dom 객체를 가져온다.
     *
     * @param {number} index - 가져올 Dom 의 위치
     * @return {?Dom} dom
     */
    get(index) {
        return this.domList[index] ?? null;
    }
    /**
     * 전체 목록을 가져온다.
     *
     * @return {Dom[]} domList
     */
    getList() {
        return this.domList;
    }
    /**
     * 전체 목록의 갯수를 가져온다.
     *
     * @return {number} count
     */
    getCount() {
        return this.domList.length;
    }
    /**
     * 목록에서 forEach() 함수를 실행한다.
     *
     * @param {Function} callback - forEach 함수
     */
    forEach(callback) {
        this.domList.forEach(callback);
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 Attribute 값을 설정한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @param {string} value - 설정할 값
     * @return {DomList} this
     */
    setAttr(key, value) {
        this.domList.forEach((dom) => {
            dom.setAttr(key, value);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 Attribute 값을 제거한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @return {Dom} this
     */
    removeAttr(key) {
        this.domList.forEach((dom) => {
            dom.removeAttr(key);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 Attribute 값을 가져온다.
     *
     * @param {string} key - 가져올 Attribute키
     * @return {string[]} value - 값
     */
    getAttrs(key) {
        let attrs = [];
        this.domList.forEach((dom) => {
            attrs.push(dom.getAttr(key));
        });
        return attrs;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 Data-Attribute 또는 Data 값을 설정한다.
     *
     * @param {string} key - 값을 가져올 Data-Attribute 키
     * @param {string} value - 값
     * @param {boolean} is_dom - HTML 엘리먼트에 data-attribute 를 생성할 지 여부
     * @return {DomList} this
     */
    setData(key, value, is_dom = true) {
        this.domList.forEach((dom) => {
            dom.setData(key, value, is_dom);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 Data-Attribute 또는 Data 값을 가져온다.
     *
     * @param {string} key - 값을 가져올 Data 키
     * @return {any[]} value - 값
     */
    getDatas(key) {
        let datas = [];
        this.domList.forEach((dom) => {
            datas.push(dom.getData(key));
        });
        return datas;
    }
    /**
     * 목록의 모든 HTML 엘리먼트 스타일을 지정한다.
     *
     * @param {string} key - 스타일명
     * @param {any} value - 스타일값
     * @return {DomList} this
     */
    setStyle(key, value) {
        this.domList.forEach((dom) => {
            dom.setStyle(key, value);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트에 스타일시트(class)를 추가한다.
     *
     * @param {string[]} className - 추가할 클래스명
     * @return {DomList} this
     */
    addClass(...className) {
        this.domList.forEach((dom) => {
            dom.addClass(...className);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 스타일시트(class)를 제거한다.
     *
     * @param {string[]} className - 제거할 클래스명
     * @return {DomList} this
     */
    removeClass(...className) {
        this.domList.forEach((dom) => {
            dom.removeClass(...className);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 스타일시트(class)가 있는 경우 제거하고, 없는 경우 추가한다.
     *
     * @param {string} className - 토글할 클래스명
     * @param {boolean} force - 강제 적용여부
     * @return {DomList} this
     */
    toggleClass(className, force = false) {
        this.domList.forEach((dom) => {
            dom.toggleClass(className, force);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트에 텍스트를 추가한다.
     *
     * @param {string} text - 추가할 텍스트명
     * @return {DomList} this
     */
    text(text) {
        this.domList.forEach((dom) => {
            dom.text(text);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 제일 처음에 Dom 을 추가한다.
     *
     * @param {Dom} child - 추가할 Dom 객체
     * @return {DomList} this
     */
    prepend(child) {
        this.domList.forEach((dom) => {
            dom.prepend(child);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트의 제일 마지막에 Dom 을 추가한다.
     *
     * @param {Dom} child - 추가할 Dom 객체
     * @return {DomList} this
     */
    append(child) {
        this.domList.forEach((dom) => {
            dom.append(child);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트를 보인다.
     */
    show() {
        this.domList.forEach((dom) => {
            dom.show();
        });
    }
    /**
     * 목록의 모든 HTML 엘리먼트를 숨긴다.
     */
    hide() {
        this.domList.forEach((dom) => {
            dom.hide();
        });
    }
    /**
     * 목록의 모든 HTML 엘리먼트를 제거한다.
     */
    remove() {
        this.domList.forEach((dom) => {
            dom.remove();
        });
    }
    /**
     * 목록의 모든 HTML 엘리먼트에 마우스 HOVER 이벤트를 추가한다.
     *
     * @param {EventListener} mouseenter - 마우스 OVER 시 이벤트리스너
     * @param {EventListener} mouseleave - 마우스 LEAVE 시 이벤트리스너
     * @return {DomList} this
     */
    hover(mouseenter, mouseleave) {
        this.domList.forEach((dom) => {
            dom.hover(mouseenter, mouseleave);
        });
        return this;
    }
    /**
     * 목록의 모든 HTML 엘리먼트에 이벤트를 추가한다.
     *
     * @param {string} name - 추가할 이벤트명
     * @param {EventListener} listener - 이벤트리스너
     * @return {DomList} this
     */
    on(name, listener) {
        this.domList.forEach((dom) => {
            dom.on(name, listener);
        });
        return this;
    }
}
