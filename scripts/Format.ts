/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Format.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 2.
 */
class Format {
    /**
     * 숫자 포맷을 지역에 따라 변경한다.
     *
     * @param {number|string} number - 숫자
     * @param {string} locale - 지역코드 (NULL 인 경우 현재 언어코드)
     */
    static number(number: number | string, locale: string = null): string {
        if (typeof number == 'string') {
            number = parseFloat(number);
        }

        return number?.toLocaleString(locale ?? iModules.getLanguage()) ?? null;
    }

    /**
     * 날짜 포맷을 변경한다.
     *
     * @param {string} format - 포맷 (PHP 의 포맷형태를 따른다.)
     * @param {number} timestamp - 타임스탬프 (NULL 인 경우 현재시각)
     * @param {string} locale - 지역코드 (NULL 인 경우 현재 언어코드)
     * @return {string} formatted
     */
    static date(format: string, timestamp: number = null, locale: string = null): string {
        timestamp ??= moment().unix();
        timestamp = timestamp * 1000;

        locale ??= iModules.getLanguage();

        /**
         * PHP date 함수 포맷텍스트를 momentjs 포맷텍스트로 치환하기 위한 배열정의
         */
        const replacements = {
            'd': 'DD',
            'D': 'ddd',
            'j': 'D',
            'l': 'dddd',
            'N': 'E',
            'S': 'o',
            'w': 'e',
            'z': 'DDD',
            'W': 'W',
            'F': 'MMMM',
            'm': 'MM',
            'M': 'MMM',
            'n': 'M',
            't': '', // no equivalent
            'L': '', // no equivalent
            'o': 'YYYY',
            'Y': 'YYYY',
            'y': 'YY',
            'a': 'a',
            'A': 'A',
            'B': '', // no equivalent
            'g': 'h',
            'G': 'H',
            'h': 'hh',
            'H': 'HH',
            'i': 'mm',
            's': 'ss',
            'u': 'SSS',
            'e': 'zz', // deprecated since version 1.6.0 of moment.js
            'I': '', // no equivalent
            'O': '', // no equivalent
            'P': '', // no equivalent
            'T': '', // no equivalent
            'Z': '', // no equivalent
            'c': '', // no equivalent
            'r': '', // no equivalent
            'U': 'X',
        };

        const reg = new RegExp(Object.keys(replacements).join('|'), 'g');
        format = format.replace(reg, (match) => {
            return replacements[match];
        });

        const datetime = moment(timestamp).format();
        const formatted = moment(timestamp).locale(locale).format(format);

        return '<time datetime="' + datetime + '">' + formatted + '</time>';
    }

    /**
     * byte 단위의 파일크기를 적절한 단위로 변환한다.
     *
     * @param {(number|string)} size - 파일크기
     * @param {boolean} is_KiB - KiB 단위 사용여부
     * @return {string} size - 단위를 포함한 파일크기
     */
    static size(size: number | string, is_KiB: boolean = false): string {
        if (typeof size == 'string') {
            size = parseInt(size, 10);
        }

        const depthSize: number = is_KiB === true ? 1024 : 1000;
        if (size / depthSize / depthSize / depthSize > 1) {
            return (size / depthSize / depthSize / depthSize).toFixed(2) + (is_KiB === true ? 'GiB' : 'GB');
        } else if (size / depthSize / depthSize > 1) {
            return (size / depthSize / depthSize).toFixed(2) + (is_KiB === true ? 'MiB' : 'MB');
        } else if (size / depthSize > 1) {
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
    static keycode(str: string): string {
        str = Format.normalizer(str);
        const chos = 'ㄱ,ㄲ,ㄴ,ㄷ,ㄸ,ㄹ,ㅁ,ㅂ,ㅃ,ㅅ,ㅆ,ㅇ,ㅈ,ㅉ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ'.split(',');
        const jungs = 'ㅏ,ㅐ,ㅑ,ㅒ,ㅓ,ㅔ,ㅕ,ㅖ,ㅗ,ㅘ,ㅙ,ㅚ,ㅛ,ㅜ,ㅝ,ㅞ,ㅟ,ㅠ,ㅡ,ㅢ,ㅣ'.split(',');
        const jongs = ',ㄱ,ㄲ,ㄳ,ㄴ,ㄵ,ㄶ,ㄷ,ㄹ,ㄺ,ㄻ,ㄼ,ㄽ,ㄾ,ㄿ,ㅀ,ㅁ,ㅂ,ㅄ,ㅅ,ㅆ,ㅇ,ㅈ,ㅊ,ㅋ,ㅌ,ㅍ,ㅎ'.split(',');

        let unicode: number[] = [];
        let values: number[] = [];
        let index = 0;

        const encoder = new TextEncoder();
        const decoder = new TextDecoder();
        for (const code of encoder.encode(str)) {
            if (code < 128) {
                unicode.push(code);
            } else {
                if (values.length == 0) {
                    index = code < 224 ? 2 : 3;
                }
                values.push(code);
                if (values.length == index) {
                    const number =
                        index == 3
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
            } else {
                if (code < 128) {
                    keycode += decoder.decode(new Uint8Array([code]));
                } else if (code < 2048) {
                    keycode += decoder.decode(new Uint8Array([192 + (code - (code % 64)) / 64, 128 + (code % 64)]));
                } else {
                    keycode += decoder.decode(
                        new Uint8Array([
                            224 + (code - (code % 4096)) / 4096,
                            128 + ((code % 4096) - (code % 64)) / 64,
                            128 + (code % 64),
                        ])
                    );
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
    static substring(string: string, length: number | [number, number]): string {
        string = string.trim();

        if (typeof length == 'number') {
            if (length < 0) {
                length = [0, length * -1];
            } else {
                length = [length, 0];
            }
        }

        if (
            Array.isArray(length) == false ||
            length.length != 2 ||
            typeof length[0] != 'number' ||
            typeof length[1] != 'number'
        ) {
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
    static normalizer(string: string): string {
        return string.normalize('NFC');
    }

    /**
     * SHA1 해시를 가져온다.
     *
     * @param {string} string - 해시를 만들 문자열
     * @return {string} hash - 해시
     */
    static sha1(string: string): string {
        const rotate_left = (n: number, s: number) => {
            return (n << s) | (n >>> (32 - s));
        };

        const cvt_hex = (val: number) => {
            let str = '';
            for (let i = 7; i >= 0; i--) {
                str += ((val >>> (i * 4)) & 0x0f).toString(16);
            }
            return str;
        };

        const W = new Array(80);
        let H0 = 0x67452301;
        let H1 = 0xefcdab89;
        let H2 = 0x98badcfe;
        let H3 = 0x10325476;
        let H4 = 0xc3d2e1f0;

        const encoder = new TextEncoder();
        const utf8 = encoder.encode(string);
        const length = utf8.length;

        var word_array = [];
        for (let i = 0; i < length - 3; i += 4) {
            word_array.push((utf8[i] << 24) | (utf8[i + 1] << 16) | (utf8[i + 2] << 8) | utf8[i + 3]);
        }

        switch (length % 4) {
            case 0:
                word_array.push(0x080000000);
                break;

            case 1:
                word_array.push((utf8[length - 1] << 24) | 0x0800000);
                break;

            case 2:
                word_array.push((utf8[length - 2] << 24) | (utf8[length - 1] << 16) | 0x08000);
                break;

            case 3:
                word_array.push((utf8[length - 3] << 24) | (utf8[length - 2] << 16) | (utf8[length - 1] << 8) | 0x80);
                break;
        }

        while (word_array.length % 16 != 14) {
            word_array.push(0);
        }

        word_array.push(length >>> 29);
        word_array.push((length << 3) & 0x0ffffffff);

        for (let blockstart = 0; blockstart < word_array.length; blockstart += 16) {
            for (let i = 0; i < 16; i++) {
                W[i] = word_array[blockstart + i];
            }
            for (let i = 16; i <= 79; i++) {
                W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);
            }

            let A = H0;
            let B = H1;
            let C = H2;
            let D = H3;
            let E = H4;
            let temp: number;

            for (let i = 0; i <= 19; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5a827999) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (let i = 20; i <= 39; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ed9eba1) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (let i = 40; i <= 59; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8f1bbcdc) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (let i = 60; i <= 79; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xca62c1d6) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            H0 = (H0 + A) & 0x0ffffffff;
            H1 = (H1 + B) & 0x0ffffffff;
            H2 = (H2 + C) & 0x0ffffffff;
            H3 = (H3 + D) & 0x0ffffffff;
            H4 = (H4 + E) & 0x0ffffffff;
        }

        const hash = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
        return hash.toLowerCase();
    }

    /**
     * 두개의 데이터가 동일한지 비교한다.
     *
     * @param {any} left
     * @param {any} right
     * @return {boolean} is_equal - 동일한지 여부
     */
    static isEqual(left: any, right: any): boolean {
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
                } else {
                    let matched: boolean = false;
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

    /**
     * 주어진 데이터가 필터조건에 해당하는지 확인한다.
     *
     * @param {Object} data - 필터조건에 일치하는지 확인할 데이터
     * @param {Object} filters - 필터조건
     * @param {'OR'|'AND'} filterMode - 필터모드 (OR, AND)
     * @return {boolean} matched - 필터조건만족여부
     */
    static filter(
        data: Object,
        filters: { [field: string]: { value: any; operator: string } },
        filterMode: 'OR' | 'AND' = 'AND'
    ): boolean {
        let matched = true;
        for (const field in filters) {
            const filter = filters[field];
            const value = data[field] ?? null;

            let passed = true;
            switch (filter.operator) {
                case '=':
                    if (value !== filter.value) {
                        passed = false;
                    }
                    break;

                case '!=':
                    if (value === filter.value) {
                        passed = false;
                    }
                    break;

                case '>=':
                    if (value < filter.value) {
                        passed = false;
                    }
                    break;

                case '>':
                    if (value <= filter.value) {
                        passed = false;
                    }
                    break;

                case '<=':
                    if (value > filter.value) {
                        passed = false;
                    }
                    break;

                case '<':
                    if (value >= filter.value) {
                        passed = false;
                    }
                    break;

                case 'in':
                    if (
                        Array.isArray(filter.value) == false ||
                        Array.isArray(value) == true ||
                        filter.value.includes(value) == false
                    ) {
                        passed = false;
                    }
                    break;

                case 'inset':
                    if (
                        Array.isArray(value) == false ||
                        Array.isArray(filter.value) == true ||
                        value.includes(filter.value) == false
                    ) {
                        passed = false;
                    }
                    break;

                case 'like':
                    if (value === null || value.search(filter.value) == -1) {
                        passed = false;
                    }
                    break;

                case 'likecode':
                    const keycode = Format.keycode(filter.value);
                    const valuecode = value === null ? null : Format.keycode(value);

                    if (valuecode === null || valuecode.search(keycode) == -1) {
                        passed = false;
                    }
                    break;

                default:
                    passed = false;
            }

            if (filterMode == 'AND') {
                matched = matched && passed;
            } else {
                matched = passed;
            }

            if ((filterMode == 'AND' && matched == false) || (filterMode == 'OR' && matched == true)) {
                break;
            }
        }

        return matched;
    }
}
