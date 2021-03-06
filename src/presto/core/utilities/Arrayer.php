<?php
namespace Presto\Core\Utilities;

use Presto\Core\Traits\Singletonable;
use Presto\Core\Databases\Model\Model;

class Arrayer
{
    use Singletonable;


    public function get(array $array, $path, $separator = '.')
    {
        $keys = explode($separator, trim($path));
        $current = $array;

        foreach ($keys as $key)
        {
            if (!isset($current[$key]))
            {
                return;
            }

            $current = $current[$key];
        }

        return $current;
    }

    public function set(array &$array, $path, $value, $separator = '.')
    {
        $keys = explode($separator, $path);
        $current = &$array;

        foreach ($keys as $key)
        {
            $current = &$current[$key];
        }

        $current = $value;
    }


    public function depth(array $array, $depth = 0)
    {
        if (is_array($array) && count($array))
        {
            ++$depth;
            $_c = array($depth);

            foreach ($array as $v)
            {
                if (is_array($v) && count($v))
                {
                    $_c[] = $this->depth($v, $depth);
                }
            }

            return max($_c);
        }

        return $depth;
    }


    public function getKeys(array $array, array $condition=[])
    {
        // 検索条件に該当したCSVの行番号一覧
        $target_keys = [];

        foreach ($condition as $field=>$val)
        {
            // 条件に該当したCSVの行番号一覧
            $keys = array_keys(array_column($array, $field), $val);

            // AND条件で行番号を絞る
            if(empty($target_keys))
            {
                $target_keys = $keys;
            }
            else
            {
                $target_keys = array_intersect($target_keys, $keys);
            }
        }

        return $target_keys;
    }


    public function toTreeString($val)
    {

    }


    /**
     * 二つの配列を結合する TODO TODO TODO TODO
     * @param Collection|Model[]|array|mixed $rows
     * @param Collection|Model[]|array|mixed $childrens
     * @param array $joins JOIN Condition
     * @param string $type
     * @param bool $is_model
     * @return array
     */
    public function mapping($rows, $childrens, array $joins, string $type=Model::HAS_MANY, bool $is_model=false)
    {
        foreach ($rows as $no=>$row)
        {
            foreach ($joins as $foreign_name=>$mappings)
            {
                $keys = array_keys($mappings);
                $values = array_map(function($key)use ($row){ return $row[$key]; }, $keys);
                $foreign_keys = array_values($mappings);

                $condition = array_combine($foreign_keys, $values);

                if($is_model)
                {
                    $rows[$no]->relations[$foreign_name] = ($type==Model::HAS_MANY) ? Collection::instance($childrens)->condition($condition) : Collection::instance($childrens)->first($condition);
                }
                else
                {
                    $rows[$no][$foreign_name] = ($type==Model::HAS_MANY) ? Collection::instance($childrens)->condition($condition)->all() : Collection::instance($childrens)->first($condition);
                }
            }
        }

        return $rows;
    }


    /**
     * 無効な入力をなくす
     * @param array $array
     * @return mixed|array
     */
    public function clean(array $array)
    {
        foreach ($array as $key=>$val)
        {
            if($this->isEmpty($val))
            {
                unset ($array[$key]);
                continue;
            }

            if(is_array($val))
            {
                $clean = $this->clean($val);
                if($this->isEmpty($clean))
                {
                    unset ($array[$key]);
                }
                else
                {
                    $array[$key] = $clean;
                }
            }
        }

        return $array;
    }


    private function isEmpty($val)
    {
        if($val==="" || $val===null || $val===[])
        {
            return true;
        }

        return false;
    }

}