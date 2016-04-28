<?php
    ob_start("ob_gzhandler");

    $time_start = microtime(true);

    require_once('includes/site.php');

    $site = new Site();

    if (isset($_GET['jsonMode']) || isset($_POST['jsonMode'])) {
        $site->outputJson();
    } else {
        $site->output();
    }

    $time_end = microtime(true);
    $time = $time_end - $time_start;

    if ((!isset($_GET['popup']) && !isset($_GET['jsonMode'])) && $GLOBALS['config']['site']['showstats']) {
        echo '<script type="text/javascript">$("#footer").html($("#footer").html() + "<br />';

        echo '<small><small>Processed in ' . round($time, 4) . ' seconds, ';
        echo ($site->db->numQueries + eveDB::getInstance()->db->numQueries) . ' DB queries, ';
        echo (eveDB::getInstance()->cacheHits) . ' cache hits; ';

        echo apiStats::$liveRequests . ' live API requests, ' . apiStats::$cacheRequests . ' from cache; ';

        echo round((memory_get_usage()/1024), 2) . 'kb memory used.<br />';

        echo '<b>API Activity:</b><br />';
        foreach (apiStats::$requests as $req) {
            echo $req['cache'] ? '[Cached] ' : '';
            echo $req['method'];
            echo ' - ' . round($req['time'], 4) . ' secs';
            echo ' - exp ' . date('d/n/Y H:i:s', $req['cacheUntil']) . '<br />';
        }
        echo '</small></small>");';
        echo '</script>';
    }

    ob_end_flush();
?>
