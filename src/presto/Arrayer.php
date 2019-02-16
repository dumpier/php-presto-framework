<?php
namespace Presto;

use Presto\Traits\Singletonable;

class Arrayer
{
    use Singletonable;


    public function toTreeString(array $array, int $recursion=0)
    {
        if(empty($array))
        {
            return "";
        }

        $string = "";
        $string .= "<ul>";

        foreach ($array as $key=>$val)
        {
            if(is_array($val))
            {
                $string .= "<li>{$key} : ";
                $string .= $this->toTreeString($val, $recursion+1);
                $string .= "</li>";
                continue;
            }

            $string .="<li>{$key} : {$val}</li>";
        }

        $string .= "</ul>";
        return $string;
    }


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

    public function unset(array &$array, $path, $separator = '.')
    {
        $keys = explode($separator, $path);
        $current = &$array;
        $parent = &$array;

        foreach ($keys as $i => $key) {
            if (!array_key_exists($key, $current))
            {
                return;
            }

            if ($i)
            {
                $parent = &$current;
            }

            $current = &$current[$key];
        }

        unset($parent[$key]);
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

    public function clean(array $array)
    {
        foreach ($array as $key=>$val)
        {
            if($this->isClearTarget($val))
            {
                unset ($array[$key]);
                continue;
            }

            if(is_array($val))
            {
                $clean = $this->clean($val);
                if($this->isClearTarget($clean))
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


    private function isClearTarget($val)
    {
        if($val==="" || $val===null || $val===[])
        {
            return true;
        }

        return false;
    }

    public function getKeys(array $array, array $conditions=[])
    {
        // 検索条件に該当したCSVの行番号一覧
        $target_keys = [];

        foreach ($conditions as $field=>$val)
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
}