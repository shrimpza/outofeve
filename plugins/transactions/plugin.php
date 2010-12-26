<?php

    class transactions extends Plugin {
        var $name = 'Market Transactions';
        var $level = 1;

        function transactions($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('main', 'Market Transactions', '?module=transactions', 'icon18_01');

            if (($this->site->plugins['mainmenu']->hasGroup('corp')) 
                    && ($this->site->character->corpMember->hasRole('corpRoleAccountant') 
                        || $this->site->character->corpMember->hasRole('corpRoleJuniorAccountant')
                        || $this->site->character->corpMember->hasRole('corpRoleTrader')
                        || $this->site->character->corpMember->hasRole('corpRoleDirector')
                        || $this->site->plugins['users']->hasForcedMenu('corpTransactions')))
                $this->site->plugins['mainmenu']->addLink('corp', 'Market Transactions', '?module=transactions&corp=1', 'icon18_01');
        }

        function getContent() {
            if (!isset($_GET['p']))
                $_GET['p'] = 0;

            if (!isset($_GET['accountKey']))
                $_GET['accountKey'] = 1000;

            if (isset($_GET['corp'])) {
                $_GET['accountKey'] = max($_GET['accountKey'], 1000);
                $this->site->character->corporation->loadTransactions($_GET['accountKey']);
                $transItems = $this->site->character->corporation->transactions;
            } else {
                $this->site->character->loadTransactions();
                $transItems = $this->site->character->transactions;
            }

            $trans = objectToArray($transItems, array('DBManager', 'eveDB'));

            if (count($trans) > 50) {
                $trans = array_chunk($trans, 50);

                $pageCount = count($trans);
                $pageNum = max((int)$_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $trans = $trans[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $vars = array('trans' => $trans, 'pageCount' => $pageCount, 
                          'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 
                          'corp' => isset($_GET['corp']), 'accountKey' => $_GET['accountKey']);

            if (isset($_GET['corp'])) {
                $vars['accounts'] = objectToArray($this->site->character->corporation->walletDivisions, array('DBManager', 'eveDB'));
            }

            return $this->render('transactions', $vars);
        }
    }

?>