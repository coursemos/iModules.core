<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 부모클래스를 정의한다.
 *
 * @file /classes/Module.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 21.
 */
class Module extends Component
{
    /**
     * @var bool $_init 모듈 클래스가 초기화되었는지 여부
     */
    private static bool $_init = false;

    /**
     * @var object $_configs 모듈 환경설정
     */
    private static object $_configs;

    /**
     * @var Route $_route 모듈이 시작된 경로
     */
    private ?Route $_route = null;

    /**
     * @var object $_template 모듈의 컨텍스트 템플릿을 초기화한다.
     */
    private object $_template;

    /**
     * 모듈을 초기화한다.
     *
     * @param ?Route $route 모듈이 시작된 경로
     */
    public function __construct(?Route $route = null)
    {
        if (Modules::isInits($this->getName(), true) == false) {
        }
        $this->_route = $route;
    }

    /**
     * 모듈 설정을 초기화한다.
     */
    public function init(): void
    {
        if (self::$_init == false) {
            self::$_init = true;
        }
    }

    /**
     * 모듈이 시작된 경로를 기준으로 특정위치의 경로를 가져온다.
     *
     * @param int $position 경로를 가져올 위치 (NULL 일 경우 전체 경로를 가져온다.)
     * @return ?string $path
     */
    public function getRouteAt(int $position): ?string
    {
        $route = $this->_route ?? Router::get();
        $paths = explode('/', preg_replace('/^\//', '', $route->getSubPath()));
        return isset($paths[$position]) == true && strlen($paths[$position]) > 0 ? $paths[$position] : null;
    }

    /**
     * 모듈 URL 을 가져온다.
     *
     * @param string|int ...$paths 모듈 URL 에 추가할 내부 경로 (없는 경우 모듈 기본 URL만 가져온다.)
     * @return string $url
     */
    public function getUrl(string|int ...$paths): string
    {
        $route = $this->_route ?? Router::get();
        $url = $route->getUrl();
        if (count($paths) > 0) {
            $url .= '/' . implode('/', $paths);
        }

        return $url;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 설정한다.
     *
     * @param object $template 템플릿설정
     */
    public function setTemplate(object $template): self
    {
        $this->_template = $template;
        return $this;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 가져온다.
     *
     * @return Template $template
     */
    public function getTemplate(): Template
    {
        /**
         * 모듈의 컨텍스트 템플릿이 지정되지 않은 경우 에러메시지를 출력한다.
         */
        if (isset($this->_template) == false) {
            ErrorHandler::get($this->error('UNDEFINED_TEMPLATE'));
        }

        return new Template($this, $this->_template);
    }

    /**
     * 모듈의 환경설정을 가져온다.
     *
     * @param ?string $key 환경설정코드값 (NULL인 경우 전체 환경설정값)
     * @return mixed $value 환경설정값
     */
    public function getConfigs(?string $key = null): mixed
    {
        if (isset(self::$_configs) == false) {
            $installed = Modules::getInstalled($this->getName());
            $configs = $installed?->configs ?? new stdClass();
            self::$_configs = $this->getPackage()->getConfigs($configs);
        }

        if ($key == null) {
            return self::$_configs;
        } elseif (isset(self::$_configs->$key) == false) {
            return null;
        } else {
            return self::$_configs->$key;
        }
    }

    /**
     * 모듈 컨텍스트의 콘텐츠를 가져온다.
     * 컨텍스트를 지원하는 모듈이라면 모듈 클래스에서 getContent() 메소드를 재정의하여야 한다.
     * 모듈클래스에서 getContent() 메소드가 정의되어 있지 않다면, 이 메소드가 호출되며,
     * 모듈에서 컨텍스트를 지원하지 않는다는 에러메시지를 출력한다.
     *
     * @param string $context 컨텍스트
     * @param ?object $configs 컨텍스트 설정
     * @return string $html
     */
    public function getContent(string $context, ?object $configs = null): string
    {
        return ErrorHandler::get($this->error('NOT_FOUND_CONTEXT_METHOD', $context, $configs));
    }

    /**
     * 모듈 프로세스 라우팅을 처리한다.
     *
     * @param &object $results 요청처리 결과를 담을 변수
     * @param string $method 요청방법
     * @param string $path 요청주소
     * @param Input $input INPUT 데이터
     */
    public function doProcess(object &$results, string $method, string $path, Input &$input = null): void
    {
        define('__IM_PROCESS__', true);

        if (is_file($this->getPath() . '/process/' . $path . '.' . $method . '.php') == true) {
            require_once $this->getPath() . '/process/' . $path . '.' . $method . '.php';
        } else {
            ErrorHandler::print(
                $this->error(
                    'NOT_FOUND_MODULE_PROCESS_FILE',
                    $this->getPath() . '/process/' . $path . '.' . $method . '.php'
                )
            );
        }
    }

    /**
     * 특수한 에러코드의 경우 에러데이터를 클래스에서 처리하여 에러클래스로 전달한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return object $error
     */
    protected function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_CONTEXT_METHOD':
                $error = ErrorHandler::data();
                $error->message = ErrorHandler::getText($code, ['module' => $this->getName()]);
                $error->suffix =
                    '$context : ' . $message . ($details === null ? '' : '<br>$configs : ' . json_encode($details));
                $error->debugModeOnly = true;
                return $error;

            default:
                return Modules::error($code, $message, $details);
        }
    }

    /**
     * 모듈을 설치한다.
     * 모듈을 설치할때 데이터 마이그레이션 등이 필요한 경우 해당 함수를 각 모듈클래스에 재정의하여
     * 현재 설치되어 있는 버전에 따라 데이터 마이그레이션을 수행하고 신규버전 데이터베이스를 구성할 수 있다.
     *
     * @param string $version 현재 설치되어 있는 버전 (NULL인 경우 신규설치)
     * @return bool $success 설치성공여부
     */
    public static function install(string $version = null): bool
    {
        $db = self::db();
        $db->displayError(false);
        $databases = self::getPackage()->getDatabases();
        foreach ($databases as $table => $schema) {
            if ($db->compare(self::table($table), $schema) == false) {
                $success = $db->create(self::table($table), $schema);
                if ($success !== true) {
                    return false;
                }
            }
        }

        return true;
    }
}
