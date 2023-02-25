<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 클래스파일을 자동으로 불러오기위한 오토로더클래스를 정의한다.
 *
 * @file /classes/AutoLoader.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class AutoLoader
{
    /**
     * @var string $_path 루트폴더 경로
     */
    private static ?string $_path = null;

    /**
     * @var object[] $_loader AutoLoad 규칙을 저장한다.
     */
    private static array $_loader = [];

    /**
     * AutoLoader 클래스를 초기화한다.
     *
     * @param string $path 최상위폴더의 상대경로 (NULL 인 경우 환경설정을 따른다.)
     */
    public static function init(?string $path = null): void
    {
        self::$_path = $path;
        spl_autoload_register(['AutoLoader', 'loader']);
    }

    /**
     * 최상위 폴더의 상대경로를 가져온다.
     *
     * @return string $root
     */
    public static function getPath(): string
    {
        if (self::$_path === null) {
            return Configs::path();
        } else {
            return self::$_path;
        }
    }

    /**
     * 최상위 폴더의 상대경로를 설정한다.
     *
     * @param string $path
     */
    public static function setPath(string $path): void
    {
        self::$_path = $path;
    }

    /**
     * AutoLoader 를 정의한다.
     * \<NamespaceName>(\<SubNamespaceNames>)\<ClassName>
     * {$basePath}/<NamespaceName>(/<SubNamespaceNames>)/){$sourcePath}/<ClassName>.php
     *
     * @param string $basePath 클래스 정의파일을 찾기위한 기본 경로
     * @param string $sourcePath PSR-4 방식에 따른 클래스 파일 경로 이후 세부위치 (예 : /src)
     */
    public static function register(string $basePath = '', string $sourcePath = '/'): void
    {
        $loader = new stdClass();
        $loader->type = 'psr-4';
        $loader->basePath = $basePath;
        $loader->sourcePath = $sourcePath == '/' ? '' : $sourcePath;

        self::$_loader[] = $loader;
    }

    /**
     * spl_autoload_register callback 함수를 정의한다.
     *
     * @param string $class 클래스명
     * @return bool $success
     */
    public static function loader(string $class): bool
    {
        if (count(self::$_loader) == 0) {
            return false;
        }

        foreach (self::$_loader as $loader) {
            if ($loader->type == 'psr-4') {
                $namespaces = explode('\\', preg_replace('/^\\\/', '', $class));
                $className = array_pop($namespaces);
                if (count($namespaces) >= 2) {
                    $root = array_splice($namespaces, 0, 2);
                } else {
                    $root = [];
                }
                $path = self::getPath() . ($loader->basePath == '/' ? '' : $loader->basePath);
                if (count($root) > 0) {
                    $path .= '/' . implode('/', $root);
                }
                $path .= $loader->sourcePath;
                if (count($namespaces) > 0) {
                    $path .= '/' . implode('/', $namespaces);
                }
                $path .= '/' . $className . '.php';

                if (is_file($path) == true) {
                    require_once $path;
                    return true;
                }
            }
        }

        return false;
    }
}
