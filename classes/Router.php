<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 요청된 주소에 따른 경로를 처리한다.
 *
 * @file /classes/Router.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 24.
 */
class Router
{
    /**
     * @var string $_path 현재경로
     */
    private static string $_path;

    /**
     * @var string $_path 현재경로의 언어코드
     */
    private static string $_language;

    /**
     * @var Route[] $_routes 지정된 전체경로
     */
    private static array $_routes = [];

    /**
     * 현재 경로를 초기화한다.
     */
    public static function init(): void
    {
        if (isset(self::$_path) == true) {
            return;
        }

        $domain = Domains::has();
        $routes = self::stringToArray(Request::get('route') ?? '');
        if ($domain?->isInternationalization() == true) {
            if (count($routes) > 0 && preg_match('/^[a-z]{2}$/i', $routes[0]) == true) {
                $code = array_shift($routes);
                self::$_language = $code;
            } else {
                self::$_language = $domain->getLanguage();
            }
        } else {
            self::$_language = $domain?->getLanguage() ?? Request::languages(true);
        }

        self::$_path = '/' . implode('/', $routes);
    }

    /**
     * 현재 사이트에 경로를 추가한다.
     *
     * @param string $path 경로
     * @param string $language 언어코드 (* : 모든 언어, @ : 현재 사이트 언어, # : 언어 구분 없음)
     * @param string $type 종류(context, html, json, blob)
     * @param callable|Context $closure
     */
    public static function add(string $path, string $language, string $type, callable $closure): void
    {
        switch ($language) {
            /**
             * 도메인 전체 언어에 대하여 경로를 추가한다.
             */
            case '*':
                foreach (Domains::get()->getLanguages() as $language) {
                    self::$_routes['/' . $language . $path] = new Route($path, $language, $type, $closure);
                }
                break;

            /**
             * 언어구분 없이 경로를 추가한다.
             */
            case '#':
                self::$_routes[$path] = new Route($path, $language, $type, $closure);
                break;

            /**
             * 현재 사이트 언어에 경로를 추가한다.
             */
            case '@':
                $language = Sites::get()->getLanguage();

            default:
                self::$_routes['/' . $language . $path] = new Route($path, $language, $type, $closure);
        }
    }

    /**
     * 현재 경로에 해당하는 객체를 가져온다.
     *
     * @param string $path 가져올경로 (NULL 인 경우 현재경로)
     * @return Route $route
     */
    public static function get(string $path = null): Route
    {
        $route = self::has($path);
        if ($route === null) {
            ErrorHandler::print(ErrorHandler::error('NOT_FOUND_URL'));
        }

        return $route;
    }

    /**
     * 경로가 존재한다면 경로 객체를 반환한다.
     *
     * @param string $path 가져올경로 (NULL 인 경우 현재경로)
     * @return Route $route
     */
    public static function has(string $path = null): ?Route
    {
        $match = $path ?? self::getPath();
        if ($match == '/' && isset(self::$_routes['/']) == false) {
            Sites::get()->getIndex();
        }
        $paths = array_keys(self::$_routes);
        $matched = null;
        $matchedCount = 0;
        foreach ($paths as $path) {
            $matcher = str_replace('/', '\/', $path);
            $matcher = str_replace('*', '(.*?)', $matcher);
            $matcher = preg_replace('/{[^}]+}/', '(.*?)', $matcher);

            if (
                preg_match('/^' . $matcher . '$/', $match, $matches) == true ||
                preg_match('/^' . $matcher . '$/', '/' . self::getLanguage() . $match, $matches) == true
            ) {
                $statics = array_filter(
                    explode('/', preg_replace('/^\/' . self::getLanguage() . '\//', '', $path)),
                    function ($p) {
                        return $p !== '*';
                    }
                );

                if ($matched === null || $matchedCount <= count($statics)) {
                    $matched = $path;
                    $matchedCount = count($statics);
                }
            }
        }

        return $matched !== null ? self::$_routes[$matched] : null;
    }

    /**
     * 현재 경로를 가져온다.
     *
     * @return string $language
     */
    public static function getPath(): string
    {
        Router::init();
        return self::$_path;
    }

    /**
     * 현재 언어코드를 가져온다.
     *
     * @return string $language
     */
    public static function getLanguage(): string
    {
        Router::init();
        return self::$_language;
    }

    /**
     * 경로문자열을 경로배열로 변경한다.
     *
     * @param string $path
     * @return string[] $path
     */
    public static function stringToArray(string $route): array
    {
        $route = preg_replace('/^\/?(.*?)(\/)?$/', '\1', $route);
        return $route ? explode('/', $route) : [];
    }
}
