<?php

class eveCorporation {

    var $corporationID = 0;
    var $corporationName = "";
    var $ticker = "";
    var $ceoID = 0;
    var $ceoName = "";
    var $description = "";
    var $url = "";
    var $allianceID = 0;
    var $allianceName = "";
    var $memberCount = 0;
    var $memberLimit = 0;
    var $taxRate = 0;
    var $shares = 0;
    var $stationID = 0;
    var $station = null;
    var $divisions = array();
    var $walletDivisions = array();
    var $logo = array();
    var $account = null;
    var $character = null;
    var $members = array();
    var $titles = array();
    var $assets = array();
    var $orders = array();
    var $journalItems = array();
    var $transactions = array();
    var $industryJobs = array();
    var $kills = array();
    var $deaths = array();
    var $starbases = array();

    function eveCorporation($account, $character) {
        $this->account = $account;
        $this->character = $character;

        //if ($corpData) {
        //    $this->load($corpData);
        //}
    }

    function load() {
        $data = new apiRequest('corp/CorporationSheet.xml.aspx', array($this->account->userId,
                    $this->account->apiKey,
                    $this->character->characterID),
                        array('version' => 2));

        if ((!$data->error) && ($data->data)) {
            $result = $data->data->result;

            $this->corporationID = (int) $result->corporationID;
            $this->corporationName = (string) $result->corporationName;
            $this->ticker = (string) $result->ticker;
            $this->ceoID = (int) $result->ceoID;
            $this->ceoName = (string) $result->ceoName;
            $this->stationID = (int) $result->stationID;
            $this->stationName = (string) $result->stationName;
            $this->description = (string) $result->description;
            $this->url = (string) $result->url;
            $this->allianceID = (int) $result->allianceID;
            $this->allianceName = (string) $result->allianceName;
            $this->taxRate = (float) $result->taxRate;
            $this->memberCount = (int) $result->memberCount;
            $this->memberLimit = (int) $result->memberLimit;
            $this->shares = (int) $result->shares;
        }

        $this->station = eveDB::getInstance()->eveStation($this->stationID);

        foreach ($result->rowset as $rowset) {
            if ($rowset['name'] == 'divisions') {
                foreach ($rowset->row as $division) {
                    $this->divisions[] = array('key' => (int) $division['accountKey'],
                        'description' => (string) $division['description']);
                }
            } else if ($rowset['name'] == 'walletDivisions') {
                foreach ($rowset->row as $division) {
                    $this->walletDivisions[] = new eveCorporationWalletDivision($division);
                }
            }
        }

        $this->logo['graphicID'] = (int) $result->logo->graphicID;
        $this->logo['layers'][] = array('shape' => (int) $result->logo->shape1,
            'color' => (int) $result->logo->color1);
        $this->logo['layers'][] = array('shape' => (int) $result->logo->shape2,
            'color' => (int) $result->logo->color2);
        $this->logo['layers'][] = array('shape' => (int) $result->logo->shape3,
            'color' => (int) $result->logo->color3);

//            $this->loadBalances();
//            $this->loadMembers();
    }

//        function loadBalances() {
//            $balanceData = new apiRequest('corp/AccountBalance.xml.aspx', array($this->account->userId,
//                                                                                   $this->account->apiKey,
//                                                                                   $this->character->characterID));
//
//            if ($balanceData->data) {
//                if (!$balanceData->data->error) {
//                    foreach ($balanceData->data->result->rowset->row as $balance) {
//                        for ($i = 0; $i < count($this->walletDivisions); $i++) {
//                            if ($this->walletDivisions[$i]['key'] == (int)$balance['accountKey']) {
//                                $this->walletDivisions[$i]['balance'] = (float)$balance['balance'];
//                            }
//                        }
//                    }
//
//                    $this->loadedBalances = true;
//                }
//            }
//        }
//        function loadMembers($full = false) {
//            if (count($this->members) == 0) {
//                $memberData = new apiRequest('corp/MemberTracking.xml.aspx', array($this->account->userId,
//                                                                                   $this->account->apiKey,
//                                                                                   $this->character->characterID));
//
//                if ($memberData->data) {
//                    if (!$memberData->data->error) {
//                        foreach ($memberData->data->result->rowset->row as $member) {
//                            $this->members[] = new eveCorporationMember($this->account, $this, $this->db, $member);
//                        }
//
//                        $this->loadedMembers = true;
//                    }
//                }
//            }
//
//            for ($i = 0; $i < count($this->members); $i++) {
//                if ($full) {
//                    $this->members[$i]->loadDetail();
//                }
//                if ($this->members[$i]->characterID == $this->character->characterID)
//                    $this->character->corpMember = $this->members[$i];
//            }
//        }

    function loadAssets($typeFilter = false) {
        if (count($this->assets) == 0) {
            $assetData = new apiRequest('corp/AssetList.xml.aspx', array($this->account->userId,
                        $this->account->apiKey,
                        $this->character->characterID),
                            array('version' => 2));

            if ($assetData->data) {
                if (!$assetData->data->error) {
                    foreach ($assetData->data->result->rowset->row as $asset) {
                        $this->assets[] = new eveAsset($this->account, $asset, $this->character);
                    }
                } else {
                    apiError('corp/AssetList.xml.aspx', $assetData->data->error);
                }
            }
        }
    }

    function loadOrders($accountKey = 0) {
        if (count($this->orders) == 0) {
            $orderData = new apiRequest('corp/MarketOrders.xml.aspx', array($this->account->userId,
                        $this->account->apiKey,
                        $this->character->characterID),
                            array('version' => 2));

            if ($orderData->data) {
                if (!$orderData->data->error) {
                    foreach ($orderData->data->result->rowset->row as $order) {
                        $newOrder = new eveMarketOrder($this->account, $order);

                        if (($accountKey == 0) || (($accountKey > 0) && ($newOrder->accountKey == $accountKey))) {
                            for ($i = 0; $i < count($this->members); $i++) {
                                if ($this->members[$i]->characterID == $newOrder->charID) {
                                    $newOrder->charName = $this->members[$i]->name;
                                    break;
                                }
                            }
                            $this->orders[] = $newOrder;
                        }
                    }
                } else {
                    apiError('corp/MarketOrders.xml.aspx', $orderData->data->error);
                }
            }
        }
    }

    function loadJournal($accountKey = 1000, $fromID = 0) {
        $params = array('accountKey' => $accountKey);
        $params['rowCount'] = $GLOBALS['config']['eve']['journal_records'];
        if ($fromID > 0) {
            $params['fromID'] = $fromID;
        }

        if ((count($this->journalItems) == 0) || ($fromID > 0)) {
            $journalData = new apiRequest('corp/WalletJournal.xml.aspx', array($this->account->userId,
                        $this->account->apiKey,
                        $this->character->characterID),
                            $params);
            if ($journalData->data) {
                if (!$journalData->data->error) {
                    $gotRows = 0;
                    foreach ($journalData->data->result->rowset->row as $journalItem) {
                        $this->journalItems[] = new eveJournalItem($this->account, $journalItem);
                        $gotRows++;
                    }

                    // keep looping journal requests until we receive no more results
                    $lowest = lowestJournalRef($this->journalItems);
                    if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                        $this->loadJournal($accountKey, $lowest);
                    } else {
                        // if this is the last run, sort all the items we have
                        usort($this->journalItems, 'journalSort');
                    }
                } else {
                    apiError('corp/WalletJournal.xml.aspx', $journalData->data->error);
                }
            }
        }
    }

    function loadTransactions($accountKey = 1000, $fromID = 0) {
        $params = array('accountKey' => $accountKey);
        $params['rowCount'] = $GLOBALS['config']['eve']['transaction_records'];
        if ($fromID > 0) {
            $params['fromID'] = $fromID;
        }

        if ((count($this->transactions) == 0) || ($fromID > 0)) {
            $transData = new apiRequest('corp/WalletTransactions.xml.aspx', array($this->account->userId,
                        $this->account->apiKey,
                        $this->character->characterID),
                            $params);
            if ($transData->data) {
                if (!$transData->data->error) {
                    foreach ($transData->data->result->rowset->row as $transaction) {
                        $this->transactions[] = new eveTransaction($this->account, $transaction, $this->character);
                        $gotRows++;
                    }

                    // keep looping requests until we receive no more results
                    $lowest = lowestTransactionRef($this->transactions);
                    if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                        $this->loadTransactions($accountKey, $lowest);
                    } else {
                        // if this is the last run, sort all the items we have
                        usort($this->transactions, 'transactionsSort');
                    }
                } else {
                    apiError('corp/WalletTransactions.xml.aspx', $transData->data->error);
                }
            }
        }
    }

    function loadIndustryJobs() {
        if (count($this->industryJobs) == 0) {
            $jobData = new apiRequest('corp/IndustryJobs.xml.aspx', array($this->account->userId,
                        $this->account->apiKey,
                        $this->character->characterID),
                            array('version' => 2));
            if ($jobData->data) {
                if (!$jobData->data->error) {
                    foreach ($jobData->data->result->rowset->row as $job) {
                        $newJob = new eveIndustryJob($this->account, $job);
                        for ($i = 0; $i < count($this->members); $i++) {
                            if ($this->members[$i]->characterID == $newJob->installerID) {
                                $newJob->installerName = $this->members[$i]->name;
                                break;
                            }
                        }
                        $this->industryJobs[] = $newJob;
                    }
                } else {
                    apiError('corp/IndustryJobs.xml.aspx', $jobData->data->error);
                }
            }
        }
    }

    function loadKills($beforeID = 0) {
        $killData = new apiRequest('corp/KillLog.xml.aspx', array($this->account->userId,
                    $this->account->apiKey,
                    $this->character->characterID),
                        array('version' => 2));

        if ($killData->data) {
            if (!$killData->data->error) {
                foreach ($killData->data->result->rowset->row as $kill) {
                    if ((int) $kill->victim['corporationID'] == $this->corporationID)
                        $this->deaths[] = new eveKill($this->account, $kill);
                    else
                        $this->kills[] = new eveKill($this->account, $kill);
                    $lastId = $kill['killID'];
                }
            } else {
                apiError('corp/KillLog.xml.aspx', $killData->data->error);
            }
        }
    }

    function loadStarbases($full = true) {
        $starbaseData = new apiRequest('corp/StarbaseList.xml.aspx', array($this->account->userId,
                    $this->account->apiKey,
                    $this->character->characterID),
                        array('version' => 2));

        if ($starbaseData->data) {
            if (!$starbaseData->data->error) {
                foreach ($starbaseData->data->result->rowset->row as $starbase) {
                    $this->starbases[] = new eveStarbase($this->account, $starbase, $this, $full);
                }
            } else {
                apiError('corp/StarbaseList.xml.aspx', $starbaseData->data->error);
            }
        }
    }

}

class eveCorporationWalletDivision {

    var $accountKey = 0;
    var $description = '';

    function eveCorporationWalletDivision($walletDivision) {
        $this->accountKey = (int) $walletDivision['accountKey'];
        $this->description = (string) $walletDivision['description'];

        if (($this->accountKey == 1000) && ($this->description == 'Wallet Division 1')) {
            $this->description = 'Master Wallet';
        }
    }

}

class eveCorporationWallet {

    var $description = '';
    var $accountID = 0;
    var $accountKey = 0;
    var $balance = 0;

    function eveCorporationWallet($wallet, $walletDivisions) {
        $this->accountID = (int) $wallet['accountID'];
        $this->accountKey = (int) $wallet['accountKey'];
        $this->balance = (float) $wallet['balance'];
        $this->description = 'Wallet ' . $this->accountKey;

        for ($i = 0; $i < count($walletDivisions); $i++) {
            if ($walletDivisions[$i]->accountKey == $this->accountKey) {
                $this->description = $walletDivisions[$i]->description;
                break;
            }
        }
    }

}

class eveCorporationWalletList {

    var $wallets = array();

    function load($corporation) {
        if (count($this->wallets) == 0) {
            $data = new apiRequest('corp/AccountBalance.xml.aspx', array($corporation->account->userId,
                        $corporation->account->apiKey,
                        $corporation->character->characterID),
                            array('version' => 2));

            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->rowset->row as $wallet) {
                    $this->wallets[] = new eveCorporationWallet($wallet, $corporation->walletDivisions);
                }
            }
        }
    }

}

class eveCorporationMemberList {

    var $members = array();

    function load($corporation, $full = false) {
        if (count($this->members) == 0) {
            $data = new apiRequest('corp/MemberTracking.xml.aspx', array($corporation->account->userId,
                        $corporation->account->apiKey,
                        $corporation->character->characterID));

            if ($data->data) {
                if (!$data->data->error) {
                    foreach ($data->data->result->rowset->row as $member) {
                        $this->members[] = new eveCorporationMember($corporation->account, $corporation, $member);
                    }
                }
            }
        }

        for ($i = 0; $i < count($this->members); $i++) {
            if ($full) {
                $this->members[$i]->loadDetail();
            }
            if ($this->members[$i]->characterID == $this->character->characterID) {
                $corporation->character->corpMember = $this->members[$i];
            }
        }
    }

}

class eveCorporationMember {

    var $characterID = 0;
    var $name = '';
    var $startDateTime = 0;
    var $baseID = 0;
    var $title = '';
    var $logonDateTime = 0;
    var $logoffDateTime = 0;
    var $locationID = 0;
    var $shipTypeID = 0;
    var $roles = 0;
    var $grantableRoles = 0;
    var $locationName = '';
    var $roleList = array();
    var $base = null;
    var $location = null;
    var $ship = null;

    function eveCorporationMember($acc, $corp, $member) {
        $this->characterID = (int) $member['characterID'];
        $this->name = (string) $member['name'];
        $this->startDateTime = strtotime((string) $member['startDateTime']) + $acc->timeOffset;
        $this->baseID = (int) $member['baseID'];
        $this->title = (string) $member['title'];
        $this->logonDateTime = strtotime((string) $member['logonDateTime']) + $acc->timeOffset;
        $this->logoffDateTime = strtotime((string) $member['logoffDateTime']) + $acc->timeOffset;
        $this->locationID = (int) $member['locationID'];
        $this->shipTypeID = (int) $member['shipTypeID'];
        $this->roles = (int) $member['roles'];
        $this->grantableRoles = (int) $member['grantableRoles'];

        $this->setRoles();
    }

    function setRoles() {
        $corpRoles = eveDB::getInstance()->corpRoleList();

        for ($i = 0; $i < count($corpRoles); $i++) {
            if ($this->roles & (int) $corpRoles[$i]['rolebit']) {
                $this->roleList[$corpRoles[$i]['rolename']] = $corpRoles[$i];
            }
        }
    }

    function hasRole($roleName) {
        return isset($this->roleList[$roleName]);
    }

    function loadDetail() {
        if ($this->baseID > 0) {
            $this->base = eveDB::getInstance()->eveStation($this->baseID);
        }

        if ($this->locationID > 0) {
            $this->location = eveDB::getInstance()->eveStation($this->locationID);
            $this->locationName = $this->location->stationname;
            if ($this->location->stationid == 0) {
                $this->location = eveDB::getInstance()->eveSolarSystem($this->locationID);
                $this->locationName = $this->location->solarsystemname;
            }
        }

        if ($this->shipTypeID > 0) {
            $this->ship = eveDB::getInstance()->eveItem($this->shipTypeID);
        }
    }

}

?>