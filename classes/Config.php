<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 환경설정 클래스를 정의한다.
 *
 * @file /classes/Config.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
class Config {
	/**
	 * 아이모듈 설치 환경설정 정보
	 */
	private static object $_configs;
	
	/**
	 * 아이모듈 package.json 정보
	 */
	private static object $_package;
	
	/**
	 * 아이모듈 언어코드
	 */
	private static array $_languages = [];
	
	/**
	 * 환경설정을 초기화한다.
	 *
	 * @param object $configs 환경설정값
	 */
	public static function init(?object $configs=null):void {
		self::$_configs = $configs;
	}
	
	/**
	 * 환경설정을 가져온다.
	 *
	 * @param string $key 가져올 설정값
	 * @return string|object|null $value
	 */
	public static function get(string $key):string|object|null {
		return self::$_configs?->{$key} ?? null;
	}
	
	/**
	 * 아이모듈 패키지정보를 가져온다.
	 *
	 * @return object $package
	 */
	public static function package():object {
		if (empty(self::$_package) == true) {
			if (is_file(self::path().'/package.json') == true) {
				self::$_package = json_decode(file_get_contents(self::path().'/package.json'));
				if (self::$_package === null) ErrorHandler::view('PACKAGE_FILE_ERROR');
			} else {
				ErrorHandler::view('NOT_FOUND_PACKAGE_FILE');
			}
		}

		return self::$_package;
	}
	
	/**
	 * 아이모듈 절대경로를 가져온다.
	 *
	 * @return string $path
	 */
	public static function path():string {
		$path = self::get('path') ?? str_replace('/classes','',str_replace('\\','/',__DIR__));
		return preg_replace('/\/$/','',$path);
	}
	
	/**
	 * 아이모듈 상대경로를 가져온다.
	 *
	 * @return string $dir
	 */
	public static function dir():string {
		$dir = self::get('dir') ?? str_replace($_SERVER['DOCUMENT_ROOT'],'',self::path());
		return preg_replace('/\/$/','',$dir);
	}

	/**
	 * 사용자 브라우져에서 설정된 모든 언어코드를 가져온다.
	 *
	 * @param bool $is_primary_only 최우선 언어코드 1개만 반환할지 여부
	 * @return array|string $languages
	 */
	public static function languages(bool $is_primary_only=false):array|string {
		if (count(self::$_languages) == 0) {
			$languages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach ($languages as &$language) {
				$language = substr($language,0,2);
			}

			self::$_languages = array_unique($languages);
			
			// 아이모듈의 기본언어는 한국어이므로, 언어코드목록에 한국어가 없는 경우 포함시킨다.
			if (in_array('ko',self::$_languages) == false) {
				self::$_languages[] = 'ko';
			}
		}

		return $is_primary_only == true ? self::$_languages[0] : self::$_languages;
	}
	
	/**
	 * 디버깅모드여부를 가져온다.
	 *
	 * @return bool $is_debug_mode
	 */
	public static function debug():bool {
		return Request::get('debug') === 'true';
	}
}
?>