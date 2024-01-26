<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 각 사이트별 데이터 구조체를 정의한다.
 *
 * @file /classes/Site.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 26.
 */
class Site
{
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
    private ?string $_description;

    /**
     * @var Theme $_theme 사이트 테마 객체
     */
    private Theme $_theme;

    /**
     * @var string $_color 사이트 테마 색상
     */
    private string $_color;

    /**
     * @var ?\modules\attachment\dtos\Attachment $_logo 사이트 로고객체
     */
    private ?\modules\attachment\dtos\Attachment $_logo;

    /**
     * @var ?\modules\attachment\dtos\Attachment $_favicon 사이트 Favicon
     */
    private ?\modules\attachment\dtos\Attachment $_favicon;

    /**
     * @var ?\modules\attachment\dtos\Attachment $_emblem 사이트 엠블럼
     */
    private ?\modules\attachment\dtos\Attachment $_emblem;

    /**
     * @var ?\modules\attachment\dtos\Attachment $_image 사이트 대표이미지
     */
    private ?\modules\attachment\dtos\Attachment $_image;

    /**
     * @var object $_header 사이트 헤더설정
     */
    private ?object $_header;

    /**
     * @var ?object $_footer 사이트 푸터설정
     */
    private ?object $_footer;

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
    public function __construct(object $site)
    {
        $this->_site = $site;
        $this->_host = $site->host;
        $this->_language = $site->language;
        $this->_title = $site->title;
        $this->_description = $site->description;
        $this->_color = $site->color;
        $this->_header = json_decode($site->header ?? '');
        $this->_footer = json_decode($site->footer ?? '');
    }

    /**
     * 현재 사이트의 도메인 정보를 가져온다.
     *
     * @return Domain $domain
     */
    public function getDomain(): Domain
    {
        return Domains::get($this->_host);
    }

    /**
     * 사이트 호스트를 가져온다.
     *
     * @return string $host
     */
    public function getHost(): string
    {
        return $this->_host;
    }

    /**
     * 사이트 기본 언어를 가져온다.
     *
     * @return string $language
     */
    public function getLanguage(): string
    {
        return $this->_language;
    }

    /**
     * 사이트 제목을 가져온다.
     *
     * @return string $title
     */
    public function getTitle(): string
    {
        return $this->_title;
    }

    /**
     * 사이트 설명을 가져온다.
     *
     * @return string $description
     */
    public function getDescription(): string
    {
        return $this->_description ?? '';
    }

    /**
     * 사이트 테마를 가져온다.
     *
     * @return Theme $theme
     */
    public function getTheme(): Theme
    {
        if (isset($this->_theme) == true) {
            return $this->_theme;
        }

        $this->_theme = new Theme(json_decode($this->_site->theme));
        return $this->_theme;
    }

    /**
     * 사이트 테마색상을 가져온다.
     *
     * @return string $color
     */
    public function getColor(): string
    {
        return $this->_color;
    }

    /**
     * 사이트 로고이미지를 가져온다.
     *
     * @return ?\modules\attachment\dtos\Attachment $logo
     */
    public function getLogo(): ?\modules\attachment\dtos\Attachment
    {
        if (isset($this->_logo) == true) {
            return $this->_logo;
        }

        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = Modules::get('attachment');
        $this->_logo = $this->_site->logo != null ? $mAttachment->getAttachment($this->_site->logo) : null;

        return $this->_logo;
    }

    /**
     * 사이트 패비콘을 가져온다.
     *
     * @return ?\modules\attachment\dtos\Attachment $favicon
     */
    public function getFavicon(): ?\modules\attachment\dtos\Attachment
    {
        if (isset($this->_favicon) == true) {
            return $this->_favicon;
        }

        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = Modules::get('attachment');
        $this->_favicon = $this->_site->favicon != null ? $mAttachment->getAttachment($this->_site->favicon) : null;

        return $this->_favicon;
    }

    /**
     * 사이트 엠블럼을 가져온다.
     *
     * @return ?\modules\attachment\dtos\Attachment $emblem
     */
    public function getEmblem(): ?\modules\attachment\dtos\Attachment
    {
        if (isset($this->_emblem) == true) {
            return $this->_emblem;
        }

        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = Modules::get('attachment');
        $this->_emblem = $this->_site->emblem != null ? $mAttachment->getAttachment($this->_site->emblem) : null;

        return $this->_emblem;
    }

    /**
     * 사이트 대표이미지를 가져온다.
     *
     * @return ?\modules\attachment\dtos\Attachment $image
     */
    public function getImage(): ?\modules\attachment\dtos\Attachment
    {
        if (isset($this->_image) == true) {
            return $this->_image;
        }

        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = Modules::get('attachment');
        $this->_image = $this->_site->image != null ? $mAttachment->getAttachment($this->_site->image) : null;

        return $this->_image;
    }

    /**
     * 사이트 주소를 가져온다.
     *
     * @param bool $is_domain 도메인 포함 여부 (기본값 : false)
     * @param string $url
     */
    public function getUrl(bool $is_domain = false): string
    {
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
        if (
            $this->getDomain()->isInternationalization() == true &&
            $this->getDomain()->getLanguage() != $this->getLanguage()
        ) {
            $route .= '/' . $this->getLanguage();
        }

        if ($this->getDomain()->isRewrite() == true) {
            $url .= $route != '' ? $route : '/';
        } else {
            $url .= '/' . ($route != '' ? '?route=' . $route : '');
        }

        return $url;
    }

    /**
     * 사이트인덱스 컨텍스트를 가져온다.
     *
     * @return Context $index
     */
    public function getIndex(): Context
    {
        if (isset($this->_index) == true) {
            return $this->_index;
        }

        $contexts = Contexts::all($this);
        if (isset($contexts['/']) == false) {
            iModules::db()
                ->insert(
                    iModules::table('contexts'),
                    [
                        'host' => $this->_host,
                        'language' => $this->_language,
                        'path' => '/',
                        'title' => 'INDEX',
                        'type' => 'EMPTY',
                        'target' => '',
                        'context' => '',
                        'context_configs' => '{}',
                        'layout' => 'index',
                        'header' => null,
                        'footer' => null,
                        'permission' => 'true',
                        'is_sitemap' => 'FALSE',
                        'is_footer_menu' => 'FALSE',
                        'sort' => 0,
                    ],
                    ['host', 'language']
                )
                ->execute();

            Cache::remove('contexts');
            Contexts::init();
            $contexts = Contexts::all($this);
        }

        return $contexts['/'];
    }

    /**
     * 사이트맵을 가져온다.
     *
     * @return Context[] $sitemap
     */
    public function getSitemap(): array
    {
        if (isset($this->_sitemap) == true) {
            return $this->_sitemap;
        }

        $this->_sitemap = [];
        $contexts = Contexts::all($this);
        foreach ($contexts as $context) {
            if (preg_match('/^\/[^\/]+$/', $context->getPath()) == true) {
                if ($context->isSitemap() == true && $context->hasPermission() == true) {
                    $this->_sitemap[] = $context;
                }
            }
        }

        return $this->_sitemap;
    }

    /**
     * 사이트 하단메뉴를 가져온다.
     *
     * @return Context[] $menu
     */
    public function getFooterMenus(): array
    {
        $menu = [];
        $contexts = Contexts::all($this);
        foreach ($contexts as $context) {
            if ($context->isFooterMenu() == true && $context->hasPermission() == true) {
                $menu[] = $context;
            }
        }

        return $menu;
    }
}
