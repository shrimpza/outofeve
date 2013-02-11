<?php

class orders extends Plugin {

    var $name = 'Market Orders';
    var $level = 1;

    function orders($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_MarketOrders)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Market Orders', '?module=orders', 'icon17_02');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_MarketOrders)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Market Orders', '?module=orders&corp=1', 'icon17_02');
        }
    }

    function getContent() {
        if (!isset($_GET['accountKey'])) {
            $_GET['accountKey'] = 1000;
        }
        if (!isset($_GET['complete'])) {
            $_GET['complete'] = 0;
        }
        
        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $_GET['accountKey'] = max($_GET['accountKey'], 1000);
                $o = new eveMarketOrderList(eveKeyManager::getKey($this->site->user->corp_apikey_id), $_GET['accountKey']);
                $o->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $o = new eveMarketOrderList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $o->load();
            }
        }

        $orderList = $o->orders;

        $buying = array();
        $selling = array();

        for ($i = 0; $i < count($orderList); $i++) {
            if (($_GET['complete'] > 0) || (($_GET['complete'] == 0) && ($orderList[$i]->orderState == 0) && ($orderList[$i]->remainingTime > 0))) {
                if ($orderList[$i]->buying) {
                    $buying[] = objectToArray($orderList[$i], array('DBManager', 'eveDB'));
                } else {
                    $selling[] = objectToArray($orderList[$i], array('DBManager', 'eveDB'));
                }
            }
        }

        $vars = array('buying' => $buying, 'selling' => $selling, 'corp' => isset($_GET['corp']), 'accountKey' => $_GET['accountKey'], 'complete' => $_GET['complete']);

        if (isset($_GET['corp'])) {
            // todo: come back to this
            $vars['accounts'] = objectToArray($this->site->character->corporation->walletDivisions, array('DBManager', 'eveDB'));
        }

        return $this->render('orders', $vars);
    }

}

?>
