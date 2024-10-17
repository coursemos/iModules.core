<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈코어 및 각 컴포넌트의 공통 이벤트를 정의한다.
 *
 * @file /classes/Event.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 16.
 */
class Event
{
    /**
     * @var int $_priority 우선순위 (숫자가 작을수록 먼저 실행한다.)
     */
    public static int $_priority = 10;

    /**
     * 각 컴포넌트의 프로세스가 처리되기 전 발생한다.
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
     * 각 컴포넌트의 API가 처리되기 전 발생한다.
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

    /**
     * 각 컴포넌트의 컨텍스트를 가져오기 전 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param \Template $template 컨텍스트를 처리한 템플릿
     * @param string $file 컨텍스트명 (템플릿파일명)
     * @param string $header 컨텍스트 HTML 상단에 포함할 HTML
     * @param string $footer 컨텍스트 HTML 하단에 포함할 HTML
     * @return ?string $html 컨텍스트 HTML (NULL 이 아닌 경우 해당 내용으로 컨텍스트 콘텐츠를 대치한다.)
     */
    public static function beforeGetContext(
        \Component $caller,
        \Template $template,
        string &$file,
        string &$header,
        string &$footer
    ): ?string {
        return null;
    }

    /**
     * 각 컴포넌트의 컨텍스트를 가져온 후 발생한다.
     *
     * @param \Component $caller 이벤트를 호출한 컴포넌트
     * @param \Template $template 컨텍스트를 처리한 템플릿
     * @param string $file 컨텍스트명 (템플릿파일명)
     * @param string $content 컨텍스트 HTML
     * @param string &$footer 컨텍스트 HTML 하단에 포함할 HTML
     */
    public static function afterGetContext(
        \Component $caller,
        \Template $template,
        string $file,
        string &$content
    ): void {
    }

    /**
     * 테마가 공통 레이아웃을 생성하기전 발생한다.
     *
     * @param \Theme $theme 사이트테마클래스
     * @param string $main 공통 테마디자인에 포함될 콘텐츠
     * @param string $type 공통 레이아웃 타입 (NULL : 웹사이트, context : 컨텍스트)
     * @return ?string $html 테마 레이아웃 HTML (NULL 이 아닌 경우 해당 내용으로 테마 레이아웃을 대치한다.)
     */
    public static function beforeLayout(\Theme $theme, string &$main, ?string &$type): ?string
    {
        return null;
    }

    /**
     * 테마가 공통 레이아웃을 가져온 후 발생한다.
     *
     * @param \Theme $theme 사이트테마클래스
     * @param string &$index 공통 레이아웃 HTML
     * @param string $type 공통 레이아웃 타입 (NULL : 웹사이트, context : 컨텍스트)
     */
    public static function afterLayout(\Theme $theme, string &$main, ?string $type): void
    {
    }
}
