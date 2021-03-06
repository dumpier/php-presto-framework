<?php
namespace Presto\Core\Utilities;

use Presto\Core\Traits\Instanceable;
use Presto\Core\Traits\Bases\ArrayAccessTrait;
use Presto\Core\Traits\Bases\IteratorTrait;

class Collection implements \ArrayAccess, \Iterator
{
    use Instanceable;
    use ArrayAccessTrait;
    use IteratorTrait;

    /** @var array */
    protected $rows=[];

    /** @var int */
    protected $count = 0;


    public function __construct($rows=[], $class="")
    {
        if($rows instanceof self)
        {
            $this->rows = $rows->all();
        }
        else
        {
            $this->rows = ($class) ? $this->converts($rows, $class) : $rows;
        }
    }

    // ----------------------------------------------------------
    // クラスの変換 TODO 改良
    // ----------------------------------------------------------
    private function converts($rows, string $class)
    {
        $result = [];
        foreach ($rows as $row)
        {
            $result[] = $this->convert($row, $class);
        }

        return $result;
    }

    private function convert($row, string $class)
    {
        return new $class($row);
    }

    // TODO 改良
    public function toArray()
    {
        $rows = [];
        foreach ($this->rows as $key=>$row)
        {
            $rows[$key] = is_array($row) ? $row : $row->toArray();
        }
        return $rows;
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
    }
    // ----------------------------------------------------------


    // ----------------------------------------------------------
    // 追加、削除など
    // ----------------------------------------------------------
    // 追加
    public function put($name, $row=null)
    {
        if($row === null)
        {
            $this->rows[] = $name;
        }
        else
        {
            $this->rows[$name] = $row;
        }

        return $this;
    }


    // 削除
    public function delete(string $name, $expression, ...$value)
    {
        return $this->nowhere($name, $expression, $value);
    }
    // ----------------------------------------------------------


    /**
     * 全部取得
     * @return array
     */
    public function all(int $count=0)
    {
        if($count)
        {
            $rows = array_slice($this->rows, 0, $count);
            return new static($rows);
        }

        return $this->rows;
    }


    /**
     * 指定条件で先頭の1個を取得
     * @param array $condition
     * @return array|mixed
     */
    public function first(array $condition=[])
    {
        $rows = $this->condition($condition)->all();
        return array_shift($rows);
    }


    /**
     * 指定条件で最後の1個を取得
     * @param array $condition
     * @return array|mixed
     */
    public function last(array $condition=[])
    {
        return end($this->rows);
    }


    /**
     * データ件数の取得
     * @param array $condition
     * @return number
     */
    public function count(array $condition=[])
    {
        if($condition)
        {
            return $this->condition($condition)->count;
        }

        return $this->count = count($this->rows);
    }


    /**
     * 合計
     * @param string $name
     * @return number
     */
    public function sum(string $name)
    {
        return array_sum(array_column($this->rows, $name));
    }

    /**
     * 指定項目の抽出
     * @param string $name
     * @return \Presto\Core\Utilities\Collection
     */
    public function column(string $name)
    {
        return new static(array_column($this->rows, $name));
    }


    /**
     * 指定項目の抽出 TODO 改良
     * @param string ...$names
     * @return \Presto\Core\Utilities\Collection
     */
    public function columns(...$names)
    {
        $rows = [];

        $columns = array_keys($this->rows);

        foreach ($this->rows as $key=>$val)
        {
            $row = [];
            foreach ($columns as $column)
            {
                if(in_array($column, $names))
                {
                    $row[$column] = $val[$column];
                }
            }

            $rows[$key] = $row;
        }

        return new static($rows);
    }


    public function shuffle()
    {
        $rows = $this->rows;
        shuffle($rows);
        return new static($rows);
    }

    public function merge(Collection $Collection)
    {
        $rows = array_merge($this->rows, $Collection->rows);
        return new static($rows);
    }


    /**
     * WHERE
     * @param string $name
     * @param mixed $expression
     * @param mixed ...$value
     * @return \Presto\Core\Utilities\Collection
     */
    public function where(string $name, $expression, ...$value)
    {
        return $this->whereOrNowhere(TRUE, $name, $expression, $value);
    }

    /**
     * NOWHERE
     * @param string $name
     * @param mixed $expression
     * @param mixed ...$value
     * @return \Presto\Core\Utilities\Collection
     */
    public function nowhere(string $name, $expression, ...$value)
    {
        return $this->whereOrNowhere(FALSE, $name, $expression, $value);
    }

    private function whereOrNowhere(bool $is_where=TRUE, string $name, $expression, ...$value)
    {
        $rows = array_filter($this->rows, function($row) use ($is_where, $name, $expression, $value) {
            return $is_where && Expression::instance()->compare($row[$name], $expression, ...$value);
        });

        return new static($rows);
    }


    /**
     * 条件で一覧を絞る
     * @param array $condition
     * @return \Presto\Core\Utilities\Collection
     */
    public function condition(array $condition)
    {
        $rows = [];
        $count = 0;
        $limit = empty($condition["limit"]) ? 0 : $condition["limit"];

        foreach ($this->rows as $row)
        {
            if(Expression::instance()->isMatch($row, $condition))
            {
                $rows[] = $row;
                $count ++;

                if($limit && $count >= $limit )
                {
                    break;
                }
            }
        }

        return new static($rows);
    }


    /**
     * 並び替え
     * @param string $property
     * @param int $sort
     * @return \Presto\Core\Utilities\Collection
     */
    public function sort(string $property, int $sort=SORT_ASC)
    {
        $properties = array_column($this->rows, $property);

        $rows = array_multisort($properties, $sort, $this->rows);

        return new static($rows);
    }


    /**
     * 配列の一部を展開する
     * @return \Presto\Core\Utilities\Collection
     */
    public function slice(int $limit, int $offset=0)
    {
        $rows = array_slice(array_values($this->rows), $offset, $limit);

        return new static($rows);
    }


    /**
     * 配列を指定サイズで再分割する
     * @param int $size
     * @param bool $preserve
     * @return \Presto\Core\Utilities\Collection
     */
    public function chunk(int $size, bool $preserve=false)
    {
        return new static(array_chunk($this->rows, $size, $preserve));
    }
}