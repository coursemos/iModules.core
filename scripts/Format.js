/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Format.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
class Format {
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
}
