<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에러데이터를 정의한다.
 *
 * @file /classes/ErrorData.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 8.
 */
class ErrorData
{
    /**
     * @var string $code 에러코드
     */
    public string $code;

    /**
     * @var ?string $title 에러제목
     */
    public ?string $title = null;

    /**
     * @var ?string $message 에러메시지
     */
    public ?string $message = null;

    /**
     * @var ?string $prefix 에러메시지 상단 내용
     */
    public ?string $prefix = null;

    /**
     * @var ?string $suffix 에러메시지 하단 상세내용
     */
    public ?string $suffix = null;

    /**
     * @var ?string $file 에러가 발생한 파일명
     */
    public ?string $file = null;

    /**
     * @var ?int $line 에러가 발생한 LINE
     */
    public ?int $line = null;

    /**
     * @var ?array $stacktrace 에러발생 추적데이터
     */
    public ?array $stacktrace = null;

    /**
     * @var ?object $details 에러와 관련된 추가정보
     */
    public ?object $details = null;

    /**
     * @var ?Component $component 에러가 발생된 컴포넌트
     */
    public ?Component $component = null;

    /**
     * @var bool $debugModeOnly 디버그모드에서만 상세 에러메시지를 보여줄지 여부
     */
    public bool $debugModeOnly = false;

    /**
     * @var bool $debugMode 현재 디버그모드인지 여부
     */
    public bool $debugMode = false;

    /**
     * 에러데이터를 정의한다.
     *
     * @param string $title 에러명
     * @param ?string $message 에러메시지
     */
    public function __construct(string $code, ?Component $component = null)
    {
        $this->code = $code;
        $this->component = $component;
        $this->debugMode = Configs::debug();
    }
}
