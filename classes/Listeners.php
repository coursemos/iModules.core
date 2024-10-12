<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 공통 이벤트를 정의한다.
 *
 * @file /classes/Listeners.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 12.
 */
class Listeners
{
    /**
     * 각 컴포넌트의 프로세스가 처리되기전 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param string $method 요청방법
     * @param string $process 요청명
     * @param string $path 요청경로
     * @param object $results 처리결과
     * @return bool $stopped (FALSE 반환시 해당 프로세스 요청이 중단된다.)
     */
    public static function beforeDoProcess(
        \Component $caller,
        string $method,
        string $process,
        string $path,
        object &$results
    ): bool {
        return true;
    }

    /**
     * 각 컴포넌트의 프로세스가 처리된 후 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param string $method 요청방법
     * @param string $process 요청명
     * @param string $path 요청경로
     * @param object $values 요청을 처리하기 위해 정의된 모든 변수
     * @param object $results 처리결과
     */
    public static function afterDoProcess(
        \Component $caller,
        string $method,
        string $process,
        string $path,
        object &$values,
        object &$results
    ): void {
    }

    /**
     * 각 컴포넌트의 API가 처리되기전 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param string $method 요청방법
     * @param string $api API명
     * @param string $path 요청경로
     * @param object $results 처리결과
     * @return bool $stopped (FALSE 반환시 해당 프로세스 요청이 중단된다.)
     */
    public static function beforeDoApi(
        \Component $caller,
        string $method,
        string $api,
        string $path,
        object &$results
    ): bool {
        return true;
    }

    /**
     * 각 컴포넌트의 API가 처리된 후 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param string $method 요청방법
     * @param string $api API명
     * @param string $path 요청경로
     * @param object $values 요청을 처리하기 위해 정의된 모든 변수
     * @param object $results 처리결과
     */
    public static function afterDoApi(
        \Component $caller,
        string $method,
        string $api,
        string $path,
        object &$values,
        object &$results
    ): void {
    }
}
