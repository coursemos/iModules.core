<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 모듈, 플러그인, 위젯의 인터페이스 추상 클래스를 정의한다.
 *
 * @file /classes/Component.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 21.
 */
abstract class Component
{
    /**
     * 컴포넌트 설정을 초기화한다.
     */
    abstract public function init(): void;

    /**
     * 각 컴포넌트에서 사용할 데이터베이스 인터페이스 클래스를 가져온다.
     *
     * @param ?string $name 데이터베이스 인터페이스 고유명
     * @param ?object $connector 데이터베이스정보
     * @return DatabaseInterface $interface
     */
    public static function db(?string $name = null, ?object $connector = null): DatabaseInterface
    {
        return Database::getInterface(
            $name ?? self::getType() . '/' . self::getName(),
            $connector ?? Configs::get('db')
        );
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
     * @param string $code 에러코드
     * @param ?array $placeHolder 치환자
     * @return string $message 치환된 메시지
     */
    public static function getErrorText(string $code, ?array $placeHolder = null): string
    {
        return self::getText('errors/' . $code, $placeHolder);
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
     * 컴포넌트명을 가져온다.
     *
     * @return string $module
     */
    public static function getName(): string
    {
        $className = str_replace('\\', '/', get_called_class());
        $namespace = preg_replace('/\/[^\/]+$/', '', $className);
        return preg_replace('/^\/?' . self::getType() . 's\//', '', $namespace);
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
            return '<i class="icon" style="background-image:url(' . self::getDir() . '/' . $icon . ');"></i>';
        }
        return '<i class="icon ' . $icon . '"></i>';
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
    public function getVersion(): string
    {
        return self::getPackage()->getVersion();
    }

    /**
     * 컴포넌트의 기본경로를 가져온다.
     *
     * @return string $base
     */
    public static function getBase(): string
    {
        return '/' . self::getType() . 's/' . self::getName();
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
     * @return string $type 컴포넌트 종류(module, plugin, widget)
     */
    public static function getType(): string
    {
        /**
         * 컴포넌트 종류를 가져온다.
         */
        $regExp = '/^(module|plugin|widget)s\\\/';
        if (preg_match($regExp, get_called_class(), $match) == true) {
            return $match[1];
        } else {
            return null;
        }
    }
}
