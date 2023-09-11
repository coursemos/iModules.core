<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 자동화 작업을 수행하기 위한 클래스를 정의한다.
 *
 * @file /classes/Cron.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 8. 21.
 */
class Cron
{
    /**
     * 자동화 작업 클래스를 초기화한다.
     */
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        Modules::init();
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

        // @todo 데일리 작업 동작시간 환경설정
        if (isset($_SERVER['SSH_CLIENT']) === true || $hour == 4) {
            if (is_file($component->getPath() . '/crons/daily.php') == true) {
                if (isset($_SERVER['SSH_CLIENT']) === true) {
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

        if (is_file($component->getPath() . '/crons/hourly.php') == true) {
            if (isset($_SERVER['SSH_CLIENT']) === true) {
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
    }

    /**
     * 매일 수행하는 자동화 작업을 수행한다.
     *
     * @param Component $component 자동화작업을 수행할 컴포넌트
     * @return void  description
     */
    private function _daily(Component &$component): void
    {
        if (is_file($component->getPath() . '/crons/daily.php') == true) {
            $me = $component;

            ob_start();
            include $component->getPath() . '/crons/daily.php';
            $logs = ob_get_clean();

            // @todo 데이터베이스에 기록
            echo $logs;

            echo PHP_EOL;
        }

        if (is_file($component->getPath() . '/crons/hourly.php') == true) {
            $me = $component;

            ob_start();
            include $component->getPath() . '/crons/hourly.php';
            $logs = ob_get_clean();

            // @todo 데이터베이스에 기록
            echo $logs;

            echo PHP_EOL;
        }
        exit();
    }
}
