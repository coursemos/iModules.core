<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 모듈의 부모클래스를 정의한다.
 *
 * @file /classes/Module.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 23.
 */
abstract class Module extends Component
{
    /**
     * @var bool $_init 모듈 클래스가 초기화되었는지 여부
     */
    private bool $_init = false;

    /**
     * @var object $_configs 모듈 환경설정
     */
    private object $_configs;

    /**
     * @var Route $_route 모듈이 시작된 경로
     */
    private ?Route $_route = null;

    /**
     * @var object $_template 모듈의 컨텍스트 템플릿을 초기화한다.
     */
    private object $_template;

    /**
     * @var array $_contextAttributes 모듈의 콘텐츠 컨테이너의 속성값을 저장한다.
     */
    private array $_contextAttributes = [];

    /**
     * 모듈을 초기화한다.
     *
     * @param ?Route $route 모듈이 시작된 경로
     */
    public function __construct(?Route $route = null)
    {
        Modules::isInits($this->getName(), true);
        $this->_route = $route;
    }

    /**
     * 모듈 설정을 초기화한다.
     */
    public function init(): void
    {
        if ($this->_init == false) {
            $this->_init = true;
        }
    }

    /**
     * 패키지의 모듈 속성을 가져온다.
     *
     * @return string[] $properties
     */
    final public function getPackageProperties(): array
    {
        $properties = [];
        if (($this->getPackage()->get('global') === true) == true) {
            $properties[] = 'GLOBAL';
        }
        if ($this->getPackage()->get('admin') === true) {
            $properties[] = 'ADMIN';
        }
        if ($this->getPackage()->get('context') === true) {
            $properties[] = 'CONTEXT';
        }
        if ($this->getPackage()->get('widget') === true) {
            $properties[] = 'WIDGET';
        }
        if ($this->getPackage()->get('theme') === true) {
            $properties[] = 'THEME';
        }
        if ($this->getPackage()->get('cron') === true) {
            $properties[] = 'CRON';
        }
        if ($this->getPackage()->get('listeners') !== null) {
            $properties[] = 'LISTENERS';
        }
        if ($this->getPackage()->get('configs') !== null) {
            $properties[] = 'CONFIGS';
        }

        return $properties;
    }

    /**
     * 패키지의 모듈 속성이 존재하는지 확인한다.
     *
     * @param string $property 확인할 속성
     * @return bool $hasProperty
     */
    final public function hasPackageProperty(string $property): bool
    {
        return in_array($property, $this->getPackageProperties());
    }

    /**
     * 모듈의 데이터를 가져온다.
     *
     * @param string $key 가져올 데이터키
     * @return mixed $value 데이터값
     */
    final public function getData(string $key): mixed
    {
        return Modules::getData($this->getName(), $key);
    }

    /**
     * 모듈의 데이터를 저장한다.
     *
     * @param string $key 저장할 데이터키
     * @param mixed $value 저장할 데이터값
     * @return bool $success
     */
    final public function setData(string $key, mixed $value): bool
    {
        return Modules::setData($this->getName(), $key, $value);
    }

    /**
     * 모듈 설치정보를 가져온다.
     *
     * @return ?object $installed 모듈설치정보
     */
    final public function getInstalled(): ?object
    {
        return Modules::getInstalled($this->getName());
    }

    /**
     * 모듈이 설치되어 있는지 확인한다.
     *
     * @return bool $is_installed 설치여부
     */
    final public function isInstalled(): bool
    {
        return $this->getInstalled() !== null;
    }

    /**
     * 모듈을 업데이트해야하는 상태인지 확인한다.
     *
     * @return bool $is_updatable 업데이트여부
     */
    final public function isUpdatable(): bool
    {
        return $this->getPackage()->getHash() != $this->getInstalled()->hash ||
            Format::isEqual($this->getListeners(), $this->getInstalled()->listeners) == false;
    }

    /**
     * 모듈이 시작된 경로를 기준으로 특정위치의 경로를 가져온다.
     *
     * @param int $position 경로를 가져올 위치 (NULL 일 경우 전체 경로를 가져온다.)
     * @return ?string $path
     */
    final public function getRouteAt(int $position): ?string
    {
        $route = $this->_route ?? Router::get();
        $paths = explode('/', preg_replace('/^\//', '', $route->getSubPath()));
        return isset($paths[$position]) == true && strlen($paths[$position]) > 0 ? $paths[$position] : null;
    }

    /**
     * 모듈 URL 을 가져온다.
     *
     * @param string|int ...$paths 모듈 URL 에 추가할 내부 경로 (없는 경우 모듈 기본 URL만 가져온다.)
     * @return string $url
     */
    final public function getUrl(string|int ...$paths): string
    {
        $route = $this->_route ?? Router::get();
        $url = $route->getUrl();
        if (count($paths) > 0) {
            $url .= '/' . implode('/', $paths);
        }

        return $url;
    }

    /**
     * 프로세스 URL 을 가져온다.
     *
     * @param string $path 프로세스 경로
     * @return string $url
     */
    final public function getProcessUrl(string $path): string
    {
        return iModules::getProcessUrl('module', $this->getName(), $path);
    }

    /**
     * API URL 을 가져온다.
     *
     * @param string $path API 경로
     * @return string $url
     */
    final public function getApiUrl(string $path): string
    {
        return iModules::getApiUrl('module', $this->getName(), $path);
    }

    /**
     * 모듈의 컨텍스트 템플릿을 설정한다.
     *
     * @param object $template 템플릿설정
     */
    final public function setTemplate(object $template): self
    {
        $this->_template = $template;
        return $this;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 가져온다.
     *
     * @param ?object $template 템플릿설정
     * @return Template $template
     */
    final public function getTemplate(?object $template = null): Template
    {
        if ($template !== null) {
            return new Template($this, $template);
        } else {
            /**
             * 모듈의 컨텍스트 템플릿이 지정되지 않은 경우 에러메시지를 출력한다.
             */
            if (isset($this->_template) == false) {
                ErrorHandler::print($this->error('UNDEFINED_TEMPLATE'));
            }

            return new Template($this, $this->_template);
        }
    }

    /**
     * 모듈의 컨텍스트 템플릿을 확인한다.
     *
     * @return ?Template $template
     */
    final public function checkTemplate(): ?Template
    {
        if (isset($this->_template) == false) {
            return null;
        }

        return new Template($this, $this->_template);
    }

    /**
     * 모듈의 환경설정을 가져온다.
     *
     * @param ?string $key 환경설정코드값 (NULL인 경우 전체 환경설정값)
     * @return mixed $value 환경설정값
     */
    final public function getConfigs(?string $key = null): mixed
    {
        if (isset($this->_configs) == false) {
            $installed = Modules::getInstalled($this->getName());
            $configs = $installed?->configs ?? new stdClass();
            $this->_configs = $this->getPackage()->getConfigs($configs);
        }

        if ($key == null) {
            return $this->_configs;
        } elseif (isset($this->_configs->$key) == false) {
            return null;
        } else {
            return $this->_configs->$key;
        }
    }

    /**
     * 모듈의 컨텍스트 목록을 가져온다.
     * 컨텍스트를 지원하는 모듈이라면 모듈 클래스에서 getContexts() 메소드를 재정의하여야 한다.
     *
     * @return array $contexts 컨텍스트목록
     */
    public function getContexts(): array
    {
        return [['name' => null, 'title' => $this->getErrorText('NOT_FOUND_MODULE_CONTEXTS')]];
    }

    /**
     * 모듈의 컨텍스트 제목을 가져온다.
     *
     * @param string $name 컨텍스트명
     * @param string $title 컨텍스트제목
     */
    public function getContextTitle(string $name): string
    {
        $contexts = $this->getContexts();
        foreach ($contexts as $context) {
            if ($context['name'] == $name) {
                return $context['title'];
            }
        }

        return $name;
    }

    /**
     * 모듈의 컨텍스트 설정필드를 가져온다.
     * 컨텍스트를 지원하는 모듈이라면 모듈 클래스에서 getContextConfigsFields() 메소드를 재정의하여야 한다.
     * 모듈클래스에서 getContextConfigsFields() 메소드가 정의되어 있지 않다면, 이 메소드가 호출되며,
     * 기본적인 컨텍스트 템플릿 설정 필드를 반환한다.
     *
     * @return array $context 컨텍스트명
     * @return array $fields 설정필드목록
     */
    public function getContextConfigsFields(string $context): array
    {
        $template = [
            'name' => 'template',
            'label' => $this->getText('template'),
            'type' => 'template',
            'component' => [
                'type' => 'module',
                'name' => $this->getName(),
                'use_default' => true,
            ],
            'value' => 'default',
        ];

        return [$template];
    }

    /**
     * 모듈 컨텍스트를 가져온다.
     * 컨텍스트를 지원하는 모듈이라면 모듈 클래스에서 getContext() 메소드를 재정의하여야 한다.
     * 모듈클래스에서 getContext() 메소드가 정의되어 있지 않다면, 이 메소드가 호출되며,
     * 모듈에서 컨텍스트를 지원하지 않는다는 에러메시지를 출력한다.
     *
     * @param string $context 컨텍스트
     * @param ?object $configs 컨텍스트 설정
     * @return string $html
     */
    protected function getContext(string $context, ?object $configs = null): string
    {
        return ErrorHandler::get($this->error('NOT_FOUND_CONTEXT_METHOD', $context, $configs));
    }

    /**
     * 모듈 컨텍스트의 내용을 모듈 컨테이너와 함께 가져온다.
     *
     * @param string $context 컨텍스트
     * @param ?object $configs 컨텍스트 설정
     * @return string $html
     */
    final public function getContent(string $context, ?object $configs = null): string
    {
        $content = $this->getContext($context, $configs);

        $attributes = $this->_contextAttributes;
        $attributes['data-role'] ??= 'module';
        $attributes['data-module'] ??= $this->getName();
        $attributes['data-context'] ??= $context;
        $attributes['data-template'] ??= $this->checkTemplate()?->getName();

        return Html::element('div', $attributes, $content);
    }

    /**
     * 모듈 콘텐츠 컨테이터의 속성을 추가한다.
     * 각 모듈 클래스에서 콘텐츠영역에 추가할 속성이 있는 경우 사용한다.
     *
     * @param string $name 속성명
     * @param string $value 속성값
     */
    final protected function setContextAttribute(string $name, string $value): void
    {
        $this->_contextAttributes[$name] = $value;
    }

    /**
     * 모듈 프로세스 라우팅을 처리한다.
     *
     * @param string $method 요청방법
     * @param string $process 요청명
     * @param string $path 요청경로
     */
    public function doProcess(string $method, string $process, string $path): object
    {
        define('__IM_PROCESS__', true);

        $results = new stdClass();
        if (is_file($this->getPath() . '/processes/' . $process . '.' . $method . '.php') == true) {
            $stopped = Events::fireEvent(
                $this,
                'beforeDoProcess',
                [$this, $method, $process, $path, &$results],
                'FALSE'
            );
            if ($stopped !== false) {
                $values = File::execute(
                    $this->getPath() . '/processes/' . $process . '.' . $method . '.php',
                    [
                        'me' => &$this,
                        'results' => &$results,
                        'path' => $path,
                    ],
                    true
                );

                Events::fireEvent($this, 'afterDoProcess', [$this, $method, $process, $path, &$values, &$results]);
            }
        } else {
            ErrorHandler::print(
                $this->error(
                    'NOT_FOUND_PROCESS_FILE',
                    $this->getPath() . '/processes/' . $process . '.' . $method . '.php'
                )
            );
        }

        return $results;
    }

    /**
     * 모듈 API 라우팅을 처리한다.
     *
     * @param string $method 요청방법
     * @param string $api API명
     * @param string $path 요청경로
     */
    public function doApi(string $method, string $api, string $path): object
    {
        define('__IM_API__', true);

        $results = new stdClass();
        if (is_file($this->getPath() . '/apis/' . $api . '.' . $method . '.php') == true) {
            $stopped = Events::fireEvent($this, 'beforeDoApi', [$this, $method, $api, $path, &$results], 'FALSE');
            if ($stopped !== false) {
                $values = File::execute(
                    $this->getPath() . '/apis/' . $api . '.' . $method . '.php',
                    [
                        'me' => &$this,
                        'results' => &$results,
                        'path' => $path,
                    ],
                    true
                );

                Events::fireEvent($this, 'afterDoApi', [$this, $method, $api, $path, &$values, &$results]);
            }
        } else {
            ErrorHandler::print(
                $this->error('NOT_FOUND_API_FILE', $this->getPath() . '/apis/' . $api . '.' . $method . '.php')
            );
        }

        return $results;
    }

    /**
     * 특수한 에러코드의 경우 에러데이터를 클래스에서 처리하여 에러클래스로 전달한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return object $error
     */
    public function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_CONTEXT_METHOD':
                $error = ErrorHandler::data($code, $this);
                $error->message = ErrorHandler::getText($code, ['module' => $this->getName()]);
                $error->suffix =
                    '$context : ' .
                    $message .
                    ($details === null ? '' : '<br>$configs : ' . '<pre>' . Format::toJson($details) . '</pre>');
                $error->debugModeOnly = true;
                return $error;

            default:
                return ErrorHandler::error($code, $message, $details, $this);
        }
    }

    /**
     * 모듈을 설치한다.
     * 모듈을 설치할때 데이터 마이그레이션 등이 필요한 경우 해당 함수를 각 모듈클래스에 재정의하여
     * 현재 설치되어 있는 버전에 따라 데이터 마이그레이션을 수행하고 신규버전 데이터베이스를 구성할 수 있다.
     *
     * @param string $previous 이전설치버전 (NULL 인 경우 신규설치)
     * @param object $configs 모듈설정
     * @return bool|string $success 설치성공여부
     */
    public function install(string $previous = null, object $configs = null): bool|string
    {
        $db = $this->db();
        $db->displayError(false);
        $databases = $this->getPackage()->getDatabases();
        foreach ($databases as $table => $schema) {
            if ($db->compare($this->table($table), $schema, true) == false) {
                $success = $db->create($this->table($table), $schema);
                if ($success !== true) {
                    return $this->getErrorText('DATABASE_TABLE_CREATE_ERROR', [
                        'table' => $this->table($table),
                        'message' => $success,
                    ]);
                }
            }
        }

        return true;
    }
}
