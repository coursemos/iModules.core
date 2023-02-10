/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 응답이 오래걸리는 요청을 처리하면서 프로그래스바를 구현하기 위한 클래스를 정의한다.
 *
 * @file /scripts/Progress.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class Progress {
    bytesTotal: number;
    bytesCurrent: number;
    total: number;
    current: number;
    latests: object;
    chunks: Uint8Array[];

    /**
     * 멀티바이트 문자열의 정확한 크기(Bytes) 계산한다.
     *
     * @param {string} text - 계산할 문자열
     * @return {number} bytes - 문자열 크기(Bytes)
     */
    getByteLength(text: string): number {
        if (text != undefined && text != '') {
            let bytes: number = 0;
            let char: number;
            for (let i = 0; (char = text.charCodeAt(i++)); bytes += char >> 11 ? 3 : char >> 7 ? 2 : 1);
            return bytes;
        } else {
            return 0;
        }
    }

    /**
     * 프로그래스바를 위한 데이터를 가져온다.
     *
     * @return {Object} progress
     */
    getProgressData(): { current: number; total: number; datas: object; progress: number } {
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
                if (typeof data == 'object') break;
            } catch (e) {}
        }

        if (data == null) {
            data = { current: 0, total: this.total, datas: null };
        }
        data.progress = this.bytesCurrent / this.bytesTotal;

        return data;
    }

    /**
     * GET 요청을 프로그래스바 요청과 함께 가져온다.
     *
     * @param {string} url - 요청 URL
     * @param {Function} callback - 프로그래스 데이터를 받을 콜백함수
     */
    async get(
        url: string,
        callback: (progress: { current: number; total: number; datas: object; progress: number }) => void
    ): Promise<void> {
        const response = await fetch(url);
        const reader = response.body.getReader();
        this.chunks = [];
        this.bytesCurrent = 0;
        this.bytesTotal = parseInt(response.headers.get('Content-Length') ?? '0', 10);
        this.total = parseInt(response.headers.get('X-Progress-Total') ?? '0', 10);

        if (this.bytesTotal == 0 || this.total == 0) {
            callback({ current: 0, total: 0, datas: null, progress: 0 });
            return;
        }

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                callback(this.getProgressData());
                break;
            }

            this.chunks.push(value);
            this.bytesCurrent += value.length;

            callback(this.getProgressData());
        }
    }

    /**
     * 프로그래스바 데이터가 정상적으로 종료되었는지 확인한다.
     *
     * @return {boolean} success
     */
    isSuccess(): boolean {
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
