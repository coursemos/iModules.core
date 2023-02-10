<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 응답이 오래걸리는 요청을 처리하는 중 프로그래스바를 구현하기 위한 클래스를 정의한다.
 *
 * @file /classes/Progress.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class Progress
{
    private int $_total;
    private int $_progress;
    private int $_buffer = 10000;
    private ?object $_latest = null;

    /**
     * 프로그래스 클래스를 정의한다.
     *
     * @param int $total 완료갯수
     */
    function __construct(int $total = 100)
    {
        $this->_total = $total;
        $this->_progress = 0;

        if (headers_sent() == false) {
            set_time_limit(0);
            @ini_set('memory_limit', -1);
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            @ini_set('output_handler', '');
            @ini_set('max_execution_time', 0);

            if (function_exists('apache_setenv') == true) {
                @apache_setenv('no-gzip', 1);
            }

            header('Content-Encoding: none');
            header('X-Accel-Buffering: no');
            header('Content-Length: ' . $this->_buffer * 100);
            header('X-Progress-Total:' . $this->_total);
        }
    }

    /**
     * 데이터 우측에 버퍼용량 초과를 위한 빈문자열을 포함하여 문자열을 생성한다.
     *
     * @param object $output 변환할 데이터
     * @return string $text
     */
    public function pad(object $output, bool $is_last = false): string
    {
        $json = json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return str_pad($json, $this->_buffer - ($is_last == true ? 2 : 1)) . ($is_last == true ? "\n" . '@' : "\n");
    }

    /**
     * 프로그래스바를 위해 데이터를 출력한다.
     *
     * @param int $current 현재갯수
     * @param array|object $datas 전달할 데이터
     */
    public function progress(int $current, array|object $datas = null): void
    {
        $progress = round(($current / $this->_total) * 100);
        if ($progress != $this->_progress) {
            $output = new stdClass();
            $output->current = $current;
            $output->total = $this->_total;
            $output->datas = $datas;

            $this->_latest = $output;

            for ($i = $this->_progress; $i < $progress; $i++) {
                if ($i >= 99) {
                    $this->end();
                    return;
                }
                echo $this->pad($this->_latest);
                $this->_progress = $i + 1;
                flush();
            }
        }
    }

    public function end(): void
    {
        for ($i = $this->_progress; $i < 99; $i++) {
            $this->_latest->end = true;
            echo $this->pad($this->_latest);
            $this->_progress = $i + 1;
            flush();
        }

        $end = $this->_latest;
        $end->current = $this->_total;
        $end->last = true;
        echo $this->pad($end, true);
        flush();
    }
}
