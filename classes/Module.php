<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 부모클래스를 정의한다.
 *
 * @file /classes/Module.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Module extends Component
{
    /**
     * @var string $_name 모듈명
     */
    private string $_name;

    /**
     * @var bool $_init 모듈 클래스가 초기화되었는지 여부
     */
    private static bool $_init = false;

    /**
     * @var object $_package 모듈 패키지정보
     */
    private static object $_package;

    /**
     * @var object $_configs 모듈 환경설정
     */
    private static object $_configs;

    /**
     * @var Route $_route 모듈이 시작된 경로
     */
    private ?Route $_route = null;

    /**
     * 모듈의 컨텍스트 템플릿을 초기화한다.
     */
    private object $_templet;

    /**
     * 모듈을 초기화한다.
     *
     * @param ?Route $route 모듈이 시작된 경로
     */
    public function __construct(?Route $route = null)
    {
        if (Modules::isInits($this->getName()) == false) {
            if (is_file($this->getPath() . '/scripts/' . ucfirst($this->getName()) . '.js') == true) {
                Cache::script('modules', $this->getBase() . '/scripts/' . ucfirst($this->getName()) . '.js');
            }
            self::$_init = true;
        }
        $this->_route = $route;
    }

    /**
     * 모듈을 설정을 초기화한다.
     */
    public function init(): void
    {
    }

    /**
     * 각 모듈에서 사용할 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @param string $name 데이터베이스 인터페이스 고유명
     * @param ?object $connector 데이터베이스정보
     * @return DatabaseInterface $interface
     */
    public function db(string $name = 'default', ?object $connector = null): DatabaseInterface
    {
        return Database::getInterface($name, $connector ?? Configs::get('db'));
    }

    /**
     * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
     *
     * @param string $table;
     * @return string $table;
     */
    public function table(string $table): string
    {
        return iModules::table('module_' . $this->getName() . '_' . $table);
    }

    /**
     * 언어팩 코드 문자열을 가져온다.
     *
     * @param string $text 코드
     * @param ?array $placeHolder 치환자
     * @return string|array $message 치환된 메시지
     */
    public function getText(string $text, ?array $placeHolder = null): string|array
    {
        return Language::getText($text, $placeHolder, ['/modules/' . $this->getName(), '/']);
    }

    /**
     * 언어팩 에러코드 문자열을 가져온다.
     *
     * @param string $code 에러코드
     * @param ?array $placeHolder 치환자
     * @return string $message 치환된 메시지
     */
    public function getErrorText(string $code, ?array $placeHolder = null): string
    {
        return $this->getText('error/' . $code, $placeHolder);
    }

    /**
     * 모듈명을 가져온다.
     *
     * @return string $module
     */
    public function getName(): string
    {
        if (isset($this->_name) == true) {
            return $this->_name;
        }

        $this->_name = str_replace(
            '\\',
            '/',
            preg_replace('/\\\[^\\\]+$/', '', preg_replace('/^(\\\)?modules\\\/', '', get_class($this)))
        );
        return $this->_name;
    }

    /**
     * 모듈의 기본경로를 가져온다.
     *
     * @return string $base
     */
    public function getBase(): string
    {
        return '/modules/' . $this->getName();
    }

    /**
     * 모듈의 상태경로를 가져온다.
     *
     * @return string $dir
     */
    public function getDir(): string
    {
        return Configs::dir() . $this->getBase();
    }

    /**
     * 모듈의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public function getPath(): string
    {
        return Configs::path() . $this->getBase();
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
     * @param object $templet 템플릿설정
     */
    public function setTemplet(object $templet): self
    {
        $this->_templet = $templet;
        return $this;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 가져온다.
     *
     * @return Templet $templet
     */
    public function getTemplet(): Templet
    {
        /**
         * 모듈의 컨텍스트 템플릿이 지정되지 않은 경우 에러메시지를 출력한다.
         */
        if (isset($this->_templet) == false) {
            ErrorHandler::get($this->error('UNDEFINED_TEMPLET'));
        }

        return new Templet($this, $this->_templet);
    }

    /**
     * 모듈 패키지정보를 가져온다.
     *
     * @return object $package
     */
    public function getPackage(): object
    {
        if (isset(self::$_package) == true) {
            return self::$_package;
        }

        self::$_package = Modules::getPackage($this->getName());
        return self::$_package;
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
            $configs = $installed->configs ?? new stdClass();
            $package = $this->getPackage();

            $configKeys = [];
            foreach ($package->configs as $configKey => $configValue) {
                $configKeys[] = $configKey;
                $configs->$configKey = Configs::getConfigsDefaultValue($configValue, $configs->$configKey ?? null);
            }

            foreach ($configs as $configKey => $configValue) {
                if (in_array($configKey, $configKeys) == false) {
                    unset($configs->$configKey);
                }
            }

            self::$_configs = $configs;
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
                return iModules::error($code, $message, $details);
        }
    }
}
