<?php
$articleDir = __DIR__ . '/articles';
date_default_timezone_set('PRC');
$config = [
    'introduction' => [
        'nickname' => '我和你荡秋千',
        'birthday' => '1995-01-09',
        'jobs' => '程序员(programmer)',
        'email' => '2274313786@qq.com',
        'address' => '北京市海淀区',
        'github'=>"<a target='_blank' href='https://github.com/baagee'>https://github.com/baagee</a>"
    ],
    'update_time' => date('Y-m-d H:i:s'),
];

function scanArticles($dir)
{
    $key = basename($dir);
    $subFiles[$key] = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        if (in_array($file, ['.', '..'])) {
            continue;
        }
        $absFile = $dir . '/' . $file;
        if (is_file($absFile)) {
            $subFiles[$key][] = sprintf("<a href='view.html?p=%s'>%s</a>", str_replace(__DIR__, '', $absFile), $file);
        } elseif (is_dir($absFile))
            $subFiles[$key][] = scanArticles($absFile);
    }
    return $subFiles;
}

$tree = scanArticles($articleDir);
$tree = array_merge($config, $tree);
$json = json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$html = file_get_contents(__DIR__ . '/assets/index.tpl.html');
$html = str_replace('{{TREE}}', $json, $html);
file_put_contents(__DIR__ . '/index.html', $html);
echo 'success' . PHP_EOL;
