<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 각 컨텍스트 데이터 구조체를 정의한다.
 *
 * @file /classes/Context.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Context
{
    /**
     * @var string $_host 호스트명
     */
    private string $_host;

    /**
     * @var string $_host 언어코드
     */
    private string $_language;

    /**
     * @var string $_host 경로
     */
    private string $_path;

    /**
     * @var string $_icon 컨텍스트 아이콘
     */
    private string $_icon;

    /**
     * @var string $_icon 컨텍스트 제목
     */
    private string $_title;

    /**
     * @var string $_icon 컨텍스트 대표이미지
     */
    private string $_image;

    /**
     * @var string $_icon 컨텍스트 설명
     */
    private string $_description;

    /**
     * @var string $_type 컨텍스트 종류
     */
    private string $_type;

    /**
     * @var string $_target 컨텍스트 대상
     */
    private string $_target;

    /**
     * @var string $_context 컨텍스트 코드
     */
    private string $_context;

    /**
     * @var ?object $_context_configs 컨텍스트 설정
     */
    private ?object $_context_configs;

    /**
     * @var string $_layout 컨텍스트를 표현할 사이트 테마의 레이아웃명
     */
    private string $_layout;

    /**
     * @var ?object $_header 컨텍스트 헤더설정
     */
    private ?object $_header;

    /**
     * @var ?object $_footer 컨텍스트 푸터설정
     */
    private ?object $_footer;

    /**
     * @var string $_permission 컨텍스트 접근권한 설정
     */
    private string $_permission;

    /**
     * @var bool $_is_routing 컨텍스트가 하위경로를 가지는지 여부
     */
    private bool $_is_routing;

    /**
     * @var bool $_is_sitemap 컨텍스트가 사이트맵에서 포함되는지 여부
     */
    private bool $_is_sitemap;

    /**
     * @var bool $_is_footer_menu 컨텍스트가 하단메뉴에 포함되는지 여부
     */
    private bool $_is_footer_menu;

    /**
     * @var Context[] $_children 자식 컨텍스트
     */
    private array $_children;

    /**
     * 컨텍스트 데이터 구조체를 정의한다.
     *
     * @param object $context 컨텍스트정보
     */
    public function __construct(object $context)
    {
        $this->_host = $context->host;
        $this->_language = $context->language;
        $this->_path = $context->path;
        $this->_icon = $context->icon;
        $this->_title = $context->title;
        $this->_image = $context->image;
        $this->_description = $context->description;
        $this->_type = $context->type;
        $this->_target = $context->target;
        $this->_context = $context->context;
        $this->_context_configs = json_decode($context->context_configs);
        $this->_layout = $context->layout;
        $this->_header = json_decode($context->header);
        $this->_footer = json_decode($context->footer);
        $this->_permission = $context->permission;
        $this->_is_routing = $context->is_routing == 'TRUE';
        $this->_is_sitemap = $context->is_sitemap == 'TRUE';
        $this->_is_footer_menu = $context->is_footer_menu == 'TRUE';
    }

    /**
     * 컨텍스트 호스트명을 가져온다.
     *
     * @return string $host
     */
    public function getHost(): string
    {
        return $this->_host;
    }

    /**
     * 컨텍스트 언어코드를 가져온다.
     *
     * @return string $language
     */
    public function getLanguage(): string
    {
        return $this->_language;
    }

    /**
     * 컨텍스트 경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return $this->_path;
    }

    /**
     * 컨텍스트 경로트리를 배열로 가져온다.
     *
     * @return string[] $tree
     */
    public function getTree(): array
    {
        return explode('/', preg_replace('/^\//', '', $this->_path));
    }

    /**
     * 컨텍스트 경로트리에서 특정 뎁스의 경로를 가져온다.
     *
     * @param int $depth 경로뎁스
     * @return string $path
     */
    public function getTreeAt(int $depth): string
    {
        return $this->getTree()[$depth] ?? '';
    }

    /**
     * 컨텍스트 아이콘을 가져온다.
     *
     * @return string $icon
     */
    public function getIcon(): string
    {
        return $this->_icon;
    }

    /**
     * 컨텍스트 제목을 가져온다.
     *
     * @return string $title
     */
    public function getTitle(): string
    {
        return $this->_title;
    }

    /**
     * 컨텍스트 대표이미지를 가져온다.
     *
     * @return string $image
     */
    public function getImage(): string
    {
        return $this->_image;
    }

    /**
     * 컨텍스트 설명을 가져온다.
     *
     * @return string $description
     */
    public function getDescription(): string
    {
        return $this->_description;
    }

    /**
     * 컨텍스트 종류을 가져온다.
     *
     * @return string $type
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * 컨텍스트 대상을 가져온다.
     *
     * @return string $target
     */
    public function getTarget(): string
    {
        return $this->_target;
    }

    /**
     * 컨텍스트 코드를 가져온다.
     *
     * @return string $context
     */
    public function getContext(): string
    {
        return $this->_context;
    }

    /**
     * 컨텍스트 코드를 가져온다.
     *
     * @return ?object $context
     */
    public function getContextConfigs(): ?object
    {
        return $this->_context_configs;
    }

    /**
     * 컨텍스트를 표현할 사이트 테마의 레이아웃명을 가져온다.
     *
     * @return string $layout
     */
    public function getLayout(): string
    {
        return $this->_layout;
    }

    /**
	 * 컨텍스트 기본 경로를 배열로 가져온다.
	 *
	 * @return array $routes
	 *
	public function getRoutes():array {
		$routes = preg_replace('/^\/?(.*?)(\/)?$/','\1',$this->_route);
		$routes = strlen($routes) > 0 ? explode('/',$routes) : [];
		
		return $routes;
	}
	*/

    /**
     * 컨텍스트가 특정 컨텍스트와 일치하는지 확인한다.
     *
     * @param string $type 일치할 컨텍스트 종류 (NULL 인 경우 비교하지 않음)
     * @param string $target 일치할 컨텍스트 대상 (NULL 인 경우 비교하지 않음)
     * @param string $context 일치할 컨텍스트 명 (NULL 인 경우 비교하지 않음)
     * @return bool $matched 일치 여부
     */
    public function is(?string $type = null, ?string $target = null, ?string $context = null): bool
    {
        if ($type !== null && $this->_type != $type) {
            return false;
        }

        if ($target !== null && $this->_target != $target) {
            return false;
        }

        if ($context !== null && $this->_context != $context) {
            return false;
        }

        return true;
    }

    /**
     * 하위 경로 사용여부를 가져온다.
     *
     * @return bool $is_routing
     */
    public function isRouting(): bool
    {
        return $this->_is_routing;
    }

    /**
     * 컨텍스트가 속한 도메인을 가져온다.
     *
     * @return Domain $domain
     */
    public function getDomain(): Domain
    {
        return Domains::get($this->_host);
    }

    /**
     * 컨텍스트가 속한 사이트를 가져온다.
     *
     * @return Site $site
     */
    public function getSite(): Site
    {
        return Sites::get($this->_host, $this->_language);
    }

    /**
     * 컨텍스트의 콘텐츠를 가져온다.
     *
     * @param Route $route
     * @return string $content
     */
    public function getContent(Route $route): string
    {
        $content = '';
        switch ($this->_type) {
            case 'PAGE':
                $content = $this->getSite()
                    ->getTheme()
                    ->getPage($this->_context);
                break;

            case 'MODULE':
                $content = Modules::get($this->_target, $route)->getContent($this->_context, $this->_context_configs);
                break;
        }

        return $content;
    }

    /**
     * 사이트맵에 포함되는지 여부를 가져온다.
     *
     * @return bool $is_sitemap
     */
    public function isSitemap(): bool
    {
        return $this->_is_sitemap;
    }

    /**
     * 사이트 하단메뉴에 포함되는지 여부를 가져온다.
     *
     * @return bool $is_footer_menu
     */
    public function isFooterMenu(): bool
    {
        return $this->_is_footer_menu;
    }

    /**
     * 컨텍스트 주소를 가져온다.
     *
     * @param bool $is_domain 도메인 포함 여부 (기본값 : false)
     * @param string $url
     */
    public function getUrl(bool $is_domain = false): string
    {
        if ($this->getPath() == '/') {
            return $this->getSite()->getUrl();
        }

        if (
            $is_domain == true ||
            $this->getHost() != Request::host() ||
            $this->getDomain()->isHttps() != Request::isHttps()
        ) {
            $url = $this->getDomain()->getUrl();
        } else {
            $url = '';
        }
        $url .= Configs::dir();

        $route = '';
        if ($this->getDomain()->isInternationalization() == true) {
            $route .= '/' . $this->getLanguage();
        }

        $route .= $this->getPath();

        if ($this->getDomain()->isRewrite() == true) {
            $url .= $route != '' ? $route : '/';
        } else {
            $url .= '/' . ($route != '' ? '?route=' . $route : '');
        }

        return $url;
    }

    /**
     * 자식 컨텍스트를 가져온다.
     *
     * @param bool $is_sitemap 사이트맵에 포함된 자식 컨텍스트만 가져올지 여부
     * @return Context[] $children
     */
    public function getChildren(bool $is_sitemap = true): array
    {
        if (isset($this->_children) == true) {
            return $this->_children;
        }

        $this->_children = [];
        $path = str_replace('/', '\\/', $this->getPath());
        $contexts = Contexts::all($this->getSite());
        foreach ($contexts as $context) {
            if (preg_match('/^' . $path . '\/[^\/]+$/', $context->getPath()) == true) {
                if ($is_sitemap === false || $context->isSitemap() == true) {
                    if ($context->hasPermission() == true) {
                        $this->_children[] = $context;
                    }
                }
            }
        }

        return $this->_children;
    }

    /**
     * 자식 컨텍스트가 존재하는지 확인한다.
     *
     * @param bool $is_sitemap 사이트맵에 포함된 자식 컨텍스트만 가져올지 여부
     * @return bool $hasChild
     */
    public function hasChild(bool $is_sitemap = true): bool
    {
        return count($this->getChildren($is_sitemap)) > 0;
    }

    /**
     * 컨텍스트 접근권한이 있는지 확인한다.
     *
     * @return bool $has_permission
     */
    public function hasPermission(): bool
    {
        return iModules::parsePermissionString($this->_permission);
    }
}
