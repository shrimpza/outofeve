<?php
    $time_start = microtime(true);

    require_once('includes/site.php');

    if (!$GLOBALS['config']['site']['precache']) {
        echo 'Precaching disabled.';
        exit;
    }

    function quickRequest($method, $account, $params = null) {
        $data = new apiRequest($method, array($account->apiuser, trim(decryptKey($account->apikey)), $account->character_id), $params, false);
    }

    $db = new DBManager($GLOBALS['config']['database']);
    $accs = $db->getObjects('account', "precache > 0 and character_id <> ''");

    if ($accs) {
        // set php time limit to 20 seconds per account we need to query.
        set_time_limit(count($accs) * 20);

        // hit every API for this account/character
        foreach ($accs as $account) {
            quickRequest('account/Characters.xml.aspx', $account);
            quickRequest('char/AccountBalance.xml.aspx', $account);
            quickRequest('char/CharacterSheet.xml.aspx', $account);
            quickRequest('char/SkillInTraining.xml.aspx', $account);
            quickRequest('char/FacWarStats.xml.aspx', $account);
            quickRequest('char/AssetList.xml.aspx', $account, array('version' => 2));
            quickRequest('char/MarketOrders.xml.aspx', $account, array('version' => 2));
            quickRequest('char/WalletTransactions.xml.aspx', $account);
            quickRequest('char/WalletJournal.xml.aspx', $account);
            quickRequest('char/IndustryJobs.xml.aspx', $account, array('version' => 2));
            quickRequest('char/KillLog.xml.aspx', $account, array('version' => 2));
            quickRequest('corp/CorporationSheet.xml.aspx', $account);
            quickRequest('corp/AccountBalance.xml.aspx', $account);
            quickRequest('corp/MemberTracking.xml.aspx', $account);
            quickRequest('corp/AssetList.xml.aspx', $account, array('version' => 2));
            quickRequest('corp/MarketOrders.xml.aspx', $account, array('version' => 2));
            quickRequest('corp/WalletJournal.xml.aspx', $account, array('accountKey' => 1000));
            quickRequest('corp/WalletTransactions.xml.aspx', $account, array('accountKey' => 1000));
            quickRequest('corp/IndustryJobs.xml.aspx', $account, array('version' => 2));
            quickRequest('corp/KillLog.xml.aspx', $account, array('version' => 2));
            quickRequest('corp/StarbaseList.xml.aspx', $account, array('version' => 2));
        }
    }

    // the basics
    new apiRequest('eve/SkillTree.xml.aspx');
    new apiRequest('eve/CertificateTree.xml.aspx');

    $time_end = microtime(true);
    $time = $time_end - $time_start;

    echo count($accs) . ' account(s), done in ' . round($time, 4) . ' seconds, ' . round((memory_get_usage()/1024), 2) . 'kb memory used.';

?>