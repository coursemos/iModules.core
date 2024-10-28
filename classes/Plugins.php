<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 플러그인 관리하는 클래스를 정의한다.
 *
 * @file /classes/Plugins.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 28.
 */
class Plugins
{
    /**
     * @var bool $_init 초기화여부
     */
    private static bool $_init = false;

    /**
     * @var bool[] $_inits 플러그인별 초기화여부
     */
    private static array $_inits = [];

    /**
     * @var Package[] $_packages 플러그인 패키지 정보
     */
    private static array $_packages = [];

    /**
     * @var object[] $_plugins 설치된 플러그인클래스
     */
    private static array $_plugins = [];

    /**
     * @var object[] $_installeds 플러그인 설치 정보
     */
    private static array $_installeds = [];

    /**
     * @var Plugin[] $_classes 플러그인 클래스
     */
    private static array $_classes = [];

    /**
     * 플러그인 클래스를 초기화한다.
     */
    public static function init()
    {
        if (self::$_init == true) {
            return;
        }

        /**
         * 설치된 플러그인을 초기화한다.
         */
        if (false && Cache::has('plugins') === true) {
            self::$_plugins = Cache::get('plugins');
        } else {
            foreach (
                self::db()
                    ->select()
                    ->from(self::table('plugins'))
                    ->get()
                as $plugin
            ) {
                self::$_plugins[$plugin->name] = $plugin;
            }

            Cache::store('plugins', self::$_plugins);
        }

        /**
         * 전역플러그인을 초기화한다.
         */
        foreach (self::$_plugins as $plugin) {
            if ($plugin->is_global == 'TRUE') {
                self::get($plugin->name);
            }
        }

        /**
         * 플러그인 프로세스 라우터를 초기화한다.
         */
        Router::add('/plugin/{name}/process/{path}', '#', 'blob', ['Plugins', 'doProcess']);

        /**
         * 플러그인 API 라우터를 초기화한다.
         */
        Router::add('/plugin/{name}/api/{path}', '#', 'blob', ['Plugins', 'doApi']);
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
     * 플러그인클래스가 초기화되었는지 여부를 가져온다.
     *
     * @param string $name 플러그인명
     * @param bool $init 초기화여부를 가져온뒤 초기화여부
     * @return bool $is_init
     */
    public static function isInits(string $name): bool
    {
        return isset(self::$_inits[$name]) == true && self::$_inits[$name] == true;
    }

    /**
     * 전체플러그인을 가져온다.
     *
     * @param bool $is_installed 설치된 플러그인만 가져올지 여부
     * @return Plugin[] $plugins
     */
    public static function all(bool $is_installed = true): array
    {
        if ($is_installed === true) {
            $classes = [];
            foreach (self::$_plugins as $plugin) {
                $classes[] = self::get($plugin->name, false);
            }
            return $classes;
        } else {
            return self::explorer();
        }
    }

    /**
     * 플러그인폴더에 존재하는 모든 플러그인을 가져온다.
     *
     * @return array $plugins
     */
    private static function explorer(string $path = null): array
    {
        $plugins = [];
        $path ??= Configs::path() . '/plugins';
        $names = File::getDirectoryItems($path, 'directory', false);
        foreach ($names as $name) {
            if (is_file($name . '/package.json') == true) {
                array_push($plugins, self::get(str_replace(Configs::path() . '/plugins/', '', $name), false));
            } else {
                array_push($plugins, ...self::explorer($name));
            }
        }

        return $plugins;
    }

    /**
     * 플러그인 클래스를 불러온다.
     *
     * @param string $name 플러그인명
     * @param bool $is_init 플러그인 클래스를 정의하고 초기화할지 여부
     * @return Plugin $class 플러그인클래스
     */
    public static function get(string $name, bool $is_init = true): Plugin
    {
        if (isset(self::$_classes[$name]) == true) {
            $class = self::$_classes[$name];
        } else {
            $classPaths = explode('/', $name);
            $className = ucfirst(end($classPaths));
            $className = '\\plugins\\' . implode('\\', $classPaths) . '\\' . $className;
            if (class_exists($className) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_PLUGIN', $name));
            }
            $class = new $className();
        }

        self::$_classes[$name] = $class;

        if ($is_init == true && self::isInstalled($name) == true && self::isInits($name) == false) {
            $class->init();
            self::$_inits[$name] = true;
        }

        return $class;
    }

    /**
     * 플러그인 패키지정보를 가져온다.
     *
     * @param string $name 플러그인명
     * @return Package $package
     */
    public static function getPackage(string $name): Package
    {
        if (isset(self::$_packages[$name]) == true) {
            return self::$_packages[$name];
        }

        self::$_packages[$name] = new Package('/plugins/' . $name . '/package.json');

        return self::$_packages[$name];
    }

    /**
     * 플러그인 설치정보를 가져온다.
     *
     * @param string $name 플러그인명
     * @return ?object $installed 플러그인설치정보
     */
    public static function getInstalled(string $name): ?object
    {
        if (isset(self::$_installeds[$name]) == true) {
            return self::$_installeds[$name];
        }

        /**
         * 플러그인 폴더가 존재하는지 확인한다.
         */
        if (is_dir(Configs::path() . '/plugins/' . $name) == false) {
            self::$_installeds[$name] = null;
            return null;
        }

        /**
         * 설치정보가 존재하는지 확인한다.
         */
        if (isset(self::$_plugins[$name]) == false) {
            return null;
        }

        /**
         * 플러그인 클래스 파일이 존재하는지 확인한다.
         */
        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        if (class_exists('\\plugins\\' . implode('\\', $classPaths) . '\\' . $className) == false) {
            self::$_installeds[$name] = null;
            return null;
        }

        $installed = json_decode(json_encode(self::$_plugins[$name]));

        if ($installed !== null) {
            $installed->is_admin = $installed->is_admin == 'TRUE';
            $installed->is_global = $installed->is_global == 'TRUE';
            $installed->configs = json_decode($installed->configs);
            $installed->listeners = json_decode($installed->listeners ?? 'null');
        }

        /**
         * 플러그인이 설치정보를 가져온다.
         */
        self::$_installeds[$name] = $installed;

        return self::$_installeds[$name];
    }

    /**
     * 플러그인이 설치되어 있는지 확인한다.
     *
     * @param string $name 플러그인명
     * @return bool $is_installed 설치여부
     */
    public static function isInstalled(string $name): bool
    {
        return self::getInstalled($name) !== null;
    }

    /**
     * 플러그인 데이터를 가져온다..
     *
     * @param string $name 플러그인명
     * @param string $key 가져올 데이터키
     * @return mixed $value 데이터값
     */
    public static function getData(string $name, string $key): mixed
    {
        $installed = self::db()
            ->select()
            ->from(self::table('plugins'))
            ->where('name', $name)
            ->getOne();
        $dataset = json_decode($installed?->dataset ?? 'null');
        return $dataset?->{$key} ?? null;
    }

    /**
     * 플러그인 데이터를 저장한다.
     *
     * @param string $name 플러그인명
     * @param string $key 저장할 데이터키
     * @param mixed $value 저장할 데이터값
     * @return bool $success
     */
    public static function setData(string $name, string $key, mixed $value): bool
    {
        $installed = self::db()
            ->select()
            ->from(self::table('plugins'))
            ->where('name', $name)
            ->getOne();
        if ($installed === null) {
            return false;
        }

        $dataset = json_decode($installed->dataset ?? 'null') ?? new stdClass();
        $dataset->{$key} = $value;

        $success = self::db()
            ->update(self::table('plugins'), ['dataset' => Format::toJson($dataset)])
            ->where('name', $name)
            ->execute();

        return $success->success;
    }

    /**
     * 설치된 모든 플러그인의 자바스크립트 파일을 가져온다.
     *
     * @return array|string $scripts 플러그인 자바스크립트
     */
    public static function scripts(): array|string
    {
        foreach (self::all() as $plugin) {
            $filename = basename($plugin->getName());
            if (is_file($plugin->getPath() . '/scripts/' . ucfirst($filename) . '.js') == true) {
                Cache::script('plugins', $plugin->getBase() . '/scripts/' . ucfirst($filename) . '.js');
            }
        }

        return Cache::script('plugins');
    }

    /**
     * 설치된 모든 플러그인의 스타일시트 파일을 가져온다.
     *
     * @return array|string $styles 플러그인 스타일시트
     */
    public static function styles(): array|string
    {
        foreach (self::all() as $plugin) {
            $filename = basename($plugin->getName());
            if (is_file($plugin->getPath() . '/styles/' . ucfirst($filename) . '.scss') == true) {
                Cache::style('plugins', $plugin->getBase() . '/styles/' . ucfirst($filename) . '.scss');
            }
            if (is_file($plugin->getPath() . '/styles/' . ucfirst($filename) . '.css') == true) {
                Cache::style('plugins', $plugin->getBase() . '/styles/' . ucfirst($filename) . '.css');
            }
        }

        return Cache::style('plugins');
    }

    /**
     * 플러그인이 설치가능한지 확인한다.
     *
     * @param string $name 설치가능여부를 확인할 플러그인명
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
        if (class_exists('\\plugins\\' . implode('\\', $classPaths) . '\\' . $className) == false) {
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
     * 플러그인을 설치한다.
     *
     * @param string $name 설치한 플러그인명
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

        $plugin = self::get($name);
        if ($plugin === null) {
            return false;
        }

        $package = $plugin->getPackage();
        $installed = self::getInstalled($name);
        $previous = $installed?->version ?? null;

        $configs = $package->getConfigs($configs);
        $success = $plugin->install($previous, $configs);

        if ($success === true) {
            // @todo 용량 갱신
            $databases = $installed?->databases ?? 0;
            $attachments = $installed?->attachments ?? 0;
            $sort =
                $installed?->sort ??
                self::db()
                    ->select()
                    ->from(self::table('plugins'))
                    ->count();

            self::db()
                ->insert(
                    self::table('plugins'),
                    [
                        'name' => $name,
                        'version' => $package->getVersion(),
                        'hash' => $package->getHash(),
                        'databases' => $databases,
                        'attachments' => $attachments,
                        'is_admin' => $plugin->hasPackageProperty('ADMIN') == true ? 'TRUE' : 'FALSE',
                        'is_global' => $plugin->hasPackageProperty('GLOBAL') == true ? 'TRUE' : 'FALSE',
                        'configs' => Format::toJson($configs),
                        'listeners' => Format::toJson($plugin->getListeners(), true),
                        'updated_at' => time(),
                        'sort' => $sort,
                    ],
                    [
                        'version',
                        'hash',
                        'databases',
                        'attachments',
                        'is_admin',
                        'is_global',
                        'configs',
                        'listeners',
                        'updated_at',
                    ]
                )
                ->execute();
        }

        return $success;
    }

    /**
     * 플러그인 프로세스 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $name 플러그인명
     * @param string $path 요청주소
     */
    public static function doProcess(Route $route, string $name, string $path): void
    {
        Header::type('json');
        iModules::session_start();
        $language = Request::languages(true);
        $route->setLanguage($language);
        $method = strtolower(Request::method());

        $paths = explode('/', $path);
        $process = array_shift($paths);
        $path = implode('/', $paths);

        if (self::isInstalled($name) == true) {
            $mPlugin = self::get($name);
            $results = call_user_func_array([$mPlugin, 'doProcess'], [$method, $process, $path]);
            if (isset($results->success) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_MODULE_PROCESS', $name));
            }
        } else {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }

        if (Header::length() !== null) {
            exit(str_pad(Format::toJson($results), Header::length()));
        } else {
            exit(Format::toJson($results));
        }
    }

    /**
     * 플러그인 API 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $name 플러그인명
     * @param string $path 요청주소
     */
    public static function doApi(Route $route, string $name, string $path): void
    {
        Header::type('json');
        Header::cors();
        $language = Request::languages(true);
        $route->setLanguage($language);
        $method = strtolower(Request::method());

        $paths = explode('/', $path);
        $api = array_shift($paths);
        $path = implode('/', $paths);

        if (self::isInstalled($name) == true) {
            $mPlugin = self::get($name);
            $results = call_user_func_array([$mPlugin, 'doApi'], [$method, $api, $path]);
            if (isset($results->success) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_MODULE_API', $name));
            }
        } else {
            ErrorHandler::print(self::error('NOT_FOUND_MODULE', $name));
        }

        if (Header::length() !== null) {
            exit(str_pad(Format::toJson($results), Header::length()));
        } else {
            exit(Format::toJson($results));
        }
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
                $error->message = ErrorHandler::getText($code, ['plugin' => $message]);
                $error->suffix = Request::url();
                return $error;

            case 'NOT_FOUND_MODULE_PROCESS':
                $error = ErrorHandler::data($code);
                $error->message = ErrorHandler::getText($code, ['plugin' => $message]);
                $error->suffix = Request::url();
                return $error;

            case 'NOT_FOUND_MODULE_API':
                $error = ErrorHandler::data($code);
                $error->message = ErrorHandler::getText($code, ['plugin' => $message]);
                $error->suffix = Request::url();
                return $error;

            default:
                return ErrorHandler::error($code, $message, $details);
        }
    }
}
