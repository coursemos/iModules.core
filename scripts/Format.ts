/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Format.ts
 * @author sungjin <esung246@naddle.net>
 * @license MIT License
 * @modified 2025. 1. 22.
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
     * PHP date 함수 포맷텍스트를 momentjs 포맷텍스트로 치환한다.
     *
     * @param {string} format - PHP 포맷
     * @return {string} format - moment 포맷
     */
    static moment(format: string): string {
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
            't': '',
            'L': '',
            'o': 'YYYY',
            'Y': 'YYYY',
            'y': 'YY',
            'a': 'a',
            'A': 'A',
            'B': '',
            'g': 'h',
            'G': 'H',
            'h': 'hh',
            'H': 'HH',
            'i': 'mm',
            's': 'ss',
            'u': 'SSS',
            'e': 'zz',
            'I': '',
            'O': '',
            'P': '',
            'T': '',
            'Z': '',
            'c': '',
            'r': '',
            'U': 'X',
        };

        const reg = new RegExp(Object.keys(replacements).join('|'), 'g');
        format = format.replace(reg, (match) => {
            return replacements[match];
        });

        return format;
    }

    /**
     * 날짜 포맷을 변경한다.
     *
     * @param {string} format - 포맷 (PHP 의 포맷형태를 따른다.)
     * @param {number|moment} timestamp - 타임스탬프 (NULL 인 경우 현재시각)
     * @param {string} locale - 지역코드 (NULL 인 경우 현재 언어코드)
     * @param {boolean} is_html - time 태그 여부
     * @return {string} formatted
     */
    static date(format: string, timestamp: any = null, locale: string = null, is_html: boolean = true): string {
        timestamp ??= moment().unix();

        let m = null;
        if (timestamp instanceof moment) {
            m = timestamp;
        } else {
            m = moment(timestamp * 1000);
        }

        locale ??= iModules.getLanguage();

        const datetime = m.format();
        const formatted = m.locale(locale).format(Format.moment(format));

        if (is_html === true) {
            return '<time datetime="' + datetime + '">' + formatted + '</time>';
        } else {
            return formatted;
        }
    }

    /**
     * 날짜 기간의 포맷을 변경한다.
     * @param {string} format - 포맷 (PHP 의 포맷형태를 따른다.)
     * @param {number|moment} start - 타임스탬프
     * @param {number|moment} end - 타임스탬프
     * @param {string} locale - 지역코드 (NULL 인 경우 현재 언어코드)
     * @param {boolean} is_html - time 태그 여부
     * @return {string} formatted
     */
    static dates(
        format: string,
        start: any = null,
        end: any = null,
        locale: string = null,
        is_html: boolean = true
    ): string {
        let m = null;
        if (start instanceof moment) {
            m = start;
        } else {
            m = moment(start * 1000);
        }

        let n = null;
        if (end instanceof moment) {
            n = end;
        } else {
            n = moment(end * 1000);
        }

        locale ??= iModules.getLanguage();

        const startTime = m.format();
        const startFormatted = m.locale(locale).format(Format.moment(format));

        const endTime = n.format();
        const endFormatted = n.locale(locale).format(Format.moment(format));

        if (is_html === true) {
            return `<time datetime="${startTime}~${endTime}">${startFormatted} ~ ${endFormatted}</time>`;
        } else {
            return `${startFormatted} ~ ${endFormatted}`;
        }
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

        if (typeof left === 'function' || typeof right === 'function') {
            if (typeof left != typeof right) {
                return false;
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
        filters ??= {};
        let matched = true;
        for (const field in filters) {
            const filter = filters[field];
            const value = data[field] ?? null;

            let passed = true;
            switch (filter.operator) {
                case '=':
                    if (value.toString() !== filter.value.toString()) {
                        passed = false;
                    }
                    break;

                case '!=':
                    if (value.toString() === filter.value.toString()) {
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

                case 'range':
                    if (filter.value?.start?.value) {
                        const operator = filter.value?.start?.operator ?? '>';

                        if (operator == '>') {
                            if (value <= filter.value?.start?.value) {
                                passed = false;
                            }
                        } else {
                            if (value < filter.value?.start?.value) {
                                passed = false;
                            }
                        }
                    }

                    if (filter.value?.end?.value) {
                        const operator = filter.value?.end?.operator ?? '<';

                        if (operator == '<') {
                            if (value >= filter.value?.end?.value) {
                                passed = false;
                            }
                        } else {
                            if (value > filter.value?.end?.value) {
                                passed = false;
                            }
                        }
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

                case 'not_in':
                    if (
                        Array.isArray(filter.value) == false ||
                        Array.isArray(value) == true ||
                        filter.value.includes(value) == true
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
                    if (value === null || value.indexOf(filter.value) == -1) {
                        passed = false;
                    }
                    break;

                case 'likes':
                    if (value === null) {
                        passed = false;
                    }
                    let hasKeyword = false;
                    for (const keyword of filter.value.split(' ')) {
                        if (value.indexOf(keyword) > -1) {
                            hasKeyword = true;
                            break;
                        }
                    }
                    if (hasKeyword == false) {
                        passed = false;
                    }
                    break;

                case 'likesall':
                    if (value === null) {
                        passed = false;
                    }
                    let isAll = true;
                    for (const keyword of filter.value.split(' ')) {
                        if (value.indexOf(keyword) == -1) {
                            isAll = false;
                            break;
                        }
                    }
                    if (isAll == false) {
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

                case 'date':
                    const dateoperator = filter.value.operator;
                    const dateformat = filter.value.format;

                    let datestart = null;
                    let dateend = null;

                    if (dateoperator == 'today') {
                        datestart = dateend = moment();
                    } else if (dateoperator == 'yesterday') {
                        datestart = dateend = moment().add(-1, 'days');
                    } else if (dateoperator == 'thisweek') {
                        datestart = moment().startOf('week');
                        dateend = moment().endOf('week');
                    } else if (dateoperator == 'lastweek') {
                        datestart = moment().add(-1, 'weeks').startOf('week');
                        dateend = moment().add(-1, 'weeks').endOf('week');
                    } else if (dateoperator == 'thismonth') {
                        datestart = moment().startOf('month');
                        dateend = moment().endOf('month');
                    } else if (dateoperator == 'lastmonth') {
                        datestart = moment().add(-1, 'months').startOf('month');
                        dateend = moment().add(-1, 'months').endOf('month');
                    } else if (dateoperator == 'thisyear') {
                        datestart = moment().startOf('year');
                        dateend = moment().endOf('year');
                    } else if (dateoperator == 'lastyear') {
                        datestart = moment().add(-1, 'years').startOf('year');
                        dateend = moment().add(-1, 'years').endOf('year');
                    } else if (dateoperator == 'range') {
                        datestart = moment(filter.value.range[0]);
                        dateend = moment(filter.value.range[1]);
                    } else if (dateoperator == '=') {
                        datestart = dateend = moment(filter.value.value);
                    } else if (dateoperator == '<=') {
                        dateend = moment(filter.value.value);
                    } else if (dateoperator == '>=') {
                        datestart = moment(filter.value.value);
                    }

                    if (dateformat == 'timestamp') {
                        let timestamp = parseInt(value, 10);
                        datestart = datestart?.unix() ?? null;
                        dateend = dateend?.add(1, 'days')?.unix() ?? null;

                        if (datestart !== null) {
                            if (timestamp < datestart) {
                                passed = false;
                                break;
                            }
                        }

                        if (dateend !== null) {
                            if (timestamp >= dateend) {
                                passed = false;
                            }
                        }
                    } else if (dateformat == 'date') {
                        let timestamp = parseInt(value, 10);
                        datestart = datestart?.format('YYYY-MM-DD') ?? null;
                        dateend = dateend?.add(1, 'days')?.format('YYYY-MM-DD') ?? null;

                        if (datestart !== null) {
                            if (timestamp < datestart) {
                                passed = false;
                                break;
                            }
                        }

                        if (dateend !== null) {
                            if (timestamp >= dateend) {
                                passed = false;
                            }
                        }
                    } else if (dateformat == 'datetime') {
                        let timestamp = parseInt(value, 10);
                        datestart = datestart?.format('YYYY-MM-DD HH:mm:ss') ?? null;
                        dateend = dateend?.add(1, 'days')?.format('YYYY-MM-DD HH:mm:ss') ?? null;

                        if (datestart !== null) {
                            if (timestamp < datestart) {
                                passed = false;
                                break;
                            }
                        }

                        if (dateend !== null) {
                            if (timestamp >= dateend) {
                                passed = false;
                            }
                        }
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
