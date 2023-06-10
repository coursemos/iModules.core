<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 위젯을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Widgets.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 25.
 */
class Widgets
{
    /**
     * @var bool $_init 초기화여부
     */
    private static bool $_init = false;

    /**
     * @var Package[] $_packages 모듈 패키지 정보
     */
    private static array $_packages = [];

    /**
     * @var bool[] $_installeds 위젯 설치 정보
     */
    private static array $_installeds = [];

    /**
     * 모듈 클래스를 초기화한다.
     */
    public static function init()
    {
        if (self::$_init == true) {
            return;
        }

        /**
         * 위젯 라우터를 초기화한다.
         */
        Router::add('/widget/{name}/process/{path}', '#', 'blob', ['Widgets', 'doProcess']);
    }

    /**
     * 위젯 클래스를 불러온다.
     *
     * @param string $name 위젯명
     * @param ?string $module 위젯을 불러올 모듈명 (NULL 인 경우 아이모듈 코어의 위젯을 가져온다.)
     * @return Widget $class 위젯클래스
     */
    public static function get(string $name, ?string $module = null): Widget
    {
        if ($module === null) {
            $parentName = '';
        } else {
            $modulePaths = explode('/', $module);
            $parentName = '\\modules\\' . implode('\\', $modulePaths);
        }

        $classPaths = explode('/', $name);
        $className = ucfirst(end($classPaths));
        $className = $parentName . '\\widgets\\' . implode('\\', $classPaths) . '\\' . $className;
        if (class_exists($className) == false) {
            ErrorHandler::print(self::error('NOT_FOUND_WIDGET', $name));
        }
        $class = new $className();

        return $class;
    }

    /**
     * 위젯 패키지정보를 가져온다.
     *
     * @param string $name 위젯명
     * @param ?string $module 위젯을 불러올 모듈명 (NULL 인 경우 아이모듈 코어의 위젯을 가져온다.)
     * @return Package $package
     */
    public static function getPackage(string $name, ?string $module = null): Package
    {
        if ($module === null) {
            $base = '';
        } else {
            $base = '/modules/' . $module;
        }
        $base .= '/widgets/' . $name;

        if (isset(self::$_packages[$base]) == true) {
            return self::$_packages[$base];
        }

        self::$_packages[$base] = new Package($base);

        return self::$_packages[$base];
    }

    /**
     * 위젯이 설치되어 있는지 확인한다.
     *
     * @param string $name 위젯명
     * @param string $module 위젯을 소유한 모듈명 (NULL 인 경우 아이모듈코어)
     * @return bool $is_installed 설치여부
     */
    public static function isInstalled(string $name, string $module = null): bool
    {
        if ($module === null) {
            $base = '';
        } else {
            $base = '/modules/' . $module;
        }
        $base .= '/widgets/' . $name;

        if (isset(self::$_installeds[$base]) == true) {
            return self::$_installeds[$base];
        }

        if ($module !== null) {
            if (Modules::get($module)->isInstalled() === false) {
                self::$_installeds[$base] = false;
                return self::$_installeds[$base];
            }
        }

        if (is_dir(Configs::path() . $base) == false || is_file(Configs::path() . $base . '/package.json') == false) {
            self::$_installeds[$base] = false;
        } else {
            self::$_installeds[$base] = true;
        }

        return self::$_installeds[$base];
    }

    /**
     * 위젯 프로세스 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $name 위젯명
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

        $paths = explode('/', $path);
        $process = array_shift($paths);
        $path = implode('/', $paths);

        if (self::isInstalled($name) == true) {
            $mWidget = self::get($name);
            $results = call_user_func_array([$mWidget, 'doProcess'], [$method, $process, $path, $input]);
            if (isset($results->success) == false) {
                ErrorHandler::print(self::error('NOT_FOUND_WIDGET_PROCESS', $name));
            }
        } else {
            ErrorHandler::print(self::error('NOT_FOUND_WIDGET', $name));
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
        $error = ErrorHandler::data();

        switch ($code) {
            case 'NOT_FOUND_WIDGET':
                $error->message = ErrorHandler::getText($code, ['widget' => $message]);
                $error->suffix = Request::url();
                break;

            case 'NOT_FOUND_WIDGET_PROCESS':
                $error->message = ErrorHandler::getText($code, ['widget' => $message]);
                $error->suffix = Request::url();
                break;

            case 'NOT_FOUND_WIDGET_PROCESS_FILE':
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;
                break;

            default:
                return iModules::error($code, $message, $details);
        }

        return $error;
    }
}
