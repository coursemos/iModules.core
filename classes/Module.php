<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 부모클래스를 정의한다.
 *
 * @file /classes/Module.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 3.
 */
class Module {
	/**
	 * 현재 모듈이 시작된 경로를 초기화한다.
	 */
	protected array $_routes = [];
	
	/**
	 * 각 모듈에서 사용할 데이터베이스 인터페이스 클래스를 가져온다.
	 *
	 * @param string $name 데이터베이스 인터페이스 고유명
	 * @param ?object $connector 데이터베이스정보
	 * @return DatabaseInterface $interface
	 */
	public function db(string $name='default',?object $connector=null):DatabaseInterface {
		return Database::getInterface($name,$connector ?? Config::get('db'));
	}
	
	/**
	 * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
	 *
	 * @param string $table;
	 * @return string $table;
	 */
	public function table(string $table):string {
		// todo: prefix 설정 제대로
		return 'im_module_'.$this->getName().'_'.$table;
	}
	
	/**
	 * 언어팩 에러코드 문자열을 가져온다.
	 *
	 * @param string $code 에러코드
	 * @param ?array $placeHolder 치환자
	 * @return string|array $message 치환된 메시지
	 */
	public function getText(string $text,?array $placeHolder=null):string|array {
		return Language::getInstance()->getText($text,$placeHolder,['/modules/'.$this->getName(),'/']);
	}
	
	/**
	 * 현재 모듈명을 가져온다.
	 *
	 * @return string $module
	 */
	public function getName():string {
		return lcfirst(preg_replace('/^Module/','',get_class($this)));
	}
	
	/**
	 * 현재 모듈의 절대경로를 가져온다.
	 *
	 * @return string $path
	 */
	public function getPath():string {
		return Config::path().'/modules/'.$this->getName();
	}
	
	/**
	 * 현재 모듈이 시작된 경로를 설정한다.
	 *
	 * @param array $routes
	 * @return $this
	 */
	public function setRoutes(array $routes):self {
		$this->_routes = $routes;
		return $this;
	}
	
	/**
	 * 모듈이 시작된 경로를 기준으로 특정위치의 경로를 가져온다.
	 *
	 * @param int $position 경로를 가져올 위치 (NULL 일 경우 전체 경로를 가져온다.)
	 * @return ?string $route
	 */
	public function getRouteAt(int $position):?string {
		/**
		 * 전체 경로에서 모듈이 시작된 경로를 가져온다.
		 */
		$IM = iModules::getInstance();
		$rotues = $IM->getRoute(count($this->_routes));
		return isset($rotues[$position]) == true ? $rotues[$position] : null;
	}
	
	/**
	 * 모듈이 시작된 경로를 포함하여, 모듈 내부 경로에 따른 URL 주소를 가져온다.
	 *
	 * @param string|int ...$routes 모듈 내부 경로 (NULL 인 경우 해당하는 현재 경로로 대체된다.)
	 * @return string $url
	 */
	public function getRouteUrl(string|int ...$routes):string {
		$move = $this->_routes;
		foreach ($routes as $index=>$route) {
			$route ??= $this->getRouteAt($index);
			if ($route == null) break;
			$move[] = $route;
		}
		
		return iModules::getInstance()->getRouteUrl($move);
	}
	
	/**
	 * 템플릿 클래스를 가져온다.
	 *
	 * @param string $name 템플릿명
	 * @param ?object $templet_configs 템플릿 설정
	 * @return Templet $templet
	 */
	public function getTemplet(string $name,?object $templet_configs=null):Templet {
		$templet = new Templet($this);
		return $templet->setTemplet($name,$templet_configs);
	}
}
?>