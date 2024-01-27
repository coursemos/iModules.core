<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 설치작업을 처리한다.
 *
 * @file /install/process/index.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 28.
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

Configs::setPath(str_replace('/install/process/index.php', '', $_SERVER['SCRIPT_FILENAME']));

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
        include Configs::path() . '/configs/configs.php';
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
            if (
                $db
                    ->select()
                    ->from(iModules::table('domains'))
                    ->where('host', $_SERVER['HTTP_HOST'])
                    ->has() == false
            ) {
                $db->insert(iModules::table('domains'), [
                    'host' => $_SERVER['HTTP_HOST'],
                    'alias' => '',
                    'language' => Request::languages(true),
                    'is_https' => Request::isHttps() == true ? 'TRUE' : 'FALSE',
                    'is_rewrite' => 'TRUE', // @todo Rewrite 설정확인,
                    'is_internationalization' => 'FALSE',
                    'sort' => 0,
                ])->execute();
            }

            if (
                $db
                    ->select()
                    ->from(iModules::table('sites'))
                    ->where('host', $_SERVER['HTTP_HOST'])
                    ->where('language', Request::languages(true))
                    ->has() == false
            ) {
                $db->insert(iModules::table('sites'), [
                    'host' => $_SERVER['HTTP_HOST'],
                    'language' => Request::languages(true),
                    'title' => 'iModules',
                    'description' => '',
                    'theme' => Format::toJson(['name' => 'default', 'configs' => null]),
                ])->execute();
            }

            Cache::remove('domains');
            Cache::remove('sites');
            Cache::remove('contexts');
            Cache::remove('modules');

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
        $results->requirement = Configs::package()->getDependencies()->{$key} ?? false;

        if ($type == 'modules') {
            $installable = Modules::installable($name, false);
            $results->current = $installable->exists === false ? '0.0.0' : $installable->exists;

            if (
                $installable->success == false ||
                version_compare($installable->exists, $results->requirement, '<') == true
            ) {
                $results->status = 'upload';
            } else {
                $success = Modules::install($name, null, false);
                $results->success = $success === true;
                if ($success !== true) {
                    $results->status = 'fail';
                    $results->message = $success;
                }
            }
        }

        Configs::exit($results);
    }
}
