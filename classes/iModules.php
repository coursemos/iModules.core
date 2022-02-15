<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 코어 클래스로 모든 사이트 레이아웃 및 모듈, 위젯, 플러그인 클래스는 아이모듈 코어 클래스를 통해 호출된다.
 * 이 클래스는 index.php 파일에 의해 선언되며 아이모듈과 관련된 모든 PHP파일에서 $IM 변수로 접근할 수 있다.
 *
 * @file /classes/iModules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @version 4.0.0
 * @modified 2022. 2. 15.
 */
class iModules {
	/**
	 * 전체 도메인 및 전체 사이트 정보
	 * 한번 초기화한 데이터를 재사용할 수 있도록 static 으로 지정한다.
	 */
	private static ?array $_domains = null;
	private static ?array $_sites = null;
	private static ?array $_contexts = null;
	
	/**
	 * 현재 도메인 및 사이트 정보
	 */
	private ?object $_domain = null;
	private ?object $_site = null;
	
	/**
	 * 전체 경로 경로
	 */
	private array $_routes = [];
	
	/**
	 * 경로에 연결된 컨텍스트 정보
	 * 한번 초기화한 데이터를 재사용할 수 있도록 static 으로 지정한다.
	 */
	private static array $_routings = [];
	
	/**
	 * 현재 경로 대상 (web, api, admin)
	 */
	private ?string $_routeTarget = null;
	
	/**
	 * 경로 시작 지점
	 */
	private int $_routeStart = 0;
	
	/**
	 * 현재 언어코드
	 */
	private ?string $_language = null;
	
	/**
	 * 싱글톤 방식으로 아이모듈 코어클래스를 선언한다.
	 */
	private static iModules $_instance;
	public static function &getInstance():iModules {
		if (empty(self::$_instance) == true) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * 아이모듈 코어클래스를 선언한다.
	 */
	public function __construct() {
		/**
		 * PHP 세션이 필요한 경우, PHP 세션을 시작한다.
		 */
		if (in_array($this->getRouteTarget(),['web','admin','process']) == true) {
			$this->session_start();
		}
	}
	
	/**
	 * 현재 도메인정보를 초기화한다.
	 */
	private function _initDomain():void {
		/**
		 * 전체 도메인정보가 없다면, 전체 도메인정보를 초기화한다.
		 */
		if (self::$_domains === null) $this->_initDomains();
		
		/**
		 * 현재 도메인에 해당하는 존재하는지 확인하고, 존재하지 않는 경우 별칭도메인을 이용하여 도메인을 구한다.
		 */
		if (isset(self::$_domains[Request::host()]) == true) {
			$this->_domain = self::$_domains[Request::host()];
		} else {
			foreach (self::$_domains as $domain) {
				if ($domain->alias && preg_match('/^('.str_replace([',','.','*'],['|','\.','[^\.]+'],strtolower($domain->alias)).')$/',Request::host()) == true) {
					$this->_domain = $domain;
					break;
				}
			}
		}
		
		if ($this->_domain === null) {
			ErrorHandler::view($this->error('NOT_FOUND_SITE',Request::host()));
		}
	}
	
	/**
	 * 전체 도메인정보를 초기화한다.
	 */
	private function _initDomains():void {
		/**
		 * 전체 도메인정보를 가져온다.
		 * todo:  캐시처리
		 */
		if (false) {
			
		} else {
			self::$_domains = [];
			$domains = $this->db()->select()->from($this->table('domains'))->get();
			foreach ($domains as $domain) {
				$domain->is_https = $domain->is_https == 'TRUE';
				$domain->is_rewrite = $domain->is_rewrite == 'TRUE';
				$domain->is_internationalization = $domain->is_internationalization == 'TRUE';
				
				self::$_domains[$domain->domain] = $domain;
			}
		}
	}
	
	/**
	 * 현재 사이트정보를 초기화한다.
	 */
	private function _initSite():void {
		/**
		 * 전체 사이트정보가 없다면, 전체 사이트를 초기화한다.
		 */
		if (self::$_sites === null) $this->_initSites();
		
		$domain = $this->getDomain();
		
		/**
		 * 언어코드가 필요하지 않는 경로 대상인 경우 현재 도메인의 기본 언어코드를 사용한다.
		 */
		$language = $this->getLanguage() == '*' ? $domain->language : $this->getLanguage();
		
		/**
		 * 현재 언어코드에 해당하는 사이트가 존재하면 해당 사이트로 초기화하고,
		 * 그렇지 않은 경우 현재 도메인의 기본 언어코드 사이트로 초기화한다.
		 */
		if (isset(self::$_sites[$domain->domain.'@'.$language]) == true) {
			$this->_site = self::$_sites[$domain->domain.'@'.$language];
		} elseif ($this->_site === null && isset(self::$_sites[$domain->domain.'@'.$domain->language]) == true) {
			$this->_site = self::$_sites[$domain->domain.'@'.$domain->language];
		} else {
			ErrorHandler::view($this->error('NOT_FOUND_SITE',Request::host()));
		}
	}
	
	/**
	 * 현재 도메인과, 현재 경로에 따라 현재 언어코드를 초기화한다.
	 * 
	 */
	private function _initLanguage():void {
		/**
		 * 언어코드가 필요하지 않은 경로 대상에서는 언어코드를 초기화하지 않는다.
		 */
		if (in_array($this->getRouteTarget(),['api','admin']) == true) {
			$this->_language = '*';
		} else {
			$domain = $this->getDomain();
			$routes = $this->getRoutes();
			
			/**
			 * 다국어 사이트인 경우 경로에서 언어코드를 가져오고,
			 * 다국어 사이트가 아닌 경우, 도메인의 기본 언어코드를 사용한다.
			 */
			if ($domain->is_internationalization === true) {
				$this->_language = isset($routes[1]) == true ? $routes[1] : $domain->language;
				$this->_routeStart = 2;
			} else {
				$this->_language = $domain->language;
				$this->_routeStart = 1;
			}
		}
	}
	
	/**
	 * 전체 사이트정보를 초기화한다.
	 */
	private function _initSites():void {
		/**
		 * 전체 사이트 정보를 가져온다.
		 * todo:  캐시처리
		 */
		if (false) {
			
		} else {
			self::$_sites = [];
			$sites = $this->db()->select()->from($this->table('sites'))->get();
			foreach ($sites as $site) {
				$site->theme_configs = json_decode($site->theme_configs);
				self::$_sites[$site->domain.'@'.$site->language] = $site;
			}
		}
	}
	
	/**
	 * 전체 경로 경로를 초기화한다.
	 */
	private function _initRoutes():void {
		/**
		 * URL에 따라 경로 경로를 초기화한다.
		 */
		$route = Request::get('route') !== null && is_string(Request::get('route')) == true ? preg_replace('/^([^\/])/','/\0',Request::get('route')) : '/';
		$this->_routes = explode('/',$route);
		
		if (strlen(trim(end($this->_routes))) == 0) array_pop($this->_routes);
		
		/**
		 * 경로 대상을 초기화한다.
		 */
		if (isset($this->_routes[1]) == true && preg_match('/(admin|api|process)/',$this->_routes[1]) == true) {
			$this->_routeTarget == $this->_routes[1];
		} else {
			$this->_routeTarget = 'web';
		}
	}
	
	/**
	 * 전체 컨텍스트를 정보를 초기화한다.
	 */
	private function _initContexts():void {
		/**
		 * 전체 사이트정보가 없다면, 전체 사이트를 초기화한다.
		 */
		if (self::$_sites === null) $this->_initSites();
		
		/**
		 * 전체 컨텍스트 정보를 가져온다.
		 * todo:  캐시처리
		 */
		if (false) {
			
		} else {
			self::$_contexts = [];
			
			/**
			 * 전체 사이트 목록을 가져와서 각 사이트별로 컨텍스트를 초기화한다.
			 */
			foreach (self::$_sites as $site) {
				/**
				 * 각 사이트의 인덱스 최상위 컨텍스트 객체를 정의한다.
				 * 하위 컨텍스트가 저장되는 children 배열을 선언한다. (1차 메뉴)
				 */
				self::$_contexts[$site->domain.'@'.$site->language] = new stdClass();
				self::$_contexts[$site->domain.'@'.$site->language]->index = null;
				self::$_contexts[$site->domain.'@'.$site->language]->route = [];
				self::$_contexts[$site->domain.'@'.$site->language]->children = [];
				
				$contexts = $this->db()->select()->from($this->table('contexts'))->where('domain',$site->domain)->where('language','ko')->orderBy('sort','asc')->get();
				foreach ($contexts as $context) {
					$context->context_configs = json_decode($context->context_configs);
					$context->header = json_decode($context->header);
					$context->footer = json_decode($context->footer);
					$context->is_routing = $context->is_routing == 'TRUE';
					$context->is_footer = $context->is_footer == 'TRUE';
					$context->is_hide = $context->is_hide == 'TRUE';
					
					/**
					 * 경로 경로가 / 인 경우 해당 사이트의 index 에 페이지를 할당하고,
					 * 그렇지 않은 경우 경로에 따라 부모 컨텍스트의 children 배열에 추가한다.
					 */
					if ($context->route == '/') {
						self::$_contexts[$context->domain.'@'.$context->language]->index = $context;
					} else {
						/**
						 * 부모 컨텍스트 객체
						 */
						$parent = self::$_contexts[$site->domain.'@'.$site->language];
						
						/**
						 * 페이지의 경로 경로를 분리한다.
						 */
						$routes = explode('/',substr($context->route,1));
						foreach ($routes as $index=>$route) {
							/**
							 * n차 컨텍스트 객체가 존재하지 않을 경우, 컨텍스트 객체를 초기화한다. (1차메뉴 객체 구조와 동일)
							 */
							if (isset($parent->children[$route]) === false) {
								$parent->children[$route] = new stdClass();
								$parent->children[$route]->index = null;
								$parent->children[$route]->route = array_slice($routes,0,$index + 1);
								$parent->children[$route]->children = [];
							}
							
							$lastParent = $parent;
							$parent = $parent->children[$route];
						}
						
						/**
						 * 마지막 부모 컨텍스트의 index 에 현재 페이지 정보를 할당한다.
						 */
						$lastParent->children[$route]->index = $context;
					}
				}
			}
		}
	}
	
	/**
	 * 세션을 시작한다.
	 */
	public function session_start():void {
		/**
		 * 별도의 세션폴더가 생성되어있다면, 해당 폴더에 세션을 저장한다.
		 */
		if (Config::get('session_path') !== null && is_dir(Config::get('session_path')) == true && is_writable(Config::get('session_path')) == true) {
			session_save_path(Config::get('session_path'));
		}
		
		session_set_cookie_params(0,'/',(Config::get('session_domain') ?? ''),Request::isHttps() == true,Request::isHttps() == false);
		session_start();
	}
	
	/**
	 * 데이터베이스 인터페이스 클래스를 가져온다.
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
		return 'im_'.$table;
	}
	
	/**
	 * 현재 도메인을 가져온다.
	 *
	 * @param ?string $host 호스트정보 (없는경우 현재 호스트)
	 * @return object $domain
	 */
	public function getDomain(?string $host=null):object {
		if ($host === null) {
			/**
			 * 현재 도메인 정보가 없다면, 도메인을 초기화한다.
			 */
			if ($this->_domain === null) $this->_initDomain();
			return $this->_domain;
		} else {
			/**
			 * 전체 도메인정보가 없다면, 전체 도메인정보를 초기화한다.
			 */
			if (self::$_domains === null) $this->_initDomains();
			
			if (isset(self::$_domains[$host]) == true) {
				return self::$_domains[$host];
			} else {
				ErrorHandler::view($this->error('NOT_FOUND_DOMAIN',$host));
			}
		}
	}
	
	/**
	 * 현재 언어코드를 가져온다.
	 *
	 * @return string $langauge
	 */
	public function getLanguage():string {
		/**
		 * 현재 언어코드가 없다면, 언어코드를 초기화한다.
		 */
		if ($this->_language === null) $this->_initLanguage();
		
		return $this->_language;
	}
	
	/**
	 * 템플릿 클래스를 가져온다.
	 *
	 * @param string $name 템플릿명
	 * @param ?object $templet_configs 템플릿 설정
	 * @return Templet $templet
	 */
	public function getTemplet(string $name,?object $templet_configs=null):Templet {
		$templet = new Templet();
		return $templet->setTemplet($name,$templet_configs);
	}
	
	/**
	 * 사이트의 테마를 가져온다.
	 *
	 * @return Templet $theme
	 */
	public function getTheme():Templet {
		$site = $this->getSite();
		return $this->getTemplet($site->theme,$site->theme_configs);
	}
	
	/**
	 * 현재 사이트정보를 가져온다.
	 * 
	 * @return object $site
	 */
	public function getSite():object {
		/**
		 * 현재 사이트정보가 없다면, 전체 사이트를 초기화한다.
		 */
		if ($this->_site === null) $this->_initSite();
		
		return $this->_site;
	}
	
	/**
	 * 사이트주소를 가져온다.
	 *
	 * @param ?object $site 주소를 가져올 사이트정보 (없을 경우 현재 사이트)
	 * @param bool $is_language_code 언어코드 포함여부 (기본값 : true)
	 * @return string $url
	 */
	public function getSiteUrl(?object $site,bool $is_language_code=true):string {
		$site ??= $this->getSite();
		$domain = $this->getDomain($site->domain);
		
		$url = $domain->is_https == true ? 'https://' : 'http://';
		$url.= $domain->domain;
		
		/**
		 * 모임즈툴즈 상대경로를 추가한다. (웹사이트 루트경로가 아닌 서브경로에 설치된 경우)
		 */
		$url.= Config::dir();
		
		/**
		 * 사이트 언어코드가 도메인의 기본언어코드와 다를 경우 언어코드를 추가한다.
		 * 다국어 사이트를 지원하지 않는 경우 무조건 언어코드가 일치하기 때문에 추가되지 않는다.
		 */
		if ($is_language_code === true && $site->language != $domain->language) $url.= '/'.$site->language;
		
		return $url;
	}
	
	/**
	 * 경로 경로를 가져온다.
	 *
	 * @param int $start 가져올 경로 경로 시작지점 (언어코드는 제외된다.)
	 * @param int $limit 가져올 경로 갯수 (0 인 경우 전체 경로)
	 * @return array $route
	 */
	public function getRoute(int $start=0,int $limit=0):array {
		$routes = $this->getRoutes();
		return array_slice($routes,$this->_routeStart + $start,$limit === 0 ? null : $limit);
	}
	
	/**
	 * 전체 경로 경로를 가져온다.
	 *
	 * @return array $routes
	 */
	public function getRoutes():array {
		/**
		 * 전체 경로 경로가 없다면, 경로 경로를 초기화한다.
		 */
		if (count($this->_routes) === 0) $this->_initRoutes();
		
		return $this->_routes;
	}
	
	/**
	 * 현재 경로 대상을 가져온다.
	 *
	 * @return string $routeTarget
	 */
	public function getRouteTarget():string {
		/**
		 * 현재 경로 대상이 없다면, 경로 경로를 초기화한다.
		 */
		if ($this->_routeTarget === null) $this->_initRoutes();
		
		return $this->_routeTarget;
	}
	
	/**
	 * 경로의 URL 경로를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로 경로 기준 사이트 (없을 경우 현재 사이트)
	 * @param bool $is_site_url 사이트주소 포함여부
	 * @return string $url
	 */
	public function getRouteUrl(?array $routes=null,?object $site=null,bool $is_site_url=false):string {
		/**
		 * 경로 URL 경로를 가져오기 위한 기본 경로를 계산한다.
		 * 기본 경로에는 설정에 따라 사이트 전체주소 또는 모임즈툴즈 상대경로가 포함된다.
		 */
		$url = $is_site_url === true || $site !== null ? $this->getSiteUrl($site,false) : Config::dir();
		
		$site ??= $this->getSite();
		$domain = $this->getDomain($site !== null ? $site->domain : null);
		
		$routes ??=  $this->getRoute();
		$routeUrl = '';
		
		/**
		 * 사이트 언어코드가 도메인의 기본언어코드와 다를 경우 언어코드를 추가한다.
		 * 다국어 사이트를 지원하지 않는 경우 무조건 언어코드가 일치하기 때문에 추가되지 않는다.
		 */
		if ($site->language != $domain->language) {
			$routeUrl.= '/'.$site->language;
		}
		
		if (count($routes) > 0) {
			/**
			 * 경로경로가 있고, 다국어 사이트인 경우 무조건 언어코드를 추가한다.
			 * 단, 이미 사이트 언어코드와 도메인의 기본언어코드가 달라 언어코드가 추가된 경우에는 추가하지 않는다.
			 */
			if (strlen($routeUrl) == 0 && $domain->is_internationalization === true) {
				$routeUrl.= '/'.$site->language;
			}
			
			$routeUrl.= '/'.implode('/',$routes);
		}
		
		return $url.($domain->is_rewrite === true ? $routeUrl : (strlen($routeUrl) > 0 ? '/?route='.$routeUrl : ''));
	}
	
	/**
	 * 경로에 따른 컨텍스트를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로를 검색할 사이트객체
	 * @return ?object $context 컨텍스트 페이지
	 */
	public function getContext(?array $routes=null,?object $site=null):?object {
		/**
		 * 전체 컨텍스트 정보가 없다면, 전체 컨텍스트를 초기화한다.
		 */
		if (self::$_contexts === null) $this->_initContexts();
		
		$site ??= $this->getSite();
		$routes ??= $this->getRoute();
		if (isset(self::$_routings[$site->domain.'@'.$site->language.'/'.implode('/',$routes)]) == true) {
			return self::$_routings[$site->domain.'@'.$site->language.'/'.implode('/',$routes)];
		}
		
		$context = self::$_contexts[$site->domain.'@'.$site->language];
		if ($context === null) return null;
		
		foreach ($routes as $route) {
			if (isset($context->children[$route]) == true) {
				/**
				 * 현재 단계의 컨텍스트가 자체적인 세부 경로를 가질 경우 현재 단계를 반환한다.
				 */
				$context = $context->children[$route];
				if ($context->index?->is_routing === true) break;
			} else {
				$context = null;
				break;
			}
		}
		
		self::$_routings[$site->domain.'@'.$site->language.'/'.implode('/',$routes)] = $context;
		
		return $context;
	}
	
	/**
	 * 경로에 따른 컨텍스트 인덱스를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로를 검색할 사이트객체
	 * @return ?object $context 컨텍스트 페이지
	 */
	public function getContextIndex(?array $routes=null,?object $site=null):?object {
		$context = $this->getContext($routes,$site);
		return $context?->index;
	}
	
	/**
	 * 현재 컨텍스트가 시작된 경로를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로를 검색할 사이트객체
	 * @return array $route 최종 경로 경로
	 */
	public function getContextRoutes(?array $routes=null,?object $site=null):array {
		$context = $this->getContext($routes,$site);
		return $context?->route ?? [];
	}
	
	/**
	 * 경로에 따른 컨텍스트의 자식 컨텍스트를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로를 검색할 사이트객체
	 * @return ?object $context 컨텍스트 페이지
	 */
	public function getContextChildren(?array $routes=null,?object $site=null):array {
		$context = $this->getContext($routes,$site);
		return $context?->children ?? [];
	}
	
	/**
	 * 특정 경로에 해당하는 컨텍스트 문서(HTML)를 가져온다.
	 *
	 * @param ?array $routes 경로 경로
	 * @param ?object $site 경로를 검색할 사이트객체
	 * @return string $document 문서내용(HTML)
	 */
	public function getDocument(?array $routes=null,?object $site=null):string {
		$context = $this->getContextIndex($routes,$site);
		if ($context === null) {
			return ErrorHandler::get($this->error('NOT_FOUND_PAGE'));
		}
		$routes = $this->getContextRoutes($routes,$site);
		
		/**
		 * 컨텍스트 타입에 따라 컨텍스트 문서를 가져온다.
		 */
		$document = '';
		switch ($context->type) {
			case 'FILE' :
				$document = $this->getDocumentFromFile($context->target,$context->context,$routes);
				break;
			
			case 'MODULE' :
				$document = $this->getDocumentFromModule($context->target,$context->context,$context->context_configs,$routes);
				break;
		}
		
		return $document;
	}
	
	/**
	 * 현재 문서의 제목을 가져온다.
	 * 
	 * @return string $title 문서제목
	 */
	public function getDocumentTitle():string {
		$site = $this->getSite();
		$context = $this->getContext();
		
		$title = $context?->index?->title;
		return $title === null ? $site->title : $title.= '-'.$site->title;
		
		return $title;
	}
	
	/**
	 * 현재 문서의 설명을 가져온다.
	 * 
	 * @return string $description 문서설명
	 */
	public function getDocumentDescription():string {
		$site = $this->getSite();
		$context = $this->getContext();
		
		return $context?->index?->description ?? $site->description;
	}
	
	/**
	 * HTML 문서 헤더를 가져온다.
	 *
	 * @return string $header
	 */
	public function getDocumentHeader():string {
		/**
		 * 사이트 설명 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
		 */
		Html::title($this->getDocumentTitle());
		Html::description($this->getDocumentDescription());
		
		/*
		$this->head('link',array('rel'=>'canonical','href'=>$this->getCanonical()));
		$this->head('meta',array('name'=>'robots','content'=>$this->getRobots()));
		*/
		
		/**
		 * OG 태그를 설정한다.
		 *
		$this->head('meta',array('property'=>'og:url','content'=>$this->getCanonical()));
		$this->head('meta',array('property'=>'og:type','content'=>'website'));
		$this->head('meta',array('property'=>'og:title','content'=>$this->getViewTitle()));
		$this->head('meta',array('property'=>'og:description','content'=>preg_replace('/(\r|\n)/',' ',$this->getViewDescription())));
		$viewImage = $this->getViewImage(true,true);
		if (is_object($viewImage) == true) {
			$this->head('meta',array('property'=>'og:image','content'=>$this->getViewImage(true)));
			$this->head('meta',array('property'=>'og:image:width','content'=>$viewImage->width));
			$this->head('meta',array('property'=>'og:image:height','content'=>$viewImage->height));
		} elseif ($viewImage != null) {
			$this->head('meta',array('property'=>'og:image','content'=>$viewImage));
		}
		$this->head('meta',array('property'=>'twitter:card','content'=>'summary_large_image'));
		*/
		
		/**
		 * 모바일기기 및 애플 디바이스를 위한 TOUCH-ICON 태그를 정의한다.
		 *
		if ($this->getSiteEmblem() !== null) {
			$this->head('link',array('rel'=>'apple-touch-icon','sizes'=>'57x57','href'=>$this->getSiteEmblem(true)));
			$this->head('link',array('rel'=>'apple-touch-icon','sizes'=>'114x114','href'=>$this->getSiteEmblem(true)));
			$this->head('link',array('rel'=>'apple-touch-icon','sizes'=>'72x72','href'=>$this->getSiteEmblem(true)));
			$this->head('link',array('rel'=>'apple-touch-icon','sizes'=>'144x144','href'=>$this->getSiteEmblem(true)));
		}
		*/
		
		/**
		 * 사이트 Favicon 태그를 정의한다.
		 *
		if ($this->getSiteFavicon() !== null) {
			$this->head('link',array('rel'=>'shortcut icon','type'=>'image/x-icon','href'=>$this->getSiteFavicon(true)));
		}
		*/
		
		/**
		 * 템플릿을 불러온다.
		 */
		return $this->getTheme()->getHeader();
	}
	
	/**
	 * HTML 문서 푸터를 가져온다.
	 *
	 * @return string $footer
	 */
	public function getDocumentFooter():string {
		/**
		 * 템플릿을 불러온다.
		 */
		return $this->getTheme()->getFooter();
	}
	
	/**
	 * 외부파일에서 컨텍스트 문서(HTML)을 가져온다.
	 *
	 * @param string $name 템플릿명
	 * @param string $filename 외부파일명
	 * @return int $document 문서내용(HTML)
	 */
	public function getDocumentFromFile(string $name,string $filename):string {
		$temp = explode('/',$name);
		$type = $temp[1];
		
		/**
		 * 템플릿 경로에 따라 외부파일을 처리할 템플릿 클래스를 설정한다.
		 */
		switch ($type) {
			/**
			 * 사이트테마인 경우
			 */
			case 'themes' :
				/**
				 * 현재 사이트테마와 동일한 테마인 경우 사이트 테마 클래스를 통해 외부파일을 호출한다.
				 */
				if ($this->getTheme()->getName() === $name) {
					return $this->getTheme()->getFile($filename);
				} else {
					$details = new stdClass();
					$details->theme = $name;
					$details->filename = $filename;
					return ErrorHandler::get($this->error('NOT_MATCHED_FILE_THEME',null,$details));
				}
				break;
		}
		
		return ErrorHandler::get($this->error('NOT_FOUND_DOCUMENT_FILE',$name.'/'.$filename));
	}
	
	/**
	 * 외부파일에서 컨텍스트 문서(HTML)을 가져온다.
	 *
	 * @param string $module 모듈명
	 * @param string $context 컨텍스트명
	 * @return int $document 문서내용(HTML)
	 */
	public function getDocumentFromModule(string $module,string $context,?object $configs=null,?array $routes=null):string {
		/**
		 * 모듈 컨텍스트가 시작된 경로를 지정한다.
		 */
		$routes ??= $this->getContextRoutes();
		
		/**
		 * 모듈 클래스를 불러온다.
		 */
		$mModule = Modules::get($module,$routes);
		if (method_exists($mModule,'getContext') == false) {
			ErrorHandler::view('CONTEXT_METHOD_NOT_EXISTS');
		}
		
		return $mModule->getContext($context,$configs);
	}
	
	/**
	 * URL 경로에 따라 프로세스를 처리한다.
	 */
	public function route():void {
		switch ($this->getRouteTarget()) {
			case 'web' :
				$this->routeWeb();
				break;
				
			case 'api' :
				$this->routeApi();
				break;
				
			case 'admin' :
				$this->routeAdmin();
				break;
				
			default :
				ErrorHandler::view($this->error('NOT_FOUND_ROUTE_TARGET'));
		}
	}

	/**
	 * 웹페이지 경로를 처리한다.
	 */
	public function routeWeb():void {
		/**
		 * 현재 사이트 정보를 가져온다.
		 */
		$site = $this->getSite();
		
		/**
		 * 현재 사이트정보와 접속한 주소가 다른 경우 정상적인 사이트주소로 이동한다.
		 */
		if (Request::host() != $site->domain || $this->getLanguage() != $site->language) {
			header('location: '.$this->getRouteUrl(null,$site,true));
			exit;
		}
		
		// todo: 이벤트발생
		
		/**
		 * 현재 경로에 해당하는 컨텍스트 HTML 을 가져온다.
		 * 컨텍스트가 NULL 인 경우, 404 에러를 출력한다.
		 */
		$context = $this->getDocument();
		
		$footer = $this->getDocumentFooter();
		$header = $this->getDocumentHeader();
		
		echo Html::tag(
			$header,
			$context,
			$footer
		);
	}
	
	/**
	 * API 경로를 처리한다.
	 */
	public function routeApi():void {
		
	}
	
	/**
	 * 관리자 경로를 처리한다.
	 */
	public function routeAdmin():void {
		
	}
	
	/**
	 * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
	 *
	 * @param string $code 에러코드
	 * @param ?string $message 에러메시지
	 * @param ?object $details 에러와 관련된 추가정보
	 * @return object $error
	 */
	public function error(string $code,?string $message=null,?object $details=null):object {
		$error = ErrorHandler::data();
		
		switch ($code) {
			case 'NOT_FOUND_SITE' :
				ErrorHandler::code(404);
				
				$error->prefix = ErrorHandler::getText('SITE_ERROR');
				$error->message = ErrorHandler::getText('NOT_FOUND_SITE');
				$error->suffix = $message;
				$error->stacktrace = ErrorHandler::trace();
				
				break;
				
			case 'NOT_MATCHED_FILE_THEME' :
				$error->prefix = ErrorHandler::getText('CONTEXT_ERROR');
				$error->message = ErrorHandler::getText('NOT_MATCHED_FILE_THEME',['theme'=>$details->theme]);
				$error->suffix = $details->filename;
				$error->stacktrace = ErrorHandler::trace();
				
				break;
			
			case 'NOT_FOUND_TEMPLET_FILE' :
				$error->prefix = ErrorHandler::getText('TEMPLET_ERROR');
				$error->message = ErrorHandler::getText('NOT_FOUND_TEMPLET_FILE');
				$error->suffix = $message;
				$error->stacktrace = ErrorHandler::trace('Templet');
				
				break;
				
			default :
				$error->message = ErrorHandler::getText($code);
		}
		
		return $error;
	}
}
?>