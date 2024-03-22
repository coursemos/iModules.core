<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Modules.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 3. 22.
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
     * @var Package[] $_packages 모듈 패키지 정보
     */
    private static array $_packages = [];

    /**
     * @var object[] $_installeds 모듈 설치 정보
     */
    private static array $_installeds = [];

    /**
     * @var Module[] $_modules 설치된 모듈클래스
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
        if (Cache::has('modules') === true) {
            self::$_modules = Cache::get('modules');
        } else {
            foreach (
                self::db()
                    ->select()
                    ->from(self::table('modules'))
                    ->get('name')
                as $name
            ) {
                self::$_modules[$name] = self::get($name);
            }

            Cache::store('modules', self::$_modules);
        }

        /**
         * 전역모듈을 초기화한다.
         */
        foreach (self::$_modules as $module) {
            if ($module->hasPackageProperty('GLOBAL') === true) {
                $module->init();
            }
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
     * @param bool $is_installed 설치된 모듈만 가져올지 여부
     * @return Module[] $modules
     */
    public static function all(bool $is_installed = true): array
    {
        if ($is_installed === true) {
            return array_values(self::$_modules);
        } else {
            return self::explorer();
        }
    }

    /**
     * 모듈폴더에 존재하는 모든 모듈을 가져온다.
     *
     * @return array $modules
     */
    private static function explorer(string $path = null): array
    {
        $modules = [];
        $path ??= Configs::path() . '/modules';
        $names = File::getDirectoryItems($path, 'directory', false);
        foreach ($names as $name) {
            if (is_file($name . '/package.json') == true) {
                array_push($modules, self::get(str_replace(Configs::path() . '/modules/', '', $name)));
            } else {
                array_push($modules, ...self::explorer($name));
            }
        }

        return $modules;
    }

    /**
     * 모듈 클래스를 불러온다.
     *
     * @param string $name 모듈명
     * @param ?Route $route 모듈 컨텍스트가 시작된 경로
     * @return Module $class 모듈클래스
     */
    public static function get(string $name, ?Route $route = null): Module
    {
        if ($route === null && isset(self::$_modules[$name]) == true) {
            return self::$_modules[$name];
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        $className = '\\modules\\' . implode('\\', $classPaths) . '\\' . $className;
        if (class_exists($className) == false) {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }
        $class = new $className($route);

        return $class;
    }

    /**
     * 모듈 패키지정보를 가져온다.
     *
     * @param string $name 모듈명
     * @return Package $package
     */
    public static function getPackage(string $name): Package
    {
        if (isset(self::$_packages[$name]) == true) {
            return self::$_packages[$name];
        }

        self::$_packages[$name] = new Package('/modules/' . $name . '/package.json');

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

        $installed = self::db()
            ->select()
            ->from(self::table('modules'))
            ->where('name', $name)
            ->getOne();

        if ($installed !== null) {
            $installed->is_admin = $installed->is_admin == 'TRUE';
            $installed->is_global = $installed->is_global == 'TRUE';
            $installed->is_context = $installed->is_context == 'TRUE';
            $installed->is_widget = $installed->is_widget == 'TRUE';
            $installed->is_theme = $installed->is_theme == 'TRUE';
            $installed->is_cron = $installed->is_cron == 'TRUE';
            $installed->configs = json_decode($installed->configs);
            $installed->events = json_decode($installed->events);
        }

        /**
         * 모듈이 설치정보를 가져온다.
         */
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
     * 모듈 데이터를 가져온다..
     *
     * @param string $name 모듈명
     * @param string $key 가져올 데이터키
     * @return mixed $value 데이터값
     */
    public static function getData(string $name, string $key): mixed
    {
        $installed = self::db()
            ->select()
            ->from(self::table('modules'))
            ->where('name', $name)
            ->getOne();
        $dataset = json_decode($installed?->dataset ?? 'null');
        return $dataset?->{$key} ?? null;
    }

    /**
     * 모듈 데이터를 저장한다.
     *
     * @param string $name 모듈명
     * @param string $key 저장할 데이터키
     * @param mixed $value 저장할 데이터값
     * @return bool $success
     */
    public static function setData(string $name, string $key, mixed $value): bool
    {
        $installed = self::db()
            ->select()
            ->from(self::table('modules'))
            ->where('name', $name)
            ->getOne();
        if ($installed === null) {
            return false;
        }

        $dataset = json_decode($installed->dataset ?? 'null') ?? new stdClass();
        $dataset->{$key} = $value;

        $success = self::db()
            ->update(self::table('modules'), ['dataset' => Format::toJson($dataset)])
            ->where('name', $name)
            ->execute();

        return $success->success;
    }

    /**
     * 설치된 모든 모듈의 자바스크립트 파일을 가져온다.
     *
     * @return array|string $scripts 모듈 자바스크립트
     */
    public static function scripts(): array|string
    {
        foreach (self::all() as $module) {
            $filename = basename($module->getName());
            if (is_file($module->getPath() . '/scripts/' . ucfirst($filename) . '.js') == true) {
                Cache::script('modules', $module->getBase() . '/scripts/' . ucfirst($filename) . '.js');
            }
        }

        return Cache::script('modules');
    }

    /**
     * 설치된 모든 모듈의 스타일시트 파일을 가져온다.
     *
     * @return array|string $styles 모듈 스타일시트
     */
    public static function styles(): array|string
    {
        foreach (self::all() as $module) {
            $filename = basename($module->getName());
            if (is_file($module->getPath() . '/styles/' . ucfirst($filename) . '.scss') == true) {
                Cache::style('modules', $module->getBase() . '/styles/' . ucfirst($filename) . '.scss');
            }
            if (is_file($module->getPath() . '/styles/' . ucfirst($filename) . '.css') == true) {
                Cache::style('modules', $module->getBase() . '/styles/' . ucfirst($filename) . '.css');
            }
        }

        return Cache::style('modules');
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
        if ($package->exists() == false) {
            return $installable;
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        if (class_exists('\\modules\\' . implode('\\', $classPaths) . '\\' . $className) == false) {
            return $installable;
        }

        $installable->exists = $package->getVersion();

        if ($check_dependency == true) {
            $dependencies = $package->getDependencies();
            foreach ($dependencies as $name => $version) {
                if ($name == 'core') {
                    $core = new Package('/package.json');
                    if (version_compare($core->getVersion(), $version, '<') == true) {
                        $installable->dependencies[$name] = new stdClass();
                        $installable->dependencies[$name]->current = $core->getVersion() ?? '0.0.0';
                        $installable->dependencies[$name]->requirement = $version;
                    }
                } else {
                    $installed = self::getInstalled($name);
                    if ($installed == null || version_compare($installed->version, $version, '<') == true) {
                        $installable->dependencies[$name] = new stdClass();
                        $installable->dependencies[$name]->current = $installed?->version ?? '0.0.0';
                        $installable->dependencies[$name]->requirement = $version;
                    }
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
     * @param object $configs 환경설정
     * @param bool $check_dependency 요구사항 확인여부
     * @return bool|string $success 설치성공여부
     */
    public static function install(string $name, object $configs = null, bool $check_dependency = true): bool|string
    {
        $installable = self::installable($name, $check_dependency);
        if ($installable->success = false) {
            return false;
        }

        $module = self::get($name);
        if ($module === null) {
            return false;
        }

        $package = $module->getPackage();
        $installed = self::getInstalled($name);
        $previous = $installed?->version ?? null;

        $configs = $package->getConfigs($configs);
        $success = $module->install($previous, $configs);

        if ($success === true) {
            // @todo 용량 갱신
            $databases = $installed?->databases ?? 0;
            $attachments = $installed?->attachments ?? 0;
            $sort =
                $installed?->sort ??
                self::db()
                    ->select()
                    ->from(self::table('modules'))
                    ->count();

            self::db()
                ->insert(
                    self::table('modules'),
                    [
                        'name' => $name,
                        'version' => $package->getVersion(),
                        'hash' => $package->getHash(),
                        'databases' => $databases,
                        'attachments' => $attachments,
                        'is_admin' => $module->hasPackageProperty('ADMIN') == true ? 'TRUE' : 'FALSE',
                        'is_global' => $module->hasPackageProperty('GLOBAL') == true ? 'TRUE' : 'FALSE',
                        'is_context' => $module->hasPackageProperty('CONTEXT') == true ? 'TRUE' : 'FALSE',
                        'is_widget' => $module->hasPackageProperty('WIDGET') == true ? 'TRUE' : 'FALSE',
                        'is_theme' => $module->hasPackageProperty('THEME') == true ? 'TRUE' : 'FALSE',
                        'is_cron' => $module->hasPackageProperty('CRON') == true ? 'TRUE' : 'FALSE',
                        'configs' => Format::toJson($configs),
                        'events' => 'null',
                        'sort' => $sort,
                    ],
                    [
                        'version',
                        'hash',
                        'databases',
                        'attachments',
                        'is_admin',
                        'is_global',
                        'is_context',
                        'is_widget',
                        'is_theme',
                        'is_cron',
                        'configs',
                        'events',
                    ]
                )
                ->execute();
        }

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

        $paths = explode('/', $path);
        $process = array_shift($paths);
        $path = implode('/', $paths);

        if (self::isInstalled($name) == true) {
            $mModule = self::get($name);
            $results = call_user_func_array([$mModule, 'doProcess'], [$method, $process, $path]);
            if (isset($results->success) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_MODULE_PROCESS', $name));
            }
        } else {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }

        exit(Format::toJson($results));
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
        switch ($code) {
            case 'NOT_FOUND_MODULE':
                $error = ErrorHandler::data($code);
                $error->message = ErrorHandler::getText($code, ['module' => $message]);
                $error->suffix = Request::url();
                return $error;

            case 'NOT_FOUND_MODULE_PROCESS':
                $error = ErrorHandler::data($code);
                $error->message = ErrorHandler::getText($code, ['module' => $message]);
                $error->suffix = Request::url();
                return $error;

            default:
                return ErrorHandler::error($code, $message, $details);
        }
    }
}
