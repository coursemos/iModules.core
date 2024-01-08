/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 관리자모듈에서 사용되는 비동기호출 클래스를 정의한다.
 *
 * @file /scripts/Ajax.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
class Ajax {
    static fetchs = new Map();
    /**
     * Fetch 요청의 UUID 값을 생성한다.
     *
     * @param {string} method - 요청방식
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {string} uuid
     */
    static #uuid(method = 'GET', url, params = {}, data = {}, is_retry = true) {
        let postData = {};
        if (data instanceof FormData) {
            data.forEach((value, key) => {
                if (value instanceof File) {
                    postData[key] = value.name;
                }
                else {
                    postData[key] = value;
                }
            });
        }
        else {
            postData = data;
        }
        return Format.sha1(JSON.stringify({ method: method, url: url, params: params, data: postData, is_retry: is_retry }));
    }
    /**
     * 짧은시간내에 동일한 Fetch 요청이 될 경우,
     * 제일 처음 요청된 Fetch 만 수행하고 응답된 데이터를 다른 중복요청한 곳으로 반환한다.
     *
     * @param {string} method - 요청방식
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {Promise<Ajax.Results>} promise - 동일한 요청의 제일 첫번째 요청
     */
    static async #call(method = 'GET', url, params = {}, data = {}, is_retry = true) {
        const uuid = Ajax.#uuid(method, url, params, data, is_retry);
        if (Ajax.fetchs.has(uuid) == true) {
            return Ajax.fetchs.get(uuid);
        }
        Ajax.fetchs.set(uuid, Ajax.#fetch(method, url, params, data, is_retry));
        return Ajax.fetchs.get(uuid);
    }
    /**
     * 실제 Fetch 함수를 실행한다.
     *
     * @param {string} method - 요청방식
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {Promise<Ajax.Results>} results - 요청결과
     */
    static async #fetch(method = 'GET', url, params = {}, data = {}, is_retry = true) {
        const uuid = Ajax.#uuid(method, url, params, data, is_retry);
        const requestUrl = new URL(url, location.origin);
        for (const name in params) {
            if (params[name] === null) {
                requestUrl.searchParams.delete(name);
            }
            else {
                requestUrl.searchParams.append(name, params[name]);
            }
        }
        url = requestUrl.toString();
        let retry = (is_retry === false ? 10 : is_retry);
        const headers = {
            'X-Method': method,
            'Accept-Language': iModules.getLanguage(),
            'Accept': 'application/json',
        };
        let body = null;
        if (method == 'POST' || method == 'PUT') {
            if (data instanceof FormData) {
                body = data;
            }
            else {
                headers['Content-Type'] = 'application/json; charset=utf-8';
                body = JSON.stringify(data);
            }
        }
        try {
            const response = (await fetch(url, {
                method: method,
                headers: headers,
                body: body,
                cache: 'no-store',
                redirect: 'follow',
            }).catch((error) => {
                Ajax.fetchs.delete(uuid);
                if (retry <= 3) {
                    return Ajax.#call(method, url, params, data, ++retry);
                }
                else {
                    // @todo 에러메시지
                    console.error(error);
                    return { success: false };
                }
            }));
            const results = (await response.json());
            if (results.success == false && results.message !== undefined) {
                // @todo 에러메시지
            }
            Ajax.fetchs.delete(uuid);
            return results;
        }
        catch (e) {
            Ajax.fetchs.delete(uuid);
            if (retry <= 3) {
                return Ajax.#call(method, url, params, data, ++retry);
            }
            else {
                // @todo 에러메시지
                console.error(e);
                return { success: false };
            }
        }
    }
    /**
     * GET 방식으로 데이터를 가져온다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {Promise<Ajax.Results>} results - 요청결과
     */
    static async get(url, params = {}, is_retry = true) {
        return Ajax.#call('GET', url, params, null, is_retry);
    }
    /**
     * POST 방식으로 데이터를 가져온다.
     * 전송할 데이터는 JSON 방식으로 전송된다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {Ajax.Params} params - GET 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {Promise<Ajax.Results>} results - 요청결과
     */
    static async post(url, data = {}, params = {}, is_retry = true) {
        return Ajax.#call('POST', url, params, data, is_retry);
    }
    /**
     * DELETE 방식으로 데이터를 가져온다.
     * 전송할 데이터는 JSON 방식으로 전송된다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {boolean|number} is_retry - 재시도여부
     * @return {Promise<Ajax.Results>} results - 요청결과
     */
    static async delete(url, params = {}, is_retry = true) {
        return Ajax.#call('DELETE', url, params, null, is_retry);
    }
}
