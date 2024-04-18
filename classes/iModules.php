<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 코어 클래스로 모든 사이트 레이아웃 및 모듈, 위젯, 플러그인 클래스는 아이모듈 코어 클래스를 통해 호출된다.
 * 이 클래스의 모든 메소드는 static 으로 iModules::[method]() 방식으로 호출할 수 있다.
 *
 * @file /classes/iModules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 4. 16.
 */
class iModules
{
    /**
     * @var bool $_init 초기화여부
     */
    private static bool $_init = false;

    /**
     * @var float $_startTime 최초시작시간
     * @var array $_loadingTime 로딩시간 기록
     */
    private static float $_startTime = 0;
    private static array $_loadingTime = [];

    /**
     * 아이모듈을 초기화한다.
     */
    private static function init(): void
    {
        if (self::$_init == true) {
            return;
        }

        self::$_startTime = Format::microtime();

        /**
         * 세션을 시작한다.
         */
        self::session_start();

        /**
         * 라우터를 초기화한다.
         */
        Router::init();

        /**
         * 설치가 되지 않았을 경우 설치화면으로 이동한다.
         */
        if (Configs::isInstalled() == false) {
            exit();
        }

        /**
         * 캐시를 초기화한다.
         */
        Cache::init();

        /**
         * 언어팩을 초기화한다.
         */
        Language::init(self::getLanguageCustomize());

        /**
         * 모듈을 초기화한다.
         */
        Modules::init();

        /**
         * 전체 도메인을 초기화한다.
         */
        Domains::init();

        /**
         * 전체 사이트를 초기화한다.
         */
        Sites::init();

        /**
         * 전체 컨텍스트를 초기화한다.
         */
        Contexts::init();

        self::loadingTime('init');
    }

    /**
     * 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @param string $name 데이터베이스 인터페이스 고유명
     * @param ?DatabaseConnector $connector 데이터베이스정보
     * @return DatabaseInterface $interface
     */
    public static function db(string $name = 'default', ?DatabaseConnector $connector = null): DatabaseInterface
    {
        return Database::getInterface($name, $connector ?? Configs::db());
    }

    /**
     * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
     *
     * @param string $table
     * @return string $table
     */
    public static function table(string $table): string
    {
        // todo: prefix 설정 제대로
        return 'im_' . $table;
    }

    /**
     * 로딩시간을 기록한다.
     *
     * @param string $name
     */
    public static function loadingTime(string $name): void
    {
        self::$_loadingTime[] = [$name, Format::microtime()];
    }

    /**
     * 기록된 전체 로딩시간을 가져온다.
     *
     * @param object $times
     */
    public static function loadingTimes(): array
    {
        $loadingTimes = [];
        $latestTime = self::$_startTime;
        foreach (self::$_loadingTime as $time) {
            $loadingTimes[] = [
                'name' => $time[0],
                'current' => sprintf('%0.6f', $time[1] - $latestTime),
                'total' => sprintf('%0.6f', $time[1] - self::$_startTime),
            ];
            $latestTime = $time[1];
        }

        return $loadingTimes;
    }

    /**
     * 세션을 시작한다.
     */
    public static function session_start(): void
    {
        if (defined('IM_SESSION_STARTED') == true) {
            return;
        }

        /**
         * 별도의 세션폴더가 생성되어있다면, 해당 폴더에 세션을 저장한다.
         */
        if (
            Configs::get('session_path') !== null &&
            is_dir(Configs::get('session_path')) == true &&
            is_writable(Configs::get('session_path')) == true
        ) {
            session_save_path(Configs::get('session_path'));
        }

        $options = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => Configs::get('session_domain'),
            'secure' => Request::isHttps() == true,
            'httponly' => true,
            'samesite' => Request::isHttps() == true ? 'None' : 'Lax',
        ];

        session_name('IM_SESSION_ID');
        session_set_cookie_params($options);
        session_cache_expire(3600);
        $started = session_start();
        if ($started == true) {
            define('IM_SESSION_STARTED', true);
        }
    }

    /**
     * 세션쓰기 대기를 방지하기 위해 세션쓰기를 일시중단한다.
     */
    public static function session_stop(): void
    {
        session_write_close();
    }

    /**
     * 공통 리스소를 불러온다.
     */
    public static function resources(): void
    {
        /**
         * 공통적으로 사용하는 자바스크립트를 불러온다.
         */
        Cache::script('common', '/scripts/Html.js');
        Cache::script('common', '/scripts/Dom.js');
        Cache::script('common', '/scripts/DomList.js');
        Cache::script('common', '/scripts/Form.js');
        Cache::script('common', '/scripts/Scrollbar.js');
        Html::script(Cache::script('common'), 1);

        Cache::script('core', '/scripts/iModules.js');
        Cache::script('core', '/scripts/Ajax.js');
        Cache::script('core', '/scripts/Modules.js');
        Cache::script('core', '/scripts/Component.js');
        Cache::script('core', '/scripts/Module.js');
        Cache::script('core', '/scripts/Format.js');
        Cache::script('core', '/scripts/Language.js');
        Html::script(Cache::script('core'), 1);

        /**
         * 제3자 기본 스크립트를 불러온다.
         */
        Html::script('/scripts/moment.js');

        /**
         * 모듈의 자바스크립트파일을 불러온다.
         */
        Html::script(Modules::scripts(), 5);

        /**
         * 기본 스타일시트 및 폰트를 불러온다.
         */
        Html::font('moimz');
        Html::style('/styles/common.css', 1);

        /**
         * 모듈의 스타일시트파일을 불러온다.
         */
        Html::style(Modules::styles(), 5);

        self::loadingTime('resources');
    }

    /**
     * 기본 META 태그를 설정한다.
     */
    public static function metas(): void
    {
        $site = Sites::get();

        /**
         * 테마색상을 추가한다.
         */
        Html::head('meta', ['name' => 'theme-color', 'content' => $site->getColor()]);

        /**
         * 모바일기기 및 애플 디바이스를 위한 TOUCH-ICON 태그를 정의한다.
         */
        if ($site->getEmblem() !== null) {
            Html::head('link', ['rel' => 'apple-touch-icon', 'href' => $site->getEmblem()->getUrl('origin')]);
        }

        /**
         * 사이트 Favicon 태그를 정의한다.
         */
        if ($site->getFavicon() !== null) {
            Html::head('link', [
                'rel' => 'shortcut icon',
                'type' => 'image/x-icon',
                'href' => $site->getFavicon()->getUrl('origin'),
            ]);
        }
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
    }

    /**
     * 커스터마이즈 언어팩을 불러온다.
     *
     * @return object $customize
     */
    public static function getLanguageCustomize(): object
    {
        // @todo 캐시적용
        $customize = new stdClass();
        $languages = iModules::db()
            ->select()
            ->from(iModules::table('languages'))
            ->get();
        foreach ($languages as $language) {
            $component = '/' . $language->component_type . 's/' . $language->component_name;
            $customize->{$component} ??= new stdClass();
            $customize->{$component}->{$language->language} ??= new stdClass();
            $paths = explode('.', $language->path);

            $current = $customize->{$component}->{$language->language};
            while ($path = array_shift($paths)) {
                if (empty($paths) == true) {
                    $current->{$path} = $language->text;
                } else {
                    $current->{$path} ??= new stdClass();
                    $current = $current->{$path};
                }
            }
        }

        return $customize;
    }

    /**
     * 아이모듈 기본 URL 을 가져온다.
     *
     * @param bool $is_full_url 도메인을 포함한 전체 URL 여부
     * @return string $url
     */
    public static function getUrl(bool $is_full_url = false): string
    {
        $url = '';
        if ($is_full_url === true) {
            $url .= Domains::get()->getUrl();
        }
        $url .= \Configs::dir();

        return $url;
    }

    /**
     * 프로세스 URL 을 가져온다.
     *
     * @param string $type 프로세스를 실행할 컴포넌트종류 (module, plugin, widget)
     * @param string $name 컴포넌트명
     * @param string $path 프로세스 경로
     * @return string $url
     */
    public static function getProcessUrl(string $type, string $name, string $path): string
    {
        $domain = Domains::get();

        $route = '/' . $type . '/' . $name . '/process/' . $path;
        if ($domain->isRewrite() == true) {
            return Configs::dir() . $route;
        } else {
            return Configs::dir() . '?route=' . $route;
        }
    }

    /**
     * 권한문자열을 파싱하여 권한이 있는지 여부를 확인한다.
     *
     * @param string $permission 권한문자열
     * @return bool $has_permission
     */
    public static function parsePermissionString(string $permission): bool
    {
        // @todo 실제 권한여부 확인
        return strlen($permission) > 0;
    }

    /**
     * 문서 레이아웃을 초기화한다.
     */
    public static function initContent(): void
    {
        $route = Router::get();

        /**
         * 경로대상이 웹페이지, 관리자, 모듈인 경우 HTML 헤더를 정의하고,
         * 그렇지 않은 경우 JSON 헤더를 정의한다.
         */
        if (in_array($route->getType(), ['context', 'html']) == true) {
            /**
             * Content-Type 을 지정한다.
             */
            Header::type('html');

            /**
             * 사이트명 및 설명에 대한 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
             */
            $site = $route->getSite();
            Html::title($site->getTitle());
            Html::description($site->getDescription(false));
            if ($site->getKeywords()) {
                Html::keywords($site->getKeywords());
            }
            Html::canonical($route->getUrl(true, true), false);

            /**
             * 아이모듈 페이지에서 공통적으로 사용하는 리소스를 불러온다.
             */
            iModules::resources();

            /**
             * 메타데이터를 설정한다.
             */
            iModules::metas();
        } else {
            /**
             * Content-Type 을 지정한다.
             */
            Header::type('json');
        }

        self::loadingTime('initContent');
    }

    /**
     * 요청에 따른 응답을 처리한다.
     */
    public static function respond(): void
    {
        self::init();

        $route = Router::get();

        /**
         * 경로 타입에 따라 응답을 처리한다.
         */
        switch ($route->getType()) {
            case 'context':
                self::doContext($route);
                break;

            case 'html':
                self::doHtml($route);
                break;

            case 'blob':
                self::doBlob($route);
                break;
        }
    }

    /**
     * 컨텍스트 요청을 처리한다.
     *
     * @param Route $route
     */
    public static function doContext(Route $route): void
    {
        /**
         * 요청된 주소와 경로가 다를 경우, 정상적인 경로로 리다이렉트한다.
         */
        if (Request::url($route->getDomain()->isRewrite() == true ? false : ['route']) != $route->getUrl(true)) {
            Header::location(Request::combine($route->getUrl(true), Request::query()));
            exit();
        }

        /**
         * 컨텍스트 콘텐츠를 초기화한다.
         */
        self::initContent();

        /**
         * 컨텍스트를 가져온다.
         */
        $context = Contexts::get($route);

        /**
         * 컨텍스트 및 설명에 대한 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
         */
        if ($route->getPath() !== '/') {
            Html::title($context->getTitle() . ' - ' . $context->getSite()->getTitle());
        }
        if ($context->getDescription(false)) {
            Html::description($context->getDescription(false));
        }
        if ($context->getKeywords()) {
            Html::keywords($context->getKeywords());
        }

        /**
         * 컨텍스트의 콘텐츠를 가져온다.
         */
        $content = $route->getContent();

        /**
         * 사이트의 레이아웃에 콘텐츠를 포함하여 가져온다.
         */
        $theme = $route->getSite()->getTheme();
        $theme->assign('site', $context->getSite());
        $theme->assign('context', $context);
        $layout = $theme->getLayout($context->getLayout(), $content);

        self::loadingTime('doContext');

        /**
         * HTML 헤더 및 푸터를 포함하여 출력한다.
         * @todo 이벤트
         */
        Html::body('data-context-url', $context->getUrl());
        Html::body('data-color-scheme', Request::cookie('IM_COLOR_SCHEME') ?? 'auto', false);
        Html::print(Html::header(), $layout, Html::footer());

        Html::print(
            Html::tag(
                '<!--',
                'Powered By : ',
                '  _ __  __           _       _           ',
                ' (_)  \\/  | ___   __| |_   _| | ___  ___ ',
                ' | | |\\/| |/ _ \\ / _` | | | | |/ _ \\/ __|',
                ' | | |  | | (_) | (_| | |_| | |  __/\\__ \\',
                ' |_|_|  |_|\\___/ \\__,_|\\__,_|_|\\___||___/  v' . __IM_VERSION__,
                '-->'
            )
        );
        if (Configs::debug() == true) {
            if (count(self::$_loadingTime) > 0) {
                foreach (self::loadingTimes() as $time) {
                    $loadingTimes[] =
                        '<!-- ' .
                        $time['name'] .
                        ':' .
                        str_repeat(' ', 20 - strlen($time['name'])) .
                        $time['total'] .
                        '(+' .
                        $time['current'] .
                        ') -->';
                }
                Html::print(...$loadingTimes);
            }
        }
    }

    /**
     * 컨텍스트 요청을 처리한다.
     *
     * @param Route $route
     */
    public static function doHtml(Route $route): void
    {
        /**
         * 요청된 주소와 경로가 다를 경우, 정상적인 경로로 리다이렉트한다.
         */
        if (Request::url($route->getDomain()->isRewrite() == true ? false : ['route']) != $route->getUrl(true)) {
            Header::location(Request::combine($route->getUrl(true), Request::query()));
            exit();
        }

        /**
         * 요청된 주소와 경로가 다를 경우, 정상적인 경로로 리다이렉트한다.
         */
        if (Request::url($route->getDomain()->isRewrite() == true ? false : ['route']) != $route->getUrl(true)) {
            Header::location(Request::combine($route->getUrl(true), Request::query()));
            exit();
        }

        /**
         * 컨텍스트의 콘텐츠를 가져온다.
         */
        $content = $route->getContent();

        /**
         * 컨텍스트 콘텐츠를 초기화한다.
         */
        self::initContent();

        /**
         * HTML 헤더 및 푸터를 포함하여 출력한다.
         * @todo 이벤트
         */
        Html::print(Html::header(), $content, Html::footer());
    }

    /**
     * 파일 요청을 처리한다.
     *
     * @param Route $route
     */
    public static function doBlob(Route $route): void
    {
        $route->printContent();
    }
}
