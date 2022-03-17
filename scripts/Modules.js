/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈의 자바스크립트 클래스를 관리하는 클래스를 정의한다.
 *
 * @file /scripts/Modules.js
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 3. 11.
 */
class Modules {
	/**
	 * 모듈 클래스를 가져온다.
	 *
	 * @param string name 모듈명
	 * @return ?Module class 모듈클래스
	 */
	static get(name) {
		let className = "Module" + name[0].toUpperCase() + name.slice(1,name.length);
		console.log(className);
		
		let check = eval("typeof " + className);
		if (check !== "function") return null;
		
		return eval("new " + className);
	}
}