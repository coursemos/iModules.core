/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 자바스크립트 클래스의 부모클래스를 정의한다.
 *
 * @file /scripts/Module.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
class Module {
    name;
    /**
     * 모듈 클래스를 생성한다.
     *
     * @param {string} name - 모듈명
     */
    constructor(name) {
        this.name = name;
    }
    /**
     * 해당 모듈의 Dom 객체를 초기화한다.
     *
     * @param {Dom} $dom - 현재 모듈의 DOM 객체
     */
    init($dom) { }
    /**
     * 프로세스 URL 경로를 가져온다.
     *
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    getProcessUrl(path) {
        return iModules.getProcessUrl('module', this.name, path);
    }
    /**
     * 언어팩을 불러온다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @return array|string|null $message 치환된 메시지
     */
    async getText(text, placeHolder = null) {
        const paths = ['/modules/' + this.name, '/'];
        return Language.getText(text, placeHolder, paths);
    }
    /**
     * 언어팩 문자열이 위치할 DOM 을 반환하고, 언어팩이 비동기적으로 로딩되면 언어팩 내용으로 변환한다.
     *
     * @param string $text 언어팩코드
     * @param ?array $placeHolder 치환자
     * @return array|string|null $message 치환된 메시지
     */
    printText(text, placeHolder = null) {
        const paths = ['/modules/' + this.name, '/'];
        return Language.printText(text, placeHolder, paths);
    }
}
