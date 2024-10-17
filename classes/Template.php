<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 테마 및 템플릿을 화면에 출력하기 위한 템플릿 엔진 클래스를 정의한다.
 *
 * @file /classes/Template.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 17.
 */
class Template
{
    /**
     * @var bool $_configs 템플릿 초기화여부
     */
    private bool $_init = false;

    /**
     * @var ?Component $_owner 템플릿 소유자 (NULL 인 경우 아이모듈 코어)
     */
    private ?Component $_owner = null;

    /**
     * @var Component $parent 템플릿을 처리할 부모클래스
     */
    private Component $_parent;

    /**
     * @var string $_path 템플릿경로
     */
    private string $_path;

    /**
     * @var string $_name 템플릿명 (템플릿 폴더명)
     */
    private string $_name;

    /**
     * @var string $_pathname 단축경로를 포함한 템플릿명
     */
    private string $_pathname;

    /**
     * @var Package $_name 템플릿 패키지 정보 (package.json)
     */
    private Package $_package;

    /**
     * @var ?object $_configs 템플릿 환경설정
     */
    private object $_configs;

    /**
     * @var mixed[] $_values 현재 템플릿에서 사용할 변수를 지정한다.
     */
    private array $_values = [];

    /**
     * 템플릿 클래스를 선언한다.
     *
     * @param Component $parent 템플릿을 처리할 부모클래스
     * @param object $template 템플릿정보
     */
    public function __construct(Component $parent, object $template)
    {
        $this->_parent = $parent;
        $this->_pathname = $template->name;

        /**
         * 템플릿명이 경로를 포함할 경우, 해당 경로에서 템플릿을 정의하고,
         * 그렇지 않을 경우 템플릿을 처리할 부모클래스의 기본 템플릿 경로에서 템플릿을 정의한다.
         */
        if (strpos($template->name, '/') === 0) {
            /**
             * 템플릿명에서 템플릿 경로를 적절하게 계산한다.
             */
            $paths = explode('/', preg_replace('/^\//', '', $template->name));

            /**
             * 템플릿명
             */
            $this->_name = array_pop($paths);

            /**
             * 템플릿을 가진 테마명
             */
            $theme = array_pop($paths);

            /**
             * 템플릿을 가지고 있는 대상의 종류에 따라 템플릿 소유자를 정의한다.
             */
            if (count($paths) == 0) {
                $this->_owner = null;
                $this->_path = '';
            } else {
                switch (array_shift($paths)) {
                    case 'modules':
                        $this->_owner = Modules::get(implode('/', $paths));
                        break;
                }

                if ($this->_owner == null) {
                    ErrorHandler::print($this->error('NOT_FOUND_TEMPLATE', $this->getPath()));
                }

                $this->_path = $this->_owner->getBase();
            }

            $this->_path .= '/themes/' . $theme . $this->_parent->getBase();
        } else {
            $this->_owner = $parent;
            $this->_name = $template->name;
            $this->_path = $this->_parent->getBase();
        }
        $this->_path .= '/templates/' . $this->_name;

        if (is_dir($this->getPath()) == false || is_file($this->getPath() . '/package.json') == false) {
            ErrorHandler::print($this->error('NOT_FOUND_TEMPLATE', $this->getPath()));
        }

        $this->_configs = $this->getPackage()->getConfigs($template->configs ?? null);
    }

    /**
     * 템플릿을 정의된 요소들을 초기화한다.
     */
    public function init(): void
    {
        if ($this->_init == true) {
            return;
        }

        /**
         * 템플릿을 설정되지 않은 경우 에러메시지를 반환한다.
         */
        if ($this->_isLoaded() === false) {
            ErrorHandler::print($this->error('NOT_INITIALIZED_TEMPLATE'));
        }

        /**
         * 템플릿의 package.json 에 styles 나 scripts 가 설정되어 있다면, 해당 파일을 불러온다.
         */
        $package = $this->getPackage();
        if ($package->hasStyle() == true) {
            foreach ($package->getStyles() as $style) {
                $style = preg_match('/^(http(s)?:)?\/\//', $style) == true ? $style : $this->getDir() . $style;
                Html::style($style);
            }
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
     * 템플릿 설정이 정의되어 있는지 확인한다.
     *
     * @return bool $isLoaded
     */
    private function _isLoaded(): bool
    {
        return isset($this->_name) == true;
    }

    /**
     * 현재 템플릿명(템플릿 폴더명)를 가져온다.
     *
     * @return string $name
     */
    public function getName(): string
    {
        return $this->_name ?? 'undefined';
    }

    /**
     * 현재 템플릿의 단축경로명을 가져온다.
     *
     * @return string $pathname
     */
    public function getPathName(): string
    {
        return $this->_pathname ?? 'undefined';
    }

    /**
     * 현재 템플릿 제목을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $title
     */
    public function getTitle($language = null): string
    {
        return $this->getPackage()->getTitle($language);
    }

    /**
     * 템플릿 스크린샷을 가져온다.
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
     * 현재 템플릿의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return Configs::path() . $this->_path;
    }

    /**
     * 현재 템플릿의 상대경로를 가져온다.
     *
     * @return string $dir
     */
    public function getDir(): string
    {
        return Configs::dir() . $this->_path;
    }

    /**
     * 템플릿의 패키지정보를 가져온다.
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
     * 템플릿의 환경설정을 가져온다.
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
     * 템플릿의 환경설정을 임시로 저장한다.
     *
     * @param string|object $key 설정을 저장할 키값
     * @param mixed $value 설정값
     * @return mixed $value 환경설정값
     */
    public function setConfigs(string|object $key, mixed $value): Template
    {
        if (is_string($key) == true) {
            $this->_configs->{$key} = $value;
        } else {
            $this->_configs = $key;
        }
        return $this;
    }

    /**
     * 템플릿 파일에서 사용할 변수를 할당한다.
     *
     * @param string|array $name 변수명
     * @param mixed $value 변수데이터
     * @param bool $is_clone 복제여부
     * @return Template $this
     */
    public function assign(string|array $name, mixed $value = null, bool $is_clone = false): Template
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
     * 템플릿 파일에서 이용할 수 있는 데이터를 정리한다.
     *
     * @return mixed[] $values 정리된 변수
     */
    private function getValues(): array
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

        $values['me'] = &$this->_parent;
        $values['template'] = &$this;

        return $values;
    }

    /**
     * 템플릿 파일에서 이용하는 변수를 초기화한다.
     */
    private function resetValues(): void
    {
        foreach (array_keys($this->_values) as $key) {
            if (in_array($key, ['site', 'theme', 'context', 'route', 'me', 'template']) == false) {
                unset($this->_values[$key]);
            }
        }
    }

    /**
     * 템플릿 파일에서 현재 데이터를 유지하며 다른 페이지를 삽입한다.
     *
     * @param string $path 삽입할 파일 경로 (아이모듈의 절대경로를 제외한 나머지경로)
     */
    function include(string $path): void
    {
        if (is_file(Configs::path() . $path) == false) {
            ErrorHandler::print($this->error('NOT_FOUND_FILE', $path));
        }

        File::include(Configs::path() . $path, $this->getValues());
    }

    /**
     * 컨텍스트 콘텐츠 페이지를 가져온다.
     *
     * @param string $file HTML 확장자를 포함하지 않는 콘텐츠 파일명
     * @param string $header 컨텍스트 HTML 상단에 포함할 HTML
     * @param string $footer 컨텍스트 HTML 하단에 포함할 HTML
     * @return string $html 컨텍스트 HTML
     */
    function getContext(string $file, string $header = '', string $footer = ''): string
    {
        $content = Events::fireEvent(
            $this->_parent,
            'beforeGetContext',
            [$this->_parent, $this, &$file, &$header, &$footer],
            'NOTNULL'
        );
        if ($content !== null) {
            return $content;
        }

        /**
         * 템플릿폴더에 파일이 없다면 에러메세지를 출력한다.
         */
        if (is_file($this->getPath() . '/contexts/' . $file . '.html') == false) {
            return ErrorHandler::get(
                $this->error('NOT_FOUND_TEMPLATE_FILE', $this->getPath() . '/contexts/' . $file . '.html')
            );
        }

        if (is_file($this->getPath() . '/contexts/' . $file . '.html') == true) {
            $context = File::include($this->getPath() . '/contexts/' . $file . '.html', $this->getValues(), true);
        }

        $content = Html::tag($header, $context, $footer);
        Events::fireEvent($this->_parent, 'beforeGetContext', [$this->_parent, $this, $file, &$content]);

        return $content;
    }

    /**
     * 템플릿 공용 레이아웃을 가져온다.
     *
     * @param string $main 메인콘텐츠
     * @return string $html 템플릿 최종콘텐츠
     */
    function getLayout(string $main = ''): string
    {
        $this->init();

        /**
         * @todo 이벤트를 발생시킨다.
         */
        if (is_file($this->getPath() . '/index.html') == true) {
            $layout = File::include($this->getPath() . '/index.html', [...$this->getValues(), 'main' => $main], true);
        } else {
            $layout = $main;
        }

        $this->resetValues();

        /**
         * @todo 이벤트를 발생시킨다.
         */
        return $layout;
    }

    /**
     * 페이지이동 네비게이션을 가져온다.
     *
     * @param int $p 현재페이지
     * @param int $total 총 페이지
     * @param int $pagenum 페이지이동버튼 갯수
     * @param string $mode 페이지 표시 형식 (FIXED, CENTER)
     * @param ?string $link 페이지 이동링크 (페이지번호가 들어가는 부분에 ${PAGE} 치환자 사용)
     * @param ?string $file 페이지 네비게이션 템플릿 파일명
     * @return string $html
     */
    public static function getPagination(
        int $page,
        int $totalPage,
        int $pagenum,
        string $mode = 'CENTER',
        ?string $link = null,
        ?string $file = null
    ): string {
        $page = is_numeric($page) == true && $p > 0 ? $page : 1;
        $link = $link;
        if (strpos($link, '#') !== 0) {
            //$link .= $this->IM->getQueryString();
        }

        $totalPage = $totalPage == 0 ? 1 : $totalPage;

        if ($mode == 'FIXED') {
            $startPage = floor(($page - 1) / $pagenum) * $pagenum + 1;
            $endPage = $startPage + $pagenum - 1 < $totalPage ? $startPage + $pagenum - 1 : $totalPage;
            $prevPageStart = $startPage - $pagenum > 0 ? $startPage - $pagenum : false;
            $nextPageStart = $endPage < $totalPage ? $endPage + 1 : false;
        } else {
            $gap = max(1, floor($pagenum / 2));
            $startPage = $page - $gap > 0 ? $page - $gap : 1;
            $endPage = $page + $gap;
            if ($endPage - $startPage < $gap * 2 + 1) {
                $endPage = $gap * 2 - $startPage + 1;
            }
            $endPage = $endPage > $totalPage ? $totalPage : $endPage;
            if ($startPage > 1 && $endPage - $startPage < $gap * 2 + 1) {
                $startPage = max(1, $endPage - $gap * 2 + 1);
            }
            $prevPageStart = null;
            $nextPageStart = null;
        }

        $prevPage = $page > 1 ? $page - 1 : false;
        $nextPage = $page < $totalPage ? $page + 1 : false;

        /*
        if ($file == null) {
            ob_start();
            if (is_file($this->getPath() . '/pagination.php') == true) {
                include $this->getPath() . '/pagination.php';
            } else {
                include __IM_PATH__ . '/includes/pagination.php';
            }
            $html = ob_get_clean();
        } else {
            if (is_file($this->getPath() . '/' . $file . '.php') == true) {
                ob_start();
                include $this->getPath() . '/pagination.php';
                $html = ob_get_clean();
            } else {
                return $this->getError('NOT_FOUND_TEMPLET_FILE', $this->getDir() . '/' . $file . '.php', false);
            }
        }

        $html =
            PHP_EOL .
            '<div data-role="pagination" data-page="' .
            $p .
            '" data-total="' .
            $total .
            '">' .
            $html .
            '</div>' .
            PHP_EOL;

        return $html;
        */

        return '';
    }

    /**
     * 템플릿 관련 에러를 처리한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_TEMPLATE':
                $error = ErrorHandler::data($code);
                $error->prefix = ErrorHandler::getText('TEMPLATE_ERROR');
                $error->message = ErrorHandler::getText('NOT_FOUND_TEMPLATE');
                $error->suffix = $message;
                return $error;

            case 'NOT_FOUND_TEMPLATE_FILE':
                $error = ErrorHandler::data($code);
                $error->prefix = ErrorHandler::getText('TEMPLATE_ERROR');
                $error->message = ErrorHandler::getText('NOT_FOUND_TEMPLATE_FILE');
                $error->suffix = $message;
                return $error;

            case 'NOT_FOUND_FILE':
                $error = ErrorHandler::data($code);
                $error->prefix = ErrorHandler::getText('TEMPLATE_ERROR');
                $error->message = ErrorHandler::getText('NOT_FOUND_FILE');
                $error->suffix = $message;
                return $error;

            default:
                return ErrorHandler::error($code, $message, $details);
        }
    }
}
