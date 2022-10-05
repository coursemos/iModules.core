<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 전체 컨텍스트 데이터를 처리한다.
 *
 * @file /classes/Contexts.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 10. 5.
 */
class Contexts {
	/**
	 * @var Context[][][] $_contexts 전체 컨텍스트 정보
	 */
	private static array $_contexts;
	
	/**
	 * 전체 컨텍스트를 초기화한다.
	 */
	public static function init():void {
		/**
		 * 전체 컨텍스트 정보를 초기화한다.
		 * @todo 캐시적용
		 */
		$contexts = iModules::db()->select()->from(iModules::table('contexts'))->get();
		foreach ($contexts as $context) {
			self::$_contexts[$context->host] ??= [];
			self::$_contexts[$context->host][$context->language] ??= [];
			self::$_contexts[$context->host][$context->language][$context->path] = new Context($context);
		}
		
		/**
		 * 현재 도메인에 해당하는 컨텍스트를 경로에 추가한다.
		 */
		$domain = Domains::get();
		
		/**
		 * @var string $language 언어코드
		 * @var Context[] $contexts 해당 언어코드의 전체 컨텍스트
		 */
		foreach (self::$_contexts[$domain->getHost()] ?? [] as $language=>$contexts) {
			foreach ($contexts as $context) {
				Router::add($context->getPath(),$language,'context',[$context,'getContent']);
				if ($context->isRouting() == true) {
					Router::add($context->getPath().'/*',$language,'context',[$context,'getContent']);
				}
			}
		}
	}
	
	/**
	 * 특정 사이트의 전체 컨텍스트를 가져온다.
	 *
	 * @param Site $site
	 * @return Context[] $contexts
	 */
	public static function all(Site $site):array {
		return self::$_contexts[$site->getHost()][$site->getLanguage()] ?? [];
	}
	
	/**
	 * 경로에 따른 컨텍스트를 가져온다.
	 *
	 * @param ?Route $route 경로 (NULL 인 경우 현재 경로)
	 * @return Context $context
	 */
	public static function get(?Route $route=null):Context {
		$route ??= Router::get();
		if ($route->getType() != 'context') {
			ErrorHandler::print(self::error('NOT_FOUND_CONTEXT',$route->getUrl(true)));
		}
		
		$contexts = self::all($route->getSite());
		if (isset($contexts[$route->getPath()]) == false) {
			ErrorHandler::print(self::error('NOT_FOUND_CONTEXT',$route->getUrl(true)));
		}
		
		return $contexts[$route->getPath()];
	}
	
	/**
	 * Web 컨텍스트를 가져온다.
	 *
	 * @param Route $route 경로 (NULL 인 경우 현재 경로)
	 * @param ?Context $context
	 *
	private static function _web(Route $route):?Context {
		if ($route->getTarget() != 'web') return null;
		if (isset(self::$_contexts) == false) self::init('web');
		
		$site = $route->getSite();
		$routes = $route->getRoutes();
		$contexts = self::$_contexts[$site->getHost()][$site->getLanguage()] ?? [];
		
		$path = '';
		$context = $contexts['/'];
		foreach ($routes as $current) {
			$path.= '/'.$current;
			if (isset($contexts[$path]) == true) {
				$context = $contexts[$path];
				if ($context->isRouting() == true) break;
			} else {
				break;
			}
		}
		
		if ($context->isRouting() == false && $context->getRoute() != $route->getRoute()) {
			$context = null;
		}
		
		return $context;
	}
	*/
	
	/**
	 * 컨텍스트 관련 에러를 처리한다.
	 *
	 * @param string $code 에러코드
	 * @param ?string $message 에러메시지
	 * @param ?object $details 에러와 관련된 추가정보
	 * @return ErrorData $error
	 */
	public static function error(string $code,?string $message=null,?object $details=null):ErrorData {
		switch ($code) {
			case 'NOT_FOUND_CONTEXT' :
				$error = ErrorHandler::data();
				$error->message = ErrorHandler::getText($code);
				$error->suffix = $message;
				$error->stacktrace = ErrorHandler::trace('Contexts');
				return $error;
				
			default :
				return iModules::error($code,$message,$details);
		}
	}
}
?>