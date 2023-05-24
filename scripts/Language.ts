/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 언어팩 클래스를 정의한다.
 *
 * @file /scripts/Language.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 24.
 */
class Language {
    static observer: MutationObserver;
    static isObserve: boolean = false;
    static texts: Map<string, { [key: string]: string | object }> = new Map();
    static promises: Map<string, Promise<{ [key: string]: string | object }>> = new Map();
    static prints: Map<
        string,
        {
            text: string;
            placeHolder: { [key: string]: string };
            paths: string[];
            codes: string[];
        }
    > = new Map();

    /**
     * 언어팩 Fetch 를 가져온다.
     * 동시에 언어팩 요청이 이루어질 경우 Fetch 가 중복실행되는 것을 방지한다.
     *
     * @param {string} url - 언어팩주소
     * @return {Promise<Response>} fetch
     */
    static fetch(url: string): Promise<{ [key: string]: string | object }> {
        if (Language.promises.has(url) === true) {
            return Language.promises.get(url);
        }

        Language.promises.set(
            url,
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            }).then((response: Response) => response.json())
        );

        return Language.promises.get(url);
    }

    /**
     * 언어팩을 비동기로 불러온다.
     *
     * @param {string} path - 불러올 언어팩 경로
     * @param {string} code - 언어코드
     * @param {number} retry - 재시도횟수
     * @return {Promise<object>} texts - 언어팩
     */
    static async load(path: string, code: string, retry: number = 0): Promise<{ [key: string]: string | object }> {
        const url = Language.getPath(path) + '/' + code + '.json';
        if (Language.texts.has(url) == true) {
            return Language.texts.get(url);
        }

        try {
            const text: { [key: string]: string | object } = await Language.fetch(url);
            Language.texts.set(url, text);
            return Language.texts.get(url);
        } catch (e) {
            console.error(e);

            if (retry < 3) {
                return Language.load(path, code, ++retry);
            } else {
                return {};
            }
        }
    }

    /**
     * 아이모듈 기본 URL 경로를 가져온다.
     *
     * @return {string} baseUrl
     */
    static getBase(): string {
        return Html.get('body').getAttr('data-base');
    }

    /**
     * 루트폴더를 포함한 언어팩 경로를 가져온다.
     *
     * @param string $path 언어팩을 탐색할 경로
     * @return string $path 루트폴더를 포함한 언어팩 탐색 경로
     */
    static getPath(path: string): string {
        return Language.getBase() + (path == '/' ? '' : path) + '/languages';
    }

    /**
     * 문자열 템플릿에서 치환자를 실제 데이터로 변환한다.
     *
     * @param {string} text - 문자열 템플릿
     * @param {object} placeHolder - 치환될 데이터
     * @return {string} message - 치환된 메시지
     */
    static replacePlaceHolder(text: string, placeHolder: { [key: string]: string } = null): string {
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
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @param ?array $paths 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param ?array $codes 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return array|string|null $message 치환된 메시지
     */
    static async getText(
        text: string,
        placeHolder: { [key: string]: string } = null,
        paths: string[] = null,
        codes: string[] = null
    ): Promise<string | object> {
        paths ??= ['/'];
        codes ??= [Html.get('html').getAttr('lang')];
        const keys: string[] = text.split('.');

        let string = null;
        for (const path of paths) {
            for (const code of codes) {
                let string: string | { [key: string]: string | object } = await Language.load(path, code);

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

        if (string === null) {
            return text;
        }

        return typeof string == 'string' ? Language.replacePlaceHolder(string, placeHolder) : string;
    }

    /**
     * 언어팩을 출력한다.
     * 언어팩을 비동기방식으로 가져오기때문에 치환자를 먼저 반환하고, 언어팩이 로딩완료되면 언어팩으로 대치한다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @param ?array $paths 언어팩을 탐색할 경로 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @param ?array $codes 언어팩을 탐색할 언어코드 (우선순위가 가장높은 경로를 배열의 처음에 정의한다.)
     * @return array|string|null $message 치환된 메시지
     */
    static printText(
        text: string,
        placeHolder: { [key: string]: string } = null,
        paths: string[] = null,
        codes: string[] = null
    ): string {
        const uuid = crypto.randomUUID();
        Language.prints.set(uuid, { text: text, placeHolder: placeHolder, paths: paths, codes: codes });
        Language.observe();
        return '<span data-language="' + uuid + '">' + text + '</span>';
    }

    /**
     * 언어팩 출력을 위한 옵저버를 시작한다.
     */
    static observe(): void {
        if (Language.observer === undefined) {
            Language.observer = new MutationObserver(() => {
                document.querySelectorAll('span[data-language]').forEach((dom) => {
                    const options = Language.prints.get(dom.getAttribute('data-language'));
                    if (options === undefined) {
                        return;
                    }

                    Language.getText(options.text, options.placeHolder, options.paths, options.codes).then((string) => {
                        const span = document.querySelector(
                            'span[data-language="' + dom.getAttribute('data-language') + '"]'
                        );
                        if (span !== null) {
                            dom.outerHTML = typeof string == 'string' ? string : JSON.stringify(string);
                        }
                        //Language.prints.delete(dom.getAttribute('data-language'));
                        //Language.disconnect();
                    });
                });

                //Language.disconnect();
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
     *
    static disconnect(): void {
        if (Language.isObserve === true && Language.prints.size === 0) {
            Language.isObserve = false;
            Language.observer.disconnect();
        }
    }
    */
}

Html.ready(() => {
    Language.observe();
});
