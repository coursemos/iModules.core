/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 언어팩 클래스를 정의한다.
 *
 * @file /scripts/Language.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 22.
 */
class Language {
    static observer;
    static isObserve = false;
    static texts = new Map();
    static promises = new Map();
    static prints = new Map();
    /**
     * 언어팩 Fetch 를 가져온다.
     * 동시에 언어팩 요청이 이루어질 경우 Fetch 가 중복실행되는 것을 방지한다.
     *
     * @param {string} url - 언어팩주소
     * @return {Promise<Response>} fetch
     */
    static async fetch(url) {
        if (Language.promises.has(url) === true) {
            return Language.promises.get(url);
        }
        Language.promises.set(url, fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        }).then((response) => {
            return response.json();
        }));
        return Language.promises.get(url);
    }
    /**
     * 언어팩을 비동기로 불러온다.
     *
     * @param {string} path - 불러올 언어팩 경로
     * @param {string} code - 언어코드
     * @param {number} retry - 재시도횟수
     * @return {Promise<Object>} texts - 언어팩
     */
    static async load(path, code, retry = 0) {
        const url = Language.getPath(path) + '/' + code + '.json';
        if (Language.texts.has(url) == true) {
            return Language.texts.get(url);
        }
        try {
            const text = await Language.fetch(url);
            Language.texts.set(url, text);
            return Language.texts.get(url);
        }
        catch (e) {
            console.error(e);
            if (retry < 3) {
                return Language.load(path, code, ++retry);
            }
            else {
                return {};
            }
        }
    }
    /**
     * 이미 불러온 언어팩이 존재한다면 가져온다.
     *
     * @param {string} path - 불러올 언어팩 경로
     * @param {string} code - 언어코드
     * @return {Object|boolean} texts - 언어팩 (false 인 경우 불러온 언어팩이 존재하지 않음)
     */
    static has(path, code) {
        const url = Language.getPath(path) + '/' + code + '.json';
        if (Language.texts.has(url) == true) {
            return Language.texts.get(url);
        }
        else {
            return false;
        }
    }
    /**
     * 아이모듈 기본 URL 경로를 가져온다.
     *
     * @return {string} baseUrl
     */
    static getBase() {
        return Html.get('body').getAttr('data-base');
    }
    /**
     * 루트폴더를 포함한 언어팩 경로를 가져온다.
     *
     * @param string $path 언어팩을 탐색할 경로
     * @return string $path 루트폴더를 포함한 언어팩 탐색 경로
     */
    static getPath(path) {
        return Language.getBase() + (path == '/' ? '' : path);
    }
    /**
     * 문자열 템플릿에서 치환자를 실제 데이터로 변환한다.
     *
     * @param {string} text - 문자열 템플릿
     * @param {object} placeHolder - 치환될 데이터
     * @return {string} message - 치환된 메시지
     */
    static replacePlaceHolder(text, placeHolder = null) {
        if (placeHolder === null) {
            return text;
        }
        const templets = [...text.matchAll(/\$\{(.*?)\}/g)];
        templets.forEach(([templet, key]) => {
            text = text.replace(templet, placeHolder[key] ?? '');
        });
        return text;
    }
    /**
     * 언어팩을 불러온다.
     *
     * @param {string} text - 언어팩코드
     * @param {Object} placeHolder - 치환자
     * @param {string[]} paths - 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param {string[]} codes - 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return {Promise<string>} message - 치환된 메시지
     */
    static async getText(text, placeHolder = null, paths = null, codes = null) {
        paths ??= ['/languages'];
        codes ??= [Html.get('html').getAttr('lang').split('-').shift()];
        const keys = text.split('.');
        let string = null;
        for (const path of paths) {
            for (const code of codes) {
                let string = await Language.load(path, code);
                keys.forEach((key) => {
                    if (string === null || string[key] === undefined) {
                        string = null;
                        return false;
                    }
                    string = string[key];
                });
                if (string !== null) {
                    return typeof string == 'string'
                        ? Language.replacePlaceHolder(string, placeHolder)
                        : JSON.stringify(string);
                }
            }
        }
        if (string === null) {
            return text;
        }
        return typeof string == 'string' ? Language.replacePlaceHolder(string, placeHolder) : JSON.stringify(string);
    }
    /**
     * 언어팩 객체를 불러온다.
     *
     * @param {string} key - 언어팩키
     * @param {string[]} paths - 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param {string[]} codes - 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return {Promise<object>} texts - 언어팩객체
     */
    static async getTexts(key, paths = null, codes = null) {
        paths ??= ['/languages'];
        codes ??= [Html.get('html').getAttr('lang').split('-').shift()];
        const keys = key.split('.');
        let string = null;
        for (const path of paths) {
            for (const code of codes) {
                let string = await Language.load(path, code);
                keys.forEach((key) => {
                    if (string === null || string[key] === undefined) {
                        string = null;
                        return false;
                    }
                    string = string[key];
                });
                if (string !== null) {
                    return typeof string == 'string' ? { key: string } : string;
                }
            }
        }
        if (string === null) {
            return { key: null };
        }
        return typeof string == 'string' ? { key: string } : string;
    }
    /**
     * 에러메시지를 불러온다.
     *
     * @param {string} error - 에러코드
     * @param {Object} placeHolder - 치환자
     * @param {string[]} paths - 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param {string[]} codes - 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return {Promise<string>} message - 치환된 메시지
     */
    static async getErrorText(error, placeHolder = null, paths = null, codes = null) {
        const text = await Language.getText('errors.' + error, placeHolder, paths, codes);
        if (typeof text == 'string') {
            return text;
        }
        else {
            return error;
        }
    }
    /**
     * 언어팩을 출력한다.
     * 언어팩을 비동기방식으로 가져오기때문에 치환자를 먼저 반환하고, 언어팩이 로딩완료되면 언어팩으로 대치한다.
     *
     * @param {string} text - 언어팩코드
     * @param {Object} placeHolder - 치환자
     * @param {string[]} paths - 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param {string[]} codes - 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return {string} message - 치환된 메시지
     */
    static printText(text, placeHolder = null, paths = null, codes = null) {
        paths ??= ['/languages'];
        codes ??= [Html.get('html').getAttr('lang').split('-').shift()];
        const keys = text.split('.');
        let string = null;
        for (const path of paths) {
            for (const code of codes) {
                let string = Language.has(path, code);
                if (string === false) {
                    const uuid = iModules.uuid();
                    Language.prints.set(uuid, { text: text, placeHolder: placeHolder, paths: paths, codes: codes });
                    Language.observe();
                    return '<span data-language="' + uuid + '">...</span>';
                }
                else {
                    string = string;
                    keys.forEach((key) => {
                        if (string === null || string[key] === undefined) {
                            string = null;
                            return false;
                        }
                        string = string[key];
                    });
                    if (string !== null) {
                        return typeof string == 'string' ? Language.replacePlaceHolder(string, placeHolder) : string;
                    }
                }
            }
        }
        if (string === null) {
            return text;
        }
        return typeof string == 'string' ? Language.replacePlaceHolder(string, placeHolder) : string;
    }
    /**
     * 에러메시지를 출력한다.
     * 언어팩을 비동기방식으로 가져오기때문에 치환자를 먼저 반환하고, 언어팩이 로딩완료되면 언어팩으로 대치한다.
     *
     * @param {string} error - 에러코드
     * @param {Object} placeHolder - 치환자
     * @param {string[]} paths - 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param {string[]} codes - 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return {string} $message 치환된 메시지
     */
    static printErrorText(error, placeHolder = null, paths = null, codes = null) {
        return Language.printText('errors.' + error, placeHolder, paths, codes);
    }
    /**
     * 언어팩 출력을 위한 옵저버를 시작한다.
     */
    static observe() {
        if (Language.observer === undefined) {
            Language.observer = new MutationObserver(() => {
                document.querySelectorAll('span[data-language]').forEach((dom) => {
                    const options = Language.prints.get(dom.getAttribute('data-language'));
                    if (options === undefined) {
                        return;
                    }
                    Language.getText(options.text, options.placeHolder, options.paths, options.codes).then((string) => {
                        const span = document.querySelector('span[data-language="' + dom.getAttribute('data-language') + '"]');
                        if (span !== null) {
                            dom.outerHTML = typeof string == 'string' ? string : JSON.stringify(string);
                        }
                        Language.prints.delete(dom.getAttribute('data-language'));
                        Language.disconnect();
                    });
                });
                Language.disconnect();
            });
        }
        if (document.querySelector('body') != null && Language.isObserve === false && Language.prints.size > 0) {
            Language.isObserve = true;
            Language.observer.observe(document.querySelector('body'), {
                attributes: false,
                childList: true,
                characterData: false,
                subtree: true,
            });
        }
    }
    /**
     * 옵저버를 중지한다.
     */
    static disconnect() {
        if (Language.isObserve === true && Language.prints.size === 0) {
            Language.isObserve = false;
            Language.observer.disconnect();
        }
    }
}
Html.ready(() => {
    Language.observe();
});
