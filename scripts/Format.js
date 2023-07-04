/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Format.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 7. 4.
 */
class Format {
    /**
     * 숫자 포맷을 지역에 따라 변경한다.
     *
     * @param {number|string} number - 숫자
     * @param {string} locale - 지역코드 (NULL 인 경우 현재 언어코드)
     */
    static number(number, locale = null) {
        if (typeof number == 'string') {
            number = parseFloat(number);
        }
        return number.toLocaleString(locale ?? iModules.getLanguage());
    }
    /**
     * byte 단위의 파일크기를 적절한 단위로 변환한다.
     *
     * @param {(number|string)} size - 파일크기
     * @param {boolean} is_KiB - KiB 단위 사용여부
     * @return {string} size - 단위를 포함한 파일크기
     */
    static size(size, is_KiB = false) {
        if (typeof size == 'string') {
            size = parseInt(size, 10);
        }
        const depthSize = is_KiB === true ? 1024 : 1000;
        if (size / depthSize / depthSize / depthSize > 1) {
            return (size / depthSize / depthSize / depthSize).toFixed(2) + (is_KiB === true ? 'GiB' : 'GB');
        }
        else if (size / depthSize / depthSize > 1) {
            return (size / depthSize / depthSize).toFixed(2) + (is_KiB === true ? 'MiB' : 'MB');
        }
        else if (size / depthSize > 1) {
            return (size / depthSize).toFixed(2) + (is_KiB === true ? 'KiB' : 'KB');
        }
        return size + 'B';
    }
    /**
     * 키코드값을 가져온다.
     *
     * @param {string} str - 변환할 텍스트
     * @return {string} keycode - 키코드
     */
    static keycode(str) {
        str = Format.normalizer(str);
        const chos = 'ㄱ,ㄲ,ㄴ,ㄷ,ㄸ,ㄹ,ㅁ,ㅂ,ㅃ,ㅅ,ㅆ,ㅇ,ㅈ,ㅉ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ'.split(',');
        const jungs = 'ㅏ,ㅐ,ㅑ,ㅒ,ㅓ,ㅔ,ㅕ,ㅖ,ㅗ,ㅘ,ㅙ,ㅚ,ㅛ,ㅜ,ㅝ,ㅞ,ㅟ,ㅠ,ㅡ,ㅢ,ㅣ'.split(',');
        const jongs = ',ㄱ,ㄲ,ㄳ,ㄴ,ㄵ,ㄶ,ㄷ,ㄹ,ㄺ,ㄻ,ㄼ,ㄽ,ㄾ,ㄿ,ㅀ,ㅁ,ㅂ,ㅄ,ㅅ,ㅆ,ㅇ,ㅈ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ'.split(',');
        let unicode = [];
        let values = [];
        let index = 0;
        const encoder = new TextEncoder();
        const decoder = new TextDecoder();
        for (const code of encoder.encode(str)) {
            if (code < 128) {
                unicode.push(code);
            }
            else {
                if (values.length == 0) {
                    index = code < 224 ? 2 : 3;
                }
                values.push(code);
                if (values.length == index) {
                    const number = index == 3
                        ? (values[0] % 16) * 4096 + (values[1] % 64) * 64 + (values[2] % 64)
                        : (values[0] % 32) * 64 + (values[1] % 64);
                    unicode.push(number);
                    values = [];
                    index = 1;
                }
            }
        }
        let keycode = '';
        for (const code of unicode) {
            if (code >= 44032 && code <= 55203) {
                const temp = code - 44032;
                const cho = Math.floor(temp / 21 / 28);
                const jung = Math.floor((temp % (21 * 28)) / 28);
                const jong = Math.floor(temp % 28);
                keycode += chos[cho] + jungs[jung] + jongs[jong];
            }
            else {
                if (code < 128) {
                    keycode += decoder.decode(new Uint8Array([code]));
                }
                else if (code < 2048) {
                    keycode += decoder.decode(new Uint8Array([192 + (code - (code % 64)) / 64, 128 + (code % 64)]));
                }
                else {
                    keycode += decoder.decode(new Uint8Array([
                        224 + (code - (code % 4096)) / 4096,
                        128 + ((code % 4096) - (code % 64)) / 64,
                        128 + (code % 64),
                    ]));
                }
            }
        }
        return keycode.replace(/ /g, '').toLowerCase();
    }
    /**
     * 부분문자열을 위치에 따라 가져온다.
     *
     * @param {string} string - 문자열
     * @param {number|[number, number]} length - 부분문자열 길이 (양수:앞, 음수:뒤, [앞, 뒤])
     * @return {string} substring - 부분문자열
     */
    static substring(string, length) {
        string = string.trim();
        if (typeof length == 'number') {
            if (length < 0) {
                length = [0, length * -1];
            }
            else {
                length = [length, 0];
            }
        }
        if (Array.isArray(length) == false ||
            length.length != 2 ||
            typeof length[0] != 'number' ||
            typeof length[1] != 'number') {
            return string;
        }
        const origin = string.length;
        if (origin <= length[0] + length[1]) {
            return string;
        }
        let substring = string.substring(0, length[0]).trim() + '…';
        substring += string.substring(origin - length[1], origin).trim();
        return substring;
    }
    /**
     * 유니코드 문자열을 정규화한다.
     *
     * @param {string} string - 대상문자열
     * @return {string} string - NFC 정규화 문자열
     */
    static normalizer(string) {
        return string.normalize('NFC');
    }
    /**
     * 두개의 형식 및 데이터가 동일한지 비교한다.
     *
     * @param {any} left
     * @param {any} right
     * @returns {boolean} is_equal - 동일한지 여부
     */
    static isEqual(left, right) {
        if (left === null || right === null) {
            return left === right;
        }
        if (typeof left !== typeof right) {
            return false;
        }
        if (Array.isArray(left) == true || Array.isArray(right) == true) {
            if (Array.isArray(left) != Array.isArray(right)) {
                return false;
            }
            if (left.length != right.length) {
                return false;
            }
            for (const v of left) {
                if (['number', 'boolean', 'string'].includes(typeof v) == true) {
                    if (right.includes(v) === false) {
                        return false;
                    }
                }
                else {
                    let matched = false;
                    for (const c of right) {
                        if (Format.isEqual(v, c) == true) {
                            matched = true;
                            break;
                        }
                    }
                    if (matched == false) {
                        return false;
                    }
                }
            }
            return true;
        }
        if (typeof left === 'object' || typeof right === 'object') {
            if (typeof left != typeof right) {
                return false;
            }
            const leftKeys = Object.keys(left);
            const rightKeys = Object.keys(right);
            if (leftKeys.length != rightKeys.length) {
                return false;
            }
            for (const k of leftKeys) {
                if (rightKeys.includes(k) === false) {
                    return false;
                }
                if (Format.isEqual(left[k], right[k]) == false) {
                    return false;
                }
            }
            return true;
        }
        return left === right;
    }
}
