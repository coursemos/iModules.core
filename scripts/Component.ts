/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈, 플러그인, 위젯의 자바스크립트 인터페이스 클래스를 정의한다.
 *
 * @file /scripts/Component.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 */
class Component {
    name: string;
    type: string;

    /**
     * 컴포넌트 타입을 가져온다.
     *
     * @return {string} type - 컴포넌트타입
     */
    getType(): string {
        return this.type;
    }

    /**
     * 컴포넌트명을 가져온다.
     *
     * @return {string} name - 컴포넌트명
     */
    getName(): string {
        return this.name;
    }

    /**
     * 컴포넌트 경로를 가져온다.
     *
     * @return {string} dir - 컴포넌트 경로
     */
    getDir(): string {
        return iModules.getDir() + '/' + this.type + 's/' + this.name;
    }

    /**
     * 언어팩을 불러온다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @return array|string|null $message 치환된 메시지
     */
    async getText(text: string, placeHolder: { [key: string]: string } = null): Promise<string | object> {
        const paths: string[] = ['/' + this.type + 's/' + this.name, '/'];
        return Language.getText(text, placeHolder, paths);
    }

    /**
     * 언어팩 문자열이 위치할 DOM 을 반환하고, 언어팩이 비동기적으로 로딩되면 언어팩 내용으로 변환한다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @return array|string|null $message 치환된 메시지
     */
    printText(text: string, placeHolder: { [key: string]: string } = null): string {
        const paths: string[] = ['/' + this.type + 's/' + this.name, '/'];
        return Language.printText(text, placeHolder, paths);
    }

    /**
     * 프로세스 URL 경로를 가져온다.
     *
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    getProcessUrl(path: string): string {
        return iModules.getProcessUrl(this.type, this.name, path);
    }
}
