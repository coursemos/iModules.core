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
 * @modified 2023. 4. 10.
 */
class iModules
{
    /**
     * @var bool $_init 초기화여부
     */
    private static bool $_init = false;

    /**
     * 아이모듈을 초기화한다.
     */
    private static function init(): void
    {
        if (self::$_init == true) {
            return;
        }

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
    }

    /**
     * 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @param string $name 데이터베이스 인터페이스 고유명
     * @param ?object $connector 데이터베이스정보
     * @return DatabaseInterface $interface
     */
    public static function db(string $name = 'default', ?object $connector = null): DatabaseInterface
    {
        return Database::getInterface($name, $connector ?? Configs::get('db'));
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
     * 세션을 시작한다.
     */
    public static function session_start(): void
    {
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

        session_set_cookie_params(
            0,
            '/',
            Configs::get('session_domain') ?? '',
            Request::isHttps() == true,
            Request::isHttps() == false
        );
        session_start();
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
        Html::script(Cache::script('common'), 1);

        Cache::script('core', '/scripts/iModules.js');
        Cache::script('core', '/scripts/Ajax.js');
        Cache::script('core', '/scripts/Modules.js');
        Cache::script('core', '/scripts/Module.js');
        Cache::script('core', '/scripts/Format.js');
        Cache::script('core', '/scripts/Language.js');
        Html::script(Cache::script('core'), 1);

        /**
         * 모듈의 자바스크립트파일을 불러온다.
         */
        Html::script(Modules::scripts(), 5);

        /**
         * 기본 스타일시트 및 폰트를 불러온다.
         */
        Html::font('moimz');
        Cache::style('core', '/styles/common.scss');
        Html::style(Cache::style('core'), 1);

        /**
         * 모듈의 스타일시트파일을 불러온다.
         */
        Html::style(Modules::styles(), 5);
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
            header('Content-type: text/html; charset=utf-8');

            /**
             * 사이트명 및 설명에 대한 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
             */
            $site = $route->getSite();
            Html::title($site->getTitle());
            Html::description($site->getDescription());
            Html::canonical($route->getUrl(true, true), false);

            /**
             * 아이모듈 페이지에서 공통적으로 사용하는 리소스를 불러온다.
             */
            iModules::resources();

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
        } else {
        }
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
         * 컨텍스트를 가져온다.
         */
        $context = Contexts::get($route);

        /**
         * 컨텍스트의 콘텐츠를 가져온다.
         */
        $content = $route->getContent();

        /**
         * 사이트 테마에 콘텐츠를 포함하여 가져온다.
         */
        $layout = $route->getSite()->getTheme();
        $layout->assign('site', $context->getSite());
        $layout->assign('context', $context);
        $layout->assign('content', $content);
        $layout = $layout->getLayout($context->getLayout());

        /**
         * 컨텍스트 및 설명에 대한 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
         */
        if ($route->getPath() !== '/') {
            Html::title($context->getTitle() . ' - ' . $context->getSite()->getTitle());
        }
        if ($context->getDescription()) {
            Html::description($context->getDescription());
        }

        /**
         * 컨텍스트 콘텐츠를 초기화한다.
         */
        self::initContent();

        /**
         * HTML 헤더 및 푸터를 포함하여 출력한다.
         * @todo 이벤트
         */
        Html::print(Html::header(), $layout, Html::footer());
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

    /**
     * 에러페이지를 출력하기 위한 데이터를 가공한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        $error = ErrorHandler::data();

        return $error;
    }
}
