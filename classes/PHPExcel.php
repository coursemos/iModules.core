<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 엑셀파일 생성을 위한 클래스를 정의한다.
 *
 * @file /classes/PHPExcel.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 8.
 */
class PHPExcel
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet $_excel 엑셀문서 객체
     */
    private \PhpOffice\PhpSpreadsheet\Spreadsheet $_excel;

    /**
     * @var string[] $_columns 제목행이 설정된 경우 컬럼별 dataIndex
     */
    private array $_dataIndexes = [];

    /**
     * @var int[] 각 컬럼별 글자수길이를 기억한다.
     */
    private array $_columnLengths = [];

    /**
     * 엑셀파일 생성을 위한 클래스를 정의한다.
     *
     * @param string $path 기존엑셀파일 경로 (NULL 인 경우 신규 엑셀파일을 생성한다.)
     */
    public function __construct(string $path = null)
    {
        if ($path !== null && is_file($path) == true) {
            $this->_excel = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        } else {
            $this->_excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        }
    }

    /**
     * 컬럼 인덱스를 가져온다.
     *
     * @param int|string $columnIndex 컬럼 인덱스
     * @return string $index 컬럼 인덱스
     */
    public function getColumnByIndex(int|string $columnIndex): string
    {
        if (is_numeric($columnIndex) == true) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
        } else {
            return $columnIndex;
        }
    }

    /**
     * 셀 인덱스를 가져온다.
     *
     * @param int $rowIndex 행 인덱스
     * @param int|string $columnIndex 컬럼 인덱스
     * @return string $index 셀 인덱스
     */
    public function getCellByIndex(int $rowIndex, int|string $columnIndex): string
    {
        return $this->getColumnByIndex($columnIndex) . $rowIndex;
    }

    /**
     * 셀 범위를 가져온다.
     *
     * @param int $startRowIndex 시작 행인덱스
     * @param int|string $startColumnIndex 시작 컬럼인덱스
     * @param int $endRowIndex 끝 행인덱스
     * @param int|string $endColumnIndex 끝 컬럼인덱스
     * @return string $range 셀 범위
     */
    public function getRangeByIndex(
        int $startRowIndex,
        int|string $startColumnIndex,
        int $endRowIndex,
        int|string $endColumnIndex
    ): string {
        return $this->getCellByIndex($startRowIndex, $startColumnIndex) .
            ':' .
            $this->getCellByIndex($endRowIndex, $endColumnIndex);
    }

    /**
     * 문서 제목을 설정한다.
     *
     * @param string $title 문서제목
     * @param string $description 문서설명
     * @return PHPExcel $this
     */
    public function setTitle(string $title, string $description = ''): PHPExcel
    {
        $this->_excel
            ->getProperties()
            ->setTitle($title)
            ->setDescription($description);
        return $this;
    }

    /**
     * 문서 작성자와 작성시각을 설정한다.
     *
     * @param string $name 작성자
     * @param ?int $time 작성시각
     * @return PHPExcel $this
     */
    public function setCreator(string $name, ?int $time = null): PHPExcel
    {
        $time ??= time();
        $this->_excel
            ->getProperties()
            ->setCreator($name)
            ->setCreated($time)
            ->setLastModifiedBy($name)
            ->setModified($time);
        return $this;
    }

    /**
     * 현재 활성화된 시트를 가져온다.
     *
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     */
    public function getActiveSheet(): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        return $this->_excel->getActiveSheet();
    }

    /**
     * 특정셀의 스타일을 다른 셀로 복사한다.
     *
     * @param string $origin 원본 스타일을 가진 셀
     * @param string $target 스타일을 복사할 셀
     */
    public function duplicateStyle(string $origin, string $target): void
    {
        $style = $this->getActiveSheet()->getStyle($origin);
        $this->getActiveSheet()->duplicateStyle($style, $target);
    }

    /**
     * 제목행의 행갯수를 가져온다.
     *
     * @param array $headers 제목행 [{dataIndex:string, text:string, columns:{dataIndex:string, text:string}[]}...]
     * @param integer $rowIndex 제목행이 시작된 행인덱스
     * @return int $lastIndex 제목행이 끝나는 행인덱스
     */
    public function getHeaderCount(array $headers = [], int $rowIndex = 1): int
    {
        foreach ($headers as $header) {
            if (count($header->columns ?? []) > 0) {
                $rowIndex = $this->getHeaderCount($header->columns, ++$rowIndex);
            }
        }

        return $rowIndex;
    }

    /**
     * 제목행을 생성한다.
     *
     * @param array $headers - 제목행 [{dataIndex:string, text:string, columns:{dataIndex:string, text:string}[]}...]
     * @param int $rowIndex 제목행을 시작할 행인덱스
     * @param int $start_column 제목행을 시작할 컬럼인덱스
     * @return array $lastCellIndex 제목행이 끝나는 행, 컬럼인덱스
     */
    public function setHeader(array $headers = [], int $rowIndex = 1, int $columnIndex = 1): array
    {
        $lastRowIndex = $this->getHeaderCount($headers, $rowIndex);

        if (count($headers) > 0) {
            foreach ($headers as $header) {
                $column = $this->getColumnByIndex($columnIndex);
                $this->setValue($column . $rowIndex, $header->text);
                $this->_dataIndexes[$column] = $header->dataIndex ?? null;

                if (count($header->columns ?? []) > 0) {
                    $columnIndex = $this->setHeader($header->columns, $rowIndex + 1, $columnIndex)[1];

                    $this->getActiveSheet()->mergeCells(
                        $this->getRangeByIndex($rowIndex, $column, $rowIndex, $columnIndex)
                    );
                } else {
                    $this->getActiveSheet()->mergeCells(
                        $this->getRangeByIndex($rowIndex, $column, $lastRowIndex, $column)
                    );
                }

                $columnIndex++;
            }

            return [$lastRowIndex, $columnIndex - 1];
        } else {
            return [$lastRowIndex, $columnIndex];
        }
    }

    /**
     * 제목행 설정에 따른 dataIndex 를 가져온다.
     *
     * @return array $dataIndexes ['컬럼인덱스'=>dataIndex]
     */
    public function getDataIndexes(): array
    {
        return $this->_dataIndexes;
    }

    /**
     * 셀 세로정렬을 설정한다.
     *
     * @param string $cellIndex 셀 인덱스
     * @param string $alignment 정렬 (TOP, BOTTOM, CENTER, JUSTIFY)
     */
    public function setVertical(string $cellIndex, string $alignment): void
    {
        $alignments = [
            'TOP' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
            'CENTER' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'BOTTOM' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM,
            'JUSTIFY' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_JUSTIFY,
        ];
        if (isset($alignments[$alignment]) == true) {
            $this->getActiveSheet()
                ->getStyle($cellIndex)
                ->getAlignment()
                ->setVertical($alignments[$alignment]);
        }
    }

    /**
     * 셀 세로정렬을 설정한다.
     *
     * @param string $cellIndex 셀 인덱스
     * @param string $alignment 정렬 (TOP, BOTTOM, CENTER, JUSTIFY)
     */
    public function setHorizontal(string $cellIndex, string $alignment): void
    {
        $alignments = [
            'LEFT' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            'CENTER' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'RIGHT' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            'JUSTIFY' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_JUSTIFY,
            'FILL' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_FILL,
        ];

        if (isset($alignments[$alignment]) == true) {
            $this->getActiveSheet()
                ->getStyle($cellIndex)
                ->getAlignment()
                ->setHorizontal($alignments[$alignment]);
        }
    }

    /**
     * 셀 필터를 적용한다.
     *
     * @param string $range 적용할 범위
     */
    public function setAutoFilter(string $range): void
    {
        $this->getActiveSheet()->setAutoFilter($range);
    }

    /**
     * 셀에 값을 지정한다.
     *
     * @param string $cellIndex 셀 인덱스
     * @param mixed $value 셀 데이터
     * @param string $format 셀 포맷
     */
    public function setValue(string $cellIndex, mixed $value, string $format = null): void
    {
        if (is_numeric($value) == false || preg_match('/0[0-9]+/', $value) === true) {
            $this->getActiveSheet()->setCellValueExplicit(
                $cellIndex,
                $value,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
        } else {
            $this->getActiveSheet()->setCellValue($cellIndex, $value);
            if ($format !== null) {
                $this->getActiveSheet()
                    ->getStyle($cellIndex)
                    ->getNumberFormat()
                    ->setFormatCode($format);
            }
        }

        if (preg_match('/([A-Z]+)/', $cellIndex, $match) == true) {
            $column = $match[1];
            $this->setColumnLength($column, $value);
        }
    }

    /**
     * 셀에 날짜형식의 값을 지정한다.
     *
     * @param string $cellIndex 셀 인덱스
     * @param mixed $value 데이터
     * @param string $format 날짜포맷
     */
    public function setDateValue(string $cellIndex, mixed $value, string $format = 'yyyy-mm-dd hh:mm:ss'): void
    {
        if (is_numeric($value) == false) {
            $value = strtotime($value);
        }
        $value = date('Y-m-d H:i:s', $value);
        $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($value);
        $this->getActiveSheet()->setCellValue($cellIndex, $value);
        $this->getActiveSheet()
            ->getStyle($cellIndex)
            ->getNumberFormat()
            ->setFormatCode($format);

        if (preg_match('/([A-Z]+)/', $cellIndex, $match) == true) {
            $column = $match[1];
            $this->setColumnLength($column, $format);
        }
    }

    /**
     * 셀에 값을 지정한다.
     *
     * @param string $cellIndex 셀 인덱스
     * @param mixed $value 셀 데이터
     * @param string $format 셀 포맷
     */
    public function setLinkValue(string $cellIndex, mixed $value): void
    {
        if ($value) {
            $this->getActiveSheet()->setCellValue($cellIndex, $value);
            $this->getActiveSheet()
                ->getHyperlink($cellIndex)
                ->setUrl($value);
            if (preg_match('/([A-Z]+)/', $cellIndex, $match) == true) {
                $column = $match[1];
                $this->setColumnLength($column, $value);
            }
        }
    }

    /**
     * 열의 최대글자길이를 저장한다.
     *
     * @param string $column 열인덱스
     * @param mixed $value 데이터
     * @param bool $is_recount 길이재계산 여부
     */
    public function setColumnLength(string $column, mixed $value, bool $is_recount = true): void
    {
        if ($is_recount == false && isset($this->_columnLengths[$column]) == true) {
            return;
        }

        $length = mb_strlen($value, 'utf-8');
        $alnum_length = strlen(preg_replace('/[가-힣]*/', '', $value));
        $size = $length * 1.5 - $alnum_length * 0.65;

        if (isset($this->_columnLengths[$column]) == false) {
            $this->_columnLengths[$column] = $size;
        }

        if ($is_recount == true) {
            $this->_columnLengths[$column] = max($this->_columnLengths[$column], $size);
        }
    }

    /**
     * 자동너비를 적용한다.
     *
     * @param int|string $columnIndex 적용할 컬럼인덱스
     * @param bool $is_auto 자동너비사용여부
     */
    public function setAutoSize(int|string $columnIndex, bool $is_auto = true): void
    {
        $this->getActiveSheet()
            ->getColumnDimension($this->getColumnByIndex($columnIndex))
            ->setAutoSize($is_auto);
    }

    /**
     * 자동너비를 적용한다.
     *
     * @param int|string $columnIndex 적용할 컬럼인덱스
     * @param bool $is_auto 자동너비사용여부
     */
    public function setAutoSizeAll(): void
    {
        foreach ($this->_columnLengths as $column => $length) {
            $length = min(300, $length);
            $length = max(12, $length);
            $this->getActiveSheet()
                ->getColumnDimension($column)
                ->setWidth($length);
        }
    }

    /**
     * 고정 행/열을 적용한다.
     *
     * @param int $rowIndex 행인덱스
     * @param int|string $columnIndex 적용할 컬럼인덱스
     */
    public function freezePane(int $rowIndex, int|string $columnIndex): void
    {
        $this->getActiveSheet()->freezePane($this->getColumnByIndex($columnIndex) . $rowIndex);
    }

    /**
     * 시트를 추가한다.
     * @param string $title
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    public function addSheet(string $title): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        foreach ($this->_excel->getWorksheetIterator() as $sheet) {
            if ($sheet->getTitle() === $title) {
                $this->_excel->setActiveSheetIndexByName($title);
                return $sheet;
            }
        }

        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->_excel, $title);
        $this->_excel->addSheet($sheet);
        $this->_excel->setActiveSheetIndexByName($title);
        return $sheet;
    }

    /**
     * 템플릿으로 시트를 추가한다.
     * @param string $path
     * @param string $title
     * @return void
     * @throws Exception
     */
    public function addSheetFromTemplate(string $path, string $title): void
    {
        if (!is_file($path)) {
            throw new \Exception("Excel template file not found: {$path}");
        }

        $template = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $template->getSheet(0);
        $sheet->setTitle($title);

        $this->_excel->addExternalSheet($sheet);
        $this->_excel->setActiveSheetIndexByName($title);
    }

    /**
     * 엑셀파일을 저장한다.
     *
     * @param string $path 파일경로
     * @return bool $success
     */
    public function save(string $path): bool
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->_excel);
        $writer->save($path);

        return is_file($path);
    }
}
