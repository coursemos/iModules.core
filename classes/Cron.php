<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 자동화 작업을 수행하기 위한 클래스를 정의한다.
 *
 * @file /classes/Cron.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 3. 4.
 */
class Cron
{
    /**
     * @var bool $is_ssh SSH 클라이언트를 통해 실행중인지 여부
     */
    private static bool $is_ssh = false;

    /**
     * @var bool $linebreak 자동화실행 중 출력한 메시지에서 줄바꿈을 출력하였는지 여부
     */
    private static bool $linebreak = false;

    /**
     * @var int $progress 자동화실행 중 진행률을 출력한 횟수
     */
    private static int $progress = 0;

    /**
     * 자동화 작업 클래스를 초기화한다.
     */
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        Modules::init();

        self::$is_ssh = isset($_SERVER['SSH_CLIENT']) == true;
    }

    /**
     * 자동화 작업을 수행한다.
     */
    public function execute(): void
    {
        if (defined('__IM_CRON__') == false) {
            exit();
        }

        /**
         * 자동화작업이 존재하는 모듈을 불러온다.
         */
        foreach (Modules::all() as $module) {
            if ($module->hasPackageProperty('CRON') == true) {
                $this->_execute($module);
            }
        }

        exit();
    }

    /**
     * 자동화 작업을 수행한다.
     *
     * @param Component $component 자동화작업을 수행할 컴포넌트
     */
    private function _execute(Component &$component): void
    {
        $hour = date('G');
        $me = $component;

        $insert = [];
        $insert['date'] = date('Y-m-d');
        $insert['hour'] = $hour;
        $insert['component_type'] = $me->getType();
        $insert['component_name'] = $me->getName();

        if (is_file($component->getPath() . '/crons/hourly.php') == true) {
            if (self::$is_ssh === true) {
                include $component->getPath() . '/crons/hourly.php';
            } else {
                $start = Format::microtime(3) * 1000;
                ob_start();
                $executed = include $component->getPath() . '/crons/hourly.php';
                print_r($_SERVER);
                $logs = ob_get_clean();

                if ($executed === true) {
                    $runtime = Format::microtime(3) * 1000 - $start;
                    $insert['type'] = 'DAILY';
                    $insert['started_at'] = $start;
                    $insert['runtime'] = $runtime;
                    $insert['logs'] = strlen($logs) > 0 ? $logs : null;

                    iModules::db('default')
                        ->replace(iModules::table('crons'), $insert)
                        ->execute();
                }
            }
        }

        // @todo 데일리 작업 동작시간 환경설정
        if (self::$is_ssh === true || $hour == 4) {
            if (is_file($component->getPath() . '/crons/daily.php') == true) {
                if (self::$is_ssh === true) {
                    include $component->getPath() . '/crons/daily.php';
                } else {
                    $start = Format::microtime(3) * 1000;
                    ob_start();
                    $executed = include $component->getPath() . '/crons/daily.php';
                    $logs = ob_get_clean();

                    if ($executed === true) {
                        $runtime = Format::microtime(3) * 1000 - $start;
                        $insert['type'] = 'DAILY';
                        $insert['started_at'] = $start;
                        $insert['runtime'] = $runtime;
                        $insert['logs'] = strlen($logs) > 0 ? $logs : null;

                        iModules::db('default')
                            ->replace(iModules::table('crons'), $insert)
                            ->execute();
                    }
                }
            }
        }
    }

    /**
     * 자동화작업인 경우에만 디버그메시지를 출력한다.
     *
     * @param ...mixed $message 출력할 메시지
     */
    public static function message(mixed ...$messages): void
    {
        self::$progress = 0;

        if (defined('__IM_CRON__') == true) {
            if (self::$linebreak == false) {
                echo PHP_EOL;
            }

            $blank = true;
            foreach ($messages as $message) {
                if ($blank == false) {
                    echo ' ';
                    $blank = true;
                }
                if (is_string($message) == true || is_numeric($message) == true) {
                    echo $message;
                    $blank = false;
                } else {
                    echo PHP_EOL;
                    var_dump($message);
                    $blank = true;
                }
            }

            echo PHP_EOL;
            self::$linebreak = true;

            if (self::$is_ssh == true) {
                flush();
            }
        }
    }

    /**
     * 자동화작업 진행률을 표시하기 위해 숫자, 또는 지정된 문자열을 출력한다.
     * 100개가 출력된 뒤 줄바꿈을 출력한다.
     *
     * @param string $char 출력할 문자열 (NULL 인 경우 진행률 순서를 0-9 로 표시한다.)
     */
    public static function progress(string $char = null): void
    {
        if (defined('__IM_CRON__') == true) {
            if (self::$progress == 0 && self::$linebreak == false) {
                echo PHP_EOL;
            }

            echo $char ?? self::$progress % 10;
            if (self::$progress % 100 == 99) {
                echo PHP_EOL;
            }
            self::$progress++;
            self::$linebreak = false;

            if (self::$is_ssh == true) {
                flush();
            }
        }
    }
}
