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
     * 유니코드 문자열을 정규화한다.
     *
     * @param {string} string - 대상문자열
     * @return {string} string - NFC 정규화 문자열
     */
    static normalizer(string: string): string {
        return string.normalize('NFC');
    }
}
