<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈, 플러그인, 위젯의 인터페이스 추상 클래스를 정의한다.
 *
 * @file /classes/Component.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 27.
 */
abstract class Component
{
    /**
     * @var \modules\admin\admin\Admin $_adminClass 관리자 클래스
     */
    private static \modules\admin\admin\Admin $_adminClass;

    /**
     * 컴포넌트 설정을 초기화한다.
     */
    abstract public function init(): void;

    /**
     * 각 컴포넌트에서 사용할 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @param ?string $name 데이터베이스 인터페이스 고유명
     * @param ?DatabaseConnector $connector 데이터베이스정보
     * @return DatabaseInterface $interface
     */
    public static function db(?string $name = null, ?DatabaseConnector $connector = null): DatabaseInterface
    {
        return Database::getInterface($name ?? self::getType() . '/' . self::getName(), $connector ?? Configs::db());
    }

    /**
     * 간략화된 테이블명으로 실제 데이터베이스 테이블명을 가져온다.
     *
     * @param string $table;
     * @return string $table;
     */
    public static function table(string $table): string
    {
        return iModules::table(self::getType() . '_' . str_replace('/', '_', self::getName()) . '_' . $table);
    }

    /**
     * 캐시데이터를 가져온다.
     *
     * @param string $name 캐시명
     * @param bool $lifetime 캐시유지시간
     * @param bool $is_raw RAW 데이터 여부
     * @return mixed $data 캐시데이터 (NULL 인 경우 캐시가 존재하지 않음)
     */
    public static function getCache(string $name, int $lifetime = 0, bool $is_raw = false): mixed
    {
        $cache = self::getType();
        if (self::getParentModule() !== null) {
            $cache .= '.' . self::getParentModule()->getName();
        }
        $cache .= '.' . self::getName();
        $cache .= '.' . $name;
        $cache = str_replace('/', '.', $cache);

        return Cache::get($cache, $lifetime, $is_raw);
    }

    /**
     * 캐시데이터를 제거한다.
     *
     * @param string $name 캐시명
     * @param mixed $data 캐시데이터
     * @param bool $is_raw RAW 데이터 여부
     * @return bool $success
     */
    public static function storeCache(string $name, mixed $data, bool $is_raw = false): bool
    {
        $cache = self::getType();
        if (self::getParentModule() !== null) {
            $cache .= '.' . self::getParentModule()->getName();
        }
        $cache .= '.' . self::getName();
        $cache .= '.' . $name;
        $cache = str_replace('/', '.', $cache);

        return Cache::store($cache, $data, $is_raw);
    }

    /**
     * 캐시데이터를 제거한다.
     *
     * @param string $name 캐시명
     * @param bool $is_raw RAW 데이터 여부
     */
    public static function removeCache(string $name, bool $is_raw = false): void
    {
        $cache = self::getType();
        if (self::getParentModule() !== null) {
            $cache .= '.' . self::getParentModule()->getName();
        }
        $cache .= '.' . self::getName();
        $cache .= '.' . $name;
        $cache = str_replace('/', '.', $cache);

        Cache::remove($cache, $is_raw);
    }

    /**
     * 언어팩 코드 문자열을 가져온다.
     *
     * @param string $text 코드
     * @param ?array $placeHolder 치환자
     * @return string|array $message 치환된 메시지
     */
    public static function getText(string $text, ?array $placeHolder = null): string|array
    {
        return Language::getText($text, $placeHolder, [self::getBase(), '/']);
    }

    /**
     * 언어팩 에러코드 문자열을 가져온다.
     *
     * @param string $error 에러코드
     * @param ?array $placeHolder 치환자
     * @return string $message 치환된 메시지
     */
    public static function getErrorText(string $error, ?array $placeHolder = null): string
    {
        return Language::getErrorText($error, $placeHolder, [self::getBase(), '/']);
    }

    /**
     * 컴포넌트명의 패키지 정보를 가져온다.
     *
     * @return Package $package
     */
    public static function getPackage(): Package
    {
        return new Package(self::getBase() . '/package.json');
    }

    /**
     * 컴포넌트 클래스를 호출한 클래스명을 정제하여 가져온다.
     *
     * @param bool $namespace_only 네임스페이스만 가져올지 여부
     * @return string $className
     */
    public static function getCalledName(bool $namespace_only = false): string
    {
        $called = str_replace('\\', '/', get_called_class());
        if (strpos($called, '/') !== 0) {
            $called = '/' . $called;
        }

        if ($namespace_only == true) {
            $called = preg_replace('/\/[^\/]+$/', '', $called);
        }

        return $called;
    }

    /**
     * 컴포넌트명을 가져온다.
     *
     * @return string $name
     */
    public static function getName(): string
    {
        $namespace = self::getCalledName(true);
        return explode('/' . self::getType() . 's/', $namespace)[1];
    }

    /**
     * 컴포넌트 클래스명을 가져온다.
     *
     * @return string $className
     */
    public static function getClassName(): string
    {
        $temp = explode('/', self::getName());
        return ucfirst(end($temp));
    }

    /**
     * 컴포넌트아이콘을 가져온다.
     *
     * @return string $icon
     */
    public static function getIcon(): string
    {
        $icon = self::getPackage()->getIcon() ?? 'xi xi-box';
        if (preg_match('/\.(gif|png|svg)$/', $icon) == true) {
            $iconUrl = self::getDir() . '/' . $icon;
            return Html::element(
                'i',
                ['class' => 'icon', 'style' => 'background-image:url(' . $iconUrl . '); background-color:#fff;'],
                ''
            );
        }

        return Html::element('i', ['class' => 'icon ' . $icon], '');
    }

    /**
     * 컴포넌트제목을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $title
     */
    public static function getTitle($language = null): string
    {
        return self::getPackage()->getTitle($language);
    }

    /**
     * 컴포넌트버전을 가져온다.
     *
     * @return string $version
     */
    public static function getVersion(): string
    {
        return self::getPackage()->getVersion();
    }

    /**
     * 모듈에 의하여 위젯 또는 플러그인 클래스가 호출된 경우, 해당 부모모듈 클래스를 가져온다.
     *
     * @return ?Module $parentModule
     */
    public static function getParentModule(): ?Module
    {
        if (in_array(self::getType(), ['widget', 'plugin']) == true) {
            $className = self::getCalledName();
            if (preg_match('/^\/modules/', $className) == true) {
                $className = preg_replace('/^\/modules\//', '', $className);
                $temp = explode('/' . self::getType() . 's/', $className);
                $parentName = $temp[0];

                return Modules::get($parentName);
            }

            return null;
        } else {
            return null;
        }
    }

    /**
     * 컴포넌트의 기본경로를 가져온다.
     *
     * @return string $base
     */
    public static function getBase(): string
    {
        if (self::getType() == 'module') {
            return '/' . self::getType() . 's/' . self::getName();
        } else {
            return (self::getParentModule()?->getBase() ?? '') . '/' . self::getType() . 's/' . self::getName();
        }
    }

    /**
     * 컴포넌트의 상태경로를 가져온다.
     *
     * @return string $dir
     */
    public static function getDir(): string
    {
        return Configs::dir() . self::getBase();
    }

    /**
     * 컴포넌트의 절대경로를 가져온다.
     *
     * @return string $path
     */
    public static function getPath(): string
    {
        return Configs::path() . self::getBase();
    }

    /**
     * 컴포넌트 종류를 가져온다.
     *
     * @return string $type 컴포넌트 종류(module, plugin, widget, component)
     */
    public static function getType(): string
    {
        $component = get_parent_class(get_called_class());
        if (in_array($component, ['Module', 'Widget', 'Plugin']) == true) {
            return strtolower($component);
        }

        return 'component';
    }

    /**
     * 컴포넌트의 관리자 클래스를 가져온다.
     *
     * @return ?\modules\admin\admin\Admin $adminClass
     */
    public function getAdmin(): ?\modules\admin\admin\Admin
    {
        if (isset(self::$_adminClass) == true) {
            return self::$_adminClass;
        }

        /**
         * @var \modules\admin\Admin $mAdmin
         */
        $mAdmin = Modules::get('admin');
        return $mAdmin->getAdminClass($this);
    }
}
