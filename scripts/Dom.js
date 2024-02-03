/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * Html Dom Node 를 확장한다.
 *
 * @file /scripts/Dom.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 23.
 */
class Dom {
    element;
    dataValues = {};
    eventListeners = {};
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
            if (Html.eventListeners.has(this.element) == true) {
                this.eventListeners = Html.eventListeners.get(this.element);
            }
            else {
                Html.eventListeners.set(this.element, this.eventListeners);
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
     * HTML 엘리먼트의 Attribute 값을 제거한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @return {Dom} this
     */
    removeAttr(key) {
        this.element?.removeAttribute(key);
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
        if (this.dataValues[key] === undefined) {
            return (this.element?.dataset[key] ??
                this.element?.dataset[key.toLowerCase().replace(/[^a-zA-Z0-9]+(.)/g, (_m, chr) => chr.toUpperCase())] ??
                null);
        }
        else {
            return this.dataValues[key] ?? null;
        }
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
     * HTML 엘리먼트의 자식요소를 가져온다.
     *
     * @return {Dom[]} children
     */
    getChildren() {
        if (this.element == null) {
            return [];
        }
        const children = [];
        Array.prototype.forEach.call(this.element.children, (item) => {
            children.push(new Dom(item));
        });
        return children;
    }
    /**
     * 현재 DOM 이 부모요소의 몇번째 자식요소인지 가져온다.
     *
     * @return {number} index - 인덱스
     */
    getIndex() {
        if (this.element == null) {
            return -1;
        }
        const children = this.element.parentElement.children ?? [];
        let index = 0;
        for (let i = 0, loop = children.length; i < loop; i++) {
            if (children[i].isEqualNode(this.element) == true) {
                return index;
            }
            if (children[i].nodeType == 1)
                index++;
        }
        return -1;
    }
    /**
     * HTML 엘리먼트가 특정 쿼리셀렉터에 일치하는지 확인한다.
     *
     * @param {string} querySelector - 일치할지 확인할 쿼리셀럭터
     * @return {boolean} is_equal
     */
    is(querySelector) {
        if (this.element == null) {
            return false;
        }
        return this.element.matches(querySelector);
    }
    /**
     * HTML 엘리먼트가 특정 DOM과 동일한지 확인한다.
     *
     * @param {Dom} dom - 동일한지 여부를 확인할 DOM
     * @return {boolean} is_equal
     */
    isEqual(dom) {
        if (this.element == null || dom.getEl() == null) {
            return false;
        }
        return this.element.isEqualNode(dom.getEl());
    }
    /**
     * HTML 엘리먼트가 특정 DOM과 일치하는지 확인한다.
     *
     * @param {Dom} dom - 동일한지 여부를 확인할 DOM
     * @return {boolean} is_equal
     */
    isSame(dom) {
        if (this.element == null || dom.getEl() == null) {
            return false;
        }
        return this.element.isSameNode(dom.getEl());
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
        if (value === null) {
            this.element.style.removeProperty(key);
        }
        else {
            this.element.style[key] = value;
        }
        return this;
    }
    /**
     * HTML 엘리먼트 스타일의 변수값을 지정한다.
     *
     * @param {string} key - 변수명
     * @param {(string|number)} value - 변수값
     * @return {Dom} this
     */
    setStyleProperty(key, value, priority) {
        this.element?.style.setProperty(key, value.toString(), priority);
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
        const rect = this.element.getBoundingClientRect();
        if (includeMargin == true) {
            const style = window.getComputedStyle(this.element);
            const margin = parseFloat(style.marginLeft) + parseFloat(style.marginRight);
            const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
            const scrollBar = this.element.offsetWidth - rect.width - border;
            if (style.boxSizing == 'border-box') {
                return rect.width + margin;
            }
            else {
                return rect.width + margin - scrollBar;
            }
        }
        return rect.width;
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
     * 현재 객체의 상대적인 위치정보를 가져온다.
     *
     * @return
     */
    getRect() {
        return this.element?.getBoundingClientRect();
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
        if (className.length == 0) {
            this.element?.classList.remove(...this.element?.classList);
        }
        else {
            this.element?.classList.remove(...className);
        }
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
     * HTML 엘리먼트에 HTML 태그를 추가한다.
     *
     * @param {string} html - 추가할 HTML
     * @return {Dom} this
     */
    html(html) {
        if (this.element === null)
            return this;
        this.element.innerHTML = html;
        return this;
    }
    /**
     * Dom 객체를 HTML 코드로 변환한다.
     *
     * @return {string} html
     */
    toHtml(is_inner_html = false) {
        if (is_inner_html === true) {
            return this.element?.innerHTML ?? '';
        }
        else {
            return this.element?.outerHTML ?? '';
        }
    }
    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 지정한다.
     *
     * @param {string|boolean} value - 지정할 값
     * @return {Dom} this
     */
    setValue(value) {
        if (this.element instanceof HTMLInputElement ||
            this.element instanceof HTMLTextAreaElement ||
            this.element instanceof HTMLSelectElement) {
            const originValue = this.getValue();
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                if (typeof value === 'boolean') {
                    this.element.checked = value;
                }
                else {
                    this.element.checked = this.element.getAttribute('value') == value;
                }
            }
            else if (typeof value == 'string') {
                this.element.value = value;
            }
            if (originValue !== this.getValue()) {
                this.trigger('change');
            }
        }
        else {
            console.error('HTMLElement is not HTMLInputElement');
        }
        return this;
    }
    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 가져온다.
     *
     * @return {string} value - 값
     */
    getValue() {
        if (this.element instanceof HTMLInputElement ||
            this.element instanceof HTMLTextAreaElement ||
            this.element instanceof HTMLSelectElement) {
            return this.element.value;
        }
    }
    /**
     * HTML 엘리먼트가 RADIO, CHECKBOX 인 경우 선택여부를 가져온다.
     *
     * @return {boolean} checked
     */
    isChecked() {
        if (this.element instanceof HTMLInputElement) {
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                return this.element.checked;
            }
        }
        else {
            return false;
        }
    }
    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 초기화한다.
     *
     * @return {Dom} this
     */
    reset() {
        if (this.element instanceof HTMLInputElement) {
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                this.element.checked = this.element.getAttribute('checked') ? true : false;
            }
            else {
                this.element.value = this.element.getAttribute('value') ? this.element.getAttribute('value') : null;
            }
        }
        else if (this.element instanceof HTMLTextAreaElement) {
            this.element.value = this.element.innerHTML ? this.element.innerHTML : null;
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
     * 현재 Dom 의 이전 Dom 을 가져온다.
     *
     * @param {string} selector - 다음에 위치한 Dom 중 찾을 selector (NULL 인 경우 바로 다음 Dom 을 반환한다.)
     * @return {Dom} prev
     */
    prev(selector = null) {
        let current = this.element;
        while (current !== null) {
            let prev = current.previousElementSibling;
            if (prev === null) {
                return null;
            }
            if (selector === null) {
                return new Dom(prev);
            }
            else {
                if (new Dom(prev).is(selector) == true) {
                    return new Dom(prev);
                }
                current = prev;
            }
        }
    }
    /**
     * 현재 Dom 의 다음 Dom 을 가져온다.
     *
     * @param {string} selector - 다음에 위치한 Dom 중 찾을 selector (NULL 인 경우 바로 다음 Dom 을 반환한다.)
     * @return {Dom} next
     */
    next(selector = null) {
        let current = this.element;
        while (current !== null) {
            let next = current.nextElementSibling;
            if (next === null) {
                return null;
            }
            if (selector === null) {
                return new Dom(next);
            }
            else {
                if (new Dom(next).is(selector) == true) {
                    return new Dom(next);
                }
                current = next;
            }
        }
    }
    /**
     * HTML 엘리먼트에 포커스를 지정한다.
     */
    focus() {
        this.element?.focus();
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
     * HTML 엘리먼트를 좌우로 흔든다.
     *
     * @param {number} times - 반복횟수(기본값 : 4)
     * @param {number} duration - 애니메이션시각(기본값 : 800)
     * @param {number} distance - 이동거리(기본값 : 10)
     */
    shake(times = 4, duration = 800, distance = 10) {
        const keyFrames = [];
        for (let i = 0; i < times; i++) {
            keyFrames.push({ transform: 'translateX(' + distance + 'px)' });
            keyFrames.push({ transform: 'translateX(' + distance * -1 + 'px)' });
        }
        keyFrames.push({ transform: 'translateX(0px)' });
        this.element?.animate(keyFrames, { duration: duration, easing: 'ease-out' });
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
     * @param {any} options - 이벤트리스너 옵션
     * @return {Dom} this
     */
    on(name, listener, options = null) {
        this.eventListeners[name] ??= [];
        this.eventListeners[name].push(listener);
        if (name == 'longpress') {
            this.element?.addEventListener('pointerdown', (e) => {
                if (e.pointerType == 'touch' || e.pointerType == 'pen') {
                    this.setData('longpress', {
                        x: e.clientX,
                        y: e.clientY,
                        timeout: setTimeout(() => {
                            e.stopImmediatePropagation();
                            e.preventDefault();
                            e.stopPropagation();
                            listener(e);
                        }, 1000),
                        cancel: (e) => {
                            const longpress = this.getData('longpress') ?? null;
                            if (e.type == 'pointermove') {
                                const diffX = Math.abs(longpress.x - e.clientX);
                                const diffY = Math.abs(longpress.y - e.clientY);
                                if (diffX < 10 && diffY < 10) {
                                    return;
                                }
                            }
                            clearTimeout(longpress.timeout);
                            Html.pointerListeners.delete(e.pointerId);
                            this.setData('longpress', null);
                        },
                    });
                    Html.pointerListeners.set(e.pointerId, this);
                }
            });
        }
        else {
            this.element?.addEventListener(name, listener, options);
            return this;
        }
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
     * 이벤트를 발생시킨다.
     *
     * @param {string} name - 발생시킬 이벤트명
     * @param {Event} e - 원본 이벤트객체
     */
    trigger(name, e = null) {
        if (name == 'pointermove' || name == 'pointerup' || name == 'pointercancel') {
            this.getData('longpress')?.cancel(e);
        }
        this.element?.dispatchEvent(new Event(name));
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
    /**
     * HTMP 엘리먼트가 숨겨진 상태인지 확인한다.
     *
     * @return {boolean} is_hidden
     */
    isHidden() {
        if (this.element === null) {
            return true;
        }
        return this.element.offsetParent === null;
    }
    /**
     * HTML 엘리먼트의 비활성 여부를 설정한다.
     *
     * @param {boolean} disabled - 비활성여부
     */
    setDisabled(disabled) {
        if (disabled == true) {
            this.setAttr('disabled', 'disabled');
        }
        else {
            this.removeAttr('disabled');
        }
    }
    /**
     * HTML 엘리먼트를 활성화한다.
     */
    enable() {
        this.setDisabled(false);
        if (this.hasClass('loading') == true) {
            this.removeClass('loading');
            this.setStyle('width', null);
            this.setStyle('height', null);
        }
    }
    /**
     * HTML 엘리먼트를 비활성화한다.
     *
     * @param {boolean} is_loading - 로딩 인디케이터를 보일지 여부
     */
    disable(is_loading = false) {
        this.setDisabled(true);
        if (is_loading == true) {
            this.setStyle('width', this.getOuterWidth() + 'px');
            this.setStyle('height', this.getOuterHeight() + 'px');
            this.addClass('loading');
        }
    }
    /**
     * HTML 엘리먼트를 복제한다.
     *
     * @param {boolean} is_event_listeners - 이벤트 리스너를 복제할지 여부
     * @return {Dom} clone
     */
    clone(is_event_listeners = false) {
        if (this.element === null) {
            return new Dom(null);
        }
        const clone = this.element.cloneNode(true);
        const $clone = new Dom(clone);
        if (is_event_listeners == true) {
            const children = clone.querySelectorAll('*');
            this.element.querySelectorAll('*').forEach((element, index) => {
                const dom = new Dom(element);
                for (const name in dom.eventListeners) {
                    for (const listener of dom.eventListeners[name]) {
                        let $child = new Dom(children[index]);
                        $child.on(name, listener);
                    }
                }
            });
        }
        if (this.element instanceof HTMLElement) {
            return $clone;
        }
    }
}
