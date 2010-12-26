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

    // some info, comment to remove.
    if (!isset($_GET['popup'])&&!isset($_GET['jsonMode'])) echo '<script type="text/javascript">document.getElementById("footer").innerHTML = document.getElementById("footer").innerHTML + "<br /><small><small>Processed in ' . round($time, 4) . ' seconds, ' . ($site->db->numQueries + (isset($site->eveAccount) ? (int)$site->eveAccount->db->db->numQueries : 0)) . ' queries, ' . round((memory_get_usage()/1024), 2) . 'kb memory used.</small></small>";</script>';

    ob_end_flush();
?>
