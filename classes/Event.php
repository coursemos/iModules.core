<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 이벤트를 처리한다.
 *
 * @file /classes/Event.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 14.
 */
class Event
{
    /**
     * @var object $_listeners 이벤트리스너 목록
     */
    private static object $_listeners;

    /**
     * @var object $_listeners 이벤트리스너 클래스목록
     */
    private static object $_classes;

    /**
     * 전체 이벤트리스너를 가져온다.
     */
    public static function init(): void
    {
        if (isset(self::$_listeners) == false) {
            self::$_listeners = new stdClass();
            self::$_classes = new stdClass();

            /**
             * 설치된 모든 모듈의 이벤트 리스너를 가져온다.
             */
            foreach (Modules::all() as $module) {
                foreach ($module->getInstalled()->listeners ?? [] as $type => $components) {
                    self::$_listeners->{$type} ??= new stdClass();
                    self::$_classes->{$type} ??= new stdClass();
                    foreach ($components as $name => $events) {
                        self::$_listeners->{$type}->{$name} ??= new stdClass();
                        self::$_classes->{$type}->{$name} ??= new stdClass();
                        foreach ($events as $event) {
                            self::$_listeners->{$type}->{$name}->{$event} ??= [];
                            self::$_listeners->{$type}->{$name}->{$event}[] = $module;
                        }
                    }
                }
            }

            /**
             * 설치된 모든 플러그인의 이벤트 리스너를 가져온다.
             */
            foreach (Plugins::all() as $plugin) {
                foreach ($plugin->getInstalled()->listeners ?? [] as $type => $components) {
                    self::$_listeners->{$type} ??= new stdClass();
                    self::$_classes->{$type} ??= new stdClass();
                    foreach ($components as $name => $events) {
                        self::$_listeners->{$type}->{$name} ??= new stdClass();
                        self::$_classes->{$type}->{$name} ??= new stdClass();
                        foreach ($events as $event) {
                            self::$_listeners->{$type}->{$name}->{$event} ??= [];
                            self::$_listeners->{$type}->{$name}->{$event}[] = $plugin;
                        }
                    }
                }
            }
        }
    }

    /**
     * 이벤트리스너 클래스를 초기화한다.
     *
     * @param Component $caller 이벤트를 발생시킨 컴포넌트 객체
     * @param string $event 이벤트명
     * @return string[] $classes 클래스명
     */
    public static function getClasses(Component $caller, string $event): array
    {
        if (isset(self::$_classes?->{$caller->getType() . 's'}?->{$caller->getName()}) == false) {
            return [];
        }

        if (isset(self::$_classes->{$caller->getType() . 's'}->{$caller->getName()}->{$event}) == false) {
            /**
             * @var Component[] $listeners
             */
            $classes = [];
            $listeners = self::$_listeners?->{$caller->getType() . 's'}?->{$caller->getName()}->{$event} ?? [];
            foreach ($listeners as $listener) {
                $listenerPaths = explode('/', $listener->getType() . 's/' . $listener->getName());
                $callerPaths = explode('/', $caller->getType() . 's/' . $caller->getName());
                $className = '\\' . implode('\\', $listenerPaths) . '\\listeners';
                $className .= '\\' . implode('\\', $callerPaths) . '\\Listeners';

                $classes[$className] = $className::$_priority;
            }

            ksort($classes);
            self::$_classes->{$caller->getType() . 's'}->{$caller->getName()}->{$event} = array_keys($classes);
        }

        return self::$_classes?->{$caller->getType() . 's'}?->{$caller->getName()}->{$event};
    }

    /**
     * 이벤트를 발생시킨다.
     *
     * @param Component $caller 이벤트를 발생시킨 컴포넌트 객체
     * @param string $event 이벤트명
     * @param array $params 이벤트리스너에 전달할 매개변수
     * @param ?string $stopped 이벤트실행을 중단할 조건 (TRUE, FALSE, NOTNULL)
     * @return mixed 이벤트리스너가 중단되었을 때의 값
     */
    public static function fireEvent(Component $caller, string $event, array $params, ?string $stopped = null): mixed
    {
        self::init();

        /**
         * @var Component[] $listeners
         */
        $listeners = self::getClasses($caller, $event);
        foreach ($listeners as $listener) {
            $result = $listener::$event(...$params);
            if ($result === true && $stopped === 'TRUE') {
                return true;
            }

            if ($result === false && $stopped === 'FALSE') {
                return false;
            }

            if ($result !== null && $stopped === 'NOTNULL') {
                return $result;
            }
        }

        return null;
    }
}
