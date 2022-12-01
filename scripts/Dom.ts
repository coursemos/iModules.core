/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * Html Dom Node 를 확장한다.
 *
 * @file /scripts/Dom.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Dom {
    element: HTMLElement | null;
    dataValues: { [key: string]: any } = {};

    /**
     * Dom 객체를 생성한다.
     *
     * @param {HTMLElement} element - HTML 엘리먼트
     */
    constructor(element: HTMLElement) {
        this.element = element;
        if (this.element !== null) {
            if (Html.dataValues.has(this.element) == true) {
                this.dataValues = Html.dataValues.get(this.element);
            } else {
                Html.dataValues.set(this.element, this.dataValues);
            }
        }
    }

    /**
     * HTML 엘리먼트를 가져온다.
     *
     * @return {HTMLElement} element - HTML 엘리먼트
     */
    getEl(): HTMLElement {
        return this.element;
    }

    /**
     * HTML 엘리먼트의 Attribute 값을 설정한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @param {string} value - 설정할 값
     * @return {Dom} this
     */
    setAttr(key: string, value: string): this {
        this.element?.setAttribute(key, value);
        return this;
    }

    /**
     * HTML 엘리먼트의 Attribute 값을 가져온다.
     *
     * @param {string} key - 값을 가져올 Attribute 키
     * @return {string} value - 값
     */
    getAttr(key: string): string {
        return this.element?.getAttribute(key) ?? '';
    }

    /**
     * HTML 엘리먼트의 Data-Attribute 또는 Data 값을 설정한다.
     *
     * @param {string} key - 값을 가져올 Data-Attribute 키
     * @param {string} value - 값
     * @param {boolean} is_dom - HTML 엘리먼트에 data-attribute 를 생성할 지 여부
     */
    setData(key: string, value: any, is_dom: boolean = true): this {
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
    getData(key: string): any {
        return (this.dataValues[key] === undefined ? this.element.dataset[key] : this.dataValues[key]) ?? null;
    }

    /**
     * HTML 엘리먼트의 부모요소를 가져온다.
     *
     * @return {Dom} parent
     */
    getParent(): Dom {
        return Html.el(this.element?.parentElement);
    }

    /**
     * HTML 엘리먼트의 부모요소 트리에서 특정 부모요소를 가져온다.
     *
     * @param {string} checker - 가져올 부모요소
     * @return {Dom} parent
     */
    getParents(checker: string): Dom {
        let parent: Dom = this;
        while (true) {
            parent = parent.getParent();
            if (parent.getEl() == null) break;

            if (parent.is(checker) == true) {
                return parent;
            }
        }

        return null;
    }

    /**
     * HTML 엘리먼트가 특정 노드와 일치하는지 확인한다.
     *
     * @param {string} querySelector - 일치할지 확인할 DOM 쿼리셀럭터
     * @return {boolean} is_equal
     */
    is(querySelector: string): boolean {
        if (this.element == null) {
            return false;
        }

        if (this.element.tagName != 'HTML' && this.element.parentElement == null) {
        }
        console.log(this.element);
        for (const dom of Html.all(querySelector).getList()) {
            if (dom.getEl().isEqualNode(this.element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 스타일시트 등을 통해 현재 HTML 엘리먼트에 적용된 스타일을 가져온다.
     *
     * @param {string} key - 가져올 스타일명
     * @param {string} pseudo - ::before 또는 ::after
     * @return {any} value - 스타일값
     */
    getStyle(key: string, pseudo: string | null = null): string {
        if (this.element === null) return '';

        return window.getComputedStyle(this.element, pseudo).getPropertyValue(key);
    }

    /**
     * HTML 엘리먼트 스타일을 지정한다.
     *
     * @param {string} key - 스타일명
     * @param {any} value - 스타일값
     * @return {Dom} this
     */
    setStyle(key: string, value: string | number): this {
        if (this.element === null) return this;
        this.element.style[key] = value;
        return this;
    }

    /**
     * HTML 엘리먼트의 너비(테두리 및 여백제외)를 가져온다.
     *
     * @return {number} width
     */
    getWidth(): number {
        if (this.element == null) return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
        const scrollBar = this.element.offsetWidth - this.element.clientWidth - border;

        if (style.boxSizing == 'border-box') {
            return this.element.offsetWidth - border - padding;
        } else {
            return this.element.offsetWidth - border - padding - scrollBar;
        }
    }

    /**
     * HTML 엘리먼트의 높이(테두리 및 여백제외)를 가져온다.
     *
     * @return {number} height
     */
    getHeight(): number {
        if (this.element == null) return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        const scrollBar = this.element.offsetHeight - this.element.clientHeight - border;

        if (style.boxSizing == 'border-box') {
            return this.element.offsetHeight - border - padding;
        } else {
            return this.element.offsetHeight - border - padding - scrollBar;
        }
    }

    /**
     * HTML 엘리먼트의 너비(테두리제외)를 가져온다.
     *
     * @return {number} width
     */
    getInnerWidth(): number {
        if (this.element == null) return 0;
        const style = window.getComputedStyle(this.element);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);

        return this.element.offsetWidth - border;
    }

    /**
     * HTML 엘리먼트의 높이(테두리제외)를 가져온다.
     *
     * @return {number} height
     */
    getInnerHeight(): number {
        if (this.element == null) return 0;
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
    getOuterWidth(includeMargin: boolean = false): number {
        if (this.element == null) return 0;
        const style = window.getComputedStyle(this.element);
        const margin = parseFloat(style.marginLeft) + parseFloat(style.marginRight);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        const scrollBar = this.element.offsetWidth - this.element.clientWidth - border;
        if (includeMargin == true) {
            if (style.boxSizing == 'border-box') {
                return this.element.offsetWidth + margin;
            } else {
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
    getOuterHeight(includeMargin: boolean = false): number {
        if (this.element == null) return 0;
        const style = window.getComputedStyle(this.element);
        const margin = parseFloat(style.marginTop) + parseFloat(style.marginBottom);
        const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        const scrollBar = this.element.offsetHeight - this.element.clientHeight - border;
        if (includeMargin == true) {
            if (style.boxSizing == 'border-box') {
                return this.element.offsetHeight + margin;
            } else {
                return this.element.offsetHeight + margin - scrollBar;
            }
        }
        return this.element.offsetHeight;
    }

    /**
     * HTML 엘리먼트의 위치를 가져온다.
     *
     * @return {{top:number, left:number}} offset
     */
    getOffset(): { top: number; left: number } {
        if (this.element == null) return { top: 0, left: 0 };
        return { left: this.element.offsetLeft, top: this.element.offsetTop };
    }

    /**
     * HTML 엘리먼트에 스타일시트(class)를 추가한다.
     *
     * @param {string[]} className - 추가할 클래스명
     * @return {Dom} this
     */
    addClass(...className: string[]): this {
        this.element?.classList.add(...className);
        return this;
    }

    /**
     * HTML 엘리먼트의 스타일시트(class)를 제거한다.
     *
     * @param {string[]} className - 제거할 클래스명
     * @return {Dom} this
     */
    removeClass(...className: string[]): this {
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
    toggleClass(className: string, force?: boolean): this {
        this.element?.classList.toggle(className, force);
        return this;
    }

    /**
     * HTML 엘리먼트에 스타일시트(class)가 존재하는지 확인한다.
     *
     * @param {string} className - 확인할 클래스명
     * @return {boolean} has_class
     */
    hasClass(className: string): boolean {
        return this.element?.classList.contains(className) ?? false;
    }

    /**
     * HTML 엘리먼트에 텍스트를 추가한다.
     *
     * @param {string} text - 추가할 텍스트명
     * @return {Dom} this
     */
    text(text: string): this {
        if (this.element === null) return this;
        this.element.textContent = text;
        return this;
    }

    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 지정한다.
     *
     * @param {string} value - 지정할 값
     * @return {Dom} this
     */
    setValue(value: string): this {
        if (this.element instanceof HTMLInputElement || this.element instanceof HTMLTextAreaElement) {
            this.element.value = value;
        } else {
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
    prepend(child: Dom): this {
        this.element?.prepend(child.getEl());
        return this;
    }

    /**
     * HTML 엘리먼트의 제일 마지막에 Dom 을 추가한다.
     *
     * @param {Dom} child - 추가할 Dom 객체
     * @return {Dom} this
     */
    append(child: Dom | Dom[]): this {
        if (child instanceof Array) {
            child.forEach((item: Dom) => {
                this.element?.append(item.getEl());
            });
        } else if (child instanceof Dom) {
            this.element?.append(child.getEl());
        }
        return this;
    }

    /**
     * HTML 엘리먼트를 다른 Dom 으로 교체한다.
     *
     * @param {Dom} replacement - 교체할 Dom 객체
     * @return {Dom} this
     */
    replaceWith(replacement: Dom): this {
        if (this.element !== null) {
            this.element.replaceWith(replacement.getEl());
        }
        return this;
    }

    /**
     * 현재 Dom 의 상위 Dom 을 가져온다.
     *
     * @return {Dom} parent
     */
    parent(): Dom | null {
        if (this.element?.parentElement === null) {
            return null;
        }

        return new Dom(this.element.parentElement);
    }

    /**
     * 특정 Dom 이 현재 Dom 의 상위 Dom 인지 확인한다.
     *
     * @param {Dom} parent - 확인할 Dom 객체
     * @return {boolean} has_parent
     */
    hasParent(parent: Dom): boolean {
        if (this.element == null || parent.getEl() == null) return false;
        return parent.getEl().contains(this.element);
    }

    /**
     * 특정 Dom 이 현재 Dom 의 하위 Dom 인지 확인한다.
     *
     * @param {Dom} child - 확인할 Dom 객체
     * @return {boolean} has_child
     */
    hasChild(child: Dom): boolean {
        if (this.element == null || child.getEl() == null) return false;
        return this.element.contains(child.getEl());
    }

    /**
     * HTML 엘리먼트를 보인다.
     */
    show(): void {
        if (this.element === null) return;
        if (this.getStyle('display') && this.getStyle('display') != 'none') return;

        if (this.element.style.display == 'none') {
            this.element.style.display = '';
            this.show();
        } else if (this.getData('origin-display')) {
            this.element.style.display = this.getData('origin-display');
            this.show();
        } else {
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
    hide(): void {
        if (this.getStyle('display') == 'none') return;

        if (this.getStyle('display') != 'none') {
            this.setData('origin-display', this.getStyle('display'));
        }
        this.setStyle('display', 'none');
    }

    /**
     * HTML 엘리먼트를 제거한다.
     */
    remove(): void {
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
    animate(
        keyFrames: Keyframe[] | PropertyIndexedKeyframes,
        options?: number | KeyframeAnimationOptions,
        finish?: EventListener
    ): Animation {
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
        this.element?.getAnimations()?.forEach((animate: Animation) => {
            if (animate.constructor.name !== 'Animation') return;
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
    hover(mouseenter: EventListener, mouseleave: EventListener): this {
        this.on('mouseenter', mouseenter);
        this.on('mouseleave', mouseleave);
        return this;
    }

    /**
     * HTML 엘리먼트에 이벤트를 추가한다.
     *
     * @param {string} name - 추가할 이벤트명
     * @param {EventListener} listener - 이벤트리스너
     * @return {Dom} this
     */
    on(name: string, listener: EventListener): this {
        this.element?.addEventListener(name, function (...args) {
            listener(...args);
        });
        return this;
    }

    /**
     * HTML 엘리먼트가 비었는지 확인한다.
     *
     * @return {boolean} is_empty
     */
    isEmpty(): boolean {
        return this.element?.hasChildNodes() === false;
    }
}
