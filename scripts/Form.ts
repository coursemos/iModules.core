/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 폼 객체를 정의한다.
 *
 * @file /scripts/Form.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 8.
 */
class Form {
    static forms: WeakMap<HTMLElement, Form> = new WeakMap();
    static elements: WeakMap<HTMLElement, FormElement.Base> = new WeakMap();
    $form: Dom;

    /**
     * 폼을 초기화한다.
     *
     * @param {Dom} $form - 폼 필드 DOM 객체
     */
    constructor($form: Dom) {
        this.$form = $form;

        this.$form.on('submit', (e: SubmitEvent) => {
            e.preventDefault();
        });
    }

    /**
     * 폼 데이터를 가져온다.
     *
     * @return {Object} data
     */
    getData(): { [key: string]: any } {
        let data: { [key: string]: any } = {};
        const input = new FormData(this.$form.getEl() as HTMLFormElement);
        Array.from(input.keys()).reduce((data, key) => {
            if (key.search(/\[\]$/) === -1) {
                if (typeof input.get(key) == 'string' && (input.get(key) as string).length > 0) {
                    data[key] = input.get(key);
                }
            } else {
                if (input.getAll(key).length > 0) {
                    data[key.replace(/\[\]$/, '')] = input.getAll(key);
                }
            }
            return data;
        }, data);

        // @todo 업로더 처리
        return data;
    }

    /**
     * 폼을 전송한다.
     *
     * @param {string} url - 전송할주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {boolean} is_retry - 실패시 재시도여부
     * @return {Promise<Ajax.Results>} results - 전송결과
     */
    async submit(url: string, params: Ajax.Params = {}, is_retry: boolean = true): Promise<Ajax.Results> {
        const data = this.getData();
        const results = await Ajax.post(url, data, params, is_retry);
        if (results.success == false && results.errors !== undefined) {
            for (const name in results.errors) {
                const $element = Html.get('div[data-role=form][data-field][data-name="' + name + '"]');
                if ($element.getEl() !== null) {
                    const element = Form.element($element);
                    element.setError(results.errors[name]);
                }
            }

            const $error = Html.all('div[data-role=form][data-field].error', this.$form).get(0);
            if ($error.getEl() !== null) {
                $error.getEl().scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return results;
    }

    /**
     * 폼 객체를 초기화한다.
     */
    static init(): void {
        Html.all('form').forEach(($form) => {
            if (Form.forms.has($form.getEl()) == false) {
                Form.forms.set($form.getEl(), new Form($form));
            }
        });

        Html.all('div[data-role=form]').forEach(($element) => {
            Form.element($element).init();
        });
    }

    /**
     * 폼 객체를 가져온다.
     *
     * @param {Dom} $form - 폼 DOM 객체
     * @return {Form} form - 폼
     */
    static get($form: Dom): Form {
        if (Form.forms.has($form.getEl()) == true) {
            return Form.forms.get($form.getEl());
        } else {
            // @todo 초기화
        }
    }

    /**
     * 폼 객체를 가져온다.
     *
     * @param {Dom} $dom - 폼 필드 DOM 객체
     * @return {FormElement.Base} element - 폼 엘리먼트
     */
    static element($dom: Dom): FormElement.Base {
        if (Form.elements.has($dom.getEl()) == true) {
            return Form.elements.get($dom.getEl());
        } else {
            const field = $dom.getAttr('data-field');
            switch (field) {
                case 'input':
                    return new FormElement.Input($dom);

                case 'check':
                case 'radio':
                    return new FormElement.Input($dom);

                case 'select':
                    return new FormElement.Select($dom);

                default:
                    return new FormElement.Base($dom);
            }
        }
    }
}

namespace FormElement {
    export class Base {
        static $absolutes: Dom;
        $dom: Dom;
        helpText: string;

        /**
         * 폼 엘리먼트를 초기화한다.
         *
         * @param {Dom} $dom - 폼 필드 DOM 객체
         */
        constructor($dom: Dom) {
            this.$dom = $dom;

            if (Html.get('div[data-role=help]', this.$dom).getEl() !== null) {
                this.helpText = Html.get('div[data-role=help]', this.$dom).toHtml(true);
            } else {
                this.helpText = null;
            }
        }

        /**
         * UI가 초기화되었는지 확인한다.
         *
         * @return {boolean} is_init
         */
        isInit(): boolean {
            return Form.elements.has(this.$dom.getEl());
        }

        /**
         * UI 를 초기화한다.
         */
        init(): void {
            Form.elements.set(this.$dom.getEl(), this);
        }

        /**
         * 확장영역 출력을 위한 절대위치 DOM 객체를 가져온다.
         *
         * @return {Dom} $absolutes
         */
        $getAbsolutes(): Dom {
            if (Html.get('body > div[data-role=absolutes]').getEl() === null) {
                const $absolutes = Html.create('div', { 'data-role': 'absolutes' });
                Html.get('body').append($absolutes);
            }

            return Html.get('body > div[data-role=absolutes]');
        }

        /**
         * 도움말 DOM 객체를 가져온다.
         *
         * @return {Dom} $helpText
         */
        $getHelpText(): Dom {
            if (Html.get('div[data-role=help]', this.$dom).getEl() == null) {
                const $helpText = Html.create('div', { 'data-role': 'help' });
                this.$dom.append($helpText);
            }

            return Html.get('div[data-role=help]', this.$dom);
        }

        /**
         * 에러가 존재하는지 확인한다.
         *
         * @return {boolean} hasError
         */
        hasError(): boolean {
            return this.$dom.hasClass('error');
        }

        /**
         * 에러메시지를 출력한다.
         *
         * @param {string|boolean} message - 에러메시지
         */
        setError(message: string | boolean): void {
            const hasError = message !== false;
            if (typeof message == 'string') {
                this.$getHelpText().html(message);
            } else if (this.helpText == null) {
                this.$getHelpText().remove();
            } else {
                this.$getHelpText().html(this.helpText);
            }

            if (hasError == true) {
                this.$dom.addClass('error');
            } else {
                this.$dom.removeClass('error');
            }
        }
    }

    export class Input extends FormElement.Base {
        // @todo 특수필드 UI 정의

        /**
         * UI 를 초기화한다.
         */
        init(): void {
            const $input = Html.get('input', this.$dom);
            $input.on('input', () => {
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
        }
    }

    export class Check extends FormElement.Base {
        /**
         * UI 를 초기화한다.
         */
        init(): void {
            const $input = Html.get('input', this.$dom);
            $input.on('change', () => {
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
        }
    }

    export class Select extends FormElement.Base {
        $expand: Dom = null;

        /**
         * UI 를 초기화한다.
         */
        init(): void {
            if (this.isInit() == true) {
                return;
            }

            const $button = Html.create('button', { type: 'button' });
            const $span = Html.create('span');
            $button.append($span);

            const $icon = Html.create('i');
            $button.append($icon);

            const $select = Html.get('select', this.$dom);
            $select.on('change', () => {
                $span.html(Html.get('option[value="' + $select.getValue() + '"]').toHtml(true));
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
            $span.html(Html.get('option[value="' + $select.getValue() + '"]').toHtml(true));

            $button.on('mousedown', (e: MouseEvent) => {
                if (this.$dom.hasClass('expand') == true) {
                } else {
                    this.expand();
                }
                e.preventDefault();
                e.stopPropagation();
            });

            $button.on('keydown', (e: KeyboardEvent) => {
                this.keydownEvent(e);
            });

            this.$dom.append($button);

            this.$dom.on('expand', () => {
                const $select = Html.get('select', this.$dom);
                Html.get('li[data-value="' + $select.getValue() + '"]', this.$expand).focus();
            });

            super.init();
        }

        /**
         * 폼 필드를 확장한다.
         */
        expand(): void {
            if (this.$dom.hasClass('expand') == true) {
                return;
            }

            if (this.hasError() == true) {
                this.setError(false);
            }

            const $absolutes = this.$getAbsolutes();
            $absolutes.addClass('show');

            const $absolute = Html.create('div', { 'data-role': 'absolute' });
            $absolute.on('mousedown', (e: MouseEvent) => {
                e.stopImmediatePropagation();
            });

            this.$expand = Html.create('div', { 'data-role': 'form', 'data-field': 'select' });
            this.$expand.setStyle('min-width', this.$dom.getOuterWidth() + 'px');

            const $select = Html.get('select', this.$dom);
            const $ul = Html.create('ul');
            Html.all('option', $select).forEach(($option) => {
                const $li = Html.create(
                    'li',
                    { 'data-value': $option.getAttr('value'), 'tabindex': '1' },
                    $option.toHtml(true)
                );
                $li.on('keydown', (e: KeyboardEvent) => {
                    this.keydownEvent(e);
                });
                $li.on('click', () => {
                    $select.setValue($li.getAttr('data-value'));
                    this.collapse();
                });
                $ul.append($li);
            });

            this.$expand.append($ul);
            this.$dom.addClass('expand');

            const styles = window.getComputedStyle(this.$dom.getEl());
            for (const name of styles) {
                if (name.indexOf('--input') === 0) {
                    this.$expand.setStyleProperty(name, this.$dom.getStyle(name));
                }
            }
            $absolute.append(this.$expand);

            $absolutes.append($absolute);
            $absolutes.on('mousedown', () => {
                this.collapse();
            });

            const targetRect = this.$dom.getEl().getBoundingClientRect();
            const absoluteRect = $absolute.getEl().getBoundingClientRect();
            const windowRect = { width: window.innerWidth, height: window.innerHeight };

            const position: {
                top?: number;
                bottom?: number;
                left?: number;
                right?: number;
                maxWidth?: number;
                maxHeight?: number;
            } = {};

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

            for (const name in position) {
                $absolute.setStyle(name, position[name] + 'px');
            }

            this.$expand.addClass('expand');
            this.$expand.setStyle('max-width', position.maxWidth + 'px');
            this.$expand.setStyle('max-height', position.maxHeight + 'px');

            this.$dom.addClass(position.top ? 'top' : 'bottom');
            this.$expand.addClass(position.top ? 'top' : 'bottom');

            this.$dom.trigger('expand');
        }

        /**
         * 폼 필드 확장을 축소한다.
         */
        collapse(): void {
            if (this.$dom.hasClass('expand') == false) {
                return;
            }
            this.$expand = null;
            this.$dom.removeClass('expand', 'top', 'bottom');
            this.$getAbsolutes().remove();
        }

        /**
         * 폼 필드 확장을 토글한다.
         */
        toggle(): void {
            if (this.$dom.hasClass('expand') == true) {
                this.collapse();
            } else {
                this.expand();
            }
        }

        /**
         * 포커스를 이동한다.
         *
         * @param {'up'|'down'|'left'|'right'} direction - 이동방향
         */
        focusMove(direction: 'up' | 'down' | 'left' | 'right'): void {
            this.expand();

            const $ul = Html.get('ul', this.$expand);
            const $items = Html.all('li[tabindex]', $ul);
            const $focus = Html.get('*:focus', $ul);

            let index = $focus.getIndex();

            if (direction == 'up' && index > 0) index--;
            if (direction == 'down' && index < $items.getCount() - 1) index++;
            if (!~index) index = 0;

            $items.get(index).focus();
        }

        /**
         * 키보드 이벤트를 처리한다.
         *
         * @param {KeyboardEvent} e
         */
        keydownEvent(e: KeyboardEvent): void {
            const $target = Html.el(e.currentTarget);

            if (e.key == 'Esc') {
                this.collapse();
                e.preventDefault();
            }

            if (e.key == 'ArrowUp' || e.key == 'ArrowDown') {
                this.focusMove(e.key == 'ArrowUp' ? 'up' : 'down');
                e.preventDefault();
            }

            if (e.key == 'Tab') {
                if (this.$dom.hasClass('expand') == true) {
                    if (e.shiftKey == true) {
                        this.focusMove('up');
                    } else {
                        this.focusMove('down');
                    }

                    e.preventDefault();
                }
            }

            if (e.key == 'Enter') {
                if ($target.is('button') == true) {
                    this.toggle();
                } else {
                    if (this.$dom.hasClass('expand') == true) {
                        const $select = Html.get('select', this.$dom);
                        const $ul = Html.get('ul', this.$expand);
                        const $focus = Html.get('*:focus', $ul);

                        if ($focus.getEl() !== null) {
                            $select.setValue($focus.getAttr('data-value'));
                            this.collapse();
                        }
                        e.preventDefault();
                    }
                }
            }
        }
    }
}
