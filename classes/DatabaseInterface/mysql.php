<?php
/**
 * 이 파일은 모임즈툴즈의 일부입니다. (https://www.moimz.tools)
 *
 * MySQL 인터페이스를 정의한다.
 *
 * @file /classes/Databases/mysql.class.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @version 2.0.0
 * @modified 2023. 5. 3.
 */
class mysql extends DatabaseInterface
{
    /**
     * mysqli 객체
     */
    private mysqli $_mysqli;

    /**
     * MySQL 서버의 기본 캐릭터셋 및 엔진 설정
     */
    private string $_charset = 'utf8mb4';
    private string $_collation = 'utf8mb4_unicode_ci';
    private string $_engine = 'InnoDB';

    private ?string $_query = null;
    private ?string $_lastQuery = null;
    private ?string $_lastError = null;

    /**
     * 쿼리빌더 내부변수
     */
    private bool $_is_builder_stated = false;
    private ?string $_startQuery = null;
    private ?string $_from_table = null;
    private ?DatabaseInterface $_from_query = null;
    private ?string $_from_alias = null;

    private array $_columns = [];
    private array $_join = [];
    private array $_where = [];
    private array $_having = [];
    private array $_orderBy = [];
    private array $_groupBy = [];
    private array $_limit = [];
    private array $_bindTypes = [];
    private array $_bindParams = [];
    private array $_tableDatas = [];
    private string $_tableLockMethod = 'READ';
    private int $_count = 0;
    private bool $_displayError = true;

    /**
     * MySQLi 클래스를 가져온다.
     *
     * @return mysqli $mysqli
     */
    public function mysqli(): mysqli
    {
        return $this->_mysqli;
    }

    /**
     * 데이터베이스에 접속한다.
     *
     * @param object $connector 데이터베이스정보
     * @return mysqli $mysqli mysqli 클래스객체
     */
    public function connect(object $connector): mysqli
    {
        set_error_handler(null);
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->_mysqli = new mysqli(
            $connector->host,
            $connector->id,
            $connector->password,
            $connector->database,
            $connector->port
        );
        if ($this->_mysqli->connect_error) {
            ErrorHandler::print(
                'DATABASE_CONNECT_ERROR',
                '(HY000/' . $this->_mysqli->connect_errno . ') ' . $this->_mysqli->connect_error,
                $connector
            );
        }
        restore_error_handler();

        $this->_mysqli->set_charset($this->_charset);
        $this->_mysqli->report_mode = MYSQLI_REPORT_OFF;

        return $this->_mysqli;
    }

    /**
     * 커넥션을 설정한다.
     *
     * @param mysqli $mysqli
     * @return bool $success
     */
    public function setConnection(&$mysqli): bool
    {
        // todo: MySQL 서버와 접속이 유지되어 있는지 확인
        $this->_mysqli = $mysqli;
        return true;
    }

    /**
     * 데이터베이스에 접속이 가능한지 확인한다.
     *
     * @param object $connector 데이터베이스정보
     * @return bool $success 접속성공여부
     */
    public function checkConnector(object $connector): bool
    {
        if (isset($connector->port) == false) {
            $connector->port = 3306;
        }
        $mysqli = @new mysqli(
            $connector->host,
            $connector->username,
            $connector->password,
            $connector->database,
            $connector->port
        );
        if ($mysqli->connect_errno) {
            return false;
        }
        if (isset($connector->charset) == true) {
            $this->_charset = $connector->charset;
        }
        if (isset($connector->collation) == true) {
            $this->_collation = $connector->collation;
        }

        $this->_mysqli->set_charset($this->_charset);
        return true;
    }

    /**
     * 데이터베이스 서버에 ping 을 보낸다.
     *
     * @return bool $pong
     */
    public function ping(): bool
    {
        if (isset($this->_mysqli) == false) {
            return false;
        }
        return $this->_mysqli->ping();
    }

    /**
     * 진행중인 쿼리빌더를 초기화한다.
     */
    public function reset(): void
    {
        $this->_is_builder_stated = false;
        $this->_startQuery = null;

        $this->_query = null;

        $this->_from_table = null;
        $this->_from_query = null;
        $this->_from_alias = null;

        $this->_columns = [];
        $this->_join = [];
        $this->_where = [];
        $this->_having = [];
        $this->_orderBy = [];
        $this->_groupBy = [];
        $this->_limit = [];
        $this->_bindTypes = [];
        $this->_bindParams = [];
        $this->_tableDatas = [];
        $this->_tableLockMethod = 'READ';
        $this->_count = 0;
    }

    /**
     * 데이터베이스의 전체 테이블목록을 가져온다.
     *
     * @param bool $include_desc 테이블구조 포함여부
     * @return array $tables
     */
    public function tables(bool $include_desc = false): array
    {
        return [];
    }

    /**
     * 테이블명이 존재하는지 확인한다.
     *
     * @param string $table 테이블명
     * @param bool $exists
     */
    public function exists(string $table): bool
    {
        return $this->query("SHOW TABLES LIKE '{$table}'")->count() > 0;
    }

    /**
     * 테이블의 용량을 가져온다.
     *
     * @param string $table 테이블명
     * @return int $size
     */
    public function size(string $table): int
    {
        return 0;
    }

    /**
     * 테이블의 구조를 가져온다.
     *
     * @param string $table 테이블명
     * @return array $desc
     */
    public function desc(string $table): array
    {
        return [];
    }

    /**
     * 테이블의 구조를 비교한다.
     *
     * @param string $table 테이블명
     * @param object $schema 테이블구조
     * @param bool $is_update 데이터손실이 없다면, 테이블구조를 변경할지 여부 (기본값 false)
     * @param bool $is_force 데이터손실을 감수하고 테이블구조를 변경할지 여부 (기본값 false)
     * @return bool $is_coincidence
     */
    public function compare(string $table, object $schema, bool $is_update = false, bool $is_force = false): bool
    {
        if ($this->exists($table) == false) {
            return false;
        }

        /**
         * 비교대상의 컬럼정보를 가져온다.
         *
         * @var object[] $columns 비교대상 테이블에 존재하는 컬럼 정보
         * @var string[] $exists 비교대상 테이블에 존재하는 컬럼명
         */
        $columns = [];
        $exists = [];
        foreach ($this->query('SHOW FULL COLUMNS FROM `' . $table . '`')->get() as $column) {
            $columns[$column->Field] = new stdClass();
            $columns[$column->Field]->type = $column->Type;
            $columns[$column->Field]->is_null = $column->Null == 'YES';

            if ($column->Default !== null && $this->_isNumeric($column->Type) == true) {
                $column->Default = intval($column->Default);
            }

            $columns[$column->Field]->default = $column->Default;
            $columns[$column->Field]->comment = $column->Comment;
            $columns[$column->Field]->collation = $column->Collation;

            $exists[] = $column->Field;
        }

        /**
         * @var string[] $alters 구조변경이 필요한 ALTER 쿼리문
         */
        $alters = [];

        /**
         * 기존 테이블에서 제거되는 컬럼이 존재하는지 확인한다.
         */
        foreach ($exists as $name) {
            if (isset($schema->columns->$name) == false) {
                if ($is_update == true && $is_force == true) {
                    $alters[] = 'DROP `' . $name . '`';
                } else {
                    return false;
                }
            }
        }

        /**
         * 전체 컬럼을 비교한다.
         */
        foreach ($schema->columns as $name => $column) {
            /**
             * 컬럼이 존재하지 않는 경우
             */
            if (in_array($name, $exists) == false) {
                // 구조변경이 가능한 경우 컬럼을 생성한다.
                if ($is_update == true) {
                    $alters[] = 'ADD ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }

            /**
             * 컬럼 형태가 다른 경우
             */
            $type = strtolower($column->type);
            if ($this->_isLength($column->type) == true && isset($column->length) == true) {
                $type .= '(' . $column->length . ')';
            }

            if ($type != $columns[$name]->type) {
                if ($is_update == true) {
                    $alters[] = 'CHANGE `' . $name . '` ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }

            if ($this->_isText($column->type) == true && $columns[$name]->collation != $this->_collation) {
                if ($is_update == true) {
                    $alters[] = 'CHANGE `' . $name . '` ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }

            if (($column->is_null ?? false) !== $columns[$name]->is_null) {
                if ($is_update == true) {
                    $alters[] = 'CHANGE `' . $name . '` ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }

            if (($column->default ?? null) !== $columns[$name]->default) {
                if ($is_update == true) {
                    $alters[] = 'CHANGE `' . $name . '` ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }

            if (($column->comment ?? '') != $columns[$name]->comment) {
                if ($is_update == true) {
                    $alters[] = 'CHANGE `' . $name . '` ' . $this->_columnQuery($name, $column);
                    continue;
                } else {
                    return false;
                }
            }
        }

        /**
         * 비교대상의 컬럼정보를 가져온다.
         *
         * @var object[] $indexes 비교대상 테이블에 존재하는 인덱스 정보
         * @var string[] $exists 인덱스 대상의 컬럼명
         */
        $indexes = [];
        $exists = [];
        foreach ($this->query('SHOW INDEX FROM `' . $table . '`')->get() as $index) {
            if (isset($indexes[$index->Key_name]) == false) {
                $indexes[$index->Key_name] = new stdClass();
                if ($index->Key_name == 'PRIMARY') {
                    $indexes[$index->Key_name]->type = 'primary_key';
                } elseif ($index->Index_type == 'FULLTEXT') {
                    $indexes[$index->Key_name]->type = 'fulltext';
                } elseif ($index->Non_unique == 0) {
                    $indexes[$index->Key_name]->type = 'unique';
                } else {
                    $indexes[$index->Key_name]->type = 'index';
                }
                $indexes[$index->Key_name]->columns = [];
            }
            $indexes[$index->Key_name]->columns[] = $index->Column_name;
        }

        /**
         * 제거된 인덱스가 존재할 경우 인덱스를 변경한다.
         */
        foreach ($indexes as $name => $index) {
            $key = implode(',', $index->columns);
            if (isset($schema->indexes?->{$key}) == true) {
                if ($schema->indexes->{$key} == $index->type) {
                }

                $exists[] = $key;
            } else {
                if ($is_update == true) {
                    $alters[] = $index->type == 'primary_key' ? 'DROP PRIMARY KEY' : 'DROP INDEX `' . $name . '`';
                } else {
                    return false;
                }
            }
        }

        /**
         * 신규 인덱스를 추가한다.
         */
        if (isset($schema->indexes) == true) {
            foreach ($schema->indexes as $column => $type) {
                $columns = explode(',', $column);
                $columns = '`' . implode('`,`', $columns) . '`';

                if (in_array($column, $exists) == false) {
                    if ($is_update == true) {
                        if ($type == 'primary_key') {
                            $alters[] = 'ADD PRIMARY KEY(' . $columns . ')';
                        } elseif ($type == 'unique') {
                            $alters[] = 'ADD UNIQUE(' . $columns . ')';
                        } elseif ($type == 'fulltext') {
                            $alters[] = 'ADD FULLTEXT(' . $columns . ')'; // @todo ngram 고민을 해보자 WITH PARSER ngram';
                        } else {
                            $alters[] = 'ADD INDEX(' . $columns . ')';
                        }
                    } else {
                        return false;
                    }
                }
            }
        }

        /**
         * 구조변경이 가능하고, 구조변경내역이 있다면 쿼리를 실행한다.
         * 쿼리 실행이 실패할 경우 구조변경을 취소하고 false 를 반환한다.
         */
        if ($is_update == true && count($alters) > 0) {
            $query = 'ALTER TABLE `' . $table . '` ';
            $alter = implode(', ', $alters);
            $query .= $alter;

            $this->query($query)->execute();
            $error = $this->getLastError();

            if ($error !== null) {
                return false;
            }
        }

        /**
         * 컬럼순서를 변경한다.
         */
        $positions = [];
        foreach ($this->query('SHOW FULL COLUMNS FROM `' . $table . '`')->get() as $position => $column) {
            $positions[$column->Field] = $position;
        }

        $loop = 0;
        $current = null;
        foreach ($schema->columns as $name => $column) {
            if ($positions[$name] != $loop) {
                $this->alter($table, $name, $column, $current ?? '@');
            }

            $current = $name;
            $loop++;
        }

        return true;
    }

    /**
     * 테이블을 생성한다.
     *
     * @param string $table 테이블명
     * @paran object $schema 테이블구조
     * @return bool|string $success
     */
    public function create(string $table, object $schema): bool|string
    {
        /**
         * 테이블이 존재할 경우, 임시 테이블을 생성한다.
         */
        if ($this->exists($table) == true) {
            /**
             * 기존테이블이 동일할 경우 테이블 생성을 중단한다.
             */
            if ($this->compare($table, $schema) == true) {
                return true;
            }

            $created = $table . '_TEMP_' . date('Y-m-d_H:i:s');
        } else {
            $created = $table;
        }

        $query = 'CREATE TABLE `' . $created . '`';

        $columns = [];
        foreach ($schema->columns as $name => $column) {
            $columns[] = $this->_columnQuery($name, $column);
        }

        $query .= ' (' . implode(', ', $columns) . ')';
        $query .= ' ENGINE = ' . $this->_engine . ' CHARACTER SET ' . $this->_charset . ' COLLATE ' . $this->_collation;
        if (isset($schema->comment) == true) {
            $query .= ' COMMENT="' . $schema->comment . '"';
        }

        $results = $this->query($query)->execute();
        if ($results->success == false) {
            return $this->getLastError();
        }

        if (isset($schema->indexes) == true) {
            $indexes = [];
            foreach ($schema->indexes as $column => $type) {
                $columns = explode(',', $column);
                $columns = '`' . implode('`,`', $columns) . '`';

                if ($type == 'primary_key') {
                    $indexes[] = 'ADD PRIMARY KEY(' . $columns . ')';
                } elseif ($type == 'unique') {
                    $indexes[] = 'ADD UNIQUE(' . $columns . ')';
                } elseif ($type == 'fulltext') {
                    $indexes[] = 'ADD FULLTEXT(' . $columns . ')'; // @todo ngram 고민을 해보자 WITH PARSER ngram';
                } else {
                    $indexes[] = 'ADD INDEX(' . $columns . ')';
                }
            }

            $query = 'ALTER TABLE `' . $created . '` ';
            $query .= implode(', ', $indexes);

            $results = $this->query($query)->execute();
            if ($results->success == false) {
                $this->drop($created);
                return $this->getLastError();
            }
        }

        if ($table != $created) {
            $datas = $this->select()
                ->from($table)
                ->get();
            if (count($datas) > 0) {
                $defaults = [];
                foreach ($schema->columns as $name => $column) {
                    if (isset($column->default) == true) {
                        $defaults[$name] = $column->default;
                    } elseif (isset($column->is_null) == true && $column->is_null == true) {
                        $defaults[$name] = null;
                    } elseif ($this->_isNumeric($column->type) == true) {
                        $defaults[$name] = 0;
                    } elseif ($column->type == 'enum') {
                        $temp = explode(',', $column->length);
                        $defaults[$name] = substr($temp[0], 1, -1);
                    } elseif ($column->type == 'json') {
                        $defaults[$name] = 'null';
                    } elseif ($column->type == 'date') {
                        $defaults[$name] = '0000-00-00';
                    } elseif ($column->type == 'datetime') {
                        $defaults[$name] = '0000-00-00 00:00:00';
                    } else {
                        $defaults[$name] = '';
                    }
                }

                $this->transaction();
                foreach ($datas as $data) {
                    $insert = $defaults;
                    foreach ($data as $name => $value) {
                        if (isset($insert[$name]) == true) {
                            $insert[$name] = $value;
                        }
                    }
                    $this->insert($created, $insert)->execute();
                }

                if ($this->getLastError() !== null) {
                    $this->rollback();
                    $this->drop($created);
                    return $this->getLastError();
                }

                $this->commit();
                $this->rename($table, $table . '_BACKUP_' . date('Y-m-d_H:i:s'));
            } else {
                $this->drop($table);
            }

            $this->rename($created, $table);
        }

        return true;
    }

    /**
     * 테이블을 삭제한다.
     *
     * @param string $table 테이블명
     * @return bool $success
     */
    public function drop(string $table): bool
    {
        $results = $this->query('DROP TABLE `' . $table . '`')->execute();
        return $results->success;
    }

    /**
     * 테이블을 비운다.
     *
     * @param string $table 테이블명
     * @return bool $success
     */
    public function truncate(string $table): bool
    {
        $results = $this->query('TRUNCATE TABLE `' . $table . '`')->execute();
        return $results->success;
    }

    /**
     * 테이블의 이름을 변경한다.
     *
     * @param string $table 변경전 테이블명
     * @param string $newname 변경할 테이블명
     * @return bool $success
     */
    public function rename(string $table, string $newname): bool
    {
        $results = $this->query('RENAME TABLE `' . $table . '` TO `' . $newname . '`')->execute();
        return $results->success;
    }

    /**
     * 백업테이블을 생성한다.
     *
     * @param string $table 백업할 테이블명
     * @return bool $success
     */
    public function backup(string $table): bool
    {
        return true;
    }

    /**
     * 컬럼을 수정한다.
     *
     * @param string $table 컬럼을 변경할 테이블명
     * @param string $target 변경할 컬럼명 (변경할 컬럼명이 테이블에 존재하지 않을 경우 컬럼을 추가한다.)
     * @param object|bool $column 변경할 컬럼구조 (FALSE 인 경우 컬럼을 삭제한다.)
     * @param ?string $after 컬럼을 추가할 위치 (NULL : 마지막, @ : 처음, 컬럼명 : 해당컬럼명 뒤)
     * @return bool $success
     */
    public function alter(string $table, string $target, object|bool $column, ?string $after = null): bool
    {
        /**
         * 테이블이 존재하는지 확인한다.
         */
        if ($this->exists($table) == false) {
            return false;
        }

        /**
         * 컬럼이 존재하는지 확인한다.
         */
        $alter = '';
        $check = $this->query('SHOW FULL COLUMNS FROM `' . $table . '` WHERE `Field`=?', [$target])->has();
        if ($check == true) {
            if (is_bool($column) == true && $column === false) {
                $alter = 'DROP';
            } elseif (is_object($column) == true) {
                $alter = 'CHANGE';
            } else {
                return false;
            }
        } else {
            if (is_object($column) == true) {
                $alter = 'ADD';
            } else {
                return false;
            }
        }

        $query = 'ALTER TABLE `' . $table . '` ' . $alter;
        if ($alter == 'CHANGE') {
            $query .= ' `' . $target . '`';
        }

        if ($alter != 'DROP') {
            $query .= ' ' . $this->_columnQuery($column->name ?? $target, $column);
            if ($after !== null) {
                $query .= $after == '@' ? ' FIRST' : ' AFTER `' . $after . '`';
            }
        }

        $this->query($query)->execute();

        return true;
    }

    /**
     * LOCK 방법을 설정한다.
     *
     * @param string $method LOCK METHOD (READ, WRITE)
     * @return DatabaseInterface $this
     */
    public function setLockMethod(string $method): DatabaseInterface
    {
        switch (strtoupper($method)) {
            case 'READ' || 'WRITE':
                $this->_tableLockMethod = $method;
                break;
            default:
                $this->_error('Bad lock type: Can be either READ or WRITE');
                break;
        }
        return $this;
    }

    /**
     * 단일 테이블을 설정된 LOCK 방법에 따라 LOCK 한다.
     *
     * @param string $table LOCK할 테이블
     * @param ?string $method LOCK METHOD (READ, WRITE)
     * @return bool $success
     */
    public function lock(string $table, ?string $method = null): bool
    {
        return $this->locks([$table], $method);
    }

    /**
     * 복수 테이블을 설정된 LOCK 방법에 따라 LOCK 한다.
     *
     * @param array $tables LOCK할 테이블
     * @param ?string $method LOCK METHOD (READ, WRITE)
     * @return bool $success
     */
    public function locks(array $tables, ?string $method = null): bool
    {
        return true;
    }

    /**
     * 현재 LOCK 중인 테이블을 UNLOCK 한다.
     *
     * @return bool $success
     */
    public function unlock(): bool
    {
        return true;
    }

    /**
     * 쿼리빌더 없이 RAW 쿼리를 실행한다.
     *
     * @param string $query 쿼리문
     * @param array $bindParams 바인딩할 변수
     * @return DatabaseInterface $this
     */
    function query(string $query, ?array $bindParams = null): DatabaseInterface
    {
        $this->_start('RAW');
        $this->_query = trim($query);
        if ($bindParams !== null) {
            $this->_bindParams($bindParams);
        }

        return $this;
    }

    /**
     * SELECT 쿼리빌더를 시작한다.
     *
     * @param array $columns 가져올 컬럼명
     * @return DatabaseInterface $this
     */
    public function select(array $columns = []): DatabaseInterface
    {
        $this->_start('SELECT');

        $this->_columns = $columns;
        $this->_query = 'SELECT ';

        return $this;
    }

    /**
     * INSERT 쿼리빌더를 시작한다.
     *
     * @param string $table 테이블명
     * @param array $data 저장할 데이터 ([컬럼명=>값] 또는 [컬럼명])
     * @return DatabaseInterface $this
     */
    public function insert(string $table, array $data): DatabaseInterface
    {
        $this->_start('INSERT');

        $this->_query = 'INSERT INTO `' . $table . '`';
        $this->_tableDatas = $data;

        return $this;
    }

    /**
     * REPLACE 쿼리빌더를 시작한다.
     *
     * @param string $table 테이블명
     * @param array $data 저장할 데이터 ([컬럼명=>값])
     * @return DatabaseInterface $this
     */
    public function replace(string $table, array $data): DatabaseInterface
    {
        $this->_start('REPLACE');

        $this->_query = 'REPLACE INTO ' . $table;
        $this->_tableDatas = $data;

        return $this;
    }

    /**
     * UPDATE 쿼리빌더를 시작한다.
     *
     * @param string $table 테이블명
     * @param array $data 변경할 데이터 ([컬럼명=>값])
     * @return DatabaseInterface $this
     */
    public function update(string $table, array $data): DatabaseInterface
    {
        $this->_start('UPDATE');

        $this->_query = 'UPDATE ' . $table . ' SET ';
        $this->_tableDatas = $data;

        return $this;
    }

    /**
     * DELETE 쿼리빌더를 시작한다.
     *
     * @param string $table 테이블명
     * @return DatabaseInterface $this
     */
    public function delete(string $table): DatabaseInterface
    {
        $this->_start('DELETE');

        $this->_query = 'DELETE FROM ' . $table;

        return $this;
    }

    /**
     * FROM 절을 정의한다.
     *
     * @param string $table 테이블명
     * @param ?string $alias 테이블별칭
     */
    public function from(string $table, ?string $alias = null): DatabaseInterface
    {
        $this->_from_table = $table;
        $this->_from_alias = $alias;

        return $this;
    }

    /**
     * 검색조건 오류를 검사한다.
     *
     * @param string $prop 조건절
     * @param mixed $value 조건값
     * @param string $operator 조건
     * @return bool $success
     */
    private function _checkWhere(string $prop, mixed $value, ?string $operator): bool
    {
        $operator ??= '';
        $operator = strtolower($operator);

        if (is_array($value) === true) {
            if (substr_count($prop, '?') != count($value) && in_array($operator, ['not in', 'in']) == false) {
                $this->_error(\Language::getText('errors/databases/array_value'));
                return false;
            }
        }

        if (in_array($operator, ['not in', 'in']) == true && is_array($value) == false) {
            $this->_error(\Language::getText('errors/databases/in_operator'));
            return false;
        }

        return true;
    }

    /**
     * WHERE 절을 정의한다. (AND조건)
     *
     * @param string $whereProp WHERE 조건절 (컬럼명 또는 WHERE 조건문)
     * @param mixed $whereValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
     * @param ?string $operator 조건 (=, !=, IN, NOT IN, LIKE 등)
     * @return DatabaseInterface $this
     */
    public function where(string $whereProp, mixed $whereValue = null, ?string $operator = null): DatabaseInterface
    {
        if ($this->_checkWhere($whereProp, $whereValue, $operator) == true) {
            if ($operator) {
                $whereValue = [$operator => $whereValue];
            }
            $this->_where[] = ['AND', $whereValue, $whereProp];
        }
        return $this;
    }

    /**
     * WHERE 절을 정의한다. (OR조건)
     *
     * @param string $whereProp WHERE 조건절 (컬럼명 또는 WHERE 조건문)
     * @param mixed $whereValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
     * @param ?string $operator 조건 (=, !=, IN, NOT IN, LIKE 등)
     * @return DatabaseInterface $this
     */
    public function orWhere(string $whereProp, $whereValue = null, ?string $operator = null): DatabaseInterface
    {
        if ($this->_checkWhere($whereProp, $whereValue, $operator) == true) {
            if ($operator) {
                $whereValue = [$operator => $whereValue];
            }
            $this->_where[] = ['OR', $whereValue, $whereProp];
        }

        return $this;
    }

    /**
     * HAVING 절을 정의한다. (AND조건)
     *
     * @param string $havingProp HAVING 조건절 (컬럼명 또는 WHERE 조건문)
     * @param mixed $havingValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
     * @param ?string $operator 조건 (=, !=, IN, NOT IN, LIKE 등)
     * @return DatabaseInterface $this
     */
    public function having($havingProp, $havingValue = null, ?string $operator = null): DatabaseInterface
    {
        if ($this->_checkWhere($havingProp, $havingProp, $operator) == true) {
            if ($operator) {
                $havingValue = [$operator => $havingValue];
            }
            $this->_having[] = ['AND', $havingValue, $havingProp];
        }

        return $this;
    }

    /**
     * HAVING 절을 정의한다. (OR조건)
     *
     * @param string $havingProp HAVING 조건절 (컬럼명 또는 WHERE 조건문)
     * @param mixed $havingValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
     * @param ?string $operator 조건 (=, !=, IN, NOT IN, LIKE 등)
     * @return DatabaseInterface $this
     */
    public function orHaving(string $havingProp, $havingValue = null, ?string $operator = null): DatabaseInterface
    {
        if ($this->_checkWhere($havingProp, $havingProp, $operator) == true) {
            if ($operator) {
                $havingValue = [$operator => $havingValue];
            }
            $this->_having[] = ['OR', $havingValue, $havingProp];
        }

        return $this;
    }

    /**
     * JOIN 절을 정의한다. (AND조건)
     *
     * @param string $joinTable JOIN 할 테이블명
     * @param string $joinAlias JOIN 할 테이블별칭
     * @param string $joinCondition JOIN 조건
     * @param string $joinType 조인형태 (LEFT, RIGHT, OUTER, INNER, LEFT OUTER, RIGHT OUTER)
     * @return DatabaseInterface $this
     */
    public function join(
        string $joinTable,
        string $joinAlias,
        string $joinCondition,
        string $joinType = ''
    ): DatabaseInterface {
        $allowedTypes = ['LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'];
        $joinType = strtoupper(trim($joinType));
        if ($joinType && in_array($joinType, $allowedTypes) == false) {
            $this->_error('Wrong JOIN type: ' . $joinType);
        }

        $this->_join[] = [$joinType, $joinAlias, $joinTable, $joinCondition];

        return $this;
    }

    /**
     * ORDER 절을 정의한다.
     *
     * @param string $orderByField 정렬할 필드명
     * @param string $orderbyDirection 정렬순서 (ASC, DESC)
     * @param ?array $customFields 우선적으로 정렬할 값 (FIELD절)
     * @return DatabaseInterface $this
     */
    public function orderBy(
        string $orderByField,
        string $orderbyDirection = 'DESC',
        ?array $customFields = null
    ): DatabaseInterface {
        $allowedDirection = ['ASC', 'DESC'];
        $orderbyDirection = strtoupper(trim($orderbyDirection));
        $orderByField = preg_replace('/[^-a-z0-9\.\(\),_\* <>=!"\']+/i', '', $orderByField);
        if (empty($orderbyDirection) == true || in_array($orderbyDirection, $allowedDirection) == false) {
            $this->_error('Wrong order direction: ' . $orderbyDirection);
        }

        if ($customFields !== null) {
            foreach ($customFields as $key => $value) {
                $customFields[$key] = preg_replace('/[^-a-z0-9\.\(\),_\* <>=!"\']+/i', '', $value);
            }
            $orderByField = 'FIELD (' . $orderByField . ',"' . implode('","', $customFields) . '")';
        }
        $this->_orderBy[$orderByField] = $orderbyDirection;

        return $this;
    }

    /**
     * GROUP 절을 정의한다.
     *
     * @param string $groupByField GROUP 할 컬럼명
     * @return DatabaseInterface $this
     */
    public function groupBy(string $groupByField): DatabaseInterface
    {
        $groupByField = preg_replace('/[^-a-z0-9\.\(\),_]+/i', '', $groupByField);
        $this->_groupBy[] = $groupByField;

        return $this;
    }

    /**
     * LIMIT 절을 정의한다.
     *
     * @param int $start 시작점
     * @param ?int $limit 가져올 갯수 ($limit 이 정의되지 않은 경우, 0번째 부터 $start 갯수만큼 가져온다.)
     * @return DatabaseInterface $this
     */
    public function limit(int $start, ?int $limit = null): DatabaseInterface
    {
        if ($limit !== null) {
            $this->_limit = [$start, $limit];
        } else {
            $this->_limit = [0, $start];
        }
        return $this;
    }

    /**
     * 쿼리를 실행한다.
     *
     * @return object $results 실행결과
     */
    public function execute(): object
    {
        if ($this->_is_builder_stated === false) {
            $this->_error('No execute query');
        }

        $this->_buildQuery();
        $results = $this->_execute();
        $this->_end();

        return $results;
    }

    /**
     * SELECT 쿼리문에 의해 선택된 데이터의 갯수를 가져온다.
     *
     * @return int $count
     */
    public function count(): int
    {
        if ($this->_is_builder_stated === false) {
            $this->_error('No execute query');
        }

        $this->_buildQuery();
        $results = $this->_execute(false);
        $this->_end();

        return $results->num_rows;
    }

    /**
     * SELECT 쿼리문에 의해 선택된 데이터가 존재하는지 확인한다.
     *
     * @return boolean $has
     */
    public function has(): bool
    {
        return $this->count() > 0;
    }

    /**
     * SELECT 쿼리문에 의해 선택된 데이터를 가져온다.
     *
     * @param ?string $field 필드명 (필드명을 지정할 경우, 컬럼명->컬럼값이 아닌 해당 필드명의 값만 배열로 반환한다.)
     * @return array $items
     */
    public function get(?string $field = null): array
    {
        if ($this->_is_builder_stated === false) {
            $this->_error('No execute query');
        }

        $this->_buildQuery();
        $results = $this->_execute();
        $this->_end();

        $datas = $results->datas;
        if ($field !== null) {
            array_walk(
                $datas,
                function (&$item, $key, $field) {
                    $item = isset($item->{$field}) == true ? $item->{$field} : null;
                },
                $field
            );
        } else {
            $datas = $results->datas;
        }

        return $datas;
    }

    /**
     * SELECT 쿼리문에 의해 선택된 데이터중 한개만 가져온다.
     *
     * @param ?string $field 필드명 (필드명을 지정할 경우, 컬럼명->컬럼값이 아닌 해당 필드명의 값만 반환한다.)
     * @return mixed $item
     */
    public function getOne(?string $field = null): mixed
    {
        $result = $this->get($field);
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * 현재까지 쿼리빌더에 의해 생성된 쿼리를 복제한다.
     *
     * @return DatabaseInterface $copy 복제된 쿼리빌더 클래스
     */
    public function copy(): DatabaseInterface
    {
        $copy = unserialize(serialize($this));
        $copy->_mysqli = $this->_mysqli;
        return $copy;
    }

    /**
     * 트랜잭션을 시작한다.
     */
    public function transaction(): void
    {
        $this->_mysqli->autocommit(false);
        $this->_transaction_in_progress = true;
        //register_shutdown_function([$this, '_transaction_status_check']);
    }

    /**
     * 입력된 모든 쿼리를 커밋한다.
     */
    public function commit(): void
    {
        $this->_mysqli->commit();
        $this->_transaction_in_progress = false;
        $this->_mysqli->autocommit(true);
    }

    /**
     * 입력된 쿼리를 롤백한다.
     */
    public function rollback(): void
    {
        $this->_mysqli->rollback();
        $this->_transaction_in_progress = false;
        $this->_mysqli->autocommit(true);
    }

    /**
     * 마지막에 실행된 쿼리문을 가져온다.
     *
     * @return string $query
     */
    public function getLastQuery(): ?string
    {
        return $this->_lastQuery;
    }

    /**
     * 마지막 에러메시지를 가져온다.
     *
     * @return ?string $error
     */
    public function getLastError(): ?string
    {
        return $this->_lastError ?? ($this->_mysqli->error ? $this->_mysqli->error : null);
    }

    /**
     * 에러메시지 표시여부를 설정한다.
     *
     * @param bool $display
     * @return DatabaseInterface $this
     */
    public function displayError(bool $display): DatabaseInterface
    {
        $this->_displayError = $display;
        return $this;
    }

    /**
     * escape 한 문자열을 가져온다.
     * 예 : iModule's class -> iModule\'s class
     *
     * @param string $str
     * @return string $escaped_str
     */
    public function escape($str)
    {
        return $this->_mysqli->real_escape_string($str);
    }

    /**
     * 쿼리빌더를 시작한다.
     *
     * @param ?string $startQuery 시작쿼리
     */
    private function _start(?string $startQuery): void
    {
        if ($this->_is_builder_stated === true) {
            if ($this->_startQuery == 'INSERT' && $startQuery == 'SELECT') {
            } else {
                $this->_error('Previous Query is not finished.');
            }
        } else {
            $this->_startQuery = $startQuery;
            $this->_is_builder_stated = true;
        }
    }

    /**
     * 쿼리빌더를 종료한다.
     */
    private function _end(): void
    {
        $this->reset();
    }

    /**
     * 쿼리빌더로 정의된 설정값을 이용하여 실제 쿼리문을 생성한다.
     */
    private function _buildQuery(): void
    {
        switch ($this->_startQuery) {
            case 'SELECT':
                $this->_buildColumn();
                $this->_buildFrom();
                break;

            case 'INSERT':
            case 'REPLACE':
            case 'UPDATE':
                $this->_buildTableData();
                break;
        }

        $this->_buildJoin();
        //		if (empty($this->_tableDatas) == false) $this->_buildTableData($this->_tableDatas);
        $this->_buildWhere();
        //		$this->_buildGroupBy();
        //		$this->_buildHaving();
        $this->_buildOrderBy();
        $this->_buildLimit();
        $this->_lastQuery = $this->_replacePlaceHolders();
    }

    /**
     * 바인딩되는 데이터를 추가하고 prepared 쿼리의 대치문자열을 반환한다.
     *
     * @param string $operator
     * @param mixed $value
     * @return string $query
     */
    private function _buildPair(string $operator, $value): string
    {
        if (is_object($value) == true) {
            return $this->_error('OBJECT_PAIR');
        }
        $this->_bindParam($value);
        return ' ' . $operator . ' ? ';
    }

    /**
     * SELECT 절의 컬럼을 생성한다.
     */
    private function _buildColumn(): void
    {
        if ($this->_startQuery !== 'SELECT') {
            return;
        }
        if (count($this->_columns) == 0) {
            $this->_query .= '*';
        } else {
            // todo: 서브쿼리
            $this->_query .= implode(', ', $this->_columns);
        }
    }

    /**
     * FROM 절을 생성한다.
     */
    private function _buildFrom(): void
    {
        if ($this->_startQuery === null) {
            return;
        }

        if ($this->_from_table !== null) {
            $this->_query .= ' FROM `' . $this->_from_table . '` ';
        } elseif ($this->_from_query !== null) {
            // todo: 서브쿼리
        }

        if ($this->_from_alias !== null) {
            $this->_query .= $this->_from_alias . ' ';
        }
    }

    /**
     * 테이블 데이터절 생성한다.
     */
    private function _buildTableData(): void
    {
        if (is_array($this->_tableDatas) == false) {
            return;
        }

        /**
         * INSERT, REPLACE 인 경우 컬럼을 추가한다.
         */
        if ($this->_startQuery !== 'UPDATE') {
            /**
             * INSERT INTO SELECT 인 경우 컬럼명만 처리한다.
             */
            if ($this->_from_table !== null) {
                $this->_query .= '(`' . implode('`,`', $this->_tableDatas) . '`)';
            } else {
                $this->_query .= '(`' . implode('`,`', array_keys($this->_tableDatas)) . '`)';
            }
            $this->_query .= ' VALUES (';
        }

        foreach ($this->_tableDatas as $column => $value) {
            /**
             * UPDATE 인 경우 컬럼명을 추가한다.
             */
            if ($this->_startQuery == 'UPDATE') {
                $this->_query .= '`' . $column . '`=';
            }

            if (is_null($value) == true) {
                $this->_query .= 'NULL,';
                continue;
            }

            if (is_object($value) == true) {
                $this->_query .= $this->_buildPair('', $value) . ',';
                continue;
            }

            if (is_array($value) == false) {
                $this->_bindParam($value);
                $this->_query .= '?,';
                continue;
            }
        }
        $this->_query = rtrim($this->_query, ',');
        if ($this->_startQuery !== 'UPDATE') {
            $this->_query .= ')';
        }
    }

    /**
     * JOIN 절을 생성한다.
     */
    private function _buildJoin(): void
    {
        if (empty($this->_join) == true) {
            return;
        }

        foreach ($this->_join as $data) {
            list($joinType, $joinAlias, $joinTable, $joinCondition) = $data;
            $joinStr = $joinTable . ' ' . $joinAlias;
            $this->_query .= ' ' . $joinType . ' JOIN ' . $joinStr . ' ON ' . $joinCondition;
        }
    }

    /**
     * WHERE 절을 생성한다.
     */
    private function _buildWhere(): void
    {
        if (empty($this->_where) == true) {
            return;
        }
        $this->_query .= ' WHERE ';
        $this->_where[0][0] = '';

        foreach ($this->_where as $index => &$cond) {
            list($concat, $wValue, $wKey) = $cond;

            if ($wKey == '(') {
                $this->_query .= ' ' . $concat . ' ';
                if (isset($this->_where[$index + 1]) == true) {
                    $this->_where[$index + 1][0] = '';
                }
            } elseif ($wKey != ')') {
                $this->_query .= ' ' . $concat . ' ';
            }
            if (
                is_array($wValue) == false ||
                (strtolower(key($wValue)) != 'inset' && strtolower(key($wValue)) != 'fulltext')
            ) {
                $this->_query .= $wKey;
            }

            if ($wValue === null) {
                continue;
            }

            if (is_array($wValue) == false) {
                $wValue = ['=' => $wValue];
            }

            $key = key($wValue);
            $val = $wValue[$key];
            switch (strtolower($key)) {
                case '0':
                    $this->_bindParams($wValue);
                    break;
                case 'not in':
                case 'in':
                    $comparison = ' ' . $key . ' (';
                    if (is_object($val) == true) {
                        $comparison .= $this->_buildPair('', $val);
                    } else {
                        foreach ($val as $v) {
                            $comparison .= ' ?,';
                            $this->_bindParam($v);
                        }
                    }
                    $this->_query .= rtrim($comparison, ',') . ' ) ';
                    break;
                case 'inset':
                    $comparison = ' FIND_IN_SET (?,' . $wKey . ')';
                    $this->_bindParam($val);

                    $this->_query .= $comparison;
                    break;
                case 'is not':
                    $this->_query .= ' IS NOT NULL';
                    break;
                case 'is':
                    $this->_query .= ' IS NULL';
                    break;
                case 'not between':
                case 'between':
                    $this->_query .= " $key ? AND ? ";
                    $this->_bindParams($val);
                    break;
                case 'not exists':
                case 'exists':
                    $this->_query .= $key . $this->_buildPair('', $val);
                    break;
                case 'not like':
                case 'like':
                    $this->_query .= " $key ? ";
                    $this->_bindParam($val);
                    break;
                case 'fulltext':
                    $comparison = ' MATCH (' . $wKey . ') AGAINST (? IN BOOLEAN MODE)';

                    $keylist = explode(' ', $val);
                    for ($i = 0, $loop = count($keylist); $i < $loop; $i++) {
                        $keylist[$i] = '\'+' . $keylist[$i] . '*\'';
                    }
                    $keylist = implode(' ', $keylist);

                    $this->_bindParam($keylist);
                    $this->_query .= $comparison;

                    break;
                default:
                    $this->_query .= $this->_buildPair($key, $val);
            }
        }
    }

    /**
     * ORDER 절을 생성한다.
     */
    private function _buildOrderBy(): void
    {
        if (empty($this->_orderBy) == true) {
            return;
        }

        $this->_query .= ' ORDER BY ';
        foreach ($this->_orderBy as $prop => $value) {
            if (strtolower(str_replace(' ', '', $prop)) == 'rand()') {
                $this->_query .= 'RAND(),';
            } else {
                $this->_query .= $prop . ' ' . $value . ',';
            }
        }
        $this->_query = rtrim($this->_query, ',') . ' ';
    }

    /**
     * LIMIT 절을 생성한다.
     */
    private function _buildLimit(): void
    {
        if (empty($this->_limit) == true) {
            return;
        }

        $this->_query .= ' LIMIT ' . $this->_limit[0] . ',' . $this->_limit[1];
    }

    /**
     * 바인딩되는 변수의 타입을 반환한다.
     *
     * @param mixed $item 변수형태를 파악하기 위한 변수
     * @return string $type
     */
    private function _determineType($item): string
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';

            case 'boolean':
            case 'integer':
                return 'i';

            case 'blob':
                return 'b';

            case 'double':
                return 'd';

            default:
                return '';
        }
    }

    /**
     * 바인딩 데이터를 처리한다.
     *
     * @param mixed $value
     */
    private function _bindParam($value): void
    {
        array_push($this->_bindTypes, $this->_determineType($value));
        array_push($this->_bindParams, $value);
    }

    /**
     * 바인딩 데이터를 처리한다.
     *
     * @param array $values
     */
    private function _bindParams(array $values): void
    {
        foreach ($values as $value) {
            $this->_bindParam($value);
        }
    }

    /**
     * 쿼리문을 실행한다.
     *
     * @param bool $is_datas 쿼리실행결과 데이터를 반환할지 여부(기본값 : true)
     * @return object $results 실행결과
     */
    private function _execute(bool $is_datas = true): object
    {
        $results = new stdClass();
        $results->success = false;
        $results->affected_rows = 0;
        $results->insert_id = 0;
        $results->num_rows = 0;
        $results->datas = [];

        $stmt = $this->_mysqli->prepare($this->_query);
        if (!$stmt) {
            $this->_lastError = $this->_mysqli->error;
            $this->_error($this->_mysqli->error);
            return $results;
        }

        if (count($this->_bindParams) > 0) {
            $params = [];
            $params[] = implode('', $this->_bindTypes);
            foreach ($this->_bindParams as &$value) {
                $params[] = &$value;
            }
            $stmt->bind_param(...$params);
            //call_user_func_array([$stmt,'bind_param'],$params);
        }

        $success = $stmt->execute();
        if ($success === false) {
            $this->_lastError = $stmt->error;
            $this->_error($stmt->error);
            return $results;
        }

        $stmt->store_result();

        $results->success = $stmt->sqlstate === '00000';
        $results->affected_rows = $stmt->affected_rows;
        $results->insert_id = $stmt->insert_id;
        $results->num_rows = $stmt->num_rows;

        /**
         * 쿼리실행결과 데이터가 있는 경우
         */
        if ($is_datas == true) {
            $metadata = $stmt->result_metadata();
            if ($metadata !== false) {
                $results->datas = $this->_dynamicBindResults($stmt, $metadata);
            }
        }

        $stmt->free_result();

        if ($results->success == true) {
            $this->_lastError = null;
        }

        return $results;
    }

    /**
     * 쿼리에러를 처리한다.
     *
     * @param string $message 에러메시지
     */
    private function _error(string $message): void
    {
        $details = new stdClass();
        $details->type = 'mysql';
        $details->error = $this->_mysqli->error;
        $details->query = $this->_replacePlaceHolders();

        $this->reset();
        if ($this->_displayError == true) {
            ErrorHandler::print('DATABASE_ERROR', $message, $details);
        }
    }

    /**
     * 바인딩 된 변수를 쿼리에서 치환한다.
     *
     * @return array $values 바인딩된 쿼리
     */
    private function _replacePlaceHolders(): string
    {
        $i = 0;
        $origin = $this->_query;
        $replaced = '';
        while ($position = strpos($origin, '?')) {
            $value = $this->_bindParams[$i];
            $type = $this->_bindTypes[$i];

            if ($type == 's') {
                $value = '\'' . $value . '\'';
            } elseif ($type == 'b') {
                $value = '[blob]';
            }

            $replaced .= substr($origin, 0, $position) . $value;
            $origin = substr($origin, $position + 1);

            $i++;
        }
        $replaced .= $origin;
        return $replaced;
    }

    /**
     * 쿼리 실행결과 반환된 결과값을 정리한다.
     *
     * @param mysqli_stmt $stmt
     * @param mysqli_result $metadata
     * @return array $results
     */
    private function _dynamicBindResults(mysqli_stmt &$stmt, mysqli_result &$metadata): array
    {
        $parameters = [];
        $results = [];
        $row = [];
        while ($field = $metadata->fetch_field()) {
            $row[$field->name] = null;
            $parameters[] = &$row[$field->name];
        }
        $stmt->store_result();
        $stmt->bind_result(...$parameters);
        //call_user_func_array([$stmt,'bind_result'],$parameters);

        $this->_count = 0;
        while ($stmt->fetch()) {
            $result = [];
            foreach ($row as $key => $val) {
                $result[$key] = $val; //isset($val) == false || $val === null ? '' : $val;
            }
            array_push($results, (object) $result);
            $this->_count++;
        }
        $stmt->free_result();

        return $results;
    }

    /**
     * 컬럼이 숫자형태의 컬럼인지 확인한다.
     *
     * @param string $type 컬럼종류
     * @return bool $is_numeric
     */
    private function _isNumeric(string $type): bool
    {
        return in_array(strtolower($type), ['tinyint', 'int', 'bigint', 'float', 'double']) == true;
    }

    /**
     * 컬럼이 인코딩 설정이 필요한 컬럼인지 확인한다.
     *
     * @param string $type 컬럼종류
     * @return bool $is_text
     */
    private function _isText(string $type): bool
    {
        return in_array(strtolower($type), ['varchar', 'text', 'longtext']) == true;
    }

    /**
     * 컬럼이 길이 설정이 필요한 컬럼인지 확인한다.
     *
     * @param string $type 컬럼종류
     * @return bool $is_length
     */
    private function _isLength(string $type): bool
    {
        return in_array(strtolower($type), ['char', 'varchar', 'enum']) == true;
    }

    /**
     * 컬럼 구조에 대한 쿼리스트링을 가져온다.
     */
    private function _columnQuery(string $name, object $column): string
    {
        $query = '`' . $name . '` ';
        $query .= strtolower($column->type);
        if ($this->_isLength($column->type) == true && isset($column->length) == true) {
            $query .= '(' . $column->length . ')';
        }

        if ($this->_isText($column->type) == true) {
            $query .= ' CHARACTER SET ' . $this->_charset . ' COLLATE ' . $this->_collation;
        }

        if (($column->is_null ?? false) == true) {
            $query .= ' NULL';
        } else {
            $query .= ' NOT NULL';
        }

        if (isset($column->default) == true) {
            if ($column->default === null) {
                $query .= ' DEFAULT NULL';
            } else {
                $query .=
                    ' DEFAULT ' .
                    ($this->_isNumeric($column->type) == true ? $column->default : "'{$column->default}'");
            }
        }

        if (isset($column->comment) == true) {
            $query .= ' COMMENT \'' . $column->comment . '\'';
        }

        return $query;
    }
}
