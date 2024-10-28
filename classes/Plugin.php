<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈의 각 플러그인의 부모클래스를 정의한다.
 *
 * @file /classes/Plugin.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 28.
 */
abstract class Plugin extends Component
{
    /**
     * @var object $_configs 플러그인 환경설정
     */
    private object $_configs;

    /**
     * 플러그인을 초기화한다.
     */
    public function __construct()
    {
    }

    /**
     * 플러그인 설정을 초기화한다.
     */
    public function init(): void
    {
    }

    /**
     * 패키지의 플러그인 속성을 가져온다.
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
        if ($this->getPackage()->get('listeners') !== null) {
            $properties[] = 'LISTENERS';
        }
        if ($this->getPackage()->get('configs') !== null) {
            $properties[] = 'CONFIGS';
        }

        return $properties;
    }

    /**
     * 패키지의 플러그인 속성이 존재하는지 확인한다.
     *
     * @param string $property 확인할 속성
     * @return bool $hasProperty
     */
    final public function hasPackageProperty(string $property): bool
    {
        return in_array($property, $this->getPackageProperties());
    }

    /**
     * 플러그인의 데이터를 가져온다.
     *
     * @param string $key 가져올 데이터키
     * @return mixed $value 데이터값
     */
    final public function getData(string $key): mixed
    {
        return Plugins::getData($this->getName(), $key);
    }

    /**
     * 플러그인의 데이터를 저장한다.
     *
     * @param string $key 저장할 데이터키
     * @param mixed $value 저장할 데이터값
     * @return bool $success
     */
    final public function setData(string $key, mixed $value): bool
    {
        return Plugins::setData($this->getName(), $key, $value);
    }

    /**
     * 플러그인 설치정보를 가져온다.
     *
     * @return ?object $installed 플러그인설치정보
     */
    final public function getInstalled(): ?object
    {
        return Plugins::getInstalled($this->getName());
    }

    /**
     * 플러그인이 설치되어 있는지 확인한다.
     *
     * @return bool $is_installed 설치여부
     */
    final public function isInstalled(): bool
    {
        return $this->getInstalled() !== null;
    }

    /**
     * 플러그인을 업데이트해야하는 상태인지 확인한다.
     *
     * @return bool $is_updatable 업데이트여부
     */
    final public function isUpdatable(): bool
    {
        return $this->getPackage()->getHash() != $this->getInstalled()->hash ||
            Format::isEqual($this->getListeners(), $this->getInstalled()->listeners) == false;
    }

    /**
     * 프로세스 URL 을 가져온다.
     *
     * @param string $path 프로세스 경로
     * @return string $url
     */
    final public function getProcessUrl(string $path): string
    {
        return iModules::getProcessUrl('plugin', $this->getName(), $path);
    }

    /**
     * API URL 을 가져온다.
     *
     * @param string $path API 경로
     * @return string $url
     */
    final public function getApiUrl(string $path): string
    {
        return iModules::getApiUrl('plugin', $this->getName(), $path);
    }

    /**
     * 플러그인의 환경설정을 가져온다.
     *
     * @param ?string $key 환경설정코드값 (NULL인 경우 전체 환경설정값)
     * @return mixed $value 환경설정값
     */
    final public function getConfigs(?string $key = null): mixed
    {
        if (isset($this->_configs) == false) {
            $installed = Plugins::getInstalled($this->getName());
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
     * 플러그인 프로세스 라우팅을 처리한다.
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
     * 플러그인 API 라우팅을 처리한다.
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
            default:
                return ErrorHandler::error($code, $message, $details, $this);
        }
    }

    /**
     * 플러그인을 설치한다.
     * 플러그인을 설치할때 데이터 마이그레이션 등이 필요한 경우 해당 함수를 각 플러그인클래스에 재정의하여
     * 현재 설치되어 있는 버전에 따라 데이터 마이그레이션을 수행하고 신규버전 데이터베이스를 구성할 수 있다.
     *
     * @param string $previous 이전설치버전 (NULL 인 경우 신규설치)
     * @param object $configs 플러그인설정
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
