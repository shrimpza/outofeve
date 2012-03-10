<?php

    class orders extends Plugin {
        var $name = 'Market Orders';
        var $level = 1;

        function orders($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('main', 'Market Orders', '?module=orders', 'icon17_02');

            if (($this->site->plugins['mainmenu']->hasGroup('corp')) 
                    && ($this->site->character->corpMember->hasRole('corpRoleAccountant') 
                        || $this->site->character->corpMember->hasRole('corpRoleJuniorAccountant')
                        || $this->site->character->corpMember->hasRole('corpRoleTrader')
                        || $this->site->character->corpMember->hasRole('corpRoleDirector')
                        || $this->site->plugins['users']->hasForcedMenu('corpOrders')))
                $this->site->plugins['mainmenu']->addLink('corp', 'Market Orders', '?module=orders&corp=1', 'icon17_02');
        }

        function getContent() {
            if (!isset($_GET['accountKey']))
                $_GET['accountKey'] = 1000;
            if (!isset($_GET['complete']))
                $_GET['complete'] = 0;

            if (isset($_GET['corp'])) {
                $_GET['accountKey'] = max($_GET['accountKey'], 0);
                $this->site->character->corporation->loadOrders($_GET['accountKey']);
                $orderList = $this->site->character->corporation->orders;
            } else {
                $ml = new eveMarketOrderList();
                $ml->load($this->site->eveAccount, $this->site->character);
                $orderList = $ml->orders;
            }

            $buying = array();
            $selling = array();

            for ($i = 0; $i < count($orderList); $i++) {
                if (($_GET['complete'] > 0) || (($_GET['complete'] == 0) && ($orderList[$i]->orderState == 0) && ($orderList[$i]->remainingTime > 0))) {
                    if ($orderList[$i]->buying)
                        $buying[] = objectToArray($orderList[$i], array('DBManager', 'eveDB'));
                    else
                        $selling[] = objectToArray($orderList[$i], array('DBManager', 'eveDB'));
                }
            }

            $vars = array('buying' => $buying, 'selling' => $selling, 'corp' => isset($_GET['corp']), 'accountKey' => $_GET['accountKey'], 'complete' => $_GET['complete']);

            if (isset($_GET['corp'])) {
                $vars['accounts'] = objectToArray($this->site->character->corporation->walletDivisions, array('DBManager', 'eveDB'));
            }

            return $this->render('orders', $vars);
        }
    }

?>
