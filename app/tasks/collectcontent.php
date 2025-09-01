<?php
namespace Fuel\Tasks;

use Config;

use Cums\Common;
use Cums\DB\Content;
use Cums\DB\Directory;
use Cums\DB\Domain;

class CollectContent
{
    public function run()
    {
        try
        {
            Config::load('cums', true);
            $find = Config::get('cums.find');

            // 1. Pull latest changes from local git repos for each domain
            foreach (Domain::list(true) as $dir) {
                Common::gitPull($dir['documentroot']);
            }

            $result = [];

            // 2. Scan index.html files locally (up to maxdepth)
            foreach (Domain::list(true) as $dir)
            {
                $files = Common::findLocalIndexHtmlFiles($dir['documentroot'], $find['maxdepth']);
                $result[$dir['domainid']] = $files;
            }

            // 3. Append additional files from Directory table (paths > 3rd level)
            foreach (Directory::list_with_domain() as $dir)
            {
                $basePath = rtrim($dir['documentroot'], '/') . '/' . trim($dir['path'], '/');
                if (strpos(basename($basePath), '.') === false) {
                    // if no dot, treat as directory, add /index.html
                    $basePath .= '/index.html';
                }

                if (is_file($basePath)) {
                    $result[$dir['domainid']][] = $basePath;
                }
            }

            $multi_parameters = [];

            // 4. Read each file locally, extract title and file modification time
            foreach ($result as $domainid => $files)
            {
                foreach ($files as $file)
                {
                    $parameters = [
                        'domainid' => $domainid,
                        'filename' => basename($file),
                        'path'     => $file,
                        'apemp'    => 'system',
                    ];

                    $target_html = file_get_contents($file);
                    $target_html = mb_convert_encoding($target_html, 'HTML-ENTITIES', 'auto');

                    $dom = new \DOMDocument;
                    @$dom->loadHTML($target_html);
                    $node = $dom->getElementsByTagName('title')->item(0);
                    $parameters['title'] = ($node && strlen($node->textContent)) ? $node->textContent : 'title無し';

                    $parameters['contentupdatedate'] = date('Y-m-d H:i:s', filemtime($file));

                    $multi_parameters[] = $parameters;
                }
            }

            // 5. Refresh content in DB
            Content::refresh($multi_parameters);

        }
        catch(\Exception $e)
        {
            \Log::error(__FILE__ . ':' . __LINE__);
            \Log::error('$e->getCode():' . var_export($e->getCode(), true));
            \Log::error('$e->getMessage():' . var_export($e->getMessage(), true));
            exit($e->getMessage() . PHP_EOL);
        }
    }
}
