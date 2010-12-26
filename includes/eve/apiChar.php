<?php

    class eveCharacter {
        var $characterID = 0;
        var $name = '';
        var $race = '';
        var $bloodLine = '';
        var $gender = '';
        var $corporationName = '';
        var $corporationID = '';
        var $balance = 0;
        var $skillPoints = 0;

        var $attributes = array();
        var $skills = array();
        var $trainingSkill = null;

        var $skillTree = null;

        var $certificates = array();

        var $certificateTree = null;

        var $outpostList = null;

        var $faction = null;

        var $assets = array();
        var $orders = array();
        var $transactions = array();
        var $journalItems = array();
        var $industryJobs = array();

        var $deaths = array();
        var $kills = array();

        var $corporation = null;

        var $db = null;

        function eveCharacter($account, $characterID, $autoLoad = true) {
            $this->db = $account->db;

            $this->account = $account;
            $this->characterID = $characterID;

            if ($autoLoad)
                $this->loadCharacter();
        }

        function loadCharacter() {
            $charData = new apiRequest('char/CharacterSheet.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey, 
                                                                             $this->characterID));

            if (!$charData->data)
                return;

            if ($charData->data->error)
                apiError('char/CharacterSheet.xml.aspx', $charData->data->error);

            foreach (get_object_vars($charData->data->result) as $var => $val) {
                if (!is_array($val) && !is_object($val))
                    $this->$var = trim($val);
            }
            
            foreach ($charData->data->result->rowset as $rowset) {
                if ($rowset['name'] == 'skills') {
                    foreach ($rowset->row as $skill) {
                        $this->skills[(string)$skill['typeID']] = new eveKnownSkill($this->account, $this->db, $skill);
                        $this->skillPoints += $this->skills[(string)$skill['typeID']]->skillPoints;
                    }
                } else if ($rowset['name'] == 'certificates') {
                    foreach ($rowset->row as $certificate) {
                        $this->certificates[(string)$certificate['certificateID']] = new eveKnownCertificate($this->account, $this->db, $certificate);
                    }
                }
            }

            foreach (get_object_vars($charData->data->result->attributes) as $var => $val) {
                $this->attributes[$var] = new eveAttribute($this->account, $charData, $var, $this);
            }


            $trainingData = new apiRequest('char/SkillInTraining.xml.aspx', array($this->account->userId,
                                                                                  $this->account->apiKey, 
                                                                                  $this->characterID));
            if ($trainingData->data) {
                $this->trainingSkill = new eveTrainingSkill($this->account, $this->db, $trainingData->data->result);
            }


            $factionData = new apiRequest('char/FacWarStats.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey, 
                                                                             $this->characterID), null, false);
            if ($factionData->data) {
                if ((!$factionData->data->error) && (!(int)$factionData->data->gotError))
                    $this->faction = new eveCharacterFaction($this->account, $this->db, $factionData->data->result);
            }

            $training = $this->getSkill($this->trainingSkill->typeID);
            if ($training && ($training != $this->trainingSkill)) {
                $training->inTraining = $this->trainingSkill->inTraining;
                $training->toLevel = $this->trainingSkill->toLevel;
            }

        }

        function loadCorporation() {
            $corpData = new apiRequest('corp/CorporationSheet.xml.aspx', array($this->account->userId,
                                                                               $this->account->apiKey,
                                                                               $this->characterID));
            if ($corpData->data && !$corpData->data->error) {
                $this->corporation = new eveCorporation($this->account, $this, $corpData);
            }
        }

        function loadAssets($typeFilter = false) {
            if (count($this->assets) == 0) {
                $assetData = new apiRequest('char/AssetList.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey, 
                                                                             $this->characterID),
                                                                       array('version' => 2));
                if ($assetData->data) {
                    if (!$assetData->data->error) {
                        foreach ($assetData->data->result->rowset->row as $asset) {
                            $this->assets[] = new eveAsset($this->account, $this->db, $asset, $this);
                        }
                    } else {
                        apiError('char/AssetList.xml.aspx', $assetData->data->error);
                    }
                }
            }
        }

        function loadOrders() {
            if (count($this->orders) == 0) {
                $orderData = new apiRequest('char/MarketOrders.xml.aspx', array($this->account->userId,
                                                                                $this->account->apiKey, 
                                                                                $this->characterID),
                                                                          array('version' => 2));

                if ($orderData->data) {
                    if (!$orderData->data->error) {
                        foreach ($orderData->data->result->rowset->row as $order) {
                            $this->orders[] = new eveMarketOrder($this->account, $this->db, $order);
                        }
                    } else {
                        apiError('char/MarketOrders.xml.aspx', $orderData->data->error);
                    }
                }
            }
        }

        function loadTransactions() {
            if (count($this->transactions) == 0) {
                $transData = new apiRequest('char/WalletTransactions.xml.aspx', array($this->account->userId,
                                                                                      $this->account->apiKey, 
                                                                                      $this->characterID));
                if ($transData->data) {
                    if (!$transData->data->error) {
                        foreach ($transData->data->result->rowset->row as $transaction) {
                            $this->transactions[] = new eveTransaction($this->account, $this->db, $transaction, $this);
                        }
                    } else {
                        apiError('char/WalletTransactions.xml.aspx', $transData->data->error);
                    }
                }
            }
        }

        function loadJournal() {
            if (count($this->journalItems) == 0) {
                $journalData = new apiRequest('char/WalletJournal.xml.aspx', array($this->account->userId,
                                                                                   $this->account->apiKey, 
                                                                                   $this->characterID));
                if ($journalData->data) {
                    if (!$journalData->data->error) {
                        foreach ($journalData->data->result->rowset->row as $journalItem) {
                            $this->journalItems[] = new eveJournalItem($this->account, $this->db, $journalItem);
                        }
                    } else {
                        apiError('char/WalletJournal.xml.aspx', $journalData->data->error);
                    }
                }
            }
        }

        function loadIndustryJobs() {
            if (count($this->industryJobs) == 0) {
                $jobData = new apiRequest('char/IndustryJobs.xml.aspx', array($this->account->userId,
                                                                              $this->account->apiKey, 
                                                                              $this->characterID),
                                                                        array('version' => 2));
                if ($jobData->data) {
                    if (!$jobData->data->error) {
                        foreach ($jobData->data->result->rowset->row as $job) {
                            $this->industryJobs[] = new eveIndustryJob($this->account, $this->db, $job);
                        }
                    } else {
                        apiError('char/IndustryJobs.xml.aspx', $jobData->data->error);
                    }
                }
            }
        }

        function loadKills() {
            $killData = new apiRequest('char/KillLog.xml.aspx', array($this->account->userId,
                                                                      $this->account->apiKey, 
                                                                      $this->characterID),
                                                                array('version' => 2));

            if ($killData->data) {
                if (!$killData->data->error) {
                    foreach ($killData->data->result->rowset->row as $kill) {
                        if ((int)$kill->victim['characterID'] == $this->characterID)
                            $this->deaths[] = new eveKill($this->account, $this->db, $kill);
                        else
                            $this->kills[] = new eveKill($this->account, $this->db, $kill);
                    }
                } else {
                    apiError('char/KillLog.xml.aspx', $killData->data->error);
                }
            }
        }

        function loadSkillTree() {
            if ($this->skillTree == null) {
                $skillData = new apiRequest('eve/SkillTree.xml.aspx');
                if ($skillData->data) {
                    if (!$skillData->data->error) {
                        $this->skillTree = new eveSkillTree($this->account, $skillData->data->result);
                    } else {
                        apiError('eve/SkillTree.xml.aspx', $skillData->data->error);
                    }
                }
            }
        }

        function loadCertificateTree() {
            if ($this->certificateTree == null) {
                $certData = new apiRequest('eve/CertificateTree.xml.aspx');
                if ($certData->data) {
                    if (!$certData->data->error) {
                        $this->certificateTree = new eveCertificateTree($this->account, $certData->data->result);
                    } else {
                        apiError('eve/CertificateTree.xml.aspx', $certData->data->error);
                    }
                }
            }
        }

        function getSkill($typeID) {
            $result = isset($this->skills[$typeID]) ? $this->skills[$typeID] : false;
            if (!$result)
                if ($this->trainingSkill->typeID == $typeID)
                    $result = $this->trainingSkill;
            return $result;
        }

        function knownSkills() {
            $result = array();
            for ($i = 0; $i < count($this->skillTree->groups); $i++) {
                for ($j = 0; $j < count($this->skillTree->groups[$i]->skills); $j++) {
                    $knownSkill = $this->getSkill($this->skillTree->groups[$i]->skills[$j]->typeID);
                    if (($knownSkill) || ($this->trainingSkill->typeID == $this->skillTree->groups[$i]->skills[$j]->typeID)) {
                        if (!isset($result[$this->skillTree->groups[$i]->groupID])) {
                            $theSkill = array();
                            $theSkill['name'] = $this->skillTree->groups[$i]->groupName;
                            $theSkill['skillpoints'] = 0;
                            $theSkill['skills'] = array();
                            $result[$this->skillTree->groups[$i]->groupID] = $theSkill;
                        }

                        $result[$this->skillTree->groups[$i]->groupID]['skillpoints'] += (int)$knownSkill->skillPoints;

                        $result[$this->skillTree->groups[$i]->groupID]['skills'][] = array(
                            'typeID' => $this->skillTree->groups[$i]->skills[$j]->typeID,
                            'name' => $this->skillTree->groups[$i]->skills[$j]->typeName,
                            'description' => $this->skillTree->groups[$i]->skills[$j]->description,
                            'level' => (int)$knownSkill->level,
                            'skillpoints' => (int)$knownSkill->skillPoints,
                            'training' => (int)$knownSkill->inTraining,
                            'toLevel' => (int)$knownSkill->toLevel);
                    }
                }
            }

            return $result;
        }

        function knownCertificates() {
            $result = array();

            foreach ($this->certificates as $certId => $knownCert) {
                $cert = $this->certificateTree->getCertificate($certId);
                $catId = $cert->cclass->category->categoryID;
                $clsId = $cert->cclass->classID;
                if (!isset($result[$catId])) {
                    $result[$catId] = array();
                    $result[$catId]['name'] = $cert->cclass->category->categoryName;
                    $result[$catId]['classes'] = array();
                }

                if (!isset($result[$catId]['classes'][$clsId])) {
                    $result[$catId]['classes'][$clsId] = array();
                    $result[$catId]['classes'][$clsId]['name'] = $cert->cclass->className;
                    $result[$catId]['classes'][$clsId]['grade'] = $cert->grade;
                    $result[$catId]['classes'][$clsId]['icon'] = $cert->icon;
                }

                if ($cert->grade > $result[$catId]['classes'][$clsId]['grade']) {
                    $result[$catId]['classes'][$clsId]['grade'] = $cert->grade;
                    $result[$catId]['classes'][$clsId]['icon'] = $cert->icon;
                }
            }

            return $result;
        }

    }

?>