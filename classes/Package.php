<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 패키지설정을 처리하는 클래스를 정의한다.
 *
 * @file /classes/Package.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 5. 24.
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
                self::$_packages[$path] = json_decode(File::read(Configs::path() . $path));
                self::$_hashes[$path] = File::hash(Configs::path() . $path);
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
     * 패키지 고유아이디를 가져온다.
     *
     * @return string $id
     */
    public function getId(): string
    {
        return $this->get('id');
    }

    /**
     * 패키지파일의 정보를 가져온다.
     *
     * @param string $path 가져올 정보 경로
     * @return mixed $value
     */
    public function get(string $path): mixed
    {
        $paths = explode('.', $path);
        $value = $this->_package;
        foreach ($paths as $path) {
            $value = $value?->$path ?? null;
        }
        return $value;
    }

    /**
     * 패키지파일의 정보에서 특정 언어코드에 해당하는 정보를 가져온다.
     *
     * @param string $path 가져올 정보 경로
     * @param string $language 가져올 언어코드
     * @return mixed $value
     */
    public function getByLanguage(string $path, string $language = null): mixed
    {
        $values = $this->get($path);
        if (is_object($values) === false) {
            return $values;
        }

        $language ??= Router::getLanguage();
        return $values?->$language ?? ($values->{$this->getLanguage()} ?? null);
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
        return $this->getByLanguage('title', $language) ?? 'NONAME';
    }

    /**
     * 패키지 설명을 가져온다.
     *
     * @param string $language 언어코드
     * @return string $description
     */
    public function getDescription($language = null): string
    {
        return $this->getByLanguage('description', $language) ?? '';
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
                '<small>&lt;' .
                ($is_link == true ? '<a href="mailto:' . $email . '">' . $email . '</a>' : $email) .
                '&gt;</small>';
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
    public function getDatabases(): object
    {
        return $this->_package?->databases ?? new stdClass();
    }

    /**
     * 설치요구사항을 가져온다.
     *
     * @return object $requirements
     */
    public function getRequirements(): object
    {
        return $this->_package?->requirements ?? new stdClass();
    }

    /**
     * 모듈 설치조건을 가져온다.
     *
     * @return object $dependencies
     */
    public function getDependencies(): object
    {
        return $this->_package?->dependencies ?? new stdClass();
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
        foreach ($package as $name => $field) {
            $field->type ??= 'text';

            if ($field->type == 'fieldset') {
                foreach ($field->items ?? [] as $childName => $childField) {
                    $configs->$childName = $this->getConfigsValue($childName, $childField, $values);
                }
            } else {
                $configs->$name = $this->getConfigsValue($name, $field, $values);
            }
        }

        return $configs;
    }

    /**
     * 패키지의 환경설정 필드값을 가져온다.
     *
     * @param string $name 필드명
     * @param object $field 필드설정
     * @param object $values 설정된 값
     * @return mixed $value 기본값을 포함된 설정된 값
     */
    private function getConfigsValue(string $name, object $field, object $values = null): mixed
    {
        $value = $values?->$name ?? null;

        switch ($field->type) {
            case 'theme':
            case 'template':
                $temp = $value;
                $value = new stdClass();
                $value->name = $temp->name ?? ($field->default ?? 'default');
                $value->configs = $temp->configs ?? null;
                break;

            case 'color':
                if ($value === null || preg_match('/^#[[:alnum:]]{6}$/', $value) == false) {
                    $value = $field->default ?? null;
                }
                break;

            case 'number':
                if ($value === null || is_numeric($value) == false) {
                    $value = null;
                } else {
                    $value = intval($value, 10);
                }
                $value ??= intval($field->default, 10) ?? null;
                break;

            default:
                $value ??= $field->default ?? null;
        }

        return $value;
    }

    /**
     * 패키지의 환경설정필드를 가져온다.
     *
     * @param object $values 현재 설정된 값
     * @param string $language 언어코드
     * @return object[] $fields
     */
    public function getConfigsFields(object $values = null, string $language = null): array
    {
        $language ??= Router::getLanguage();

        $package = $this->_package?->configs ?? new stdClass();
        $values = $this->getConfigs($values);
        $fields = [];
        foreach ($package as $name => $field) {
            $fields[] = $this->getConfigsField($name, $field, $values);
        }

        return $fields;
    }

    /**
     * 환경설정필드를 가져온다.
     *
     * @param string $name 필드명
     * @param object $configs 필드설정
     * @param object $values 현재 설정된 값
     * @return object $field
     */
    private function getConfigsField(string $name, object $configs, object $values = null): object
    {
        $language ??= Router::getLanguage();
        $field = new stdClass();
        $field->name = $name;
        $field->label = $configs->label?->$language ?? ($configs->label?->{$this->getLanguage()} ?? $name);
        $field->type = $configs->type ?? 'text';

        if (in_array($field->type, ['check', 'radio', 'select']) == true) {
            $field->options = [];
            foreach ($configs->options ?? [] as $value => $display) {
                $option = new stdClass();
                $option->display = $display->$language ?? ($display->{$this->getLanguage()} ?? $value);
                $option->value = $value;
                $field->options[] = $option;
            }
        }

        if ($field->type == 'template') {
            $field->component = new stdClass();
            $field->component->type = $configs->component?->type ?? '';
            $field->component->name = $configs->component?->name ?? '';
        }

        if ($field->type == 'fieldset') {
            $field->items = [];
            foreach ($configs->items ?? [] as $childName => $childConfigs) {
                $field->items[] = $this->getConfigsField($childName, $childConfigs, $values);
            }
        } else {
            $field->value = $values->$name ?? null;
        }

        return $field;
    }
}
