/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 폼 객체를 정의한다.
 *
 * @file /scripts/Form.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 13.
 */
class Form {
    static forms = new WeakMap();
    static elements = new WeakMap();
    static index = 0;
    $form;
    name;
    sending = false;
    loading = false;
    submitFunction = null;
    originData = null;
    latestEdited = null;
    latestAutosaved = 0;
    /**
     * 폼을 초기화한다.
     *
     * @param {Dom} $form - 폼 필드 DOM 객체
     */
    constructor($form) {
        this.$form = $form;
        this.$form.on('submit', (e) => {
            if (this.submitFunction !== null) {
                this.submitFunction(this);
            }
            e.preventDefault();
        });
        this.$form.on('input', () => {
            this.latestEdited = new Date().getTime();
        });
        this.name = this.$form.getAttr('name') ?? 'Form-' + ++Form.index;
        this.originData = this.getData(true);
        this.latestEdited = new Date().getTime();
    }
    /**
     * 폼 전송을 제어하는 이벤트를 등록한다.
     *
     * @param {Function} submit - 전송이벤트리스너
     */
    onSubmit(submit = null) {
        this.submitFunction = submit;
    }
    /**
     * submit 이벤트리스너가 등록되어 있다면 해당 이벤트리스너를 통해 폼을 전송한다.
     */
    async requestSubmit() {
        if (this.submitFunction !== null) {
            this.submitFunction(this);
            this.removeAutosaveData();
        }
    }
    /**
     * 폼 이름을 가져온다.
     *
     * @return {string} name
     */
    getName() {
        return this.name;
    }
    /**
     * 폼 데이터를 가져온다.
     *
     * @param {boolean} is_sensitive - 민감한 데이터 제외여부
     * @return {Object} data
     */
    getData(is_sensitive = false) {
        let data = {};
        const input = new FormData(this.$form.getEl());
        const uploaders = [];
        Array.from(input.keys()).reduce((data, key) => {
            const $input = Html.get('*[name="' + key + '"]', this.$form);
            if (is_sensitive == true) {
                if ($input.getAttr('type') == 'password' || $input.getAttr('type') == 'hidden') {
                    return data;
                }
            }
            if ($input.getAttr('data-role') == 'editor') {
                const wysiwyg = Modules.get('wysiwyg');
                const editor = wysiwyg.getEditor($input);
                if (editor.getValue() !== null) {
                    data[key] = editor.getValue();
                }
                uploaders.push(editor.getUploader().getId());
                return data;
            }
            if ($input.getAttr('data-role') == 'uploader') {
                if (uploaders.includes($input.getAttr('data-id')) == false) {
                    data[key] = JSON.parse(input.get(key));
                }
                return data;
            }
            if (key.search(/\[\]$/) > -1) {
                if (input.getAll(key).length > 0) {
                    data[key.replace(/\[\]$/, '')] = input.getAll(key);
                }
                return data;
            }
            if (typeof input.get(key) == 'string' && input.get(key).length > 0) {
                data[key] = input.get(key);
                return data;
            }
            return data;
        }, data);
        if (is_sensitive == true && Object.keys(data).length == 0) {
            return null;
        }
        return data;
    }
    /**
     * 폼 데이터를 지정한다.
     *
     * @param {Object} data - 지정할 데이터
     * @param {boolean} is_autosave - 자동저장된 데이터인지 여부
     * @return {boolean} loaded
     */
    setData(data, is_autosave = false) {
        if (is_autosave === true) {
            this.$form.setAttr('data-autosave-loaded', 'true');
        }
        for (const name in data) {
            const $field = Html.get('*[name="' + name + '"]', this.$form);
            if ($field.getEl() === null) {
                continue;
            }
            const value = data[name] ?? null;
            if ($field.getAttr('data-role') == 'editor') {
                const mWysiwyg = Modules.get('wysiwyg');
                const editor = mWysiwyg.getEditor($field);
                editor.setValue(value);
            }
            else if ($field.getAttr('data-role') == 'uploader') {
                //
            }
            else {
                $field.setValue(value);
            }
        }
    }
    /**
     * 자동저장된 데이터를 불러왔는지 여부를 가져온다.
     *
     * @return {boolean} loaded
     */
    isAutosaveLoaded() {
        return this.$form.getAttr('data-autosave-loaded') == 'true';
    }
    /**
     * 자동저장된 데이터를 가져온다.
     *
     * @return {Object} data
     */
    getAutosaveData() {
        const autosave = iModules.storage('autosave') ?? {};
        if (autosave[location.href] === undefined) {
            return null;
        }
        const data = autosave[location.href][this.getName()] ?? null;
        if (Format.isEqual(this.originData, data) == false) {
            return data;
        }
        return null;
    }
    /**
     * 데이터를 자동저장한다.
     */
    saveAutosaveData() {
        if (this.latestEdited > this.latestAutosaved) {
            const data = this.getData(true);
            if (Format.isEqual(this.originData, data) == true) {
                this.removeAutosaveData();
            }
            else {
                const autosave = iModules.storage('autosave') ?? {};
                autosave[location.href] ??= {};
                autosave[location.href][this.getName()] = data;
                iModules.storage('autosave', autosave);
            }
            this.latestAutosaved = new Date().getTime();
        }
    }
    /**
     * 자동저장된 데이터를 삭제한다.
     */
    removeAutosaveData() {
        if (this.$form.getAttr('autosave') == 'false') {
            return;
        }
        const autosave = iModules.storage('autosave') ?? {};
        autosave[location.href] ??= {};
        autosave[location.href][this.getName()] ??= {};
        delete autosave[location.href][this.getName()];
        if (Object.keys(autosave[location.href]).length == 0) {
            delete autosave[location.href];
        }
        iModules.storage('autosave', autosave);
    }
    /**
     * 폼을 전송한다.
     *
     * @param {string} url - 전송할주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {boolean} is_raw - JSON 방식이 아닌 전통적인 방식으로 전송할지 여부
     * @param {boolean} is_retry - 실패시 재시도여부
     * @return {Promise<Ajax.Results>} results - 전송결과
     */
    async submit(url, params = {}, is_raw = false, is_retry = true) {
        if (this.sending == true) {
            return;
        }
        if (this.loading == true) {
            iModules.Modal.show(await Language.getErrorText('TITLE'), await Language.getErrorText('LOADING'));
            return { success: false };
        }
        /**
         * 폼에 포함된 업로더의 업로드 진행상황을 확인한다.
         */
        const $uploaders = Html.all('div[data-role=uploader]', this.$form).getList();
        if ($uploaders.length > 0) {
            const attachment = Modules.get('attachment');
            for (const $uploader of $uploaders) {
                const uploader = attachment.getUploader($uploader);
                if (uploader.isUploading() == true) {
                    iModules.Modal.show(await Language.getErrorText('TITLE'), await Language.getErrorText('UPLOADING'));
                    return { success: false };
                }
            }
        }
        const $submit = Html.get('button[type=submit]', this.$form);
        $submit.disable(true);
        this.sending = true;
        Html.all('div[data-field]', this.$form).forEach(($element) => {
            const element = Form.element($element);
            element.setError(false);
        });
        const data = is_raw === true ? new FormData(this.$form.getEl()) : this.getData();
        const results = await Ajax.post(url, data, params, is_retry);
        if (results.success == false && results.errors !== undefined) {
            for (const name in results.errors) {
                const $element = Html.get('div[data-field][data-name="' + name + '"]', this.$form);
                if ($element.getEl() !== null) {
                    const element = Form.element($element);
                    element.setError(results.errors[name]);
                }
            }
            const $error = Html.all('div[data-field].error', this.$form).get(0);
            if ($error.getEl() !== null) {
                $error.getEl().scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        if (results.success == true) {
            this.removeAutosaveData();
        }
        this.sending = false;
        $submit.enable();
        return results;
    }
    /**
     * 폼 객체를 초기화한다.
     *
     * @param {Dom} $form - 특정 Form 객체를 초기화할 경우
     */
    static init($form = null) {
        if ($form === null) {
            Html.all('form').forEach(($form) => {
                if (Form.forms.has($form.getEl()) == false) {
                    Form.forms.set($form.getEl(), new Form($form));
                }
            });
            $form = Html.get('body');
        }
        else {
            if (Form.forms.has($form.getEl()) == false) {
                Form.forms.set($form.getEl(), new Form($form));
            }
        }
        Html.all('div[data-role=field]', $form).forEach(($element) => {
            Form.element($element).init();
        });
    }
    /**
     * 폼 객체를 가져온다.
     *
     * @param {Dom} $form - 폼 DOM 객체
     * @return {Form} form - 폼
     */
    static get($form) {
        if (Form.forms.has($form.getEl()) == true) {
            return Form.forms.get($form.getEl());
        }
        else {
            Form.init($form);
            return Form.forms.get($form.getEl());
        }
    }
    /**
     * 폼 객체를 가져온다.
     *
     * @param {Dom} $dom - 폼 필드 DOM 객체
     * @return {FormElement.Base} element - 폼 엘리먼트
     */
    static element($dom) {
        if (Form.elements.has($dom.getEl()) == true) {
            return Form.elements.get($dom.getEl());
        }
        else {
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
    /**
     * INPUT 태그를 생성한다.
     *
     * @param {string} name - 필드명
     * @param {string} type - 종류 (text, password, search 등)
     * @return {FormElement.Input} element
     */
    static input(name, type = 'text') {
        const $dom = Html.create('div', {
            'data-role': 'field',
            'data-field': 'input',
            'data-name': name,
        });
        $dom.append(Html.create('input', { type: type, name: name }));
        return new FormElement.Input($dom);
    }
    /**
     * 체크박스 태그를 생성한다.
     *
     * @param {string} name - 필드명
     * @param {string} value - 필드값
     * @param {string} boxLabel - 라벨텍스트
     * @return {FormElement.Input} element
     */
    static check(name, value, boxLabel = null) {
        const $dom = Html.create('div', {
            'data-role': 'field',
            'data-field': 'check',
            'data-name': name,
        });
        $dom.append(Html.create('label').html('<input type="checkbox" name="' + name + '" value="' + value + '">' + boxLabel));
        return new FormElement.Check($dom);
    }
    /**
     * 선택폼 태그를 생성한다.
     *
     * @param {string} name - 필드명
     * @param {Object} options - 선택값
     * @return {FormElement.Select} element
     */
    static select(name, options) {
        const $dom = Html.create('div', {
            'data-role': 'field',
            'data-field': 'select',
            'data-name': name,
        });
        const $select = Html.create('select', { name: name });
        for (const value in options) {
            $select.append(Html.create('option', { value: value }, options[value]));
        }
        $dom.append($select);
        return new FormElement.Select($dom);
    }
    /**
     * 자동저장을 사용하는 FORM 이 존재한다면, 자동저장을 활성화한다.
     *
     * @param {boolean} is_ready - DOM 이 처음 준비되었을때인지 여부
     */
    static async autosave(is_ready = false) {
        const $forms = Html.all('form[autosave=true]');
        if ($forms.getCount() == 0) {
            return;
        }
        if (is_ready == true) {
            let isLoadable = false;
            $forms.forEach(($form) => {
                const form = Form.get($form);
                const data = form.getAutosaveData();
                if (form.isAutosaveLoaded() == false && data !== null) {
                    isLoadable = true;
                    iModules.Modal.show(Language.printText('info'), Language.printText('actions.autosave'), [
                        {
                            text: Language.printText('buttons.cancel'),
                            handler: () => {
                                form.removeAutosaveData();
                                form.setData(null, true);
                                Form.autosave(true);
                                iModules.Modal.close();
                            },
                        },
                        {
                            text: Language.printText('buttons.ok'),
                            class: 'confirm',
                            handler: () => {
                                form.setData(data, true);
                                Form.autosave(true);
                                iModules.Modal.close();
                            },
                        },
                    ]);
                    return false;
                }
            });
            if (isLoadable === false) {
                Form.autosave(false);
            }
        }
        else {
            $forms.forEach(($form) => {
                const form = Form.get($form);
                form.saveAutosaveData();
            });
            setTimeout(Form.autosave, 10000);
        }
    }
}
var FormElement;
(function (FormElement) {
    class Base {
        static $absolutes;
        $dom;
        helpText;
        value = null;
        /**
         * 폼 엘리먼트를 초기화한다.
         *
         * @param {Dom} $dom - 폼 필드 DOM 객체
         */
        constructor($dom) {
            this.$dom = $dom;
            if (Html.get('div[data-role=help]', this.$dom).getEl() !== null) {
                this.helpText = Html.get('div[data-role=help]', this.$dom).toHtml(true);
            }
            else {
                this.helpText = null;
            }
        }
        /**
         * UI가 초기화되었는지 확인한다.
         *
         * @return {boolean} is_init
         */
        isInit() {
            return Form.elements.has(this.$dom.getEl());
        }
        /**
         * UI 를 초기화한다.
         */
        init() {
            Form.elements.set(this.$dom.getEl(), this);
        }
        /**
         * 폼필드 값을 지정한다.
         *
         * @param {any} value
         * @return {FormElement.Base}
         */
        setValue(value) {
            this.value = value;
            return this;
        }
        /**
         * 확장영역 출력을 위한 절대위치 DOM 객체를 가져온다.
         *
         * @return {Dom} $absolutes
         */
        $getAbsolutes() {
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
        $getHelpText() {
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
        hasError() {
            return this.$dom.hasClass('error');
        }
        /**
         * 에러메시지를 출력한다.
         *
         * @param {string|boolean} message - 에러메시지
         */
        setError(message) {
            const hasError = message !== false;
            if (typeof message == 'string') {
                this.$getHelpText().html(message);
            }
            else if (this.helpText == null) {
                this.$getHelpText().remove();
            }
            else {
                this.$getHelpText().html(this.helpText);
            }
            if (hasError == true) {
                this.$dom.addClass('error');
            }
            else {
                this.$dom.removeClass('error');
            }
        }
        /**
         * 폼필드 DOM 객체를 반환한다.
         *
         * @return {Dom} $dom
         */
        getLayout() {
            return this.$dom;
        }
    }
    FormElement.Base = Base;
    class Input extends FormElement.Base {
        // @todo 특수필드 UI 정의
        /**
         * UI 를 초기화한다.
         */
        init() {
            this.$getInput().on('input', () => {
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
        }
        /**
         * INPUT DOM 객체를 가져온다.
         *
         * @return {Dom} $input
         */
        $getInput() {
            return Html.get('input', this.$dom);
        }
        /**
         * 폼필드 값을 지정한다.
         *
         * @param {any} value
         * @return {FormElement.Input}
         */
        setValue(value) {
            this.$getInput().setValue(value);
            super.setValue(value);
            return this;
        }
    }
    FormElement.Input = Input;
    class Check extends FormElement.Base {
        /**
         * UI 를 초기화한다.
         */
        init() {
            this.$getInput().on('change', () => {
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
        }
        /**
         * INPUT DOM 객체를 가져온다.
         *
         * @return {Dom} $input
         */
        $getInput() {
            return Html.get('input', this.$dom);
        }
        /**
         * 폼필드 값을 지정한다.
         *
         * @param {any} value
         * @return {FormElement.Input}
         */
        setValue(value) {
            this.$getInput().setValue(value);
            super.setValue(value);
            return this;
        }
    }
    FormElement.Check = Check;
    class Select extends FormElement.Base {
        $button = null;
        $expand = null;
        /**
         * UI 를 초기화한다.
         */
        init() {
            if (this.isInit() == true) {
                return;
            }
            this.$button = Html.create('button', { type: 'button' });
            const $span = Html.create('span');
            this.$button.append($span);
            const $icon = Html.create('i');
            this.$button.append($icon);
            const $select = Html.get('select', this.$dom);
            $select.on('change', () => {
                $span.html(Html.get('option[value="' + $select.getValue() + '"]').toHtml(true));
                if (this.hasError() == true) {
                    this.setError(false);
                }
            });
            $span.html(Html.get('option[value="' + $select.getValue() + '"]').toHtml(true));
            this.$button.on('mousedown', (e) => {
                if (this.$dom.hasClass('expand') == true) {
                }
                else {
                    this.expand();
                }
                e.preventDefault();
                e.stopPropagation();
            });
            this.$button.on('keydown', (e) => {
                this.keydownEvent(e);
            });
            this.$dom.append(this.$button);
            this.$dom.on('expand', () => {
                const $select = Html.get('select', this.$dom);
                Html.get('li[data-value="' + $select.getValue() + '"]', this.$expand).focus();
            });
            super.init();
        }
        /**
         * 폼 필드를 확장한다.
         */
        expand() {
            if (this.$dom.hasClass('expand') == true) {
                return;
            }
            if (this.hasError() == true) {
                this.setError(false);
            }
            const $select = Html.get('select', this.$dom);
            this.$expand = Html.create('div', {
                'data-role': 'field',
                'data-field': 'select',
                'data-name': $select.getAttr('name'),
            });
            this.$expand.setStyle('min-width', this.$dom.getOuterWidth() + 'px');
            const $ul = Html.create('ul', { 'data-scrollbar': 'auto' });
            Html.all('option', $select).forEach(($option) => {
                const $li = Html.create('li', { 'data-value': $option.getAttr('value'), 'tabindex': '1' }, $option.toHtml(true));
                $li.on('keydown', (e) => {
                    this.keydownEvent(e);
                });
                $li.on('click', () => {
                    $select.setValue($li.getAttr('data-value'));
                    this.collapse();
                    this.$button.focus();
                });
                $ul.append($li);
            });
            this.$expand.append($ul);
            const styles = Html.getStyleProperties('input-');
            for (const name in styles) {
                this.$expand.setStyleProperty(name, window.getComputedStyle(this.$dom.getEl()).getPropertyValue(name));
            }
            iModules.Absolute.show(this.$dom, this.$expand, 'y', true, {
                show: (position) => {
                    this.$expand.addClass('expand');
                    this.$expand.addClass(position.top ? 'top' : 'bottom');
                    this.$dom.addClass('expand');
                    this.$dom.addClass(position.top ? 'top' : 'bottom');
                    this.$dom.trigger('expand');
                },
                close: () => {
                    this.$expand.removeClass('expand', 'top', 'bottom');
                    this.$dom.removeClass('expand', 'top', 'bottom');
                },
            });
        }
        /**
         * 폼 필드 확장을 축소한다.
         */
        collapse() {
            if (this.$dom.hasClass('expand') == false) {
                return;
            }
            this.$expand.remove();
            this.$dom.removeClass('expand', 'top', 'bottom');
            iModules.Absolute.close();
        }
        /**
         * 폼 필드 확장을 토글한다.
         */
        toggle() {
            if (this.$dom.hasClass('expand') == true) {
                this.collapse();
            }
            else {
                this.expand();
            }
        }
        /**
         * 포커스를 이동한다.
         *
         * @param {'up'|'down'|'left'|'right'} direction - 이동방향
         */
        focusMove(direction) {
            if (this.$dom.hasClass('expand') == false) {
                this.expand();
                return;
            }
            const $ul = Html.get('ul', this.$expand);
            const $items = Html.all('li[tabindex]', $ul);
            const $focus = Html.get('*:focus', $ul);
            let index = $focus.getIndex();
            if (direction == 'up' && index > 0)
                index--;
            if (direction == 'down' && index < $items.getCount() - 1)
                index++;
            if (!~index)
                index = 0;
            $items.get(index).focus();
        }
        /**
         * 키보드 이벤트를 처리한다.
         *
         * @param {KeyboardEvent} e
         */
        keydownEvent(e) {
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
                    }
                    else {
                        this.focusMove('down');
                    }
                    e.preventDefault();
                }
            }
            if (e.key == 'Enter') {
                if ($target.is('button') == true) {
                    this.toggle();
                }
                else {
                    if (this.$dom.hasClass('expand') == true) {
                        const $select = Html.get('select', this.$dom);
                        const $ul = Html.get('ul', this.$expand);
                        const $focus = Html.get('*:focus', $ul);
                        if ($focus.getEl() !== null) {
                            $select.setValue($focus.getAttr('data-value'));
                            this.collapse();
                            this.$button.focus();
                        }
                        e.preventDefault();
                    }
                }
            }
        }
    }
    FormElement.Select = Select;
})(FormElement || (FormElement = {}));
