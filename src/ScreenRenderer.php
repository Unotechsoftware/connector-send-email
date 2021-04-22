<?php
namespace ProcessMaker\Packages\Connectors\Email;

class ScreenRenderer
{
    public static function render($screen_config, $data)
    {
        $node = env('NODE_BIN_PATH', '/usr/local/bin/node');
        $entry = __DIR__ . '/../resources/js/ssr/entry.js';
        $cmd = join(' ', [
            $node,
            $entry,
            escapeshellarg(json_encode($screen_config)),
            escapeshellarg(json_encode($data)),
            '2>&1'
        ]);
        exec($cmd, $out, $err);
        if ($err) {
            throw new \Exception("\n\n".$cmd."\n".$err);
        }
        $out = join("\n", $out);
        if (!preg_match('/\[BEGIN-SSR\](.*)\[END-SSR\]/s', $out, $matches)) {
            throw new \Exception($out);
        }
        return $matches[1];
    }
}
