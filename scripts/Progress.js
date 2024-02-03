/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 응답이 오래걸리는 요청을 처리하면서 프로그래스바를 구현하기 위한 클래스를 정의한다.
 *
 * @file /scripts/Progress.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 3.
 */
class Progress {
    bytesTotal;
    bytesCurrent;
    total;
    current;
    latests;
    chunks;
    controller;
    signal;
    static init() {
        return new Progress();
    }
    constructor() {
        this.controller = new AbortController();
        this.signal = this.controller.signal;
    }
    /**
     * 멀티바이트 문자열의 정확한 크기(Bytes) 계산한다.
     *
     * @param {string} text - 계산할 문자열
     * @return {number} bytes - 문자열 크기(Bytes)
     */
    getByteLength(text) {
        if (text != undefined && text != '') {
            let bytes = 0;
            let char;
            for (let i = 0; (char = text.charCodeAt(i++)); bytes += char >> 11 ? 3 : char >> 7 ? 2 : 1)
                ;
            return bytes;
        }
        else {
            return 0;
        }
    }
    /**
     * 프로그래스바를 위한 데이터를 가져온다.
     *
     * @return {Progress.Results} progress
     */
    getProgressData() {
        let chunksAll = new Uint8Array(this.bytesCurrent);
        let position = 0;
        for (const chunk of this.chunks) {
            chunksAll.set(chunk, position);
            position += chunk.length;
        }
        let result = new TextDecoder('utf-8').decode(chunksAll);
        const lines = result.split('\n');
        let data = null;
        while (lines.length > 0) {
            let line = lines.pop();
            try {
                data = JSON.parse(line.trim());
                if (typeof data == 'object')
                    break;
            }
            catch (e) { }
        }
        if (data == null) {
            data = { current: 0, total: this.total, datas: null };
        }
        data.success = true;
        data.percentage = (this.bytesCurrent / this.bytesTotal) * 100;
        data.end ??= false;
        return data;
    }
    /**
     * 실제 프로그래스바 요청을 처리한다.
     *
     * @param {method} method - 요청방식
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {Function} callback - 프로그래스 콜백함수
     */
    async #fetch(method = 'GET', url, params = {}, data = {}, callback) {
        const requestUrl = new URL(url, location.origin);
        for (const name in params) {
            if (params[name] === null) {
                requestUrl.searchParams.delete(name);
            }
            else {
                requestUrl.searchParams.append(name, params[name].toString());
            }
        }
        url = requestUrl.toString();
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
                signal: this.signal,
                method: method,
                headers: headers,
                body: body,
                cache: 'no-store',
                redirect: 'follow',
            }).catch(async (e) => {
                callback({ success: false, current: 0, total: 0, datas: e, percentage: 0, end: true });
            }));
            const reader = response.body.getReader();
            this.chunks = [];
            this.bytesCurrent = 0;
            this.bytesTotal = parseInt(response.headers.get('Content-Length') ?? '0', 10);
            this.total = parseInt(response.headers.get('X-Progress-Total') ?? '-1', 10);
            if (this.bytesTotal == 0 || this.total == -1) {
                callback({ success: false, current: 0, total: 0, datas: null, percentage: 0, end: true });
                return;
            }
            callback({ success: true, current: 0, total: this.total, datas: null, percentage: 0, end: false });
            while (true) {
                const { done, value } = await reader.read();
                if (done) {
                    break;
                }
                this.chunks.push(value);
                this.bytesCurrent += value.length;
                callback(this.getProgressData());
            }
        }
        catch (e) {
            callback({ success: false, current: 0, total: 0, datas: e, percentage: 0, end: true });
            return;
        }
    }
    /**
     * 프로그래스바 요청을 취소한다.
     */
    abort() {
        this.controller.abort();
    }
    /**
     * GET 요청을 프로그래스바 요청과 함께 가져온다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Function} callback - 프로그래스 데이터를 받을 콜백함수
     */
    async get(url, params = {}, callback) {
        return await this.#fetch('GET', url, params, null, callback);
    }
    /**
     * POST 요청을 프로그래스바 요청과 함께 가져온다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Data|FormData} data - 전송할 데이터
     * @param {Ajax.Params} params - GET 데이터
     * @param {Function} callback - 프로그래스 데이터를 받을 콜백함수
     */
    async post(url, data = {}, params = {}, callback) {
        return await this.#fetch('POST', url, params, data, callback);
    }
    /**
     * DELETE 요청을 프로그래스바 요청과 함께 가져온다.
     *
     * @param {string} url - 요청주소
     * @param {Ajax.Params} params - GET 데이터
     * @param {Function} callback - 프로그래스 데이터를 받을 콜백함수
     */
    async delete(url, params = {}, callback) {
        return await this.#fetch('DELETE', url, params, null, callback);
    }
    /**
     * 프로그래스바 데이터가 정상적으로 종료되었는지 확인한다.
     *
     * @return {boolean} success
     */
    isSuccess() {
        if (this.bytesCurrent != this.bytesTotal) {
            return false;
        }
        let chunksAll = new Uint8Array(this.bytesCurrent);
        let position = 0;
        for (const chunk of this.chunks) {
            chunksAll.set(chunk, position);
            position += chunk.length;
        }
        let result = new TextDecoder('utf-8').decode(chunksAll);
        if (result.split('\n').pop() == '@') {
            return true;
        }
        return false;
    }
}
