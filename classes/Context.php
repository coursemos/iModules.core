<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 각 컨텍스트 데이터 구조체를 정의한다.
 *
 * @file /classes/Context.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 27.
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
     * @var ?string $_icon 컨텍스트 아이콘
     */
    private ?string $_icon;

    /**
     * @var string $_title 컨텍스트 제목
     */
    private string $_title;

    /**
     * @var ?string $_description 컨텍스트 설명
     */
    private ?string $_description;

    /**
     * @var ?string $_keywords 컨텍스트 키워드
     */
    private ?string $_keywords;

    /**
     * @var \modules\attachment\dtos\Attachment|string|null $_image 컨텍스트 대표이미지
     */
    private \modules\attachment\dtos\Attachment|string|null $_image;

    /**
     * @var string $_type 컨텍스트 종류
     */
    private string $_type;

    /**
     * @var ?string $_target 컨텍스트 대상
     */
    private ?string $_target;

    /**
     * @var string $_context 컨텍스트 코드
     */
    private ?string $_context;

    /**
     * @var ?object $_context_configs 컨텍스트 설정
     */
    private ?object $_context_configs;

    /**
     * @var string $_layout 컨텍스트를 표현할 사이트 테마의 레이아웃명
     */
    private ?string $_layout;

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
     * @var int $_sort 순서
     */
    private int $_sort;

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
        $this->_description = $context->description;
        $this->_keywords = $context->keywords;
        $this->_image = $context->image;
        $this->_type = $context->type;
        $this->_target = $context->target;
        $this->_context = $context->context;
        $this->_context_configs = json_decode($context->context_configs ?? '');
        $this->_layout = $context->layout;
        $this->_header = json_decode($context->header ?? '');
        $this->_footer = json_decode($context->footer ?? '');
        $this->_permission = $context->permission;
        $this->_is_routing = $context->is_routing == 'TRUE';
        $this->_is_sitemap = $context->is_sitemap == 'TRUE';
        $this->_is_footer_menu = $context->is_footer_menu == 'TRUE';
        $this->_sort = $context->sort;
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
     * 컨텍스트 경로를 배열로 가져온다.
     *
     * @return string[] $tree
     */
    public function getPathArray(): array
    {
        return explode('/', preg_replace('/^\//', '', $this->_path));
    }

    /**
     * 컨텍스트 경로배열에서 특정 인덱스의 경로명을 가져온다.
     *
     * @param int $index 인덱스
     * @return string $path
     */
    public function getPathAt(int $index): string
    {
        return $this->getPathArray()[$index] ?? '';
    }

    /**
     * 컨텍스트 경로배열에서 특정 인덱스까지의 경로명을 가져온다.
     *
     * @param int $end 가져올인덱스
     * @return string $path
     */
    public function getPathEnd(int $end): string
    {
        $path = '';
        for ($i = 0; $i <= $end; $i++) {
            $path .= '/' . $this->getPathAt($i);
        }

        return $path;
    }

    /**
     * 컨텍스트 경로배열에서 특정 인덱스까지의 경로를 가진 컨텍스트를 가져온다.
     *
     * @param int $end 가져올인덱스
     * @return Context $context
     */
    public function getContextEnd(int $end): Context
    {
        return Contexts::get(Router::get($this->getPathEnd($end)));
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
     * @return ?\modules\attachment\dtos\Attachment $image
     */
    public function getImage(): ?\modules\attachment\dtos\Attachment
    {
        if (is_string($this->_image) == true) {
            /**
             * @var \modules\attachment\Attachment $mAttachment
             */
            $mAttachment = Modules::get('attachment');
            $this->_image = $mAttachment->getAttachment($this->_image);
        }

        return $this->_image;
    }

    /**
     * 컨텍스트 설명을 가져온다.
     *
     * @param bool $is_html - 줄바꿈 기호를 HTML 태그로 치환할 지 여부
     * @return string $description
     */
    public function getDescription(bool $is_html = true): string
    {
        return preg_replace('/(\r|\n)/', $is_html == true ? '<br>' : "\n", $this->_description ?? '');
    }

    /**
     * 컨텍스트 키워드를 가져온다.
     *
     * @param string $spliter - 줄바꿈기호를 대체할 문자열 (기본값 : ,)
     * @return string $description
     */
    public function getKeywords(string $spliter = ','): string
    {
        return preg_replace('/(\r|\n)/', $spliter, $this->_keywords ?? '');
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
     * @return ?string $target
     */
    public function getTarget(): ?string
    {
        return $this->_target;
    }

    /**
     * 컨텍스트 코드를 가져온다.
     *
     * @return ?string $context
     */
    public function getContext(): ?string
    {
        return $this->_context;
    }

    /**
     * 컨텍스트 코드를 변경한다.
     *
     * @param string $context
     * @return Context $this
     */
    public function setContext(string $context): Context
    {
        $this->_context = $context;
        return $this;
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
    public function getLayout(): ?string
    {
        return $this->_layout;
    }

    /**
     * 컨텍스트를 표현할 사이트 테마의 레이아웃명을 지정한다.
     *
     * @param string $layout
     * @return Context $this
     */
    public function setLayout(string $layout): Context
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * 컨텍스트 순서를 가져온다.
     *
     * @return int $sort
     */
    public function getSort(): int
    {
        return $this->_sort;
    }

    /**
     * 컨텍스트 순서를 변경한다.
     *
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        if ($this->_sort == $sort) {
            return;
        }

        $this->_sort = $sort;
        iModules::db()
            ->update(iModules::table('contexts'), ['sort' => $sort])
            ->where('host', $this->_host)
            ->where('language', $this->_language)
            ->where('path', $this->_path)
            ->execute();
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
            case 'CHILD':
                $children = $this->getChildren(false);
                if (count($children) == 0) {
                    return ErrorHandler::print(ErrorHandler::error('NOT_FOUND_URL'));
                }

                Header::location($children[0]->getUrl());
                break;

            case 'PAGE':
                if (
                    $this->getSite()
                        ->getTheme()
                        ->getPathName() == $this->_target
                ) {
                    $theme = $this->getSite()->getTheme();
                    $theme->assign('site', $this->getSite());
                    $theme->assign('theme', $this->getSite()->getTheme());
                    $theme->assign('context', $this);
                    $theme->assign('route', $route);
                    $content = $theme->getPage($this->_context);
                } else {
                    $content = '';
                }
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
     * 컨텍스트 경로에서 서브경로가 변경된 URL 경로를 가져온다.
     *
     * @param string $subPath 변경할 하위 경로
     * @param string[] $queryString 추가할 쿼리스트링
     * @param bool $is_domain 도메인 포함 여부 (기본값 : false)
     * @return string $subUrl
     */
    public function getSubUrl(string $subPath, array $queryString = [], bool $is_domain = false): string
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

        $route .= $this->getPath() . $subPath;

        if ($this->getDomain()->isRewrite() == true) {
            $url .= $route != '' ? $route : '/';
            $url .= count($queryString) > 0 ? '?' . http_build_query($queryString) : '';
        } else {
            $url .= '/' . ($route != '' ? '?route=' . $route : '');
            $url .= count($queryString) > 0 ? '&' . http_build_query($queryString) : '';
        }

        return $url;
    }

    /**
     * 최상위 부모부터 직전 부모까지 전체 부모를 가져온다.
     *
     * @return Context[] $parents
     */
    public function getParents(): array
    {
        if ($this->_path == '/') {
            return [];
        }

        $paths = explode('/', $this->_path);
        array_pop($paths);

        $parents = [];
        while (count($paths) > 0) {
            $parent = '/' . array_shift($paths);
            $route = Router::get($parent);
            $parents[] = Contexts::get($route);
        }

        return $parents;
    }

    /**
     * 특정 순서의 부모 컨텍스트를 가져온다.
     *
     * @param int $index 가져올 부모컨텍스트 순서
     * @return ?Context $parent 부모 컨텍스트
     */
    public function getParentAt(int $index): ?Context
    {
        $parents = $this->getParents();
        return isset($parents[$index]) == true ? $parents[$index] : null;
    }

    /**
     * 직전 부모 컨텍스트를 가져온다.
     *
     * @return ?Context $parent 부모 컨텍스트
     */
    public function getParent(): ?Context
    {
        $parents = $this->getParents();
        return count($parents) > 0 ? end($parents) : null;
    }

    /**
     * 자식 컨텍스트를 가져온다.
     *
     * @param bool $is_sitemap 사이트맵에 포함된 자식 컨텍스트만 가져올지 여부
     * @param bool $hasPermission 접근권한이 존재하는 자식 컨텍스트만 가져올지 여부
     * @return Context[] $children
     */
    public function getChildren(bool $is_sitemap = true, bool $hasPermission = true): array
    {
        if (isset($this->_children) == true) {
            return $this->_children;
        }

        $this->_children = [];
        $path = $this->getPath() == '/' ? '' : str_replace('/', '\\/', $this->getPath());

        $contexts = Contexts::all($this->getSite());
        foreach ($contexts as $context) {
            if (preg_match('/^' . $path . '\/[^\/]+$/', $context->getPath()) == true) {
                if ($is_sitemap === false || $context->isSitemap() == true) {
                    if ($hasPermission === false || $context->hasPermission() == true) {
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
