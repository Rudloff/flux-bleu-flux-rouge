<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Flux bleu, flux rouge</title>
        <script>(function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0];
          if (d.getElementById(id)) return;
          js = d.createElement(s); js.id = id;
          js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.8&appId=1590627144578464";
          fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));</script>
    </head>
    <body style="width: 1000px; margin:auto;">
        <div id="fb-root"></div>
<?php
use League\Csv;
use Gilbitron\Util\SimpleCache;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config.php';

function sortPosts($a, $b)
{
    $dateA = new DateTime($a['created_time']);
    $dateB = new DateTime($b['created_time']);
    if ($dateA == $dateB) {
        return 0;
    }
    return ($dateA > $dateB) ? -1 : 1;
}

$cache = new SimpleCache();
$token = [];
parse_str(
    file_get_contents(
        'https://graph.facebook.com/oauth/access_token?client_id='.APP_ID.
        '&client_secret='.APP_SECRET.'&grant_type=client_credentials'
    ),
    $token
);
$fb = new \Facebook\Facebook([
  'app_id' => APP_ID,
  'app_secret' => APP_SECRET,
  'default_graph_version' => 'v2.8',
  'default_access_token' => $token['access_token']
]);

$csv = Csv\Reader::createFromPath(__DIR__.'/sources.csv', 'r');
$posts = [];
foreach ($csv->fetchAssoc() as $row) {
    if ($cache->is_cached($row['fb_id'])) {
        $data = json_decode($cache->get_cache($row['fb_id']), true);
    } else {
        $response = $fb->get('/'.$row['fb_id'].'/posts?limit=100');
        $data = $response->getDecodedBody();
        $cache->set_cache($row['fb_id'], $response->getBody());
    }
    foreach ($data['data'] as $post) {
        if (isset($post['message']) && stripos($post['message'], 'sarkozy') !== false) {
            $posts[$row['side']][] = $post;
        }
    }
}
usort($posts['left'], 'sortPosts');
usort($posts['right'], 'sortPosts');
foreach ($posts as $side => $postGroup) {
    echo '<div style="float:'.$side.'">';
    foreach ($postGroup as $post) {
        $id = explode('_', $post['id']);
        echo '<div>
            <div class="fb-post" data-href="https://www.facebook.com/'.$id[0].'/posts/'.$id[1].'/"
                data-width="500" data-show-text="true"></div>
            </div>';
    }
    echo '</div>';
}
?>
</body>
