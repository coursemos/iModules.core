/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * Html Dom Node 를 확장한다.
 *
 * @file /scripts/Dom.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 26.
 */
class Dom {
    element;
    dataValues = {};
    /**
     * Dom 객체를 생성한다.
     *
     * @param {HTMLElement} element - HTML 엘리먼트
     */
    constructor(element) {
        this.element = element;
        if (this.element !== null) {
            if (Html.dataValues.has(this.element) == true) {
                this.dataValues = Html.dataValues.get(this.element);
            }
            else {
                Html.dataValues.set(this.element, this.dataValues);
            }
        }
    }
    /**
     * HTML 엘리먼트를 가져온다.
     *
     * @return {HTMLElement} element - HTML 엘리먼트
     */
    getEl() {
        return this.element;
    }
    /**
     * HTML 엘리먼트의 Attribute 값을 설정한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @param {string} value - 설정할 값
     * @return {Dom} this
     */
    setAttr(key, value) {
        this.element?.setAttribute(key, value);
        return this;
    }
    /**
     * HTML 엘리먼트의 Attribute 값을 가져온다.
     *
     * @param {string} key - 값을 가져올 Attribute 키
     * @return {string} value - 값
     */
    getAttr(key) {
        return this.element?.getAttribute(key) ?? '';
    }
    /**
     * HTML 엘리먼트의 Data-Attribute 또는 Data 값을 설정한다.
     *
     * @param {string} key - 값을 가져올 Data-Attribute 키
     * @param {any} value - 값
     * @param {boolean} is_dom - HTML 엘리먼트에 data-attribute 를 생성할 지 여부
     */
    setData(key, value, is_dom = true) {
        this.dataValues[key] = value;
        if (is_dom === true && (typeof value == 'string' || typeof value == 'number')) {
            this.setAttr('data-' + key, value.toString());
        }
        return this;
    }
    /**
     * HTML 엘리먼트의 Data-Attribute 또는 Data 값을 가져온다.
     *
     * @param {string} key - 값을 가져올 Data 키
     * @return {any} value - 값
     */
    getData(key) {
        return (this.dataValues[key] === undefined ? this.element.dataset[key] : this.dataValues[key]) ?? null;
    }
    /**
     * HTML 엘리먼트의 부모요소를 가져온다.
     *
     * @return {Dom} parent
     */
    getParent() {
        if (this.element?.parentElement === null) {
            return null;
        }
        return new Dom(this.element.parentElement);
    }
    /**
     * HTML 엘리먼트의 부모요소 트리에서 특정 부모요소를 가져온다.
     *
     * @param {string} checker - 가져올 부모요소
     * @return {Dom} parent
     */
    getParents(checker) {
        if (this.element == null) {
            return null;
        }
        const parent = this.element.closest(checker);
        if (parent == null) {
            return null;
        }
        return Html.el(parent);
    }
    /**
     * HTML 엘리먼트가 특정 노드와 일치하는지 확인한다.
     *
     * @param {string} querySelector - 일치할지 확인할 DOM 쿼리셀럭터
     * @return {boolean} is_equal
     */
    is(querySelector) {
        if (this.element == null) {
            return false;
        }
        return this.element.matches(querySelector);
    }
    /**
     * 스타일시트 등을 통해 현재 HTML 엘리먼트에 적용된 스타일을 가져온다.
     *
     * @param {string} key - 가져올 스타일명
     * @param {string} pseudo - ::before 또는 ::after
     * @return {string} value - 스타일값
     */
    getStyle(key, pseudo = null) {
        if (this.element === null)
            return '';
        return window.getComputedStyle(this.element, pseudo).getPropertyValue(key);
    }
    /**
     * HTML 엘리먼트 스타일을 지정한다.
     *
     * @param {string} key - 스타일명
     * @param {(string|number)} value - 스타일값
     * @return {Dom} this
     */
    setStyle(key, value) {
        if (this.element === null)
            return this;
        this.element.style[key] = value;
        return this;
    }
    /**
     * HTML 엘리먼트의 너비(테두리 및 여백제외)를 가져온다.
     *
     * @return {number} width
     */
    getWidth() {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
        const scrollBar = this.element.offsetWidth - this.element.clientWidth - border;
        if (style.boxSizing == 'border-box') {
            return this.element.offsetWidth - border - padding;
        }
        else {
            return this.element.offsetWidth - border - padding - scrollBar;
        }
    }
    /**
     * HTML 엘리먼트의 높이(테두리 및 여백제외)를 가져온다.
     *
     * @return {number} height
     */
    getHeight() {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        const scrollBar = this.element.offsetHeight - this.element.clientHeight - border;
        if (style.boxSizing == 'border-box') {
            return this.element.offsetHeight - border - padding;
        }
        else {
            return this.element.offsetHeight - border - padding - scrollBar;
        }
    }
    /**
     * HTML 엘리먼트의 너비(테두리제외)를 가져온다.
     *
     * @return {number} width
     */
    getInnerWidth() {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        return this.element.offsetWidth - border;
    }
    /**
     * HTML 엘리먼트의 높이(테두리제외)를 가져온다.
     *
     * @return {number} height
     */
    getInnerHeight() {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        return this.element.offsetHeight - border;
    }
    /**
     * HTML 엘리먼트의 너비(테두리 및 여백포함)를 가져온다.
     *
     * @param {boolean} includeMargin - 외부간격(margin)을 포함할지 여부
     * @return {number} width
     */
    getOuterWidth(includeMargin = false) {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const margin = parseFloat(style.marginLeft) + parseFloat(style.marginRight);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        const scrollBar = this.element.offsetWidth - this.element.clientWidth - border;
        if (includeMargin == true) {
            if (style.boxSizing == 'border-box') {
                return this.element.offsetWidth + margin;
            }
            else {
                return this.element.offsetWidth + margin - scrollBar;
            }
        }
        return this.element.offsetWidth;
    }
    /**
     * HTML 엘리먼트의 높이(테두리 및 여백포함)를 가져온다.
     *
     * @param {boolean} includeMargin - 외부간격(margin)을 포함할지 여부
     * @return {number} height
     */
    getOuterHeight(includeMargin = false) {
        if (this.element == null)
            return 0;
        const style = window.getComputedStyle(this.element);
        const margin = parseFloat(style.marginTop) + parseFloat(style.marginBottom);
        const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        const scrollBar = this.element.offsetHeight - this.element.clientHeight - border;
        if (includeMargin == true) {
            if (style.boxSizing == 'border-box') {
                return this.element.offsetHeight + margin;
            }
            else {
                return this.element.offsetHeight + margin - scrollBar;
            }
        }
        return this.element.offsetHeight;
    }
    /**
     * HTML 엘리먼트의 스크롤 너비를 가져온다.
     *
     * @return {number} scrollWidth
     */
    getScrollWidth() {
        if (this.element == null)
            return 0;
        return this.element.scrollWidth;
    }
    /**
     * HTML 엘리먼트의 스크롤 높이를 가져온다.
     *
     * @return {number} scrollHeight
     */
    getScrollHeight() {
        if (this.element == null)
            return 0;
        return this.element.scrollHeight;
    }
    /**
     * HTML 엘리먼트의 문서 전체 기준으로 위치를 가져온다.
     *
     * @return {{top:number, left:number}} offset
     */
    getOffset() {
        if (this.element == null)
            return { top: 0, left: 0 };
        let rect = this.element.getBoundingClientRect();
        let view = this.element.ownerDocument.defaultView;
        return {
            top: rect.top + view.pageYOffset,
            left: rect.left + view.pageXOffset,
        };
    }
    /**
     * HTML 엘리먼트의 부모 객체 기준으로 위치를 가져온다.
     *
     * @return {{top:number, left:number}} offset
     */
    getPosition() {
        if (this.element == null)
            return { top: 0, left: 0 };
        let marginTop = parseInt(this.getStyle('margin-top').replace(/px$/, ''));
        let marginLeft = parseInt(this.getStyle('margin-left').replace(/px$/, ''));
        if (this.getStyle('position') == 'fixed') {
            let offset = this.element.getBoundingClientRect();
            return { top: offset.top - marginTop, left: offset.left - marginLeft };
        }
        else {
            let parentOffset = { top: 0, left: 0 };
            let offset = this.getOffset();
            let doc = this.element.ownerDocument;
            let offsetParent = this.element.offsetParent || doc.documentElement;
            while (offsetParent &&
                (offsetParent === doc.body || offsetParent === doc.documentElement) &&
                window.getComputedStyle(offsetParent).getPropertyValue('position') === 'static') {
                offsetParent = offsetParent.parentElement;
            }
            if (offsetParent && offsetParent !== this.element && offsetParent.nodeType === 1) {
                let $parent = Html.el(offsetParent);
                parentOffset = $parent.getOffset();
                parentOffset.top += parseInt(this.getStyle('border-top-width').replace(/px$/, ''));
                parentOffset.left += parseInt(this.getStyle('border-left-width').replace(/px$/, ''));
            }
            return {
                top: offset.top - parentOffset.top - marginTop,
                left: offset.left - parentOffset.left - marginLeft,
            };
        }
    }
    /**
     * HTML 엘리먼트의 스크롤 위치를 가져온다.
     *
     * @return {{left:number, top:number}} scroll
     */
    getScroll() {
        if (this.element == null)
            return { left: 0, top: 0 };
        return { left: this.element.scrollLeft, top: this.element.scrollTop };
    }
    /**
     * HTML 엘리먼트의 스크롤 위치를 설정한다.
     *
     * @param {number} left - 좌측위치 (NULL 인경우 이동하지 않음)
     * @param {number} top - 상단위치 (NULL 인경우 이동하지 않음)
     * @param {boolean} animate - 애니메이션 여부
     */
    setScroll(left = null, top = null, animate = true) {
        if (this.element == null)
            return;
        let options = {
            behavior: animate === true ? 'smooth' : 'auto',
        };
        if (left !== null)
            options.left = left;
        if (top !== null)
            options.top = top;
        this.element.scroll(options);
    }
    /**
     * HTML 엘리먼트에 스타일시트(class)를 추가한다.
     *
     * @param {string[]} className - 추가할 클래스명
     * @return {Dom} this
     */
    addClass(...className) {
        this.element?.classList.add(...className);
        return this;
    }
    /**
     * HTML 엘리먼트의 스타일시트(class)를 제거한다.
     *
     * @param {string[]} className - 제거할 클래스명
     * @return {Dom} this
     */
    removeClass(...className) {
        this.element?.classList.remove(...className);
        return this;
    }
    /**
     * HTML 엘리먼트의 스타일시트(class)가 있는 경우 제거하고, 없는 경우 추가한다.
     *
     * @param {string} className - 토글할 클래스명
     * @param {boolean} force - 강제 적용여부
     * @return {Dom} this
     */
    toggleClass(className, force) {
        this.element?.classList.toggle(className, force);
        return this;
    }
    /**
     * HTML 엘리먼트에 스타일시트(class)가 존재하는지 확인한다.
     *
     * @param {string} className - 확인할 클래스명
     * @return {boolean} has_class
     */
    hasClass(className) {
        return this.element?.classList.contains(className) ?? false;
    }
    /**
     * HTML 엘리먼트에 텍스트를 추가한다.
     *
     * @param {string} text - 추가할 텍스트명
     * @return {Dom} this
     */
    text(text) {
        if (this.element === null)
            return this;
        this.element.textContent = text;
        return this;
    }
    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 지정한다.
     *
     * @param {string} value - 지정할 값
     * @return {Dom} this
     */
    setValue(value) {
        if (this.element instanceof HTMLInputElement || this.element instanceof HTMLTextAreaElement) {
            this.element.value = value;
        }
        else {
            console.error('HTMLElement is not HTMLInputElement');
        }
        return this;
    }
    /**
     * HTML 엘리먼트의 제일 처음에 Dom 을 추가한다.
     *
     * @param {Dom} child - 추가할 Dom 객체
     * @return {Dom} this
     */
    prepend(child) {
        this.element?.prepend(child.getEl());
        return this;
    }
    /**
     * HTML 엘리먼트의 제일 마지막에 Dom 을 추가한다.
     *
     * @param {Dom} child - 추가할 Dom 객체
     * @param {number} position - 추가할 위치 (NULL 인 경우 제일 마지막 위치)
     * @return {Dom} this
     */
    append(child, position = null) {
        if (child instanceof Array) {
            child.forEach((item) => {
                this.append(item, position === null || position < 0 ? position : position++);
            });
        }
        else if (child instanceof Dom) {
            if (position === null || position >= (this.element?.children.length ?? 0)) {
                this.element?.append(child.getEl());
            }
            else if (position < 0 && Math.abs(position) >= (this.element?.children.length ?? 0)) {
                this.element?.prepend(child.getEl());
            }
            else if (position < 0) {
                this.element?.insertBefore(child.getEl(), this.element?.children[this.element?.children.length + position]);
            }
            else {
                this.element?.insertBefore(child.getEl(), this.element?.children[position]);
            }
        }
        return this;
    }
    /**
     * HTML 엘리먼트를 다른 Dom 으로 교체한다.
     *
     * @param {Dom} replacement - 교체할 Dom 객체
     * @return {Dom} this
     */
    replaceWith(replacement) {
        if (this.element !== null) {
            this.element.replaceWith(replacement.getEl());
        }
        return this;
    }
    /**
     * 특정 Dom 이 현재 Dom 의 상위 Dom 인지 확인한다.
     *
     * @param {Dom} parent - 확인할 Dom 객체
     * @return {boolean} has_parent
     */
    hasParent(parent) {
        if (this.element == null || parent.getEl() == null)
            return false;
        return parent.getEl().contains(this.element);
    }
    /**
     * 특정 Dom 이 현재 Dom 의 하위 Dom 인지 확인한다.
     *
     * @param {Dom} child - 확인할 Dom 객체
     * @return {boolean} has_child
     */
    hasChild(child) {
        if (this.element == null || child.getEl() == null)
            return false;
        return this.element.contains(child.getEl());
    }
    /**
     * HTML 엘리먼트를 보인다.
     */
    show() {
        if (this.element === null)
            return;
        if (this.getStyle('display') && this.getStyle('display') != 'none')
            return;
        if (this.element.style.display == 'none') {
            this.element.style.display = '';
            this.show();
        }
        else if (this.getData('origin-display')) {
            this.element.style.display = this.getData('origin-display');
            this.show();
        }
        else {
            const origin = document.createElement(this.element.tagName);
            document.body.appendChild(origin);
            const display = window.getComputedStyle(origin).display;
            origin.remove();
            this.setStyle('display', display);
        }
    }
    /**
     * HTML 엘리먼트를 숨긴다.
     */
    hide() {
        if (this.getStyle('display') == 'none')
            return;
        if (this.getStyle('display') != 'none') {
            this.setData('origin-display', this.getStyle('display'));
        }
        this.setStyle('display', 'none');
    }
    /**
     * HTML 엘리먼트를 제거한다.
     */
    remove() {
        this.element?.remove();
    }
    /**
     * HTML 엘리먼트에 애니메이션을 추가한다.
     *
     * @param {(Keyframe[]|PropertyIndexedKeyframes)} keyFrames - 키프레임
     * @param {(number|KeyframeAnimationOptions)} options - 옵션
     * @param {EventListener} finish - 애니메이션 완료시 실행할 콜백함수
     * @return {Animation} animation - 애니메이션 객체
     */
    animate(keyFrames, options, finish) {
        this.stop();
        const animate = this.element?.animate(keyFrames, options);
        if (finish) {
            animate.addEventListener('finish', finish);
        }
        return animate;
    }
    /**
     * HTML 엘리먼트의 재생중인 애니메이션을 중단한다.
     */
    stop() {
        this.element?.getAnimations()?.forEach((animate) => {
            if (animate.constructor.name !== 'Animation')
                return;
            animate.commitStyles();
            animate.cancel();
        });
    }
    /**
     * HTML 엘리먼트에 마우스 HOVER 이벤트를 추가한다.
     *
     * @param {EventListener} mouseenter - 마우스 OVER 시 이벤트리스너
     * @param {EventListener} mouseleave - 마우스 LEAVE 시 이벤트리스너
     * @return {Dom} this
     */
    hover(mouseenter, mouseleave) {
        this.on('mouseenter', (e) => {
            mouseenter(e);
            e.preventDefault();
            e.stopImmediatePropagation();
        });
        this.on('mouseleave', (e) => {
            mouseleave(e);
            e.preventDefault();
            e.stopImmediatePropagation();
        });
        return this;
    }
    /**
     * HTML 엘리먼트에 이벤트를 추가한다.
     *
     * @param {string} name - 추가할 이벤트명
     * @param {EventListener} listener - 이벤트리스너
     * @return {Dom} this
     */
    on(name, listener) {
        this.element?.addEventListener(name, listener);
        return this;
    }
    /**
     * HTML 엘리먼트에 이벤트를 제거한다.
     *
     * @param {string} name - 제거할 이벤트명
     * @param {EventListener} listener - 이벤트리스너
     * @return {Dom} this
     */
    off(name, listener) {
        this.element?.removeEventListener(name, listener);
        return this;
    }
    /**
     * HTML 엘리먼트의 모든 하위요소를 제거한다.
     */
    empty() {
        if (this.element == null)
            return;
        this.element.innerHTML = '';
    }
    /**
     * HTML 엘리먼트가 비었는지 확인한다.
     *
     * @return {boolean} is_empty
     */
    isEmpty() {
        return this.element?.hasChildNodes() === false;
    }
}
