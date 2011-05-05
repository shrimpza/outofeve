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
        var $skillQueue = null;

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

        var $mail = array();
        var $notifications = array();

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

        function loadMail() {
            if (count($this->mail) == 0) {
                $mailData = new apiRequest('char/MailMessages.xml.aspx', array($this->account->userId,
                                                                               $this->account->apiKey,
                                                                               $this->characterID),
                                                                         array('version' => 2));
                if ($mailData->data) {
                    if (!$mailData->data->error) {
                        foreach ($mailData->data->result->rowset->row as $mail) {
                            $this->mail[] = new eveMailMessage($this->account, $mail);
                        }
                    } else {
                        apiError('char/MailMessages.xml.aspx', $mailData->data->error);
                    }
                }
            }

            // get list of character and corp names for messages
            if (count($this->mail) > 0) {
                usort($this->mail, 'mailSort');

                $ids = array();
                foreach ($this->mail as $mail) {
                    if (!empty($mail->senderID) && $mail->senderID > 0) {
                        $ids[] = $mail->senderID;
                    }
                    if (!empty($mail->toCorpID) && $mail->toCorpID > 0) {
                        $ids[] = $mail->toCorpID;
                    }
                    $ids = array_merge($ids, $mail->toCharacterIDs);
                }
                $ids = array_unique($ids);
                $names = new apiRequest('eve/CharacterName.xml.aspx', null, array('ids' => implode(',', $ids)));
                if ($names->data) {
                    if (!$names->data->error) {
                        foreach ($names->data->result->rowset->row as $name) {
                            for ($i = 0; $i < count($this->mail); $i++) {
                                if ($this->mail[$i]->senderID == (int)$name['characterID']) {
                                    $this->mail[$i]->senderName = (string)$name['name'];
                                }
                                if ($this->mail[$i]->toCorpID == (int)$name['characterID']) {
                                    $this->mail[$i]->toCorpName = (string)$name['name'];
                                }
                                for ($j = 0; $j < count($this->mail[$i]->toCharacterIDs); $j++) {
                                    if ($this->mail[$i]->toCharacterIDs[$j] == (int)$name['characterID']) {
                                        $this->mail[$i]->toCharacterNames[$j] = (string)$name['name'];
                                    }
                                }
                            }
                        }
                    } else {
                        apiError('eve/CharacterName.xml.aspx', $names->data->error);
                    }
                }
            }
        }

        function getMailMessage($message) {
            $result = false;
            $mailData = new apiRequest('char/MailBodies.xml.aspx', array($this->account->userId,
                                                                         $this->account->apiKey,
                                                                         $this->characterID),
                                                                   array('version' => 2,
                                                                         'ids' => $message->messageID));
            if ($mailData->data) {
                if (!$mailData->data->error) {
                    foreach ($mailData->data->result->rowset->row as $mail) {
                        if ((int)$mail['messageID'] == $message->messageID) {
                            $result = new eveMailMessageBody($this->account, $mail);
                            $result->headers = $message;
                        }
                    }
                } else {
                    apiError('char/MailBodies.xml.aspx', $mailData->data->error);
                }
            }

            return $result;
        }

        function loadNotifications() {
            if (count($this->notifications) == 0) {
                $notificationData = new apiRequest('char/Notifications.xml.aspx', array($this->account->userId,
                                                                                        $this->account->apiKey,
                                                                                        $this->characterID),
                                                                                  array('version' => 2));
                if ($notificationData->data) {
                    if (!$notificationData->data->error) {
                        foreach ($notificationData->data->result->rowset->row as $notification) {
                            $this->notifications[] = new eveNotification($this->account, $notification);
                        }
                    } else {
                        apiError('char/Notifications.xml.aspx', $notificationData->data->error);
                    }
                }
            }

            // get list of character and corp names for messages
            if (count($this->notifications) > 0) {
                $ids = array();
                foreach ($this->notifications as $note) {
                    if ((!empty($note->senderID) && $note->senderID > 0)
                            && ($note->item->itemid == 0)) {
                        $ids[] = $note->senderID;
                    }
                }
                $ids = array_unique($ids);
                $names = new apiRequest('eve/CharacterName.xml.aspx', null, array('ids' => implode(',', $ids)));
                if ($names->data) {
                    if (!$names->data->error) {
                        foreach ($names->data->result->rowset->row as $name) {
                            for ($i = 0; $i < count($this->notifications); $i++) {
                                if ($this->notifications[$i]->senderID == (int)$name['characterID']) {
                                    $this->notifications[$i]->senderName = (string)$name['name'];
                                }
                            }
                        }
                    } else {
                        apiError('eve/CharacterName.xml.aspx', $names->data->error);
                    }
                }
            }

            usort($this->notifications, 'mailSort');
        }

        function getNotificationText($notification) {
            $result = false;
            $notificationData = new apiRequest('char/NotificationTexts.xml.aspx', array($this->account->userId,
                                                                                    $this->account->apiKey,
                                                                                    $this->characterID),
                                                                                  array('version' => 2,
                                                                                    'ids' => $notification->notificationID));
            if ($notificationData->data) {
                if (!$notificationData->data->error) {
                    foreach ($notificationData->data->result->rowset->row as $text) {
                        if ((int)$text['notificationID'] == $notification->notificationID) {
                            $result = new eveNotificationText($this->account, $text);
                            $result->headers = $notification;
                        }
                    }
                } else {
                    apiError('char/NotificationTexts.xml.aspx', $notificationData->data->error);
                }
            }

            return $result;
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

        function loadSkillQueue() {
            if ($this->skillQueue == null) {
                $skillQueueData = new apiRequest('char/SkillQueue.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey,
                                                                             $this->characterID),
                                                                             array('version' => 2));
              if ($skillQueueData->data) {
                    if (!$skillQueueData->data->error) {
                        $this->skillQueue = new eveSkillQueue($this->account, $this->db, $skillQueueData->data->result);
                    } else {
                        apiError('char/SkillQueue.xml.aspx', $skillQueueData->data->error);
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

    function mailSort($a, $b) {
        return ($a->sentDate > $b->sentDate) ? -1 : 1;
    }

?>