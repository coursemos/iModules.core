/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 클래스를 정의한다.
 *
 * @file /scripts/iModules.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 9. 6.
 */
class iModules {
    static DEBUG: any;
    static language: string;
    static loadingTimeAt: number;

    /**
     * 실행시간을 계산한다.
     * 출력메시지가 NULL 인 경우 초기시각을 지정하고,
     * 출력메시지가 존재할 경우 해당 메시지와 함께 초기시각으로 부터 경과된 시간을 표시한다.
     *
     * @param {string} message - 출력메시지 (NULL 인 경우 기준 시간을 지정한다.)
     */
    static loadingTime(message: string = null): void {
        if (message === null) {
            iModules.loadingTimeAt = window.performance.now();
        } else {
            if (iModules.loadingTimeAt === undefined) {
                console.error('loadingTime is not init.');
            } else {
                console.debug(message, (window.performance.now() - iModules.loadingTimeAt) / 1000 + 'ms');
            }
        }
    }

    /**
     * 지정된 시간만큼 자바스크립트를 지연시킨다.
     *
     * @param {number} ms - 지연될 ms
     */
    static async sleep(ms: number): Promise<void> {
        return new Promise((r) => setTimeout(r, ms));
    }

    /**
     * 현재 언어코드를 가져온다.
     *
     * @return {string} language
     */
    static getLanguage(): string {
        iModules.language ??= Html.get('html').getAttr('lang');
        return iModules.language;
    }

    /**
     * 기본 URL 경로를 가져온다.
     *
     * @return {string} baseUrl
     */
    static getDir(): string {
        return Html.get('body').getAttr('data-dir');
    }

    /**
     * 세션 스토리지의 데이터를 처리한다.
     *
     * @param {string} key - 데이터키
     * @param {any} value - 저장할 데이터 (undefined 인 경우 저장된 데이터를 가져온다.)
     * @return {any} data - 데이터를 가져올 경우 해당 데이터값
     */
    static session(key: string, value: any = undefined): any {
        const session = window.sessionStorage?.getItem('iModules-Session') ?? null;
        const datas = session !== null ? JSON.parse(session) : {};

        if (value === undefined) {
            return datas[key] ?? null;
        } else {
            datas[key] = value;
            window.sessionStorage?.setItem('iModules-Session', JSON.stringify(datas));
        }
    }

    /**
     * 로컬 스토리지의 데이터를 처리한다.
     *
     * @param {string} key - 데이터키
     * @param {any} value - 저장할 데이터 (undefined 인 경우 저장된 데이터를 가져온다.)
     * @return {any} data - 데이터를 가져올 경우 해당 데이터값
     */
    static storage(key: string, value: any = undefined): any {
        const storage = window.localStorage?.getItem('iModules-Storage') ?? null;
        const datas = storage !== null ? JSON.parse(storage) : {};

        if (value === undefined) {
            return datas[key] ?? null;
        } else {
            datas[key] = value;
            window.localStorage?.setItem('iModules-Storage', JSON.stringify(datas));
        }
    }

    /**
     * 쿠키 데이터를 처리한다.
     *
     * @param {string} key - 데이터키
     * @param {any} value - 저장할 데이터 (undefined 인 경우 저장된 데이터를 가져온다.)
     * @return {any} data - 데이터를 가져올 경우 해당 데이터값
     */
    static cookie(key: string, value: any = undefined, expired: number = 0, path: string = null): any {
        if (value === undefined) {
            const cookies = document.cookie.split(';');
            for (const cookie of cookies) {
                if (cookie.indexOf(key + '=') != -1) {
                    return cookie.split('=').pop();
                }
            }

            return null;
        } else {
            path = path ?? '/';
            const unixtime = new Date().getTime();
            if (value === null) {
                expired = unixtime - 3600 * 1000;
            } else {
                expired = unixtime + expired * 1000;
            }

            const time = new Date();
            time.setTime(expired);

            document.cookie =
                key + '=' + encodeURIComponent(value) + '; path=' + path + '; expires=' + time.toUTCString();
        }
    }

    /**
     * 컬러모드를 변경한다.
     *
     * @param {string} color - 컬러모드
     */
    static colorScheme(color: string): void {
        Html.get('body').setData('color-scheme', color);
        iModules.cookie('IM_COLOR_SCHEME', color, 60 * 60 * 24 * 365);
    }

    /**
     * 컨텍스트 URL 경로를 가져온다.
     *
     * @param {string} subUrl - 추가할 컨텍스트 URL
     * @param {Object} params - 추가할 매개변수
     * @return {string} contextUrl
     */
    static getContextUrl(subUrl: string = null, params: { [key: string]: string } = null): string {
        const is_rewrite = Html.get('body').getAttr('data-rewrite') === 'true';
        const url = Html.get('body').getAttr('data-context-url') ?? '';

        let contextUrl = url;
        if (subUrl !== null) {
            contextUrl += subUrl;
        }

        if (params !== null) {
            let queryString = new URLSearchParams(params);
            if (is_rewrite == true) {
                contextUrl += '&' + queryString;
            } else {
                contextUrl += '?' + queryString;
            }
        }

        return contextUrl;
    }

    /**
     * 프로세스 URL 경로를 가져온다.
     *
     * @param {'module'|'plugin'|'widget'} type - 컴포넌트 타입
     * @param {string} name - 컴포넌트명
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    static getProcessUrl(type: string, name: string, path: string): string {
        const is_rewrite = Html.get('body').getAttr('data-rewrite') === 'true';
        const route = '/' + type + '/' + name + '/process/' + path;
        return iModules.getDir() + (is_rewrite === true ? route + '?debug=true' : '/?route=' + route + '&debug=true');
    }

    /**
     * API URL 경로를 가져온다.
     *
     * @param {'module'|'plugin'|'widget'} type - 컴포넌트 타입
     * @param {string} name - 컴포넌트명
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    static getApiUrl(type: string, name: string, path: string): string {
        const is_rewrite = Html.get('body').getAttr('data-rewrite') === 'true';
        const route = '/' + type + '/' + name + '/api/' + path;
        return iModules.getDir() + (is_rewrite === true ? route + '?debug=true' : '/?route=' + route + '&debug=true');
    }

    /**
     * UUID 를 생성한다.
     *
     * @return {string} uuid
     */
    static uuid(): string {
        if (typeof crypto.randomUUID == 'function') {
            return crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            var r = (Math.random() * 16) | 0,
                v = c == 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    /**
     * 팝업창을 연다.
     *
     * @param {string} url - 페이지 URL
     * @param {int} width - 가로크기
     * @param {int} height - 가로크기
     * @param {boolean} scroll - 스크롤바여부
     * @param {string} name - 창이름
     * @return {Window} popup - 팝업윈도우
     */
    static popup(url: string, width: number, height: number, scroll: boolean, name: string = null): Window {
        if (screen.availWidth < width) width = screen.availWidth - 50;
        if (screen.availHeight < height) height = screen.availHeight - 50;

        const left = Math.ceil((screen.availWidth - width) / 2);
        const top = Math.ceil((screen.availHeight - height) / 2);

        const popup = window.open(
            url,
            name ?? '',
            'top=' +
                top +
                ',left=' +
                left +
                ',width=' +
                width +
                ',height=' +
                height +
                ',scrollbars=' +
                (scroll == true ? '1' : '0')
        );

        if (popup) {
            popup.resizeTo(width, height);
            return popup;
        }

        return null;
    }

    /**
     * 모바일 디바이스인지 확인한다.
     *
     * @return {boolean} is_mobile
     */
    static isMobile(): boolean {
        return window.ontouchstart !== undefined;
    }

    /**
     * 전체 UI를 활성화한다.
     */
    static enable() {
        Html.get('body > div[data-role=disabled]').remove();
    }

    /**
     * UI를 비활성화한다.
     *
     * @param {Dom|string|null} message - 비활성화된 레이어에 표시될 메시지
     * @param {string|null} icon - 비활성화된 레이어에 표시될 아이콘 (message 가 string 인 경우)
     * @return {Dom} dom - 비활성화 레이어 Dom
     */
    static disable(message: Dom | string | null = null, icon: string | null = null): Dom {
        iModules.enable();

        const $disabled = Html.create('div');
        $disabled.setData('role', 'disabled');

        if (message !== null) {
            if (typeof message == 'string') {
                if (icon !== null) {
                    const $icon = Html.create('i');
                    $icon.setData('role', 'icon');
                    $disabled.append($icon);
                }

                const $text = Html.create('div');
                $text.setData('role', 'text');
                $text.text(message);
                $disabled.append($text);
            } else if (message instanceof Dom === true) {
                $disabled.append(message);
            }
        }

        Html.get('body').prepend($disabled);
        return $disabled;
    }
}

namespace iModules {
    export namespace Absolute {
        export interface Position {
            top?: number;
            bottom?: number;
            left?: number;
            right?: number;
            maxWidth?: number;
            maxHeight?: number;
        }

        export interface Listeners {
            show?: (position: iModules.Absolute.Position) => void;
            close?: () => void;
        }
    }

    export class Absolute {
        /**
         * 절대위치 DOM 객체를 출력한다.
         *
         * @param {Dom} $target - 절대위치를 표시할 기준 DOM 객체
         * @param {Dom} $content - 절대위치 DOM 내부 콘텐츠 DOM 객체
         * @param {('x'|'y')} direction - 절대위치 DOM 이 출력될 방향
         * @param {boolean} closable - 자동닫힘 여부
         * @param {iModules.Absolute.Listeners} listeners - 이벤트리스너
         */
        static show(
            $target: Dom,
            $content: Dom,
            direction: 'x' | 'y',
            closable: boolean = true,
            listeners: iModules.Absolute.Listeners = null
        ): void {
            const $absolutes = Html.create('div', { 'data-role': 'absolutes' });
            Html.get('body').append($absolutes);
            if (closable === true) {
                $absolutes.on('mousedown', () => {
                    iModules.Absolute.close();
                });
            }

            const $absolute = Html.create('div', { 'data-role': 'absolute' });
            $absolute.on('mousedown', (e: MouseEvent) => {
                e.stopImmediatePropagation();
            });
            $absolutes.append($absolute);
            $absolute.append($content);

            const targetRect = $target.getEl().getBoundingClientRect();
            const absoluteRect = $absolute.getEl().getBoundingClientRect();
            const windowRect = { width: window.innerWidth, height: window.innerHeight };

            const position: iModules.Absolute.Position = {};

            if (direction == 'y') {
                if (
                    targetRect.bottom > windowRect.height / 2 &&
                    absoluteRect.height > windowRect.height - targetRect.bottom
                ) {
                    position.bottom = windowRect.height - targetRect.top;
                    position.maxHeight = windowRect.height - position.bottom - 10;
                } else {
                    position.top = targetRect.bottom;
                    position.maxHeight = windowRect.height - position.top - 10;
                }

                if (targetRect.left + absoluteRect.width > windowRect.width) {
                    position.right = windowRect.width - targetRect.right;
                    position.maxWidth = windowRect.width - position.right - 10;
                } else {
                    position.left = targetRect.left;
                    position.maxWidth = windowRect.width - position.left - 10;
                }
            }

            for (const name in position) {
                $absolute.setStyle(name, position[name] + 'px');
            }

            $content.setStyle('max-width', position.maxWidth + 'px');
            $content.setStyle('max-height', position.maxHeight + 'px');

            const show = listeners?.show ?? null;
            if (show !== null) {
                listeners.show(position);
            }

            if (listeners?.close) {
                $absolutes.setData('close', listeners.close, false);
            }
        }

        /**
         * 절대위치 DOM 객체를 닫는다.
         */
        static close(): void {
            const $absolutes = Html.get('body > div[data-role=absolutes]');
            if ($absolutes.getEl() !== null) {
                if (typeof $absolutes.getData('close') == 'function') {
                    $absolutes.getData('close')();
                }
                $absolutes.remove();
            }
        }
    }

    export namespace Modal {
        export interface Button {
            text: string;
            class?: string;
            handler: ($button: Dom) => void;
        }
    }

    export class Modal {
        /**
         * 모달창을 연다.
         *
         * @param {string} title - 창제목
         * @param {(string|Dom)} content - 내용
         * @param {iModules.Modal.Button[]} buttons - 버튼목록
         * @param {Function} onShow - 모달창이 열렸을 때 발생할 이벤트리스너
         */
        static show(
            title: string,
            content: string | Dom,
            buttons: iModules.Modal.Button[] = [],
            onShow: ($modal: Dom) => void = null
        ): void {
            iModules.Modal.close();

            const $modals = Html.create('div', { 'data-role': 'modals' });
            const $section = Html.create('section');

            const $modal = Html.create('div', { 'data-role': 'modal' });

            const $title = Html.create('div', { 'data-role': 'title' });
            $title.html(title);
            $modal.append($title);

            const $content = Html.create('div', { 'data-role': 'content' });
            if (typeof content == 'string') {
                $content.html(content);
            } else {
                $content.append(content);
            }
            $modal.append($content);

            const $buttons = Html.create('div', { 'data-role': 'buttons' });
            $modal.append($buttons);

            if (buttons.length == 0) {
                buttons.push({
                    text: Language.printText('buttons.close'),
                    class: 'confirm',
                    handler: () => {
                        iModules.Modal.close();
                    },
                });
            }

            for (const button of buttons) {
                const $button = Html.create('button', { 'data-role': 'button', 'type': 'button' });
                $button.html(button.text);
                $button.on('click', () => {
                    button.handler($button);
                });
                if (button.class) {
                    $button.addClass(button.class);
                }
                $buttons.append($button);
            }

            $section.append($modal);
            $modals.append($section);

            Html.get('body').append($modals);
            if (typeof onShow == 'function') {
                onShow($modal);
            }
        }

        /**
         * 모달창을 닫는다.
         */
        static close(): void {
            Html.get('body > div[data-role=modals]').remove();
        }
    }
}

/**
 * 아이모듈 페이지가 출력되었을 때 UI 이벤트를 등록한다.
 */
Html.ready(() => {
    const $body = Html.get('body');
    $body.setData('device', iModules.isMobile() == true ? 'mobile' : 'desktop');

    if ($body.getData('color-scheme') === null) {
        $body.setData('color-scheme', 'auto');
    }

    $body.setData(
        'prefers-color-scheme',
        window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    );

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        $body.setData('prefers-color-scheme', e.matches ? 'dark' : 'light');
    });

    /**
     * 현재 페이지에 사용중인 모듈 클래스를 초기화한다.
     */
    Modules.init();

    if ($body.getAttr('data-type') == 'website') {
        /**
         * 폼 객체를 초기화한다.
         */
        Form.init();
        Form.autosave(true);

        /**
         * 스크롤영역을 처리한다.
         */
        Scroll.init();

        /**
         * Ajax 에러핸들러를 등록한다.
         */
        Ajax.setErrorHandler(async (results) => {
            iModules.Modal.show(
                await Language.getErrorText('TITLE'),
                results.message ?? (await Language.getErrorText('CONNECT_FAILED'))
            );
        });

        /**
         * 리사이즈 이벤트를 처리한다.
         */
        new ResizeObserver(() => {
            iModules.Absolute.close();
        }).observe(document.body);
    }
});
