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

        var $db = null;

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

        function eveCorporation($account, $character, $corpData = null) {
            $this->db = $account->db;

            $this->account = $account;
            $this->character = $character;

            if ($corpData)
                $this->loadCorp($corpData);
        }

        function loadCorp($corpData = false) {
            if (!$corpData) {
                $corpData = new apiRequest('corp/CorporationSheet.xml.aspx', array($this->account->userId,
                                                                   $this->account->apiKey,
                                                                   $this->character->characterID));
            }

            if (!$corpData->data)
                return;

            if ($corpData->data->error)
                apiError('corp/CorporationSheet.xml.aspx', $corpData->data->error);

            foreach (get_object_vars($corpData->data->result) as $var => $val) {
                if (!is_array($val) && !is_object($val))
                    $this->$var = trim($val);
            }

            $this->station = $this->db->eveStation($this->stationID);
            
            foreach ($corpData->data->result->rowset as $rowset) {
                if ($rowset['name'] == 'divisions') {
                    foreach ($rowset->row as $division) {
                        $this->divisions[] = array('key' => (int)$division['accountKey'], 
                                                   'description' => (string)$division['description']);
                    }
                } else if ($rowset['name'] == 'walletDivisions') {
                    foreach ($rowset->row as $division) {
                        $newDiv = array('key' => (int)$division['accountKey'], 
                                        'description' => (string)$division['description'],
                                        'balance' => 0);
                        if (($newDiv['key'] == 1000) && ($newDiv['description'] == 'Wallet Division 1'))
                            $newDiv['description'] = 'Master Wallet';
                        $this->walletDivisions[] = $newDiv;
                    }
                }
            }

            $this->logo['graphicID'] = (int)$corpData->data->result->logo->graphicID;
            $this->logo['layers'][] = array('shape' => (int)$corpData->data->result->logo->shape1, 
                                            'color' => (int)$corpData->data->result->logo->color1);
            $this->logo['layers'][] = array('shape' => (int)$corpData->data->result->logo->shape2, 
                                            'color' => (int)$corpData->data->result->logo->color2);
            $this->logo['layers'][] = array('shape' => (int)$corpData->data->result->logo->shape3, 
                                            'color' => (int)$corpData->data->result->logo->color3);

            $this->loadBalances();
            $this->loadMembers();
        }

        function loadBalances() {
            $balanceData = new apiRequest('corp/AccountBalance.xml.aspx', array($this->account->userId,
                                                                                   $this->account->apiKey,
                                                                                   $this->character->characterID));

            if ($balanceData->data) {
                if (!$balanceData->data->error) {
                    foreach ($balanceData->data->result->rowset->row as $balance) {
                        for ($i = 0; $i < count($this->walletDivisions); $i++) {
                            if ($this->walletDivisions[$i]['key'] == (int)$balance['accountKey']) {
                                $this->walletDivisions[$i]['balance'] = (float)$balance['balance'];
                            }
                        }
                    }

                    $this->loadedBalances = true;
                }
            }
        }

        function loadMembers($full = false) {
            if (count($this->members) == 0) {
                $memberData = new apiRequest('corp/MemberTracking.xml.aspx', array($this->account->userId,
                                                                                   $this->account->apiKey,
                                                                                   $this->character->characterID));

                if ($memberData->data) {
                    if (!$memberData->data->error) {
                        foreach ($memberData->data->result->rowset->row as $member) {
                            $this->members[] = new eveCorporationMember($this->account, $this, $this->db, $member);
                        }

                        $this->loadedMembers = true;
                    }
                }
            }

            for ($i = 0; $i < count($this->members); $i++) {
                if ($full) {
                    $this->members[$i]->loadDetail();
                }
                if ($this->members[$i]->characterID == $this->character->characterID)
                    $this->character->corpMember = $this->members[$i];
            }

        }

        function loadAssets($typeFilter = false) {
            if (count($this->assets) == 0) {
                $assetData = new apiRequest('corp/AssetList.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey, 
                                                                             $this->character->characterID),
                                                                       array('version' => 2));

                if ($assetData->data) {
                    if (!$assetData->data->error) {
                        foreach ($assetData->data->result->rowset->row as $asset) {
                            $this->assets[] = new eveAsset($this->account, $this->db, $asset, $this->character);
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
                            $newOrder = new eveMarketOrder($this->account, $this->db, $order);

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
                            $this->journalItems[] = new eveJournalItem($this->account, $this->db, $journalItem);
                            $gotRows ++;
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
                            $this->transactions[] = new eveTransaction($this->account, $this->db, $transaction, $this->character);
                            $gotRows ++;
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
                            $newJob = new eveIndustryJob($this->account, $this->db, $job);
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
                        if ((int)$kill->victim['corporationID'] == $this->corporationID)
                            $this->deaths[] = new eveKill($this->account, $this->db, $kill);
                        else
                            $this->kills[] = new eveKill($this->account, $this->db, $kill);
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
                        $this->starbases[] = new eveStarbase($this->account, $this->db, $starbase, $this, $full);
                    }
                } else {
                    apiError('corp/StarbaseList.xml.aspx', $starbaseData->data->error);
                }
            }
        }

    }

?>