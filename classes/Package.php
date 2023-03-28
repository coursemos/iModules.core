<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 패키지설정을 처리하는 클래스를 정의한다.
 *
 * @file /classes/Package.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 21.
 */
class Package
{
    /**
     * @var object[] $_packages 패키지정보
     */
    private static array $_packages = [];

    /**
     * @var object[] $_hashes 해시정보
     */
    private static array $_hashes = [];

    /**
     * @var object $_package 패키지정보
     */
    private object $_package;

    /**
     * @var string $_hash 패키지 고유해시
     */
    private string $_hash;

    /**
     * 패키지 클래스를 정의한다.
     *
     * @param object $package 패키지파일 경로
     */
    public function __construct(string $path)
    {
        if (isset(self::$_packages[$path]) == false) {
            if (is_file(Configs::path() . $path) == true) {
                self::$_packages[$path] = json_decode(file_get_contents(Configs::path() . $path));
                self::$_hashes[$path] = md5_file(Configs::path() . $path);
            } else {
                self::$_packages[$path] = null;
                self::$_hashes[$path] = null;
            }
        }

        $this->_package = self::$_packages[$path];
        $this->_hash = self::$_hashes[$path];
    }

    /**
     * 패키지파일이 존재하는지 확인한다.
     *
     * @return bool $exists
     */
    public function exists(): bool
    {
        return $this->_package !== null;
    }

    /**
     * 패키지정보의 고유해시값을 가져온다.
     *
     * @return string $hash
     */
    public function getHash(): string
    {
        return $this->_hash;
    }

    /**
     * 패키지파일의 정보를 가져온다.
     *
     * @param string $key
     * @return mixed $value
     */
    public function get(string $key): mixed
    {
        return $this->_package?->$key ?? null;
    }

    /**
     * 아이콘 정보를 가져온다.
     *
     * @return string $icon
     */
    public function getIcon(): string
    {
        if ($this->_package === null) {
            return 'xi xi-unknown-square';
        }

        return $this->get('icon');
    }

    /**
     * 패키지 제목을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $title
     */
    public function getTitle($language = null): string
    {
        $language ??= Router::getLanguage();
        return $this->_package?->title?->$language ?? $this->_package?->title?->{$this->getLanguage()};
    }

    /**
     * 패키지 설명을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $description
     */
    public function getDescription($language = null): string
    {
        $language ??= Router::getLanguage();
        return $this->_package?->description?->$language ?? $this->_package?->description?->{$this->getLanguage()};
    }

    /**
     * 버전을 가져온다.
     *
     * @return string $version
     */
    public function getVersion(): string
    {
        return $this->get('version') ?? '0.0.0';
    }

    /**
     * 제작자를 가져온다.
     *
     * @param bool $is_link 링크여부
     * @return string $author
     */
    public function getAuthor(bool $is_link = false): string
    {
        $author = $this->get('author');
        if (is_string($author) == true) {
            return $author;
        }

        $name = $author?->name ?? 'Unknown';
        $email = $author?->email ?? null;

        if ($email !== null) {
            $email =
                '&lt;' . ($is_link == true ? '<a href="mailto:' . $email . '">' . $email . '</a>' : $email) . '&gt;';
        } else {
            $email = '';
        }

        return $name . $email;
    }

    /**
     * 제작자를 홈페이지를 가져온다..
     *
     * @param bool $is_link 링크여부
     * @return string $homepage
     */
    public function getHomepage(bool $is_link = false): string
    {
        $homepage = $this->get('homepage');
        if ($homepage == null) {
            return '';
        }

        if ($is_link == true) {
            return '<a href="' . $homepage . '" target="_blank">' . $homepage . '</a>';
        } else {
            return $homepage;
        }
    }

    /**
     * 패키지 기본 언어를 가져온다.
     *
     * @param bool $is_title 언어제목을 가져올지 여부
     * @return string $language
     */
    public function getLanguage(bool $is_title = false): string
    {
        $language = $this->get('language') ?? 'ko';

        if ($is_title == true) {
            return Language::getText('language', null, ['/'], [$language]);
        }
    }

    /**
     * 패키지 스타일시트를 불러온다.
     *
     * @return string[] $styles
     */
    public function getStyles(): array
    {
        return $this->_package?->styles ?? [];
    }

    /**
     * 패키지 스타일시트가 존재하는지 확인한다.
     *
     * @return bool $hasStyle
     */
    public function hasStyle(): bool
    {
        return count($this->getStyles()) > 0;
    }

    /**
     * 패키지 자바스크립트가 불러온다.
     *
     * @return string[] $scripts
     */
    public function getScripts(): array
    {
        return $this->_package?->scripts ?? [];
    }

    /**
     * 패키지 자바스크립트가 존재하는지 확인한다.
     *
     * @return bool $hasScript
     */
    public function hasScript(): bool
    {
        return count($this->getScripts()) > 0;
    }

    /**
     * 패키지의 데이터베이스 스키마를 가져온다.
     *
     * @return array $databases
     */
    public function getDatabases(): array
    {
        return $this->_package?->databases ?? [];
    }

    /**
     * 패키지의 환경설정을 가져온다.
     *
     * @param object $values 설정된 값
     * @return object $values 기본값을 포함된 설정된 값
     */
    public function getConfigs(object $values = null): object
    {
        $package = $this->_package?->configs ?? new stdClass();
        $configs = new stdClass();
        $keys = [];
        foreach ($package as $key => $config) {
            $keys[] = $key;
            $value = $values?->$key ?? null;

            switch ($config->type ?? '') {
                case 'theme':
                case 'template':
                    $temp = $value;
                    $value = new stdClass();
                    $value->name = $temp->name ?? ($config->default ?? 'default');
                    $value->configs = $temp->configs ?? null;
                    break;

                case 'color':
                    if ($value === null || preg_match('/^#[:alnum:]{6}$/', $value) == false) {
                        $value = $config->default ?? null;
                    }
                    break;

                default:
                    $value ??= $config->default ?? null;
            }

            $configs->$key = $value;
        }

        return $configs;
    }

    /**
     * 패키지의 환경설정필드를 가져온다.
     *
     * @param object $values 현재 설정된 값
     * @param string $language 언어코드
     * @return object[] $fields
     */
    public function getConfigFields(object $values = null, string $language = null): array
    {
        $language ??= Router::getLanguage();

        $package = $this->_package?->configs ?? new stdClass();
        $values = $this->getConfigs($values);
        $fields = [];
        foreach ($package as $name => $config) {
            $field = new stdClass();
            $field->name = $name;
            $field->label = $config->label?->$language ?? ($config->label?->{$this->getLanguage()} ?? $name);
            $field->type = $config->type ?? 'text';
            $field->options = [];
            foreach ($config->options ?? [] as $value => $display) {
                $option = new stdClass();
                $option->display = $display->$language ?? ($display->{$this->getLanguage()} ?? $value);
                $option->value = $value;
                $field->options[] = $option;
            }
            $field->value = $values->$name ?? null;

            $fields[] = $field;
        }

        return $fields;
    }
}
