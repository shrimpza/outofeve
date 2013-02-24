<?php

class transactions extends Plugin {

    var $name = 'Market Transactions';
    var $level = 1;

    function transactions($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_WalletTransactions)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Market Transactions', '?module=transactions', 'transactions');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_WalletTransactions)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Market Transactions', '?module=transactions&corp=1', 'transactions');
        }
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }

        if (!isset($_GET['transType'])) {
            $_GET['transType'] = 0;
        }

        if (!isset($_GET['accountKey'])) {
            $_GET['accountKey'] = 1000;
        }

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $_GET['accountKey'] = max($_GET['accountKey'], 1000);
                $tl = new eveTransactionList(eveKeyManager::getKey($this->site->user->corp_apikey_id), $_GET['accountKey']);
                $tl->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $tl = new eveTransactionList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $tl->load();
            }
        }

        $transList = array();
        foreach ($tl->transactions as $trans) {
            if ($_GET['transType'] == 1 && !$trans->purchase) {
                $transList[] = $trans;
            } else if ($_GET['transType'] == 2 && $trans->purchase) {
                $transList[] = $trans;
            } else if ($_GET['transType'] == 0) {
                $transList[] = $trans;
            }
        }

        $trans = objectToArray($transList, array('DBManager', 'eveDB'));

        if (count($trans) > 50) {
            $trans = array_chunk($trans, 50);

            $pageCount = count($trans);
            $pageNum = max((int) $_GET['p'], 0);
            $nextPage = min($pageNum + 1, $pageCount);
            $prevPage = max($pageNum - 1, 0);

            $trans = $trans[$pageNum];
        } else {
            $pageCount = 0;
            $pageNum = 0;
            $nextPage = 0;
            $prevPage = 0;
        }

        $vars = array('trans' => $trans, 'transType' => $_GET['transType'], 'pageCount' => $pageCount,
            'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage,
            'corp' => isset($_GET['corp']), 'accountKey' => $_GET['accountKey']);

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
                $corporation = new eveCorporation($corpKey);
                $corporation->load();
            }
            $vars['accounts'] = objectToArray($corporation->walletDivisions);
        }

        return $this->render('transactions', $vars);
    }

}

?>