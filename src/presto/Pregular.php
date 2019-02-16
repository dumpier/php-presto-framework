<?php
namespace Presto;

use Presto\Traits\Singletonable;

class Pregular
{
    use Singletonable;

    public static function all(string $pattern, string $text)
    {
        preg_match_all($pattern, $text, $rows);

        return $rows[0];
    }
}