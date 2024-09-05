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
    name;
    type;
    /**
     * 컴포넌트 타입을 가져온다.
     *
     * @return {string} type - 컴포넌트타입
     */
    getType() {
        return this.type;
    }
    /**
     * 컴포넌트명을 가져온다.
     *
     * @return {string} name - 컴포넌트명
     */
    getName() {
        return this.name;
    }
    /**
     * 컴포넌트 경로를 가져온다.
     *
     * @return {string} dir - 컴포넌트 경로
     */
    getDir() {
        return iModules.getDir() + '/' + this.type + 's/' + this.name;
    }
    /**
     * 언어팩을 불러온다.
     *
     * @param {string} text - 언어팩코드
     * @param {Object} placeHolder - 치환자
     * @return {string|Object} message 치환된 메시지
     */
    async getText(text, placeHolder = null) {
        const paths = ['/' + this.type + '/' + this.name + '/language', '/languages'];
        return Language.getText(text, placeHolder, paths);
    }
    /**
     * 에러메시지를 불러온다.
     *
     * @param {string} error - 에러코드
     * @param {Object} placeHolder - 치환자
     * @return {string} message 치환된 메시지
     */
    async getErrorText(error, placeHolder = null) {
        const paths = ['/' + this.type + '/' + this.name + '/language', '/languages'];
        return Language.getErrorText(error, placeHolder, paths);
    }
    /**
     * 언어팩을 출력한다.
     * 언어팩을 비동기방식으로 가져오기때문에 치환자를 먼저 반환하고, 언어팩이 로딩완료되면 언어팩으로 대치한다.
     *
     * @param {string} text - 언어팩코드
     * @param {Object} placeHolder - 치환자
     * @return {string} message - 치환된 메시지
     */
    printText(text, placeHolder = null) {
        const paths = ['/' + this.type + '/' + this.name + '/language', '/languages'];
        return Language.printText(text, placeHolder, paths);
    }
    /**
     * 에러메시지를 출력한다.
     * 언어팩을 비동기방식으로 가져오기때문에 치환자를 먼저 반환하고, 언어팩이 로딩완료되면 언어팩으로 대치한다.
     *
     * @param {string} error - 에러코드
     * @param {Object} placeHolder - 치환자
     * @return {string} message - 치환된 메시지
     */
    printErrorText(error, placeHolder = null) {
        const paths = ['/' + this.type + '/' + this.name + '/language', '/languages'];
        return Language.printErrorText(error, placeHolder, paths);
    }
    /**
     * 프로세스 URL 경로를 가져온다.
     *
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    getProcessUrl(path) {
        return iModules.getProcessUrl(this.type, this.name, path);
    }
    /**
     * API URL 경로를 가져온다.
     *
     * @param {string} path - 실행경로
     * @return {string} processUrl
     */
    getApiUrl(path) {
        return iModules.getApiUrl(this.type, this.name, path);
    }
}
