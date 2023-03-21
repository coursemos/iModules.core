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
     * @var object $_package 패키지정보
     */
    private object $_package;

    /**
     * 패키지 클래스를 정의한다.
     *
     * @param object $package 패키지파일 경로
     */
    public function __construct(string $path)
    {
        if (isset(self::$_packages[$path]) == false) {
            self::$_packages[$path] = json_decode(file_get_contents(Configs::path() . $path));
        }

        $this->_package = self::$_packages[$path];
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
        return $this->_package?->title?->$language ?? $this->_package?->title?->{$this->_package?->language};
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
        $configs = $this->_package?->configs ?? new stdClass();
        $keys = [];
        foreach ($configs as $key => $config) {
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

        foreach ($configs as $key => $value) {
            if (in_array($key, $keys) == false) {
                unset($configs->$key);
            }
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

        $configs = $this->_package?->configs ?? new stdClass();
        $fields = [];
        foreach ($configs as $name => $config) {
            $field = new stdClass();
            $field->name = $name;
            $field->label = $config->label?->$language ?? ($config->label?->{$this->_package?->language} ?? $name);
            $field->default = $config->default ?? null;
            $field->type = $config->type ?? 'text';
            $field->options = [];
            foreach ($config->options ?? [] as $value => $display) {
                $option = new stdClass();
                $option->display = $display->$language ?? ($display->{$this->_package?->language} ?? $value);
                $option->value = $value;
                $field->options[] = $option;
            }
            $field->value = $values->$name ?? null;

            $fields[] = $field;
        }

        return $fields;
    }
}
