<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 환경설정 클래스를 정의한다.
 *
 * @file /classes/Configs.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class Configs
{
    /**
     * @var string $_path 절대경로
     */
    private static string $_path;

    /**
     * @var object $_configs 설치 환경설정 정보
     */
    private static object $_configs;

    /**
     * @var object $_configs package.json 정보
     */
    private static object $_package;

    /**
     * 환경설정을 초기화한다.
     *
     * @param object $configs 환경설정값
     */
    public static function init(object $configs): void
    {
        self::$_configs = $configs;
    }

    /**
     * 환경설정을 가져온다.
     *
     * @param string $key 가져올 설정값
     * @return string|object|null $value
     */
    public static function get(string $key): string|object|null
    {
        if (isset(self::$_configs) == false) {
            return null;
        }
        return self::$_configs?->{$key} ?? null;
    }

    /**
     * 패키지정보를 가져온다.
     *
     * @return object $package
     */
    public static function package(): object
    {
        if (empty(self::$_package) == true) {
            if (is_file(self::path() . '/package.json') == true) {
                self::$_package = json_decode(file_get_contents(self::path() . '/package.json'));
                if (self::$_package === null) {
                    ErrorHandler::print('PACKAGE_FILE_ERROR');
                }
            } else {
                ErrorHandler::print('NOT_FOUND_PACKAGE_FILE');
            }
        }

        return self::$_package;
    }

    /**
     * 절대경로를 설정한다.
     *
     * @param string $path
     */
    public static function setPath(string $path): void
    {
        self::$_path = $path;
    }

    /**
     * 절대경로를 가져온다.
     *
     * @return string $path
     */
    public static function path(): string
    {
        if (isset(self::$_path) == true) {
            return self::$_path;
        }

        $path = self::get('path') ?? str_replace('/classes', '', str_replace('\\', '/', __DIR__));
        return preg_replace('/\/$/', '', $path);
    }

    /**
     * 상대경로를 가져온다.
     *
     * @return string $dir
     */
    public static function dir(): string
    {
        if (isset(self::$_path) == true) {
            return str_replace($_SERVER['DOCUMENT_ROOT'], '', self::$_path);
        }

        $dir = self::get('dir') ?? str_replace($_SERVER['DOCUMENT_ROOT'], '', self::path());
        return preg_replace('/\/$/', '', $dir);
    }

    /**
     * 상대경로를 절대경로로 변경한다.
     *
     * @param string $dir 상대경로
     * @return string $path 절대경로
     */
    public static function dirToPath(string $dir): string
    {
        $dir = explode('?', $dir);
        $dir = $dir[0];
        return preg_replace('/^' . str_replace('/', '\\/', self::dir()) . '/', self::path(), $dir);
    }

    /**
     * 첨부파일경로를 가져온다.
     *
     * @return string $attachment
     */
    public static function attachment(): string
    {
        return self::get('attachment') ?? self::path() . '/attachments';
    }

    /**
     * 캐시파일경로를 가져온다.
     *
     * @return string $cache
     */
    public static function cache(bool $is_path = true): string
    {
        $path = self::get('cache') ?? self::attachment() . '/cache';
        return $is_path == true ? $path : preg_replace('/^' . Format::string(self::path(), 'reg') . '/', '', $path);
    }

    /**
     * package.json 의 configs 설정의 기본값을 가져온다.
     *
     * @param object $config 기본설정값
     * @param mixed $value 현재설정값
     * @return mixed $defaultValue
     */
    public static function getConfigsDefaultValue(object $config, mixed $value = null): mixed
    {
        switch ($config->type) {
            case 'theme':
            case 'template':
                if (
                    $value == null ||
                    is_object($value) == false ||
                    isset($value->name) == false ||
                    isset($value->configs) == false
                ) {
                    $defaultValue = new stdClass();
                    $defaultValue->name = $value?->name ?? $config->default;
                    $defaultValue->configs = $value?->configs ?? null;
                    return $defaultValue;
                } else {
                    return $value;
                }
                break;

            case 'color':
                if ($value == null || preg_match('/^#[:alnum:]{6}$/', $value) == false) {
                    return $config->default;
                } else {
                    return $value;
                }
                break;

            default:
                return $value != null ? $value : $config->default;
        }
    }

    /**
     * 요구사항을 확인한다.
     *
     * @return object $requirements
     */
    public static function requirements(): object
    {
        $results = new stdClass();
        $results->success = true;

        $package = Configs::package();

        $results->latest = new stdClass();
        $results->latest->status = 'success';
        $results->latest->current = $package->version;
        $results->latest->latest = $package->version;

        if (function_exists('curl_init') == true) {
            $apiUrl = 'https://api.moimz.com/tools/' . $package->id . '/latest/' . $package->version;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            if ($response !== null) {
                $results->latest->status = $response->is_latest == true ? 'success' : 'notice';
                $results->latest->latest = $response->latest;
            }
        }

        $results->configs = new stdClass();
        $results->configs->status = is_file(self::path() . '/configs/configs.php') == false ? 'success' : 'notice';

        foreach ($package->requirements as $key => $value) {
            $check = new stdClass();
            $check->status = 'fail';
            $check->requirement = $value;

            switch ($key) {
                case 'php':
                    $check->status = version_compare(PHP_VERSION, $value, '>=') == true ? 'success' : 'fail';
                    $check->current = PHP_VERSION;
                    break;

                case 'mysql_server':
                    $check->status = 'notice';
                    break;

                case 'mysql_client':
                    $check->status =
                        function_exists('mysqli_get_client_version') == true &&
                        version_compare(mysqli_get_client_version(), $value, '>=') == true
                            ? 'success'
                            : 'fail';
                    $check->current =
                        function_exists('mysqli_get_client_version') == true
                            ? mysqli_get_client_version()
                            : 'NOT_EXISTS';
                    break;

                case 'curl':
                    $check->status = function_exists('curl_init') == true ? 'success' : 'fail';
                    break;

                case 'zip':
                    $check->status = class_exists('ZipArchive') == true ? 'success' : 'fail';
                    break;

                case 'mbstring':
                    $check->status = function_exists('mb_strlen') == true ? 'success' : 'fail';
                    break;

                case 'gd':
                    $check->status = function_exists('ImageCreateFromJPEG') == true ? 'success' : 'fail';
                    break;

                case 'openssl':
                    $check->status = function_exists('openssl_encrypt') == true ? 'success' : 'fail';
                    break;

                case 'rewrite':
                    $check->status = 'notice';
                    break;
            }

            $results->success = $check->status === 'fail' ? false : $results->success;
            $results->$key = $check;
        }

        return $results;
    }

    /**
     * 설정파일을 가져온다.
     *
     * @return object $configs 기록된 정보
     */
    public static function read(): object
    {
        $configs = new stdClass();

        if (is_file(self::path() . '/configs/configs.php') == true) {
            $_CONFIGS = new stdClass();
            $_CONFIGS->db = new stdClass();
            require_once self::path() . '/configs/configs.php';
            self::init($_CONFIGS);
        }

        $configs = new stdClass();
        $configs->has_configs = is_file('../../configs/configs.php');
        $configs->path = self::path();
        $configs->dir = self::dir();
        $configs->dir = $configs->dir == '' ? '/' : '';
        $configs->attachment = self::attachment();
        $configs->cache = self::cache();
        $configs->prefix = self::get('prefix') ?? self::package()->prefix;

        return $configs;
    }

    /**
     * 설정파일을 기록한다.
     *
     * @param object $configs 설정정보
     * @return object $configs 기록된 정보
     */
    public static function write(object $configs): object
    {
        if ($configs->path) {
            self::setPath($configs->path);
        }

        $results = new stdClass();
        $need_to_write = false;
        if (is_file(self::path() . '/configs/configs.php') == true) {
            $_CONFIGS = new stdClass();
            $_CONFIGS->db = new stdClass();
            require_once self::path() . '/configs/configs.php';

            if ($_CONFIGS->key != $configs->key) {
                $results->success = false;
                $results->errors = ['key' => ''];
                return $results;
            }

            self::init($_CONFIGS);
        } else {
            $_CONFIGS = null;
            $need_to_write = true;
        }

        $errors = [];
        $path = preg_replace('/\/$/', '', $configs->path ?? self::path());
        if (is_dir($path) == false || is_file($path . '/package.json') == false) {
            $errors['path'] = $path;
        }
        $need_to_write = $need_to_write || $path != ($_CONFIGS?->path ?? '');

        $dir = preg_replace('/\/$/', '', $configs->dir ?? self::dir());
        $url = (Request::isHttps() == true ? 'https://' : 'http://') . Request::host() . $dir . '/package.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false || json_decode($response) === null) {
            $errors['dir'] = $dir == '' ? '/' : $dir;
        }
        $need_to_write = $need_to_write || $dir != ($_CONFIGS?->dir ?? '');

        $attachment = preg_replace('/\/$/', '', $configs->attachment ?? self::attachment());
        if (strlen($attachment) == 0 || is_dir($attachment) == false || is_writable($attachment) == false) {
            $errors['attachment'] = $attachment;
        }
        $need_to_write = $need_to_write || $attachment != ($_CONFIGS?->attachment ?? '');

        $cache = preg_replace('/\/$/', '', $configs->cache ?? self::cache());
        if (preg_match('/^' . Format::string($path, 'reg') . '/', $cache) == false) {
            $errors['cache'] = $cache;
        }
        if (strlen($cache) == 0 || is_dir($cache) == false || is_writable($cache) == false) {
            $errors['cache'] = $cache;
        }
        $need_to_write = $need_to_write || $cache != ($_CONFIGS?->cache ?? '');

        $db_host = $configs->db_host ?? self::get('db')?->host;
        $need_to_write = $need_to_write || $db_host != ($_CONFIGS?->db?->host ?? '');

        $db_port = $configs->db_port ?? self::get('db')?->port;
        $need_to_write = $need_to_write || $db_port != ($_CONFIGS?->db?->port ?? '');

        $db_id = $configs->db_id ?? self::get('db')?->id;
        $need_to_write = $need_to_write || $db_id != ($_CONFIGS?->db?->id ?? '');

        $db_password = $configs->db_password ?? self::get('db')?->password;
        $need_to_write = $need_to_write || $db_password != ($_CONFIGS?->db?->password ?? '');

        $db_database = $configs->db_database ?? self::get('db')?->database;
        $need_to_write = $need_to_write || $db_database != ($_CONFIGS?->db?->database ?? '');

        $prefix = $configs->prefix ?? (self::get('prefix') ?? self::package()->prefix);
        if (preg_match('/^[a-z_]*$/', $prefix) !== 1) {
            $errors['prefix'] = $prefix;
        }
        $need_to_write = $need_to_write || $prefix != ($_CONFIGS?->prefix ?? '');

        try {
            $mysqli = @new mysqli($db_host, $db_id, $db_password, $db_database, $db_port);
            $mysql_server = $mysqli->get_server_info();
            if (self::package()?->requirements?->mysql_server) {
                if (version_compare($mysql_server, self::package()->requirements->mysql_server, '<') == true) {
                    $errors['db_host'] = $db_host;
                    $errors['db_port'] = $db_port;
                    $errors['db_id'] = $db_id;
                    $errors['db_password'] = $db_password;
                    $errors['db_database'] = $db_database;

                    $errors['db'] = [
                        'text' => 'requirements.mysql_server.version_fail',
                        'replacements' => [
                            'requirement' => self::package()->requirements->mysql_server,
                            'current' => $mysql_server,
                        ],
                    ];
                }
            }
        } catch (mysqli_sql_exception $e) {
            $errors['db_host'] = $db_host;
            $errors['db_port'] = $db_port;
            $errors['db_id'] = $db_id;
            $errors['db_password'] = $db_password;
            $errors['db_database'] = $db_database;

            $errors['db'] = [
                'text' => 'requirements.mysql_server.fail',
                'replacements' => ['message' => $e->getMessage()],
            ];
        }

        $admin_email = $configs->admin_email ?? '';
        if (Format::checkEmail($admin_email) == false) {
            $errors['admin_email'] = $admin_email;
        }

        $admin_password = $configs->admin_password ?? '';
        if (Format::checkPassword($admin_password) == false) {
            $errors['admin_password'] = $admin_password;
        }

        $admin_name = $configs->admin_name ?? '';
        if (strlen($admin_name) == 0) {
            $errors['admin_name'] = $admin_name;
        }

        if (count($errors) > 0) {
            $results = new stdClass();
            $results->success = false;
            $results->errors = $errors;

            return $results;
        }

        if ($need_to_write == true) {
            $file = [
                '<?php',
                '$_CONFIGS->key = \'' . $configs->key . '\';',
                '$_CONFIGS->path = \'' . $path . '\';',
                '$_CONFIGS->dir = \'' . $dir . '\';',
                '$_CONFIGS->attachment = \'' . $attachment . '\';',
                '$_CONFIGS->cache = \'' . $cache . '\';',
                '$_CONFIGS->db->type = \'mysql\';',
                '$_CONFIGS->db->host = \'' . $db_host . '\';',
                '$_CONFIGS->db->port = \'' . $db_port . '\';',
                '$_CONFIGS->db->id = \'' . $db_id . '\';',
                '$_CONFIGS->db->password = \'' . $db_password . '\';',
                '$_CONFIGS->db->database = \'' . $db_database . '\';',
                '$_CONFIGS->prefix = \'' . $prefix . '\';',
            ];

            if (
                is_writable($path . '/configs/configs.php') == false ||
                file_put_contents($path . '/configs/configs.php', implode("\n", $file)) == false
            ) {
                $results->success = false;
                $results->code = str_replace('<?php', '&lt;?php', implode("\n", $file));
                $results->path = $path . '/configs';
                $results->file = $path . '/configs/configs.php';

                return $results;
            }
        }

        $results->success = true;
        $results->token = Password::encoder(
            json_encode([
                'admin_email' => $admin_email,
                'admin_password' => $admin_password,
                'admin_name' => $admin_name,
                'hash' => md5_file($path . '/configs/configs.php'),
                'lifetime' => time() + 60 * 60,
            ])
        );

        return $results;
    }

    /**
     * 디버깅모드여부를 가져온다.
     *
     * @todo 환경설정 적용
     * @return bool $is_debug_mode
     */
    public static function debug(): bool
    {
        return Request::get('debug') === 'true';
    }

    /**
     * 설치여부를 가져온다.
     *
     * @return bool $is_installed
     */
    public static function isInstalled(): bool
    {
        return is_file(self::path() . '/configs/configs.php');
    }

    /**
     * 메시지를 JSON 형태로 출력하고 코드실행을 중단한다.
     *
     * @param object|array $message 출력할 메시지
     */
    public static function exit(object|array $message): void
    {
        exit(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
