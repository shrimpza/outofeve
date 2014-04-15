<?php

class journal extends Plugin {

    var $name = 'Journal';
    var $level = 1;

    /*
     * Taxable refTypeIDs - to be used when a member list is not available. 
     * Likely less accurate but will work for API keys which don't have access to corp member lists.
     */
    var $taxableRefs = [33, 34, 85];

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

        if (!isset($_GET['daycount'])) {
            $dayCount = 7;
        } else {
            $dayCount = $_GET['daycount'];
        }

        if (isset($_GET['type']) && ($_GET['type'] == 'days')) {
            return $this->getJournalByDay($j->journal, $dayCount);
        } else if (isset($_GET['corp']) && isset($_GET['type']) && ($_GET['type'] == 'tax')) {
            return $this->getJournalTaxes($j->journal);
        } else {
            return $this->getJournalAsList($j->journal);
        }
    }

    function getJournalByDay($journal, $dayCount) {
        // TODO refactor to actually group things by dayCount, rather than limiting number of days to dayCount which is a bit useless
        
        $days = array();

        $refs = array();

        if (!isset($_GET['filter'])) {
            $filter = -1;
        } else {
            $filter = $_GET['filter'];
        }

        for ($i = 0; $i < count($journal); $i++) {
            if (!isset($refs[$journal[$i]->refTypeID])) {
                $refs[$journal[$i]->refTypeID] = $journal[$i]->refType;
            }

            if (($filter <= 0) || ($journal[$i]->refTypeID == $filter)) {
                $jDate = date('Y-m-d', $journal[$i]->date);
                if (!isset($days[$jDate])) {
                    $days[$jDate] = array(
                        'date' => $journal[$i]->date,
                        'dr' => 0,
                        'cr' => 0,
                        'journal' => array(),
                    );
                }

                if ($filter == -1) {
                    if (!isset($days[$jDate]['journal'][$journal[$i]->refTypeID])) {
                        $days[$jDate]['journal'][$journal[$i]->refTypeID] = array(
                            'refType' => $journal[$i]->refType,
                            'amount' => 0,
                            'dr' => 0,
                            'cr' => 0,
                        );
                    }
                    $days[$jDate]['journal'][$journal[$i]->refTypeID]['amount'] += $journal[$i]->amount;

                    if ($journal[$i]->amount < 0) {
                        $days[$jDate]['journal'][$journal[$i]->refTypeID]['dr'] += $journal[$i]->amount;
                    } else {
                        $days[$jDate]['journal'][$journal[$i]->refTypeID]['cr'] += $journal[$i]->amount;
                    }
                } else {
                    $days[$jDate]['journal'][] = objectToArray($journal[$i]);
                }

                if ($journal[$i]->amount < 0) {
                    $days[$jDate]['dr'] += $journal[$i]->amount;
                } else {
                    $days[$jDate]['cr'] += $journal[$i]->amount;
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
    }
    
    function getJournalTaxes($journal) {
//        $members = array();
//
//        $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
//        if ($corpKey->hasAccess(CORP_MemberTrackingExtended)) {
//            $memberList = new eveCorporationMemberList($corpKey);
//            $memberList->load();
//
//            foreach ($memberList->members as $member) {
//                $members[$member->characterID] = $member;
//            }
//        }

        $filterJournal = array();
        foreach ($journal as $k => $j) {
            if (in_array($j->refTypeID, $this->taxableRefs)) {
                $filterJournal[$k] = $j;
            }
        }

        return $this->getJournalAsList($filterJournal);
    }

    function getJournalAsList($journal) {
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
            $journal[$k]->reason = $this->getJornalReason($j);
        }

        $vars = array('journal' => objectToArray($journal), 'pageCount' => $pageCount,
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

    function getJornalReason($j) {
        $reason = $j->reason;

        switch ($j->refTypeID) {
            case 1:
                // player trading, station of trade
                $station = eveDB::getInstance()->eveStation($j->argID1);
                $reason = 'Station: ' . $station->stationname . ' in ' . $station->solarSystem->solarsystemname;
                break;
            case 10:
                // donation, user text
                break;
            case 19:
                // insurance, ship destroyed
                $ship = eveDB::getInstance()->eveItem($j->argID1);
                $reason = 'Ship: ' . $ship->typename;
                break;
            case 35:
                // contact fee
                $reason = 'Contacted: ' . $j->argName1;
                break;
            case 37:
                // corp withdrawel, user text
                break;
            case 46:
                // broker fee
                $reason = $j->argName1;
                break;
            case 56:
                // manufacturing
                $reason = $this->getIndustryJobName($j->argName1);
                break;
            case 85:
                // rat kills
                $kills = explode(',', $j->reason);
                $reason = '';
                foreach ($kills as $k) {
                    if (empty($k)) {
                        
                    } else if ($k == '...') {
                        $reason .= '...';
                    } else {
                        $kill = explode(':', $k);
                        $npc = eveDB::getInstance()->eveItem($kill[0]);
                        $reason .= $kill[1] . ' x ' . $npc->typename . ' (' . $npc->getGroup()->groupname . ') &#10;';
                    }
                }
                break;
            default:
                $reason = $j->reason;
        }

        return $reason;
    }

    function getIndustryJobName($jobID) {
        $il = null;
        if (isset($_GET['corp'])) {
            $il = new eveIndustryJobList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
            $il->load();
        } else {
            $il = new eveIndustryJobList(eveKeyManager::getKey($this->site->user->char_apikey_id));
            $il->load();
        }

        $job = $il->getJob($jobID);
        if ($job != null) {
            return $job->activity->activityname . ': ' . $job->outItem->typename;
        } else {
            return null;
        }
    }

}

function journalTimeRevSort($a, $b) {
    return ($a['date'] == $b['date']) ? 0 : ($a['date'] < $b['date']) ? -1 : 1;
}

?>