<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Modules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 19.
 */
class Modules
{
    /**
     * @var bool $_init 초기화여부
     */
    private static bool $_init = false;

    /**
     * @var bool[] $_inits 모듈별 초기화여부
     */
    private static array $_inits = [];

    /**
     * @var object[] $_packages 모듈 패키지 정보
     */
    private static array $_packages = [];

    /**
     * @var object[] $_installeds 모듈 설치 정보
     */
    private static array $_installeds = [];

    /**
     * @var object[] $_modules 설치된 모듈목록
     */
    private static array $_modules = [];

    /**
     * @var Module[] $_classes 모듈 클래스
     */
    private static array $_classes = [];

    /**
     * 모듈 클래스를 초기화한다.
     */
    public static function init()
    {
        if (self::$_init == true) {
            return;
        }

        /**
         * 설치된 모듈을 초기화한다.
         */
        $globals = [];
        foreach (
            self::db()
                ->select()
                ->from(self::table('modules'))
                ->get()
            as $module
        ) {
            $module->is_admin = $module->is_admin == 'TRUE';
            $module->is_global = $module->is_global == 'TRUE';
            $module->is_context = $module->is_context == 'TRUE';
            $module->is_widget = $module->is_widget == 'TRUE';
            $module->is_theme = $module->is_theme == 'TRUE';
            $module->is_cron = $module->is_cron == 'TRUE';
            $module->configs = json_decode($module->configs);
            $module->events = json_decode($module->events);

            self::$_modules[$module->name] = $module;

            /**
             * 모듈이 전역모듈일 경우
             */
            if ($module->is_global == true) {
                $globals[] = $module->name;
            }
        }

        /**
         * 전역모듈을 초기화한다.
         */
        foreach ($globals as $global) {
            self::get($global)->init();
        }

        /**
         * 모듈 라우터를 초기화한다.
         */
        Router::add('/module/{name}/process/{path}', '#', 'blob', ['Modules', 'doProcess']);
    }

    /**
     * 아이모듈 기본 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @return DatabaseInterface $interface
     */
    private static function db(): DatabaseInterface
    {
        return iModules::db();
    }

    /**
     * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
     *
     * @param string $table;
     * @return string $table;
     */
    private static function table(string $table): string
    {
        return iModules::table($table);
    }

    /**
     * 모듈클래스가 초기화되었는지 여부를 가져온다.
     *
     * @param string $name 모듈명
     * @param bool $init 초기화여부를 가져온뒤 초기화여부
     * @return bool $is_init
     */
    public static function isInits(string $name, bool $init = false): bool
    {
        $is_init = isset(self::$_inits[$name]) == true && self::$_inits[$name] == true;
        if ($init == true) {
            self::$_inits[$name] = true;
        }
        return $is_init;
    }

    /**
     * 전체모듈을 가져온다.
     *
     * @return object[] $modules
     */
    public static function all(): array
    {
        return array_values(self::$_modules);
    }

    /**
     * 모듈 클래스를 불러온다.
     *
     * @param string $name 모듈명
     * @param ?Route $route 모듈 컨텍스트가 시작된 경로
     * @return Module $class 모듈클래스 (모듈이 설치되어 있지 않은 경우 NULL 을 반환한다.)
     */
    public static function get(string $name, ?Route $route = null): Module
    {
        if (self::isInstalled($name) === false) {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }

        if ($route === null && isset(self::$_classes[$name]) == true) {
            return self::$_classes[$name];
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        $className = '\\modules\\' . implode('\\', $classPaths) . '\\' . $className;
        $class = new $className($route);
        if ($route === null) {
            self::$_classes[$name] = $class;
        }

        return $class;
    }

    /**
     * 모듈 패키지정보를 가져온다.
     *
     * @param string $name 모듈명
     * @return object $package
     */
    public static function getPackage(string $name): ?object
    {
        if (isset(self::$_packages[$name]) == true) {
            return self::$_packages[$name];
        }

        /**
         * 모듈 폴더가 존재하는지 확인한다.
         */
        if (is_dir(Configs::path() . '/modules/' . $name) == false) {
            self::$_packages[$name] = null;
            return null;
        }

        /**
         * 패키지파일이 존재하는지 확인한다.
         */
        if (is_file(Configs::path() . '/modules/' . $name . '/package.json') == false) {
            self::$_packages[$name] = null;
            return null;
        }

        self::$_packages[$name] = json_decode(
            file_get_contents(Configs::path() . '/modules/' . $name . '/package.json')
        );

        return self::$_packages[$name];
    }

    /**
     * 모듈 설치정보를 가져온다.
     *
     * @param string $name 모듈명
     * @return ?object $installed 모듈설치정보
     */
    public static function getInstalled(string $name): ?object
    {
        if (isset(self::$_installeds[$name]) == true) {
            return self::$_installeds[$name];
        }

        /**
         * 모듈 폴더가 존재하는지 확인한다.
         */
        if (is_dir(Configs::path() . '/modules/' . $name) == false) {
            self::$_installeds[$name] = null;
            return null;
        }

        /**
         * 모듈 클래스 파일이 존재하는지 확인한다.
         */
        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        if (class_exists('\\modules\\' . implode('\\', $classPaths) . '\\' . $className) == false) {
            self::$_installeds[$name] = null;
            return null;
        }

        /**
         * 모듈이 설치정보를 가져온다.
         */
        self::$_installeds[$name] = self::$_modules[$name] ?? null;

        return self::$_installeds[$name];
    }

    /**
     * 모듈이 설치되어 있는지 확인한다.
     *
     * @param string $name 모듈명
     * @return bool $is_installed 설치여부
     */
    public static function isInstalled(string $name): bool
    {
        return self::getInstalled($name) !== null;
    }

    /**
     * 설치된 모든 모듈의 자바스크립트 파일을 가져온다.
     *
     * @return array|string $scripts 모듈 자바스크립트
     */
    public static function scripts(): array|string
    {
        $modules = self::db()
            ->select()
            ->from(self::table('modules'))
            ->get('name');
        foreach ($modules as $name) {
            if (is_file(Configs::path() . '/modules/' . $name . '/scripts/' . ucfirst($name) . '.js') == true) {
                Cache::script('modules', '/modules/' . $name . '/scripts/' . ucfirst($name) . '.js');
            }
        }

        return Cache::script('modules');
    }

    /**
     * 모듈이 설치가능한지 확인한다.
     *
     * @param string $name 설치가능여부를 확인할 모듈명
     * @param bool $check_dependency 요구사항 확인여부
     * @return object $installable 설치가능여부
     */
    public static function installable(string $name, bool $check_dependency = true): object
    {
        $installable = new stdClass();
        $installable->success = false;
        $installable->exists = false;
        $installable->dependencies = [];

        $package = self::getPackage($name);
        if ($package == null) {
            return $installable;
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        if (class_exists('\\modules\\' . implode('\\', $classPaths) . '\\' . $className) == false) {
            return $installable;
        }

        $installable->exists = $package->version;

        if ($check_dependency == true) {
            $dependencies = $package->dependencies ?? [];
            foreach ($dependencies as $name => $version) {
                $installed = self::getInstalled($name);
                if ($installed == null || version_compare($installed->version, $version, '<=') == false) {
                    $installable->dependencies[$name] = new stdClass();
                    $installable->dependencies[$name]->current = $installed?->version ?? '0.0.0';
                    $installable->dependencies[$name]->requirement = $version;
                }
            }

            if (count($installable->dependencies) > 0) {
                return $installable;
            }
        }

        $installable->success = true;
        return $installable;
    }

    /**
     * 모듈을 설치한다.
     *
     * @param string $name 설치한 모듈명
     * @param bool $check_dependency 요구사항 확인여부
     * @return bool $success 설치성공여부
     */
    public static function install(string $name, bool $check_dependency = true): bool
    {
        $installable = self::installable($name, $check_dependency);
        if ($installable->success = false) {
            return false;
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        $class = '\\modules\\' . implode('\\', $classPaths) . '\\' . $className;

        $installed = self::getInstalled($name);
        $previous = $installed?->version ?? null;

        $success = $class::install($previous);

        return $success;
    }

    /**
     * 모듈 프로세스 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $name 모듈명
     * @param string $path 요청주소
     */
    public static function doProcess(Route $route, string $name, string $path): void
    {
        Header::type('json');

        $language = Request::languages(true);
        $route->setLanguage($language);
        $method = strtolower(Request::method());

        if ($method == 'post') {
            $input = new Input(file_get_contents('php://input'), $_SERVER['CONTENT_TYPE']);
        } else {
            $input = new Input(null);
        }

        $results = new stdClass();
        if (self::isInstalled($name) == true) {
            $mModule = self::get($name);
            call_user_func_array([$mModule, 'doProcess'], [&$results, $method, $path, &$input]);
            if (isset($results->success) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_MODULE_PROCESS', $name));
            }
        } else {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }

        exit(json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        $error = ErrorHandler::data();

        switch ($code) {
            case 'NOT_FOUND_MODULE':
                $error->message = ErrorHandler::getText($code, ['module' => $message]);
                $error->suffix = Request::url();
                break;

            case 'NOT_FOUND_MODULE_PROCESS':
                $error->message = ErrorHandler::getText($code, ['module' => $message]);
                $error->suffix = Request::url();
                break;

            case 'NOT_FOUND_MODULE_PROCESS_FILE':
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;
                break;

            default:
                return iModules::error($code, $message, $details);
        }

        return $error;
    }
}
