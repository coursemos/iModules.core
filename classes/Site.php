<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 각 사이트별 데이터 구조체를 정의한다.
 *
 * @file /classes/Site.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 10. 5.
 */
class Site {
	/**
	 * @var object $_site 사이트 RAW 데이터
	 */
	private object $_site;
	
	/**
	 * @var string $_host 호스트명
	 */
	private string $_host;
	
	/**
	 * @var string $_language 언어코드
	 */
	private string $_language;
	
	/**
	 * @var string $_title 사이트명
	 */
	private string $_title;
	
	/**
	 * @var string $_description 사이트설명
	 */
	private string $_description;
	
	/**
	 * @var Templet $_templet 사이트 테마의 템플릿 객체
	 */
	private Templet $_theme;
	
	/**
	 * @var modules\attachment\File[] $_logo 사이트 로고객체
	 */
	private array $_logo = [];
	
	/**
	 * @var modules\attachment\File $_favicon 사이트 Favicon
	 */
	private modules\attachment\File $_favicon;
	
	/**
	 * @var modules\attachment\File $_emblem 사이트 엠블럼
	 */
	private modules\attachment\File $_emblem;
	
	/**
	 * @var modules\attachment\File $_image 사이트 대표이미지
	 */
	private modules\attachment\File $_image;
	
	/**
	 * @var Context $_index 사이트인덱스
	 */
	private Context $_index;
	
	/**
	 * @var Context[] $_sitemap 사이트맵
	 */
	private array $_sitemap;
	
	/**
	 * 사이트 데이터 구조체를 정의한다.
	 *
	 * @param object $site 사이트정보
	 */
	public function __construct(object $site) {
		$this->_site = $site;
		$this->_host = $site->host;
		$this->_language = $site->language;
		$this->_title = $site->title;
		$this->_description = $site->description;
	}
	
	/**
	 * 현재 사이트의 도메인 정보를 가져온다.
	 *
	 * @return Domain $domain
	 */
	public function getDomain():Domain {
		return Domains::get($this->_host);
	}
	
	/**
	 * 사이트 호스트를 가져온다.
	 *
	 * @return string $host
	 */
	public function getHost():string {
		return $this->_host;
	}
	
	/**
	 * 사이트 기본 언어를 가져온다.
	 *
	 * @return string $language
	 */
	public function getLanguage():string {
		return $this->_language;
	}
	
	/**
	 * 사이트 제목을 가져온다.
	 *
	 * @return string $title
	 */
	public function getTitle():string {
		return $this->_title;
	}
	
	/**
	 * 사이트 설명을 가져온다.
	 *
	 * @return string $description
	 */
	public function getDescription():string {
		return $this->_description;
	}
	
	/**
	 * 사이트 테마를 가져온다.
	 *
	 * @return Templet $theme
	 */
	public function getTheme():Templet {
		if (isset($this->_theme) == true) return $this->_theme;
		
		$theme = json_decode($this->_site->theme);
		
		$this->_theme = new Templet();
		$this->_theme->setType('theme')->setTemplet($theme);
		
		return $this->_theme;
	}
	
	/**
	 * 사이트 로고이미지를 가져온다.
	 *
	 * @param string $type 로고타입 (default, dark, footer)
	 * @return modules\attachment\File
	 */
	public function getLogo(string $type):modules\attachment\File {
		if (isset($this->_logo) == true && isset($this->_logo[$type]) == true) {
			return $this->_logo[$type];
		}
		
		/**
		 * @var int[] $logo 로고파일 정보
		 */
		$logo = json_decode($this->_site->logo,true);
		
		/**
		 * @var modules\attachment\Module $attachment
		 */
		$attachment = Modules::get('attachment');
		
		if (isset($logo[$type]) == false || $attachment->hasFile($logo[$type]) == false) {
			$this->_logo[$type] = $attachment->getRawFile('/images/logo.default.png');
		} else {
			$this->_logo[$type] = $attachment->getFile($logo[$type]);
		}
		
		return $this->_logo[$type];
	}
	
	/**
	 * 사이트 대표이미지를 가져온다.
	 *
	 * @return modules\attachment\File
	 */
	public function getFavicon():modules\attachment\File {
		if (isset($this->_favicon) == true) return $this->_favicon;
		
		/**
		 * @var modules\attachment\Module $attachment
		 */
		$attachment = Modules::get('attachment');
		if ($this->_site->favicon == 0 || $attachment->hasFile($this->_site->favicon) == false) {
			$this->_favicon = $attachment->getRawFile('/images/favicon.ico');
		} else {
			$this->_favicon = $attachment->getFile($this->_site->favicon);
		}
		
		return $this->_favicon;
	}
	
	/**
	 * 사이트 대표이미지를 가져온다.
	 *
	 * @return modules\attachment\File
	 */
	public function getEmblem():modules\attachment\File {
		if (isset($this->_emblem) == true) return $this->_emblem;
		
		/**
		 * @var modules\attachment\Module $attachment
		 */
		$attachment = Modules::get('attachment');
		if ($this->_site->emblem == 0 || $attachment->hasFile($this->_site->emblem) == false) {
			$this->_emblem = $attachment->getRawFile('/images/emblem.png');
		} else {
			$this->_emblem = $attachment->getFile($this->_site->emblem);
		}
		
		return $this->_emblem;
	}
	
	/**
	 * 사이트 대표이미지를 가져온다.
	 *
	 * @return modules\attachment\File
	 */
	public function getImage():modules\attachment\File {
		if (isset($this->_image) == true) return $this->_image;
		
		/**
		 * @var modules\attachment\Module $attachment
		 */
		$attachment = Modules::get('attachment');
		if ($this->_site->image == 0 || $attachment->hasFile($this->_site->image) == false) {
			$this->_image = $attachment->getRawFile('/images/default.jpg');
		} else {
			$this->_image = $attachment->getFile($this->_site->image);
		}
		
		return $this->_image;
	}
	
	/**
	 * 사이트 주소를 가져온다.
	 *
	 * @param bool $is_domain 도메인 포함 여부 (기본값 : false)
	 * @param string $url
	 */
	public function getUrl(bool $is_domain=false):string {
		if ($is_domain == true || $this->getHost() != Request::host() || $this->getDomain()->isHttps() != Request::isHttps()) {
			$url = $this->getDomain()->getUrl();
		} else {
			$url = '';
		}
		$url.= Configs::dir();
		
		$route = '';
		if ($this->getDomain()->isInternationalization() == true && $this->getDomain()->getLanguage() != $this->getLanguage()) {
			$route.= '/'.$this->getLanguage();
		}
		
		if ($this->getDomain()->isRewrite() == true) {
			$url.= $route != '' ? $route : '/';
		} else {
			$url.= '/'.($route != '' ? '?route='.$route : '');
		}
		
		return $url;
	}
	
	/**
	 * 사이트인덱스 컨텍스트를 가져온다.
	 *
	 * @return Context $index
	 */
	public function getIndex():Context {
		if (isset($this->_index) == true) return $this->_index;
		
		$contexts = Contexts::all($this);
		foreach ($contexts as $context) {
			if ($context->getPath() == '/') {
				$this->_index = $context;
				return $context;
			}
		}
		
		// @todo 오류추가
	}
	
	/**
	 * 사이트맵을 가져온다.
	 *
	 * @return Context[] $sitemap
	 */
	public function getSitemap():array {
		if (isset($this->_sitemap) == true) return $this->_sitemap;
		
		$this->_sitemap = [];
		$contexts = Contexts::all($this);
		foreach ($contexts as $context) {
			if (preg_match('/^\/[^\/]+$/',$context->getPath()) == true) {
				$this->_sitemap[] = $context;
			}
		}
		
		return $this->_sitemap;
	}
}
?>