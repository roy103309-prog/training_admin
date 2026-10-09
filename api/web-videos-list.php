<?php
require_once __DIR__ . '/web-common.php';
$user = requireAuth();
$items = webAll(getNewLifeConnection(), 'videos');
foreach ($items as &$r) {
    $yt = youtubeId($r['video_url']);
    $r['thumb'] = $yt ? 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' : '';
}
unset($r);
jsonResponse(true, 'OK', ['items' => $items]);
