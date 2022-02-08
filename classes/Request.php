<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTTP Request 데이터를 관리하는 클래스를 정의한다.
 *
 * @file /classes/Request.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 8.
 */
class Request {
	/**
	 * 현재 호스트를 가져온다.
	 *
	 * @return string $host
	 */
	public static function host():string {
		return isset($_SERVER['HTTP_HOST']) == true ? strtolower($_SERVER['HTTP_HOST']) : 'localhost';
	}
	
	/**
	 * GET 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @param bool $is_required 필수여부 (기본값 : false)
	 * @return string|array|null $value
	 */
	public static function get(string $name,bool $is_required=false):string|array|null {
		$value = isset($_GET[$name]) == true ? $_GET[$name] : null;
		if ($value === null) {
			if ($is_required == true) ErrorHandler::view('REQUIRED',$name);
			return null;
		}
	
		if (is_string($value) == true) {
			$value = trim($value);
		} else {
			foreach ($value as &$var) {
				$var = trim($var);
			}
		}
	
		return $value;
	}
	
	/**
	 * POST 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @param bool $is_required 필수여부 (기본값 : false)
	 * @return string|array|null $value
	 */
	public static function post(string $name,bool $is_required=false):string|array|null {
		$value = isset($_POST[$name]) == true ? $_POST[$name] : null;
		if ($value === null) {
			if ($is_required == true) ErrorHandler::view('REQUIRED',$name);
			return null;
		}
	
		if (is_string($value) == true) {
			$value = trim($value);
		} else {
			foreach ($value as &$var) {
				$var = trim($var);
			}
		}
	
		return $value;
	}
	
	/**
	 * REQUEST 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @param bool $is_required 필수여부 (기본값 : false)
	 * @return string|array|null $value
	 */
	public static function all(string $name,bool $is_required=false):string|array|null {
		$value = isset($_REQUEST[$name]) == true ? $_REQUEST[$name] : null;
		if ($value === null) {
			if ($is_required == true) ErrorHandler::view('REQUIRED',$name);
			return null;
		}
	
		if (is_string($value) == true) {
			$value = trim($value);
		} else {
			foreach ($value as &$var) {
				$var = trim($var);
			}
		}
	
		return $value;
	}
	
	/**
	 * FILES 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @param bool $is_required 필수여부 (기본값 : false)
	 * @return ?array $value
	 */
	public static function file(string $name,bool $is_required=false):?array {
		$value = isset($_FILES[$name]) == true ? $_FILES[$name] : null;
		if ($value === null || isset($_FILES[$name]) == false || strlen($_FILES[$name]['tmp_name']) == 0 || is_file($_FILES[$name]['tmp_name']) == false) {
			if ($is_required == true) ErrorHandler::view('REQUIRED',$name);
			return null;
		}
	
		return $_FILES[$name];
	}
	
	/**
	 * SESSION 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @return ?string $value
	 */
	public static function session(string $name):?string {
		$value = isset($_SESSION[$name]) == true ? $_SESSION[$name] : null;
		if ($value === null) return null;
	
		return $value;
	}
	
	/**
	 * COOKIE 변수데이터를 가져온다.
	 *
	 * @param string $name 변수명
	 * @return ?string $value
	 */
	public static function cookie(string $name):?string {
		$value = isset($_COOKIE[$name]) == true ? $_COOKIE[$name] : null;
		if ($value === null) return null;
	
		return $value;
	}
	
	/**
	 * HTTPS 접속여부를 확인한다.
	 *
	 * @return bool $isHttps
	 */
	public static function isHttps():bool {
		if (isset($_SERVER['HTTPS']) == true && $_SERVER['HTTPS'] == 'on') return true;
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) == true && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') return true;
		if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) == true && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') return true;

		return false;
	}
}
?>