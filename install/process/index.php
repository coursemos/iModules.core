<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 설치작업을 처리한다.
 *
 * @file /install/process/index.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 24.
 */
define('__IM__', true);

require_once '../../classes/AutoLoader.php';

/**
 * 클래스파일을 자동으로 불러오기위한 오토로더클래스를 불러온다.
 */
AutoLoader::init('../..');
AutoLoader::register('/', '/');
AutoLoader::register('/', '/classes');
AutoLoader::register('/vendor', '/src');

Configs::setPath(realpath('../..'));

/**
 * 에러클래스를 초기화한다.
 */
ErrorHandler::init();

$action = Request::get('action');
if ($action == 'requirements') {
    Header::type('json');
    Configs::exit(Configs::requirements());
}

if ($action == 'configs') {
    Configs::setPath(realpath('../..'));
    Header::type('json');

    $configs = json_decode(file_get_contents('php://input')) ?? null;
    if ($configs == null) {
        Configs::exit(Configs::read());
    } else {
        $success = Configs::write($configs);
        if ($success === true) {
            Configs::exit(['success' => true]);
        }

        Configs::exit($success);
    }
}

if ($action == 'install') {
    $token = null;
    $step = Request::get('step');
    if (is_file(Configs::path() . '/configs/configs.php') == true) {
        $_CONFIGS = new stdClass();
        $_CONFIGS->db = new stdClass();
        require_once Configs::path() . '/configs/configs.php';
        Configs::init($_CONFIGS);
        $hasConfigs = true;

        AutoLoader::setPath(Configs::path());
        $token = json_decode(Password::decode(Request::get('token') ?? ''));
    } else {
        $hasConfigs = false;
    }

    if (
        $token == null ||
        $hasConfigs == false ||
        $token->lifetime < time() ||
        $token->hash != md5_file(Configs::path() . '/configs/configs.php')
    ) {
        Header::type('json');
        Configs::exit(['success' => false, 'status' => $step == 'configs' ? 'fail' : 'notice']);
    }

    if ($step == 'configs') {
        Header::type('json');
        Configs::exit(['success' => true, 'status' => 'success']);
    }

    if ($step == 'databases') {
        Header::type('json');
        $db = iModules::db();

        $results = new stdClass();
        $results->success = false;
        $results->status = 'fail';
        $results->errors = [];
        foreach (Configs::package()->getDatabases() as $table => $schema) {
            if ($db->compare(iModules::table($table), $schema, true) == false) {
                $success = $db->create(iModules::table($table), $schema);
                if ($success !== true) {
                    $results->errors[] = $success;
                }
            }
        }

        if (count($results->errors) == 0) {
            $results->success = true;
            $results->status = 'success';
        } else {
            $results->error = implode('<br>', $results->errors);
        }

        Configs::exit($results);
    }

    if ($step == 'dependencies') {
        Header::type('json');

        $key = Request::get('key');
        $temp = explode('/', $key);
        $type = array_shift($temp);
        $name = implode('/', $temp);

        $results = new stdClass();
        $results->success = false;
        $results->key = $key;
        $results->current = '0.0.0';
        $results->requirement = Configs::package()->dependencies?->{$key} ?? false;

        if ($type == 'modules') {
            $installable = Modules::installable($name, false);
            $results->current = $installable->exists === false ? '0.0.0' : $installable->exists;

            if (
                $installable->success == false ||
                version_compare($installable->exists, $results->requirement, '<') == true
            ) {
                Configs::exit($results);
            }

            $success = Modules::install($name, null, false);
            $results->success = $success;
        }

        Configs::exit($results);
    }
}
