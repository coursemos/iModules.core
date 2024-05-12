<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 이벤트를 처리한다.
 *
 * @file /classes/Event.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 11.
 */
class Event
{
    /**
     * @var object $_listeners 이벤트리스너 목록
     */
    private static object $_listeners;

    /**
     * 전체 이벤트리스너를 가져온다.
     */
    public static function init(): void
    {
        if (isset(self::$_listeners) == false) {
            self::$_listeners = new stdClass();

            /**
             * 설치된 모든 모듈의 이벤트 리스너를 가져온다.
             */
            foreach (Modules::all() as $module) {
                foreach ($module->getInstalled()->listeners ?? [] as $name => $targets) {
                    self::$_listeners->{$name} ??= new stdClass();
                    foreach ($targets as $target => $functions) {
                        self::$_listeners->{$name}->{$target} ??= new stdClass();
                        foreach ($functions as $function) {
                            self::$_listeners->{$name}->{$target}->{$function} ??= [];
                            self::$_listeners->{$name}->{$target}->{$function}[] = $module;
                        }
                    }
                }
            }
        }
    }

    /**
     * 이벤트를 발생시킨다.
     *
     * @param string $name 이벤트명
     * @param ?Module $target 이벤트를 발생시킨 모듈명 (NULL 인 경우 아이모듈코어)
     * @param string $function 이벤트가 발생한 함수명
     * @param mixed $values 이벤트대상이 받은 변수객체
     * @param mixed $results 이벤트대상이 반환할 변수객체
     */
    public static function fireEvent(
        string $name,
        ?Module $target,
        string $function,
        mixed &$values = null,
        mixed &$results = null
    ): void {
        self::init();

        /**
         * @var Component[] $listeners
         */
        $listeners = self::$_listeners->{$name}?->{$target->getName()}?->{$function} ?? [];
        foreach ($listeners as $listener) {
            if (is_file($listener->getPath() . '/listeners/' . $name . '.php') == true) {
                File::include(
                    $listener->getPath() . '/listeners/' . $name . '.php',
                    [
                        'me' => &$listener,
                        'target' => &$target,
                        'function' => $function,
                        'values' => &$values,
                        'results' => &$results,
                    ],
                    true
                );
            }
        }
    }
}
