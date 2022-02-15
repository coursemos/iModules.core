<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 클래스파일을 자동으로 불러오기위한 오토로더클래스를 정의한다.
 *
 * @file /classes/AutoLoader.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 8.
 */
class AutoLoader {
	/**
	 * AutoLoader 를 정의한다.
	 * \<NamespaceName>(\<SubNamespaceNames>)\<ClassName>
	 * {$basePath}/<NamespaceName>(/<SubNamespaceNames>)/){$sourcePath}/<ClassName>.php
	 *
	 * @param string $basePath 클래스 정의파일을 찾기위한 기본 경로
	 * @param string $sourcePath PSR-4 방식에 따른 클래스 파일 경로 이후 세부위치 (예 : /src)
	 */
	private static AutoLoader $_instance;
	private array $_loader = [];
	public static function register(string $basePath='',string $sourcePath=''):void {
		if (empty(self::$_instance) == true) {
			self::$_instance = new self();
		}
		
		$loader = new stdClass();
		$loader->type = 'psr-4';
		$loader->basePath = $basePath;
		$loader->sourcePath = $sourcePath;
		
		self::$_instance->_loader[] = $loader;
		spl_autoload_register([self::$_instance,'loader']);
	}
	
	/**
	 * spl_autoload_register callback 함수를 정의한다.
	 *
	 * @param string $class 클래스명
	 * @return bool $success
	 */
	public function loader(string $class):bool {
		if (count($this->_loader) == 0) return false;
		
		foreach ($this->_loader as $loader) {
			if ($loader->type == 'psr-4') {
				$namespaces = explode('\\',$class);
				$className = array_pop($namespaces);
				$path = Config::path().$loader->basePath.'/'.implode('/',$namespaces).'/'.$loader->sourcePath.'/'.$className.'.php';
				if (is_file($path) == true) {
					require_once $path;
					return true;
				}
			}
		}
		
		return false;
	}
}
?>