<?php
namespace Presto\Core;

use Presto\Core\Traits\Singletonable;

class Response
{
    use Singletonable;

    const HTML = 'html';
    const JSON = 'json';
    const JSONP = 'jsonp';
    const TEXT = 'text';
    const FILE = 'file';
    const STREAM = 'stream';
    const STREAM_DOWNLOAD = 'streamDownload';
    const DOWNLOAD = 'download';

    protected $type;


    public function header(string $key, $values, $replace = true)
    {

    }


    public function redirect(string $url, int $code=302)
    {
        header("Location: $url", TRUE, $code);
        exit;
    }
}