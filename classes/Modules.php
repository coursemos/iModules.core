<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Modules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 15.
 */
class Modules {
	/**
	 * 모듈 설치 정보
	 */
	private static $_modules = [];
	
	/**
	 * 아이모듈 기본 데이터베이스 인터페이스 클래스를 가져온다.
	 *
	 * @return DatabaseInterface $interface
	 */
	private static function db():DatabaseInterface {
		return Database::getInterface('default',Config::get('db'));
	}
	
	/**
	 * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
	 *
	 * @param string $table;
	 * @return string $table;
	 */
	private static function table(string $table):string {
		// todo: prefix 설정 제대로
		return 'im_'.$table;
	}
	
	/**
	 * 모듈 클래스를 불러온다.
	 *
	 * @param string $name 모듈명
	 * @param array $routes 모듈 콘텍스트가 시작된 경로
	 * @return mixed $class 모듈클래스 (모듈이 설치되어 있지 않은 경우 NULL 을 반환한다.)
	 */
	public static function get(string $name,array $routes=[]):mixed {
		if (self::isInstalled($name) === true) {
			require_once Config::path().'/modules/'.$name.'/Module'.ucfirst($name).'.php';
			
			$className = 'Module'.ucfirst($name);
			
			// todo: 어떻게,,, 모듈클래스를 메모리에 담어?
			$class = new $className();
			
			return $class->setRoutes($routes);
		}
		
		return null;
	}
	
	/**
	 * 모듈 설치정보를 가져온다.
	 *
	 * @param string $name 모듈명
	 * @return ?object $installed 모듈설치정보
	 */
	public static function getInstalled(string $name):?object {
		if (isset(self::$_modules[$name]) == true) {
			return self::$_modules[$name];
		}
		
		/**
		 * 모듈 폴더가 존재하는지 확인한다.
		 */
		if (is_dir(Config::path().'/modules/'.$name) == false) {
			self::$_modules[$name] = null;
			return null;
		}
		
		/**
		 * 모듈 클래스 파일이 존재하는지 확인한다.
		 */
		if (is_file(Config::path().'/modules/'.$name.'/Module'.ucfirst($name).'.php') == false) {
			self::$_modules[$name] = null;
			return null;
		}
		
		/**
		 * 모듈이 설치정보를 가져온다.
		 */
		$installed = self::db()->select()->from(self::table('modules'))->where('name',$name)->getOne();
		$installed->configs = json_decode($installed->configs);
		
		self::$_modules[$name] = $installed;
		
		return self::$_modules[$name];
	}
	
	/**
	 * 모듈이 설치되어 있는지 확인한다.
	 *
	 * @param string $name 모듈명
	 * @return bool $is_installed 설치여부
	 */
	public static function isInstalled(string $name):bool {
		return self::getInstalled($name) !== null;
	}
}
?>