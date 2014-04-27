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
        $_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 0;
        $_GET['accountKey'] = isset($_GET['accountKey']) ? $_GET['accountKey'] : 1000;
        $_GET['filter'] = isset($_GET['filter']) ? $_GET['filter'] : -1;
        $_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 'list';
        $_GET['corp'] = isset($_GET['corp']) ? true : false;

        if ($_GET['corp']) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $_GET['accountKey'] = max($_GET['accountKey'], 1000);
                $journal = new eveJournal(eveKeyManager::getKey($this->site->user->corp_apikey_id), $_GET['accountKey']);
                $journal->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $journal = new eveJournal(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $journal->load();
            }
        }

        $filterJournal = array();

        $refs = array();
        foreach ($journal->journal as $k => $j) {
            if (!isset($refs[$j->refTypeID])) {
                $refs[$j->refTypeID] = $j->refType;
            }

            if ($_GET['filter'] == -1 || ($_GET['filter'] == 'tax' && in_array($j->refTypeID, $this->taxableRefs) && $j->taxAmount > 0) || $j->refTypeID == $_GET['filter']) {
                $filterJournal[$k] = $j;
            }
        }
        asort($refs);

        if (!$_GET['corp'] && $_GET['type'] == 'days' && $_GET['filter'] == 'tax') {
            return $this->getJournalByDay($filterJournal, $refs, 'days_tax');
        } else if (!$_GET['corp'] && $_GET['type'] == 'days') {
            return $this->getJournalByDay($filterJournal, $refs, 'days');
        } else if ($_GET['corp'] && ($_GET['type'] == 'tax')) {
            return $this->getJournalCorpTaxes($filterJournal, $refs);
        } else if ($_GET['filter'] == 'tax') {
            return $this->getJournalAsList($filterJournal, $refs, 'journal_tax');
        } else {
            return $this->getJournalAsList($filterJournal, $refs, 'journal');
        }
    }

    function getJournalByDay($journal, $refs, $template = 'days') {
        $days = array();

        foreach ($journal as $k => $j) {
            $jDate = date('Y-m-d', $j->date);
            if (!isset($days[$jDate])) {
                $days[$jDate] = array(
                    'date' => $j->date,
                    'dr' => 0,
                    'cr' => 0,
                    'tax' => 0,
                    'journal' => array(),
                );
            }

            if (!isset($days[$jDate]['journal'][$j->refTypeID])) {
                $days[$jDate]['journal'][$j->refTypeID] = array(
                    'refType' => $j->refType,
                    'amount' => 0,
                    'dr' => 0,
                    'cr' => 0,
                    'tax' => 0,
                );
            }
            $days[$jDate]['journal'][$j->refTypeID]['amount'] += $j->amount - $j->taxAmount;

            if ($j->amount < 0) {
                $days[$jDate]['journal'][$j->refTypeID]['dr'] += $j->amount - $j->taxAmount;
            } else {
                $days[$jDate]['journal'][$j->refTypeID]['cr'] += $j->amount - $j->taxAmount;
            }

            if ($j->amount < 0) {
                $days[$jDate]['dr'] += $j->amount - $j->taxAmount;
            } else {
                $days[$jDate]['cr'] += $j->amount - $j->taxAmount;
            }

            $days[$jDate]['journal'][$j->refTypeID]['tax'] += $j->taxAmount;
            $days[$jDate]['tax'] += $j->taxAmount;
        }

        if (count($days) > 10) {
            $days = array_chunk($days, 10);

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

        $vars = array('days' => $days, 'refTypes' => $refs, 'filter' => $_GET['filter'],
            'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage,
            'corp' => $_GET['corp'], 'accountKey' => $_GET['accountKey']);

        if ($_GET['corp']) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
                $corporation = new eveCorporation($corpKey);
                $corporation->load();
            }
            $vars['accounts'] = objectToArray($corporation->walletDivisions);
        }

        return $this->render($template, $vars);
    }

    function getJournalCorpTaxes($journal, $refs) {
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

        return $this->getJournalAsList($filterJournal, $refs);
    }

    function getJournalAsList($journal, $refs, $template = 'journal') {
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
            'filter' => $_GET['filter'], 'refTypes' => $refs,
            'corp' => $_GET['corp'], 'accountKey' => $_GET['accountKey']);

        if ($_GET['corp']) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
                $corporation = new eveCorporation($corpKey);
                $corporation->load();
            }
            $vars['accounts'] = objectToArray($corporation->walletDivisions);
        }

        return $this->render($template, $vars);
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
        if ($_GET['corp']) {
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

?>