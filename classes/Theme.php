<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 사이트테마를 화면에 출력하기 위한 테마 엔진 클래스를 정의한다.
 *
 * @file /classes/Theme.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 23.
 */
class Theme
{
    /**
     * @var bool $_configs 테마 초기화여부
     */
    private bool $_init = false;

    /**
     * @var string $_path 테마경로
     */
    private string $_path;

    /**
     * @var ?Component $_owner 테마 소유자 (NULL 인 경우 아이모듈 코어)
     */
    private ?Component $_owner = null;

    /**
     * @var string $_name 테마명 (테마 폴더명)
     */
    private string $_name;

    /**
     * @var Package $_name 테마 패키지 정보 (package.json)
     */
    private Package $_package;

    /**
     * @var object $_configs 테마 환경설정
     */
    private object $_configs;

    /**
     * @var mixed[] $_values 현재 테마에서 사용할 변수를 지정한다.
     */
    private array $_values = [];

    /**
     * 테마 클래스를 선언한다.
     *
     * @param object|string $theme 테마정보
     */
    public function __construct(object|string $theme)
    {
        if (is_string($theme) == true) {
            $name = $theme;
            $theme = new stdClass();
            $theme->name = $name;
            $theme->configs = null;
        }
        $this->_pathname = $theme->name;

        /**
         * 테마명이 경로를 포함할 경우, 해당 경로에서 테마를 정의하고,
         * 그렇지 않을 경우 기본 경로에서 테마를 정의한다.
         */
        if (strpos($theme->name, '/') === 0) {
            /**
             * 테마명에서 테마 경로를 적절하게 계산한다.
             */
            $paths = explode('/', preg_replace('/^\//', '', $theme->name));

            /**
             * 테마명
             */
            $this->_name = array_pop($paths);

            /**
             * 테마를 가지고 있는 대상의 종류에 따라 테마 소유자를 정의한다.
             */
            switch (array_shift($paths)) {
                case 'modules':
                    $this->_owner = Modules::get(implode('/', $paths));
                    break;
            }

            if ($this->_owner == null) {
                ErrorHandler::print($this->error('NOT_FOUND_THEME', $this->getPath()));
            }

            $this->_path = $this->_owner->getBase() . '/themes';
        } else {
            $this->_name = $theme->name;
            $this->_path = '/themes';
        }
        $this->_path .= '/' . $this->_name;

        if (is_dir($this->getPath()) == false || is_file($this->getPath() . '/package.json') == false) {
            ErrorHandler::print($this->error('NOT_FOUND_THEME', $this->getPath()));
        }

        $this->_configs = $this->getPackage()->getConfigs($theme->configs ?? null);
    }

    /**
     * 테마를 정의된 요소들을 초기화한다.
     */
    public function init(): void
    {
        if ($this->_init == true) {
            return;
        }

        /**
         * 테마가 설정되지 않은 경우 에러메시지를 반환한다.
         */
        if ($this->_isLoaded() === false) {
            ErrorHandler::print($this->error('NOT_INITIALIZED_THEME'));
        }

        /**
         * 테마 설정에서 css value 가 존재하는 경우 css 를 생성한다.
         */
        $cssValues = [];
        foreach ($this->getConfigs() as $key => $value) {
            if (strpos($key, '--') === 0) {
                $cssValues[] = $key . ': ' . $value . ';';
            }
        }

        if (count($cssValues) > 0) {
            if (Cache::has($this->getCacheName('values.css'), 3600, true) == false) {
                Cache::store(
                    $this->getCacheName('values.css'),
                    ':root {' . "\n" . '    ' . implode("\n    ", $cssValues) . "\n" . '}',
                    true
                );
            }
            Cache::style($this->getCacheName(), Configs::cache() . '/' . $this->getCacheName('values.css'));
        }

        /**
         * 테마의 package.json 에 styles 나 scripts 가 설정되어 있다면, 해당 파일을 불러온다.
         */
        $package = $this->getPackage();
        if ($package->hasStyle() == true) {
            foreach ($package->getStyles() as $style) {
                if (preg_match('/^(http(s)?:)?\/\//', $style) == true) {
                    Html::style($style);
                } else {
                    Cache::style($this->getCacheName(), $this->getBase() . $style);
                }
            }

            Html::style(Cache::style($this->getCacheName()));
        }

        if ($package->hasScript() == true) {
            foreach ($package->getScripts() as $script) {
                $script = preg_match('/^(http(s)?:)?\/\//', $script) == true ? $script : $this->getDir() . $script;
                Html::script($script);
            }
        }

        $this->_init = true;
    }

    /**
     * 테마 설정이 정의되어 있는지 확인한다.
     *
     * @return bool $isLoaded
     */
    private function _isLoaded(): bool
    {
        return isset($this->_name) == true;
    }

    /**
     * 현재 테마명(테마 폴더명)를 가져온다.
     *
     * @return string $name
     */
    public function getName(): string
    {
        return $this->_name ?? 'undefined';
    }

    /**
     * 현재 테마의 단축경로명을 가져온다.
     *
     * @return string $pathname
     */
    public function getPathName(): string
    {
        return $this->_pathname ?? 'undefined';
    }

    /**
     * 현재 테마 제목을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $title
     */
    public function getTitle($language = null): string
    {
        return $this->getPackage()->getTitle($language);
    }

    /**
     * 테마 스크린샷을 가져온다.
     *
     * @return string $url
     */
    public function getScreenshot(): ?string
    {
        // @todo 실제 스크린샷을 가져오도록 수정
        return null;
    }

    /**
     * 현재 테마의 기본경로를 가져온다.
     *
     * @return string $path
     */
    public function getBase(): string
    {
        return $this->_path;
    }

    /**
     * 현재 테마의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return Configs::path() . $this->_path;
    }

    /**
     * 현재 테마의 상대경로를 가져온다.
     *
     * @return string $dir
     */
    public function getDir(): string
    {
        return Configs::dir() . $this->_path;
    }

    /**
     * 테마의 패키지정보를 가져온다.
     *
     * @return Package $package 패키지정보
     */
    public function getPackage(): Package
    {
        if ($this->_isLoaded() === false) {
            return null;
        }

        if (isset($this->_package) == true) {
            return $this->_package;
        }
        $this->_package = new Package($this->getBase() . '/package.json');
        return $this->_package;
    }

    /**
     * 테마의 환경설정을 가져온다.
     *
     * @param ?string $key 설정을 가져올 키값 (NULL인 경우 전체 환경설정을 가져온다.)
     * @return mixed $value 환경설정값
     */
    public function getConfigs(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->_configs;
        }
        return $this->_configs->{$key} ?? null;
    }

    /**
     * 테마의 환경설정을 임시로 저장한다.
     *
     * @param string|object $key 설정을 저장할 키값
     * @param mixed $value 설정값
     * @return mixed $value 환경설정값
     */
    public function setConfigs(string|object $key, mixed $value): Theme
    {
        if (is_string($key) == true) {
            $this->_configs->{$key} = $value;
        } else {
            $this->_configs = $key;
        }
        return $this;
    }

    /**
     * 테마 파일에서 사용할 변수를 할당한다.
     *
     * @param string|array $name 변수명
     * @param mixed $value 변수데이터
     * @param bool $is_clone 복제여부
     * @return Theme $this
     */
    public function assign(string|array $name, mixed $value = null, bool $is_clone = false): Theme
    {
        if (is_array($name) == true) {
            foreach ($name as $key => $value) {
                $this->assign($key, $value, $is_clone);
            }
        } else {
            $this->_values[$name] = $is_clone == true ? clone $value : $value;
        }
        return $this;
    }

    /**
     * 테마 파일에서 이용할 수 있는 데이터를 정리한다.
     *
     * @return mixed[] $values 정리된 변수
     */
    function getValues(): array
    {
        $values = $this->_values;

        /**
         * 필수변수가 정의되어 있지 않은 경우, 해당 변수를 설정한다.
         */
        if (isset($values['site']) == false) {
            $values['site'] = Sites::get();
        }

        if (isset($values['theme']) == false) {
            $values['theme'] = &$this;
        }

        if (isset($values['context']) == false) {
            $values['context'] = Contexts::has() ?? Sites::get()->getIndex();
        }

        if (isset($values['route']) == false) {
            $values['route'] = Router::get();
        }

        $values['owner'] = &$this->_owner;
        $values['theme'] = &$this;

        return $values;
    }

    /**
     * 테마 파일에서 현재 데이터를 유지하며 다른 페이지를 삽입한다.
     *
     * @param string $path 삽입할 파일 경로 (아이모듈의 절대경로를 제외한 나머지경로)
     */
    function include(string $path): void
    {
        if (is_file(Configs::path() . $path) == false) {
            ErrorHandler::print($this->error('NOT_FOUND_FILE', $path));
        }

        /**
         * 삽입할 파일에서 사용할 변수선언
         */
        extract($this->getValues());

        if (is_file(Configs::path() . $path) == true) {
            ob_start();
            include Configs::path() . $path;
            $context = ob_get_clean();
        }

        echo $context;
    }

    /**
     * 콘텐츠 레이아웃을 가져온다.
     *
     * @param string $name 레이아웃명
     * @param string $content 레이아웃에 포함될 콘텐츠
     * @param string $header 콘텐츠에 추가할 헤더
     * @param string $footer 콘텐츠에 추가할 푸터
     * @return string $html 컨텍스트 HTML
     */
    function getLayout(string $name = 'NONE', string $content = '', string $header = '', string $footer = ''): string
    {
        /**
         * 테마폴더에 레이아웃이 없다면 에러메세지를 출력한다.
         */
        if ($name != 'NONE' && is_file($this->getPath() . '/layouts/' . $name . '.html') == false) {
            return ErrorHandler::get(
                $this->error('NOT_FOUND_THEME_LAYOUT', $this->getPath() . '/layouts/' . $name . '.html')
            );
        }

        if (is_file($this->getPath() . '/index.html') == false) {
            return ErrorHandler::get($this->error('NOT_FOUND_THEME_INDEX', $this->getPath() . '/index.html'));
        }

        $this->init();

        /**
         * @todo 이벤트를 발생시킨다.
         */

        /**
         * 레이아웃에서 사용할 변수선언
         */
        extract($this->getValues());

        $content = Html::element('div', ['data-role' => 'content'], $content);
        if ($name == 'NONE') {
            $main = Html::element('main', ['data-role' => 'layout', 'data-layout' => 'NONE'], $content);
        } else {
            ob_start();
            include $this->getPath() . '/layouts/' . $name . '.html';
            $main = trim(ob_get_clean());
        }

        if (preg_match('/^<main.*?data-role=\"layout\".*?>.*<\/main>$/', str_replace("\n", '', $main)) == false) {
            return ErrorHandler::get(
                $this->error('INVALID_THEME_LAYOUT', $this->getPath() . '/layouts/' . $name . '.html')
            );
        }

        ob_start();
        include $this->getPath() . '/index.html';
        $layout = ob_get_clean();

        /**
         * @todo 이벤트를 발생시킨다.
         */
        $html = Html::tag($header, $layout, $footer);

        return $html;
    }

    /**
     * 사이트테마에서 문서(HTML)를 가져온다.
     *
     * @param string $file
     * @return string $document
     */
    public function getPage(string $file): string
    {
        /**
         * 테마 폴더에 해당파일이 없다면 에러메세지를 출력한다.
         */
        if (is_file($this->getPath() . '/pages/' . $file . '.html') === false) {
            return ErrorHandler::get(
                $this->error('NOT_FOUND_THEME_PAGE', $this->getPath() . '/pages/' . $file . '.html')
            );
        }

        $this->init();

        // @todo 이벤트 발생

        /**
         * 테마파일에서 사용할 변수선언
         */
        extract($this->getValues());

        ob_start();
        include $this->getPath() . '/pages/' . $file . '.html';
        $html = ob_get_clean();

        // @todo 이벤트 발생

        return $html;
    }

    /**
     * 템플릿 캐시적용을 위한 캐시명을 가져온다.
     *
     * @param string $key 캐시코드값
     * @return string $cacheName 캐시명
     */
    public function getCacheName(string $key = ''): string
    {
        $name = 'themes';
        if ($this->_owner !== null) {
            $name .= '.' . str_replace('/', '.', $this->_owner->getName());
        }
        $name .= '.' . $this->getName() . '.' . Sites::get()->getHost() . '.' . Sites::get()->getLanguage();
        $name .= $key ? '.' . $key : '';
        return $name;
    }

    /**
     * 테마 관련 에러를 처리한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_THEME':
                $error = ErrorHandler::data();
                $error->prefix = ErrorHandler::getText('THEME_ERROR');
                $error->message = ErrorHandler::getText('NOT_FOUND_THEME');
                $error->suffix = $message;
                return $error;

            case 'NOT_FOUND_THEME_LAYOUT':
            case 'NOT_FOUND_THEME_PAGE':
            case 'NOT_FOUND_FILE':
            case 'INVALID_THEME_LAYOUT':
                $error = ErrorHandler::data();
                $error->prefix = ErrorHandler::getText('THEME_ERROR');
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;
                return $error;

            default:
                return iModules::error($code, $message, $details);
        }
    }
}
