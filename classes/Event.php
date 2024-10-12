<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 이벤트를 처리한다.
 *
 * @file /classes/Event.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 12.
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
                foreach ($module->getInstalled()->listeners ?? [] as $type => $components) {
                    self::$_listeners->{$type} ??= new stdClass();
                    foreach ($components as $name => $events) {
                        self::$_listeners->{$type}->{$name} ??= new stdClass();
                        foreach ($events as $event) {
                            self::$_listeners->{$type}->{$name}->{$event} ??= [];
                            self::$_listeners->{$type}->{$name}->{$event}[] = $module;
                        }
                    }
                }
            }
        }
    }

    /**
     * 이벤트를 발생시킨다.
     *
     * @param Component $caller 이벤트를 발생시킨 컴포넌트 객체
     * @param string $event 이벤트명
     * @param array $params 이벤트리스너에 전달할 매개변수
     * @param ?bool $stopped 이벤트실행결과가 BOOL 타입이고, $breakpoint 에 지정한 값과 동일할 경우 다른 이벤트 실행을 중단한다.
     */
    public static function fireEvent(Component $caller, string $event, array $params, ?bool $stopped = null): ?bool
    {
        self::init();

        $results = null;

        /**
         * @var Component[] $listeners
         */
        $listeners = self::$_listeners?->{$caller->getType() . 's'}?->{$caller->getName()}->{$event} ?? [];
        foreach ($listeners as $listener) {
            $listenerPaths = explode('/', $listener->getType() . 's/' . $listener->getName());
            $callerPaths = explode('/', $caller->getType() . 's/' . $caller->getName());
            $className = '\\' . implode('\\', $listenerPaths) . '\\listeners';
            $className .= '\\' . implode('\\', $callerPaths) . '\\Listeners';

            $result = $className::$event(...$params);
            if (is_bool($result) == true) {
                if ($stopped === $result) {
                    return $result;
                }

                $results = $results || $result;
            }
        }

        return $results;
    }
}
