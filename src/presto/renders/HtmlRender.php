<?php
namespace Presto\Renders;

use Presto\Traits\Singletonable;

class HtmlRender
{
    use Singletonable;

    public function html(array $contents=[])
    {
        if(!empty($contents["breadcrumb"]))
        {
            breadcrumb()->adds($contents["breadcrumb"]);
        }

        if( config('cache', 'views.enable') )
        {
            // テンプレートキャッシュのロード
            $phtml =  $this->loadCache();
        }
        else
        {
            // テンプレートのロード
            $phtml =  $this->loadTemplate();
        }

        // コントローラーから渡されたパラメータ
        extract($contents);
        eval("?>" . $phtml);
        // include($cache_file);
    }


    /**
     * キャッシュのロード
     */
    protected function loadCache()
    {
        $template_file = view()->getHtmlTemplate();

        $prefix = str_replace("/", ".", str_replace(path("app/views/"), "", trim($template_file,".phtml")));
        $checksum = md5_file($template_file);
        // TODO とりあえずファイル名を固定にする
        $checksum = 1;

        // キャッシュファイル名
        $cache_file =  path("storages/cache/views/{$prefix}.{$checksum}.phtml");

        if( file_exists($cache_file) && false )
        {
            $phtml =file_get_contents($cache_file);
        }
        else
        {
            // テンプレートをロードする
            $phtml = $this->loadTemplate($template_file);

            // キャッシュファイルを作成する
            file_put_contents($cache_file, $phtml);
        }

        return $phtml;
    }

    /**
     * テンプレートのロード
     * @param string $template_file
     * @return string
     */
    protected  function loadTemplate()
    {
        $template_file = view()->getHtmlTemplate();

        // テンプレートを読み込む
        $phtml_template = file_get_contents( $template_file );

        // レイアウトを読み込む
        $layout = view()->getHtmlLayout();

        $phtml_layout = file_get_contents( $layout );

        // レイアウトにテンプレートを反映する
        $phtml = preg_replace('/@content/', $phtml_template, $phtml_layout);

        // 独自タグを変換する
        $phtml = template()->convert($phtml);

        return $phtml;
    }

}