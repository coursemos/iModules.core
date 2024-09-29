/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * Html Dom Node 를 확장한다.
 *
 * @file /scripts/Dom.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 8. 2.
 */
class Dom {
    element: HTMLElement | null;
    dataset: { [key: string]: any } = {};
    eventListeners: { [name: string]: EventListener[] } = {};

    /**
     * Dom 객체를 생성한다.
     *
     * @param {HTMLElement} element - HTML 엘리먼트
     */
    constructor(element: HTMLElement) {
        this.element = element;
        if (this.element !== null) {
            if (Html.dataset.has(this.element) == true) {
                this.dataset = Html.dataset.get(this.element);
            } else {
                Html.dataset.set(this.element, this.dataset);
            }

            if (Html.eventListeners.has(this.element) == true) {
                this.eventListeners = Html.eventListeners.get(this.element);
            } else {
                Html.eventListeners.set(this.element, this.eventListeners);
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
     * HTML 엘리먼트의 Attribute 값을 제거한다.
     *
     * @param {string} key - 설정할 Attribute키
     * @return {Dom} this
     */
    removeAttr(key: string): this {
        this.element?.removeAttribute(key);
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
     * @param {any} value - 값
     * @param {boolean} is_dom - HTML 엘리먼트에 data-attribute 를 생성할 지 여부
     */
    setData(key: string, value: any, is_dom: boolean = true): this {
        this.dataset[key] = value;
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
        if (this.dataset[key] === undefined) {
            return (
                this.element?.dataset[key] ??
                this.element?.dataset[key.toLowerCase().replace(/[^a-zA-Z0-9]+(.)/g, (_m, chr) => chr.toUpperCase())] ??
                null
            );
        } else {
            return this.dataset[key] ?? null;
        }
    }

    /**
     * HTML 엘리먼트의 부모요소를 가져온다.
     *
     * @return {Dom} parent
     */
    getParent(): Dom | null {
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
    getParents(checker: string): Dom {
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
    getChildren(): Dom[] {
        if (this.element == null) {
            return [];
        }

        const children: Dom[] = [];
        Array.prototype.forEach.call(this.element.children, (item: HTMLElement) => {
            children.push(new Dom(item));
        });

        return children;
    }

    /**
     * 현재 DOM 이 부모요소의 몇번째 자식요소인지 가져온다.
     *
     * @return {number} index - 인덱스
     */
    getIndex(): number {
        if (this.element == null) {
            return -1;
        }

        const children = this.element.parentElement.children ?? [];
        let index = 0;
        for (let i = 0, loop = children.length; i < loop; i++) {
            if (children[i].isEqualNode(this.element) == true) {
                return index;
            }
            if (children[i].nodeType == 1) index++;
        }

        return -1;
    }

    /**
     * HTML 엘리먼트가 특정 쿼리셀렉터에 일치하는지 확인한다.
     *
     * @param {string} querySelector - 일치할지 확인할 쿼리셀럭터
     * @return {boolean} is_equal
     */
    is(querySelector: string): boolean {
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
    isEqual(dom: Dom): boolean {
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
    isSame(dom: Dom): boolean {
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
    getStyle(key: string, pseudo: string | null = null): string {
        if (this.element === null) return '';
        return window.getComputedStyle(this.element, pseudo).getPropertyValue(key);
    }

    /**
     * HTML 엘리먼트 스타일을 지정한다.
     *
     * @param {string} key - 스타일명
     * @param {(string|number)} value - 스타일값
     * @return {Dom} this
     */
    setStyle(key: string, value: string | number): this {
        if (this.element === null) return this;
        if (value === null) {
            this.element.style.removeProperty(key);
        } else {
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
    setStyleProperty(key: string, value: string | number, priority?: string): this {
        this.element?.style.setProperty(key, value.toString(), priority);
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
        const rect = this.element.getBoundingClientRect();

        if (includeMargin == true) {
            const style = window.getComputedStyle(this.element);
            const margin = parseFloat(style.marginLeft) + parseFloat(style.marginRight);
            const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
            const scrollBar = this.element.offsetWidth - rect.width - border;

            if (style.boxSizing == 'border-box') {
                return rect.width + margin;
            } else {
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
     * HTML 엘리먼트의 스크롤 너비를 가져온다.
     *
     * @param {boolean} is_border - 테두리굵기 포함여부
     * @return {number} scrollWidth
     */
    getScrollWidth(is_border: boolean = false): number {
        if (this.element == null) return 0;

        let border = 0;
        if (is_border == true) {
            const style = window.getComputedStyle(this.element);
            border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        }

        return this.element.scrollWidth + border;
    }

    /**
     * HTML 엘리먼트의 스크롤 높이를 가져온다.
     *
     * @param {boolean} is_border - 테두리굵기 포함여부
     * @return {number} scrollHeight
     */
    getScrollHeight(is_border: boolean = false): number {
        if (this.element == null) return 0;

        let border = 0;
        if (is_border == true) {
            const style = window.getComputedStyle(this.element);
            border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        }

        return this.element.scrollHeight + border;
    }

    /**
     * HTML 엘리먼트의 문서 전체 기준으로 위치를 가져온다.
     *
     * @return {{top:number, left:number}} offset
     */
    getOffset(): { top: number; left: number } {
        if (this.element == null) return { top: 0, left: 0 };

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
    getPosition(): { top: number; left: number } {
        if (this.element == null) return { top: 0, left: 0 };

        let marginTop = parseInt(this.getStyle('margin-top').replace(/px$/, ''));
        let marginLeft = parseInt(this.getStyle('margin-left').replace(/px$/, ''));

        if (this.getStyle('position') == 'fixed') {
            let offset = this.element.getBoundingClientRect();
            return { top: offset.top - marginTop, left: offset.left - marginLeft };
        } else {
            let parentOffset = { top: 0, left: 0 };
            let offset = this.getOffset();
            let doc = this.element.ownerDocument;
            let offsetParent = this.element.offsetParent || doc.documentElement;
            while (
                offsetParent &&
                (offsetParent === doc.body || offsetParent === doc.documentElement) &&
                window.getComputedStyle(offsetParent).getPropertyValue('position') === 'static'
            ) {
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
    getRect(): DOMRect {
        return this.element?.getBoundingClientRect();
    }

    /**
     * HTML 엘리먼트의 스크롤 위치를 가져온다.
     *
     * @return {{left:number, top:number}} scroll
     */
    getScroll(): { left: number; top: number } {
        if (this.element == null) return { left: 0, top: 0 };
        return { left: this.element.scrollLeft, top: this.element.scrollTop };
    }

    /**
     * HTML 엘리먼트의 스크롤 위치를 설정한다.
     *
     * @param {number} left - 좌측위치 (NULL 인경우 이동하지 않음)
     * @param {number} top - 상단위치 (NULL 인경우 이동하지 않음)
     * @param {boolean} animate - 애니메이션 여부
     */
    setScroll(left: number = null, top: number = null, animate: boolean = true): void {
        if (this.element == null) return;

        let options: ScrollToOptions = {
            behavior: animate === true ? 'smooth' : 'auto',
        };
        if (left !== null) options.left = left;
        if (top !== null) options.top = top;
        this.element.scroll(options);
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
        if (className.length == 0) {
            this.element?.classList.remove(...this.element?.classList);
        } else {
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
     * HTML 엘리먼트에 HTML 태그를 추가한다.
     *
     * @param {string} html - 추가할 HTML
     * @return {Dom} this
     */
    html(html: string): this {
        if (this.element === null) return this;
        this.element.innerHTML = html;
        return this;
    }

    /**
     * Dom 객체를 HTML 코드로 변환한다.
     *
     * @return {string} html
     */
    toHtml(is_inner_html: boolean = false): string {
        if (is_inner_html === true) {
            return this.element?.innerHTML ?? '';
        } else {
            return this.element?.outerHTML ?? '';
        }
    }

    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 지정한다.
     *
     * @param {string|boolean} value - 지정할 값
     * @return {Dom} this
     */
    setValue(value: string | boolean): this {
        if (
            this.element instanceof HTMLInputElement ||
            this.element instanceof HTMLTextAreaElement ||
            this.element instanceof HTMLSelectElement
        ) {
            const originValue = this.getValue();
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                if (typeof value === 'boolean') {
                    (this.element as HTMLInputElement).checked = value;
                } else {
                    (this.element as HTMLInputElement).checked = this.element.getAttribute('value') == value;
                }
            } else if (typeof value == 'string') {
                this.element.value = value;
            }

            if (originValue !== this.getValue()) {
                this.trigger('change');
            }
        } else {
            console.error('HTMLElement is not HTMLInputElement');
        }
        return this;
    }

    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 가져온다.
     *
     * @return {string} value - 값
     */
    getValue(): string {
        if (
            this.element instanceof HTMLInputElement ||
            this.element instanceof HTMLTextAreaElement ||
            this.element instanceof HTMLSelectElement
        ) {
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                if ((this.element as HTMLInputElement).checked == true) {
                    return this.element.value;
                } else {
                    return null;
                }
            } else {
                return this.element.value;
            }
        }
    }

    /**
     * HTML 엘리먼트가 RADIO, CHECKBOX 인 경우 선택여부를 가져온다.
     *
     * @return {boolean} checked
     */
    isChecked(): boolean {
        if (this.element instanceof HTMLInputElement) {
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                return this.element.checked;
            }
        } else {
            return false;
        }
    }

    /**
     * HTML 엘리먼트가 INPUT, TEXTAREA, SELECT 요소인 경우 값을 초기화한다.
     *
     * @return {Dom} this
     */
    reset(): this {
        if (this.element instanceof HTMLInputElement) {
            if (this.element.getAttribute('type') == 'checkbox' || this.element.getAttribute('type') == 'radio') {
                (this.element as HTMLInputElement).checked = this.element.getAttribute('checked') ? true : false;
            } else {
                this.element.value = this.element.getAttribute('value') ? this.element.getAttribute('value') : null;
            }
        } else if (this.element instanceof HTMLTextAreaElement) {
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
    prepend(child: Dom): this {
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
    append(child: Dom | Dom[], position: number = null): this {
        if (child instanceof Array) {
            child.forEach((item: Dom) => {
                this.append(item, position === null || position < 0 ? position : position++);
            });
        } else if (child instanceof Dom) {
            if (position === null || position >= (this.element?.children.length ?? 0)) {
                this.element?.append(child.getEl());
            } else if (position < 0 && Math.abs(position) >= (this.element?.children.length ?? 0)) {
                this.element?.prepend(child.getEl());
            } else if (position < 0) {
                this.element?.insertBefore(
                    child.getEl(),
                    this.element?.children[this.element?.children.length + position]
                );
            } else {
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
    replaceWith(replacement: Dom): this {
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
     * 현재 Dom 의 이전 Dom 을 가져온다.
     *
     * @param {string} selector - 다음에 위치한 Dom 중 찾을 selector (NULL 인 경우 바로 다음 Dom 을 반환한다.)
     * @return {Dom} prev
     */
    prev(selector: string = null): Dom {
        let current = this.element;
        while (current !== null) {
            let prev = current.previousElementSibling as HTMLElement;
            if (prev === null) {
                return null;
            }

            if (selector === null) {
                return new Dom(prev);
            } else {
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
    next(selector: string = null): Dom {
        let current = this.element;
        while (current !== null) {
            let next = current.nextElementSibling as HTMLElement;
            if (next === null) {
                return null;
            }

            if (selector === null) {
                return new Dom(next);
            } else {
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
    focus(): void {
        this.element?.focus();
    }

    /**
     * HTML 엘리먼트 위치로 스크롤을 이동한다.
     *
     * @param {boolean} animate - 애니메이션여부
     */
    scrollTo(animate: boolean = true): void {
        this.element?.scrollIntoView({ behavior: animate == true ? 'smooth' : 'auto' });
    }

    /**
     * HTML 엘리먼트에 포커스를 해제한다.
     */
    blur(): void {
        this.element?.blur();
    }

    /**
     * HTML 엘리먼트를 보인다.
     * 자바스크립트를 통해 숨겨진 뒤 다시 보이는 경우 원래의 display 속성을 사용하고,
     * 그렇지 않은 경우 설정된 display 속성값으로 객체를 보인다.
     *
     * @param {string} display - 초기 display 속성 (기본값 : block)
     */
    show(display: string = 'block'): void {
        if (this.element === null) return;

        if (this.element.style.getPropertyValue('display') === 'none') {
            this.element.style.removeProperty('display');
        } else {
            this.setStyle('display', this.getData('origin-display') ?? display);
        }
    }

    /**
     * HTML 엘리먼트를 숨긴다.
     */
    hide(): void {
        if (this.element === null) return;
        if (this.getStyle('display') == 'none') return;

        if (this.getStyle('display') != 'none' && this.getStyle('display') != 'block') {
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
    stop(): void {
        this.element?.getAnimations()?.forEach((animate: Animation) => {
            if (animate.constructor.name !== 'Animation') return;
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
    shake(times: number = 4, duration: number = 800, distance: number = 10): void {
        const keyFrames: Keyframe[] = [];
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
    hover(mouseenter: EventListener, mouseleave: EventListener): this {
        this.on('mouseenter', (e: MouseEvent) => {
            mouseenter(e);
            e.preventDefault();
            e.stopImmediatePropagation();
        });
        this.on('mouseleave', (e: MouseEvent) => {
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
    on(name: string, listener: EventListener, options: any = null): this {
        this.eventListeners[name] ??= [];
        this.eventListeners[name].push(listener);

        if (name == 'longpress') {
            this.element?.addEventListener('pointerdown', (e: PointerEvent) => {
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
                        cancel: (e: PointerEvent) => {
                            const longpress = this.getData('longpress') ?? null;
                            if (e.type == 'pointermove') {
                                const diffX = Math.abs(longpress.x - (e as PointerEvent).clientX);
                                const diffY = Math.abs(longpress.y - (e as PointerEvent).clientY);
                                if (diffX < 10 && diffY < 10) {
                                    return;
                                }
                            }

                            clearTimeout(longpress.timeout);
                            Html.pointerListeners.delete((e as PointerEvent).pointerId);
                            this.setData('longpress', null);
                        },
                    });
                    Html.pointerListeners.set(e.pointerId, this);
                }
            });
        } else {
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
    off(name: string, listener: EventListener): this {
        this.element?.removeEventListener(name, listener);
        return this;
    }

    /**
     * 이벤트를 발생시킨다.
     *
     * @param {string} name - 발생시킬 이벤트명
     * @param {Event} e - 원본 이벤트객체
     */
    trigger(name: string, e: Event = null): void {
        if (name == 'pointermove' || name == 'pointerup' || name == 'pointercancel') {
            this.getData('longpress')?.cancel(e);
        }

        this.element?.dispatchEvent(new Event(name));
    }

    /**
     * HTML 엘리먼트의 모든 하위요소를 제거한다.
     */
    empty(): void {
        if (this.element == null) return;
        this.element.innerHTML = '';
    }

    /**
     * HTML 엘리먼트가 비었는지 확인한다.
     *
     * @return {boolean} is_empty
     */
    isEmpty(): boolean {
        return this.element?.hasChildNodes() === false;
    }

    /**
     * HTMP 엘리먼트가 숨겨진 상태인지 확인한다.
     *
     * @return {boolean} is_hidden
     */
    isHidden(): boolean {
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
    setDisabled(disabled: boolean): void {
        if (disabled == true) {
            this.setAttr('disabled', 'disabled');
        } else {
            this.removeAttr('disabled');
        }
    }

    /**
     * HTML 엘리먼트를 활성화한다.
     */
    enable(): void {
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
    disable(is_loading: boolean = false): void {
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
    clone(is_event_listeners: boolean = false): Dom {
        if (this.element === null) {
            return new Dom(null);
        }

        const clone = this.element.cloneNode(true) as HTMLElement;
        const $clone = new Dom(clone);

        if (is_event_listeners == true) {
            const children = clone.querySelectorAll('*');
            this.element.querySelectorAll('*').forEach((element: HTMLElement, index: number) => {
                const dom = new Dom(element);

                for (const name in dom.eventListeners) {
                    for (const listener of dom.eventListeners[name]) {
                        let $child = new Dom(children[index] as HTMLElement);
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
