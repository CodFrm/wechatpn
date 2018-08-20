<?php
/**
 *============================
 * author:Farmer
 * time:2017年1月4日 下午8:46:47
 * blog:blog.icodef.com
 * function:数据库操作类
 *============================
 */

namespace lib;

use PDO;

/**
 * 数据库操作类
 * Class db
 * @package lib
 */
class db {
    /**
     * @var PDO
     */
    static $db;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $arrTable = array();

    /**
     * @var array
     */
    private $logical = array(
        'or',
        'and'
    );

    /**
     * @var array
     */
    private $mark = array('b_start', 'b_end');

    /**
     * 表前缀
     * @var string
     */
    protected static $prefix = '';

    public function __construct($table = '') {
        $this->getDBObject($table);
    }

    public static function init($host = '', $user = '', $pwd = '', $db = '', $prefix = '') {
        $dns = 'mysql:dbname=' . $db . ';host=';
        $dns .= $host . ';charset=utf8';
        static::$db = new PDO($dns, $user, $pwd);
        static::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        static::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        static::$prefix = $prefix;
    }

    /**
     * 获取一个数据库操作对象
     * @param $table
     * @return db
     */
    public static function table($table) {
        return new db($table);
    }

    public static function query($sql) {
        return static::$db->query($sql);
    }

    public function getDBObject($table = '') {
        if (substr($table, 0, 1) == ':') {
            $this->table = substr($table, 1);
        } else {
            $this->table = static::$prefix . str_replace('|', ',' . static::$prefix, $table);
            preg_match_all('/,([\d\w_]+)/', ',' . $this->table, $arr);//直接在前面加个逗号...不处理了...
            $this->arrTable = $arr[1];
        }
        return $this;
    }

    public function __call($func, $arguments) {
        if (is_null(static::$db)) {
            return 0;
        }
        return call_user_func_array(array(
            static::$db,
            $func
        ), $arguments);
    }

    /**
     * 数组转换成查询的条件
     * @param $where
     * @param $param
     * @return string
     */
    protected function where($where, &$param) {
        if (empty($where)) {
            throw new \Exception('Sql where can not be empty');
        }
        $sql = ' ';
        $logical = '';
        $subscript = 0;
        foreach ($where as $key => $value) {
            $start = '';
            $end = '';
            if (is_numeric($key)) {
                if (is_string($value)) {
                    $sql .= ($subscript++ == 0 ? 'where ' : ' and ') . $value;
                    continue;
                }
                $key = $value[0];
                array_splice($value, 0, 1);
            }
            if (is_array($value)) {
                $arrsize = sizeof($value);
                $isarr = is_array($value [0]);
                $operator = '';
                if ($isarr) {
                    $tmpArr = $value[0];
                    $value[0] = '(';
                    foreach ($tmpArr as $v) {
                        $value[0] .= '?,';
                        $param[] = $v;
                    }
                    $value[0] = substr($value[0], 0, strlen($value[0]) - 1);
                    $value[0] .= ')';
                } else {
                    $param[] = $value[0];
                    $value[0] = ' ? ';
                }
                $logical = '';
                for ($n = 1; $n < $arrsize; $n++) {
                    if (in_array($value[$n], $this->logical)) {
                        $logical = $value[$n];
                    } else if (in_array($value[$n], $this->mark)) {
                        switch ($value[$n]) {
                            case 'b_start':
                                {
                                    $start = '(';
                                    break;
                                }
                            case 'b_end':
                                {
                                    $end = ')';
                                    break;
                                }
                        }
                    } else {
                        $operator = $value[$n];
                    }
                }
                $sql .= ($subscript++ == 0 ? 'where ' : ($logical ?: ' and '));
                if ($operator == '' and $isarr) {
                    $operator = 'in';
                }
                $sql .= preg_replace(array(
                    '/\$start/',
                    '/\$key/',
                    '/\$operator/',
                    '/\$value/',
                    '/\$end/',
                ), array(
                    $start,
                    $key,
                    $operator ?: '=',
                    !empty($value[0]) ? $value[0] : 'null',
                    $end
                ), '$start $key $operator $value $end');
            } else if (is_numeric($key)) {
                $sql .= ($subscript++ == 0 ? 'where ' : ' and ') . $value . ' ';
            } else if (substr($key, 0, 2) == '__') {
                $sql .= ' ' . substr($key, 2) . ' ' . $value . ' ';
            } else {
                $sql .= ($subscript++ == 0 ? 'where ' : ' and ') . $key . ' = ? ';
                $param[] = $value;
            }
        }
        return $sql;
    }

    /**
     * 插入一条数据返回改变条数
     *
     * @access public
     * @author Farmer
     * @param array $data
     * @return int
     */
    public function insert($data = 0) {
        if (!empty ($data)) {
            $table = $this->table;
            $param = [];
            $sql = 'insert into ' . $table . '(`' . implode('`,`', array_keys($data)) . '`) values(';
            foreach ($data as $value) {
                $sql .= '?,';
                $param[] = $value;
            }
            $sql = substr($sql, 0, strlen($sql) - 1);
            $sql .= ')';
            $result = static::$db->prepare($sql);
            if ($count = $result->execute($param)) {
                return $result->rowCount();
            }
            return false;
        }
        return false;
    }

    /**
     * 删除数据返回变化条数
     *
     * @author Farmer
     * @param mixed $where
     * @return int
     */
    public function delete($where = 0) {
        $sql = "delete from $this->table";
        $param = [];
        if (is_string($where)) {
            $sql .= $where;
        } else if (is_array($where) and !empty($where)) {
            $sql .= $this->where($where, $param);
        }
        $result = static::$db->prepare($sql);
        if ($count = $result->execute($param)) {
            return $result->rowCount();
        }
        return false;
    }

    /**
     * 修改数据返回变化条数
     *
     * @access public
     * @author Farmer
     * @param array $where
     * @return int
     */
    function update($data, $where = 0) {
        $sql = "update $this->table set ";
        $param = [];
        if (is_string($data)) {
            $sql .= $data;
        } else if (is_array($data)) {
            $add = 0;
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $sql .= ($add++ != 0 ? ',' : '') . $value;
                } else {
                    $sql .= ($add++ != 0 ? ',' : '') . $key . '= ? ';
                    $param[] = $value;
                }
            }
        }
        if (is_string($where)) {
            $sql .= $where;
        } else if (is_array($where)) {
            $sql .= $this->where($where, $param);
        }
        $result = static::$db->prepare($sql);
        if ($result->execute($param)) {
            return $result->rowCount();
        }
        return false;
    }

    /**
     * 查询返回sql记录集
     * @param int $where
     * @param int $field
     * @param string $join
     * @return bool|record
     * @throws \Exception
     */
    function select($where = 0, $field = 0, $join = '') {
        $sql = 'select ' . (empty($field) ? '*' : $field);
        $join = str_replace(':', static::$prefix, $join);
        $sql .= " from $this->table $join";
        $param = [];
        if (is_string($where)) {
            $sql .= $where;
        } else if (is_array($where)) {
            $sql .= $this->where($where, $param);
        }
        $result = static::$db->prepare($sql);
        if ($result->execute($param)) {
            return new record($result);
        }
        return false;
    }

    /**
     * 查找
     * @param int $where
     * @param int $field
     * @param string $join
     * @return mixed
     * @throws \Exception
     */
    function find($where = 0, $field = 0, $join = '') {
        $where['__limit'] = '1';
        return $this->select($where, $field, $join)->fetch();
    }

    /**
     * 开始事务
     * @author Farmer
     */
    function begin() {
        $this->exec('begin');
    }

    /**
     * 提交事务
     * @author Farmer
     */
    function commit() {
        $this->exec('commit');
    }

    /**
     * 回滚事务
     * @author Farmer
     */
    function rollback() {
        $this->exec('rollback');
    }
}

/**
 * 记录集类
 * Class record
 * @package lib
 */
class record {

    /**
     * @var \PDOStatement
     */
    private $result;

    public function __call($func, $arguments) {
        if (is_null($this->result)) {
            return 0;
        }
        return call_user_func_array(array(
            $this->result,
            $func
        ), $arguments);
    }

    function __construct($result) {
        $this->result = $result;
        $this->result->setFetchMode(PDO::FETCH_ASSOC);
    }

    public function countAll() {
        $rec = db::query('select FOUND_ROWS()');
        return $rec->fetch()['FOUND_ROWS()'];
    }

    public function fetch() {
        return $this->result->fetch();
    }
}
