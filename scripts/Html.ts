/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTML DOM 을 제어한다.
 *
 * @file /scripts/Html.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 26.
 */
class Html {
    static dataValues: WeakMap<object, any> = new WeakMap();
    static eventListeners: WeakMap<object, any> = new WeakMap();
    static pointerListeners: Map<number, Dom> = new Map();

    /**
     * HTML 객체를 생성한다.
     *
     * @param {string} type - 생성할 Node명
     * @param {Object} attributes - 포함할 attribute
     * @param {string} text - 포함될 텍스트내용
     * @return {Dom} dom - 생성된 Dom 객체
     */
    static create(type: string, attributes: { [key: string]: string } = {}, text: string = ''): Dom {
        const element = document.createElement(type);
        const dom = new Dom(element);
        for (let key in attributes) {
            dom.setAttr(key, attributes[key]);
        }
        dom.text(text);
        return dom;
    }

    /**
     * HTML 엘리먼트를 Dom 객체로 가져온다.
     *
     * @param {HTMLElement|EventTarget} element - Dom 객체로 생성할 HTML 엘리먼트 객체
     * @return {Dom} dom - 생성된 Dom 객체
     */
    static el(element: HTMLElement | EventTarget): Dom | null {
        if (element instanceof HTMLElement) {
            return new Dom(element as HTMLElement);
        } else {
            return null;
        }
    }

    /**
     * HTML 코드를 Dom 객체로 변환한다.
     *
     * @param {string} html
     * @returns {Dom} dom - 변환된 Dom 객체
     */
    static html(html: string): Dom {
        const element = document.createElement(null);
        element.innerHTML = html;

        return new Dom(element.firstChild);
    }

    /**
     * 쿼리셀렉터 문자열을 표준화한다.
     *
     * @param {string} selector - 쿼리셀렉터
     * @return {string} selector - 쿼리셀렉터
     */
    static selector(selector: string): string {
        selector = selector.trim();
        if (selector.indexOf('>') === 0) {
            selector = ':scope ' + selector;
        }

        return selector;
    }

    /**
     * 쿼리셀렉터에 해당하는 DOM Element 를 가져온다.
     *
     * @param {string} selector - 쿼리셀렉터
     * @param {?Dom} context - DOM Element 를 가져올 부모요소
     * @return {?Dom} dom - 쿼리셀렉터에 해당하는 Dom 객체
     */
    static get(selector: string, context?: Dom): Dom | null {
        if (context instanceof Dom === true) {
            return new Dom(context.getEl()?.querySelector(Html.selector(selector)) ?? null);
        }

        return new Dom(document.querySelector(Html.selector(selector)));
    }

    /**
     * 쿼리셀렉터에 해당하는 모든 DOM Element 를 가져온다.
     *
     * @param {string} selector - 쿼리셀렉터
     * @param {?Dom} context - DOM Element 를 가져올 부모요소
     * @return {Dom[]} elements
     */
    static all(selector: string, context?: Dom): DomList {
        let nodes: NodeList;
        if (context !== undefined && context.constructor.name == 'Dom') {
            nodes = context.getEl()?.querySelectorAll(Html.selector(selector));
        } else {
            nodes = document.querySelectorAll(Html.selector(selector));
        }

        return new DomList(nodes);
    }

    /**
     * document 이벤트를 등록한다.
     *
     * @param {string} name - 이벤트명
     * @param {EventListener} listener - 이벤트리스너
     * @param {any} options - 이벤트리스너 옵션
     */
    static on(name: string, listener: EventListener, options: any = null): void {
        document.addEventListener(name, listener, options);
    }

    /**
     * HTML 문서의 스크롤 이벤트리스너를 등록한다.
     *
     * @param {EventListener} listener - 이벤트리스너
     */
    static scroll(listener: EventListener): void {
        Html.on('scroll', listener);
    }

    /**
     * HTML 문서의 스크롤위치를 조절한다.
     *
     * @param {number} top - 상단위치
     * @param {boolean} animate - 애니메이션여부
     * @return {number} scrollTop - 스크롤된 위치
     */
    static scrollTop(top?: number, animate: boolean = true): number {
        if (top === undefined) {
            return document.documentElement.scrollTop;
        } else {
            document.documentElement.scroll({ top: top, behavior: animate === true ? 'smooth' : 'auto' });
        }
    }

    /**
     * HTML 문서의 랜더링 완료 이벤트리스너를 등록한다.
     *
     * @param {EventListener} listener - 이벤트리스너
     */
    static ready(listener: EventListener): void {
        document.addEventListener('DOMContentLoaded', listener);
    }
}

/**
 * 페이지가 출력되었을 때 기본 UI 이벤트를 등록한다.
 */
Html.ready(() => {
    if (Html.get('main[data-type=error]').getEl() !== null) {
        const $error = Html.get('main[data-type=error]');

        Html.all('button[data-index]', $error).on('click', (e: Event) => {
            const $button = Html.el(e.currentTarget);
            const index = $button.getData('index');
            Html.all('button[data-index]', $error).removeClass('selected');

            $button.addClass('selected');

            Html.all('ul[data-role=code]', $error).removeClass('selected');
            Html.get("ul[data-role=code][data-index='" + index + "']", $error).addClass('selected');
        });

        Html.all('button[data-action=toggle]', $error).on('click', (e: Event) => {
            const $button = Html.el(e.currentTarget);
            const $aside = $button.getParents('div');
            $aside.toggleClass('opened');
        });
    }

    /**
     * 포인터 이벤트를 최초 포인터 이벤트 수신자에게 전달한다.
     */
    Html.on('pointermove', (e: PointerEvent) => {
        if (Html.pointerListeners.has(e.pointerId) == true) {
            Html.pointerListeners.get(e.pointerId)?.trigger('pointermove', e);
        }
    });

    Html.on('pointerup', (e: PointerEvent) => {
        if (Html.pointerListeners.has(e.pointerId) == true) {
            Html.pointerListeners.get(e.pointerId)?.trigger('pointerup', e);
            Html.pointerListeners.delete(e.pointerId);
        }
    });

    Html.on('pointercancel', (e: PointerEvent) => {
        if (Html.pointerListeners.has(e.pointerId) == true) {
            Html.pointerListeners.get(e.pointerId)?.trigger('pointercancel', e);
            Html.pointerListeners.delete(e.pointerId);
        }
    });
});
