<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Modules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
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
     * @var Module[] $_modules 모듈 클래스
     */
    private static array $_modules = [];

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
        $modules = self::db()
            ->select()
            ->from(self::table('modules'))
            ->get();
        foreach ($modules as $module) {
            /**
             * 모듈이 라우터를 가질 경우, 경로를 지정한다.
             */
            if ($module->is_router == 'TRUE') {
                $class = self::get($module->name)->init();
            }
        }
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

        if ($route === null && isset(self::$_modules[$name]) == true) {
            return self::$_modules[$name];
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        $className = '\\modules\\' . implode('\\', $classPaths) . '\\' . $className;
        $class = new $className($route);
        if ($route === null) {
            self::$_modules[$name] = $class;
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
        $installed = self::db()
            ->select()
            ->from(self::table('modules'))
            ->where('name', $name)
            ->getOne();
        $installed->configs = json_decode($installed->configs);

        self::$_installeds[$name] = $installed;

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
    public static function script(): array|string
    {
        $modules = self::db()
            ->select()
            ->from(self::table('modules'))
            ->get('name');
        foreach ($modules as $name) {
            if (is_file(Configs::path() . '/modules/' . $name . '/scripts/Module' . ucfirst($name) . '.js') == true) {
                Cache::script('modules', '/modules/' . $name . '/scripts/Module' . ucfirst($name) . '.js');
            }
        }

        return Cache::script('modules');
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

            default:
                return iModules::error($code, $message, $details);
        }

        return $error;
    }
}
