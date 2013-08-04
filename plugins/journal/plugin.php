<?php

class journal extends Plugin {

    var $name = 'Journal';
    var $level = 1;

    function journal($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id) && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_WalletJournal)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Journal', '?module=journal', 'journal');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id) && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_WalletJournal)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Journal', '?module=journal&corp=1', 'journal');
        }
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }

        if (!isset($_GET['accountKey'])) {
            $_GET['accountKey'] = 1000;
        }

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $_GET['accountKey'] = max($_GET['accountKey'], 1000);
                $j = new eveJournal(eveKeyManager::getKey($this->site->user->corp_apikey_id), $_GET['accountKey']);
                $j->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $j = new eveJournal(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $j->load();
            }
        }

        $journal = objectToArray($j->journal);

        if (!isset($_GET['daycount'])) {
            $dayCount = 7;
        } else {
            $dayCount = $_GET['daycount'];
        }

        if (isset($_GET['type']) && ($_GET['type'] == 'days')) {
            $days = array();

            $refs = array();

            if (!isset($_GET['filter'])) {
                $filter = -1;
            } else {
                $filter = $_GET['filter'];
            }

            for ($i = 0; $i < count($journal); $i++) {
                if (!isset($refs[$journal[$i]['refTypeID']])) {
                    $refs[$journal[$i]['refTypeID']] = $journal[$i]['refType'];
                }

                if (($filter <= 0) || ($journal[$i]['refTypeID'] == $filter)) {
                    $jDate = date('Y-m-d', $journal[$i]['date']);
                    if (!isset($days[$jDate])) {
                        $days[$jDate] = array(
                            'date' => $journal[$i]['date'],
                            'dr' => 0,
                            'cr' => 0,
                            'journal' => array(),
                        );
                    }

                    if ($filter == -1) {
                        if (!isset($days[$jDate]['journal'][$journal[$i]['refTypeID']])) {
                            $days[$jDate]['journal'][$journal[$i]['refTypeID']] = array(
                                'refType' => $journal[$i]['refType'],
                                'amount' => 0,
                                'dr' => 0,
                                'cr' => 0,
                            );
                        }
                        $days[$jDate]['journal'][$journal[$i]['refTypeID']]['amount'] += $journal[$i]['amount'];

                        if ($journal[$i]['amount'] < 0) {
                            $days[$jDate]['journal'][$journal[$i]['refTypeID']]['dr'] += $journal[$i]['amount'];
                        } else {
                            $days[$jDate]['journal'][$journal[$i]['refTypeID']]['cr'] += $journal[$i]['amount'];
                        }
                    } else {
                        $days[$jDate]['journal'][] = $journal[$i];
                    }

                    if ($journal[$i]['amount'] < 0) {
                        $days[$jDate]['dr'] += $journal[$i]['amount'];
                    } else {
                        $days[$jDate]['cr'] += $journal[$i]['amount'];
                    }
                }
            }

            asort($refs);

            if (count($days) > $dayCount) {
                $days = array_chunk($days, $dayCount);

                $pageCount = count($days);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $days = $days[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $vars = array('days' => $days, 'dayCount' => $dayCount, 'filter' => $filter, 'refTypes' => $refs,
                'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage,
                'corp' => isset($_GET['corp']), 'accountKey' => $_GET['accountKey']);

            if (isset($_GET['corp'])) {
                if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                    $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
                    $corporation = new eveCorporation($corpKey);
                    $corporation->load();
                }
                $vars['accounts'] = objectToArray($corporation->walletDivisions);
            }

            return $this->render('days', $vars);
        } else {
            if (count($journal) > 50) {
                $journal = array_chunk($journal, 50);

                $pageCount = count($journal);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $journal = $journal[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            foreach ($journal as $k => $j) {
                $journal[$k]['reason'] = $this->getJornalReason($j);
            }

            $vars = array('journal' => $journal, 'pageCount' => $pageCount,
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

            return $this->render('journal', $vars);
        }
    }

    function getJornalReason($j) {
        $reason = $j['reason'];

        switch ($j['refTypeID']) {
            case 1:
                // player trading, station of trade
                $station = eveDB::getInstance()->eveStation($j['argID1']);
                $reason = 'Station: ' . $station->stationname . ' in ' . $station->solarSystem->solarsystemname;
                break;
            case 10:
                // donation, user text
                $reason = 'Donation reason: ' . $j['reason'];
                break;
            case 19:
                // insurance, ship destroyed
                $ship = eveDB::getInstance()->eveItem($j['argID1']);
                $reason = 'Ship: ' . $ship->typename;
                break;
            case 35:
                // contact fee
                $reason = 'Contacted: ' . $j['argName1'];
                break;
            case 37:
                // corp withdrawel
                $reason = 'Withdrawel reason: ' . $j['reason'];
                break;
            case 46:
                // broker fee
                $reason = 'System: ' . $j['argName1'];
                break;
            case 56:
                // manufacturing
                $reason = 'Job: ' . $j['argName1']; // TODO maybe get description of job via eveIndustryJobList
                break;
            case 85:
                // rat kills
                $kills = explode(',', $j['reason']);
                $reason = '';
                foreach ($kills as $k) {
                    if (empty($k)) {
                    } else if ($k == '...') {
                        $reason .= '...';
                    } else {
                        $kill = explode(':', $k);
                        $npc = eveDB::getInstance()->eveItem($kill[0]);
                        $reason .= $kill[1] . ' x ' . $npc->typename . ' &#10;';
                    }
                }
                break;
            default:
                $reason = $j['reason'];
        }

        return $reason;
    }

}

function journalTimeRevSort($a, $b) {
    return ($a['date'] == $b['date']) ? 0 : ($a['date'] < $b['date']) ? -1 : 1;
}

?>