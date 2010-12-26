<?php

    class eveAttribute {
        var $name = '';
        var $value = 0;
        var $implant = '';
        var $bonus = 0;

        function eveAttribute($acc, $charData, $name, $char) {
            global $attributeMods;

            $this->name = $name;
            $this->value = (float)$charData->data->result->attributes->$name;
            $impName = $name.'Bonus';
            if (isset($charData->data->result->attributeEnhancers->$impName)) {
                $this->implant = (string)$charData->data->result->attributeEnhancers->$impName->augmentatorName;
                $this->bonus = (float)$charData->data->result->attributeEnhancers->$impName->augmentatorValue;
                $this->value += $this->bonus;
            }

            // apply bonuses from trained learning skills
            if ($attributeMods[$this->name]) {
                foreach ($attributeMods[$this->name] as $mod) {
                    if (isset($char->skills[$mod['skill']])) {
                        $this->value += $char->skills[$mod['skill']]->level * $mod['bonus'];
                    }
                }
            }

            // apply learning skill bonus
            if ($char->skills[3374]) {
                $this->value *= 1 + ($char->skills[3374]->level * 0.02);
            }
        }
    }
    
    class eveKnownSkill {
        var $name = '';
        var $typeID = 0;
        var $skillPoints = 0;
        var $level = 0;
        var $toLevel = 0;
        var $inTraining = false;
        
        function eveKnownSkill($acc, $db, $skill) {
            $this->typeID = (int)$skill['typeID'];
            $this->skillPoints = (int)$skill['skillpoints'];
            $this->level = (int)$skill['level'];
        }

        function getName($db) {
            $this->name = $db->typeName($this->typeID);
        }
    }

    class eveTrainingSkill {
        var $typeID = 0;
        var $toLevel = 0;
        var $inTraining = false;
        var $startTime = 0;
        var $endTime = 0;
        var $remainingTime = 0;

        var $skillItem = null;

        function eveTrainingSkill($acc, $db, $skill) {
            $this->typeID = (int)$skill->trainingTypeID;
            $this->toLevel = (int)$skill->trainingToLevel;
            $this->inTraining = (int)$skill->skillInTraining > 0;
            $this->startTime = strtotime((string)$skill->trainingStartTime) + $acc->timeOffset;
            $this->endTime = strtotime((string)$skill->trainingEndTime) + $acc->timeOffset;

            $this->remainingTime = ($this->endTime-$acc->timeOffset) - $GLOBALS['eveTime'];

            $this->skillItem = $db->eveItem($this->typeID);
        }
    }

    class eveSkillTree {
        var $groups = array();

        function eveSkillTree($acc, $tree) {
            foreach ($tree->rowset->row as $group)
                $this->groups[] = new eveSkillGroup($acc, $group);
        }
    }

    class eveSkillGroup {
        var $groupID = 0;
        var $groupName = 0;
        var $skills = array();

        function eveSkillGroup($acc, $group) {
            $this->groupID = (int)$group['groupID'];
            $this->groupName = (string)$group['groupName'];

            foreach ($group->rowset->row as $skill)
                $this->skills[] = new eveSkill($acc, $skill);
        }
    }

    class eveSkill {
        var $typeName = '';
        var $groupID = 0;
        var $typeID = 0;
        var $description = '';

        function eveSkill($acc, $skill) {
            $this->typeID = (int)$skill['typeID'];
            $this->groupID = (int)$skill['groupID'];
            $this->typeName = (string)$skill['typeName'];
            $this->description = (string)$skill->description;
        }
    }

    class eveKnownCertificate {
        var $certificateID = 0;
        
        function eveKnownCertificate($acc, $db, $certificate)  {
            $this->certificateID = (int)$certificate['certificateID'];
        }
    }

    class eveCertificateTree {
        var $categories = array();

        function eveCertificateTree($acc, $tree) {
            foreach ($tree->rowset->row as $category)
                $this->categories[] = new eveCertificateCategory($acc, $category);
        }

        function getCertificate($certificateId) {
            for ($i = 0; $i < count($this->categories); $i++) {
                for ($j = 0; $j < count($this->categories[$i]->classes); $j++) {
                    for ($k = 0; $k < count($this->categories[$i]->classes[$j]->certificates); $k++) {
                        if ($this->categories[$i]->classes[$j]->certificates[$k]->certificateid == $certificateId)
                            return $this->categories[$i]->classes[$j]->certificates[$k];
                    }
                }
            }
            return false;
        }
    }

    class eveCertificateCategory {
        var $categoryID = 0;
        var $categoryName = "";
        var $classes = array();

        function eveCertificateCategory($acc, $category) {
            $this->categoryID = (int)$category['categoryID'];
            $this->categoryName = (string)$category['categoryName'];

            foreach ($category->rowset->row as $class)
                $this->classes[] = new eveCertificateClass($acc, $class, $this);
        }
    }

    class eveCertificateClass {
        var $classID = 0;
        var $className = "";
        var $certificates = array();
        var $caregory = null;

        function eveCertificateClass($acc, $class, $category) {
            $this->category = $category;
            $this->classID = (int)$class['classID'];
            $this->className = (string)$class['className'];

            foreach ($class->rowset->row as $cert) {
                $this->certificates[] = $acc->db->eveCertificate((int)$cert['certificateID']);
                $this->certificates[count($this->certificates)-1]->cclass = $this;
            }
        }
    }

    class eveCharacterFaction {
        var $factionID = 0;
        var $factionName = '';
        var $enlisted = 0;
        var $currentRank = 0;
        var $highestRank = 0;
        var $killsYesterday = 0;
        var $killsLastWeek = 0;
        var $killsTotal = 0;
        var $victoryPointsYesterday = 0;
        var $victoryPointsLastWeek = 0;
        var $victoryPointsTotal = 0;

        function eveCharacterFaction($acc, $db, $faction) {
            $this->factionID = (int)$faction->trainingTypeID;
            $this->factionName = (string)$faction->factionName;
            $this->enlisted = strtotime((string)$faction->enlisted) + $acc->timeOffset;
            $this->currentRank = (int)$faction->currentRank;
            $this->highestRank = (int)$faction->highestRank;
            $this->killsYesterday = (int)$faction->killsYesterday;
            $this->killsLastWeek = (int)$faction->killsLastWeek;
            $this->killsTotal = (int)$faction->killsTotal;
            $this->victoryPointsYesterday = (int)$faction->victoryPointsYesterday;
            $this->victoryPointsLastWeek = (int)$faction->victoryPointsLastWeek;
            $this->victoryPointsTotal = (int)$faction->victoryPointsTotal;
        }
    }

    class eveAsset {
        var $typeID = 0;
        var $itemID = 0;
        var $flag = 0;
        var $qty = 0;
        var $locationID = 0;
        var $locationName = '';

        var $location = null;
        var $item = null;

        var $flagText = '';

        var $contents = false;

        function eveAsset($acc, $db, $asset, $char, $parentLocation = null) {
            $this->typeID = (int)$asset['typeID'];
            $this->itemID = (int)$asset['itemID'];
            $this->flag = (int)$asset['flag'];
            if (isset($asset['locationID'])) {
                $this->locationID = (int)$asset['locationID'];
                $this->location = $db->eveStation($this->locationID);
                $this->locationName = $this->location->stationname;
                if ($this->location->stationid == 0) {
                    $this->location = $db->eveSolarSystem($this->locationID);
                    $this->locationName = $this->location->solarsystemname;
                }
            } else if (isset($parentLocation)) {
                $this->location = $parentLocation;
                if (isset($parentLocation->stationid))
                    $this->locationID = $parentLocation->stationid;
                else
                    $this->locationID = $parentLocation->solarsystemid;
            }
            $this->item = $db->eveItem($this->typeID);
            $this->qty = (int)$asset['quantity'];

            $this->flagText = $db->flagText($this->flag);

            if (isset($asset->rowset) && ($asset->rowset['name'] == 'contents')) {
                $this->contents = array();
                foreach ($asset->rowset->row as $subAsset)
                    $this->contents[] = new eveAsset($acc, $db, $subAsset, $char, $this->location);
            }
        }
    }

    class eveMarketOrder {
        // orderState notes
        //  - 0: Active
        //  - 1: Closed
        //  - 2: Completed
        //  - 3: Cancelled
        //  - 4: Pending
        //  - 5: Deleted

        var $typeID = 0;
        var $orderID = 0;
        var $charID = 0;
        var $stationID = 0;
        var $volEntered = 0;
        var $volRemaining = 0;
        var $orderState = 0;
        var $range = 0;
        var $accountKey = 1000;
        var $duration = 0;
        var $price = 0;
        var $issued = 0;
        var $valRemaining = 0;
        var $buying = false;

        var $remainingTime = 0;

        var $item = null;
        var $station = null;

        function eveMarketOrder($acc, $db, $order) {
            $this->typeID = (int)$order['typeID'];
            $this->orderID = (int)$order['orderID'];
            $this->stationID = (int)$order['stationID'];
            $this->volEntered = (int)$order['volEntered'];
            $this->volRemaining = (int)$order['volRemaining'];
            $this->orderState = (string)$order['orderState'];
            $this->range = (int)$order['range'];
            $this->duration = (float)$order['duration'];
            $this->price = (float)$order['price'];
            $this->issued = strtotime((string)$order['issued']) + $acc->timeOffset;
            $this->buying = (int)$order['bid'] > 0;
            $this->valRemaining = $this->volRemaining * $this->price;

            $this->remainingTime = (($this->issued + ($this->duration*86400)) - $acc->timeOffset) - $GLOBALS['eveTime'];

            if (isset($order['charID'])) {
                $this->charID = (int)$order['charID'];
            }
            if (isset($order['accountKey'])) {
                $this->accountKey = (int)$order['accountKey'];
            }

            $this->item = $db->eveItem($this->typeID);
            $this->station = $db->eveStation($this->stationID);
        }
    }

    class eveIndustryJob {
        var $outputTypeID = 0;
        var $inputTypeID = 0;
        var $jobID = 0;
        var $inputLocationID = 0;
        var $outputLocationID = 0;
        var $installerID = 0;
        var $installerName = '';
        var $runs = 0;
        var $outQty = 0;
        var $solarSystemID = 0;
        var $materialMultiplier = 0;
        var $completed = 0;
        var $completedStatusID = 0;
        var $completedStatus = '';
        var $activityID = 0;
        var $installTime = 0;
        var $beginTime = 0;
        var $endTime = 0;
        var $pauseTime = 0;

        var $remainingTime = 0;
        var $percentComplete = 0;

        var $activity = null;
        var $inItem = null;
        var $outItem = null;
        var $inLocation = null;
        var $outLocation = null;

        function eveIndustryJob($acc, $db, $job) {
            $this->outputTypeID = (int)$job['outputTypeID'];
            $this->inputTypeID = (int)$job['installedItemTypeID'];
            $this->jobID = (int)$job['jobID'];
            $this->inputLocationID = (int)$job['installedItemLocationID'];
            $this->outputLocationID = (int)$job['outputLocationID'];
            $this->installerID = (int)$job['installerID'];
            $this->runs = (int)$job['runs'];
            $this->solarSystemID = (int)$job['installedInSolarSystemID'];
            $this->materialMultiplier = (int)$job['charMaterialMultiplier'];
            $this->completed = (int)$job['completed'];
            $this->completedStatusID = (int)$job['completedStatus'];
            $this->completedStatus = $db->industryCompleteText($this->completedStatusID);
            $this->activityID = (int)$job['activityID'];
            $this->activity = $db->eveIndustryActivity($this->activityID);
            $this->installTime = strtotime((string)$job['installTime']) + $acc->timeOffset;
            $this->beginTime = strtotime((string)$job['beginProductionTime']) + $acc->timeOffset;
            $this->endTime = strtotime((string)$job['endProductionTime']) + $acc->timeOffset;
            $this->pauseTime = strtotime((string)$job['pauseProductionTime']) + $acc->timeOffset;

            $this->inItem = $db->eveItem($this->inputTypeID);
            $this->outItem = $db->eveItem($this->outputTypeID);
            $this->inLocation = $db->eveStation($this->inputLocationID);
            $this->outLocation = $db->eveStation($this->outputLocationID);

            if (($this->completed == 0) && ($this->completedStatusID == 0) && ($this->endTime-$acc->timeOffset < $GLOBALS['eveTime']))
                $this->completedStatus = 'Ready';
            else if (($this->completed == 0) && ($this->completedStatusID == 0))
                $this->completedStatus = 'In Progress';

            $this->remainingTime = ($this->endTime-$acc->timeOffset) - $GLOBALS['eveTime'];
            if ($this->remainingTime < 0)
                $this->percentComplete = 100;
            else
                $this->percentComplete = 100 - ($this->remainingTime / (($this->endTime-$acc->timeOffset) - ($this->beginTime-$acc->timeOffset)) * 100);
                
            if ($this->activityID == 1)
                $this->outQty = $this->outItem->portionsize * $this->runs;
        }
    }

    class eveTransaction {
        var $typeID = 0;
        var $transactionID = 0;
        var $transactionTime = 0;
        var $qty = 0;
        var $unitPrice = 0;
        var $totalPrice = 0;
        var $clientID = 0;
        var $clientName = '';
        var $characterID = 0;
        var $characterName = '';
        var $stationID = 0;
        var $transactionType = '';
        var $transactionFor = '';
        var $purchase = false;

        var $item = null;
        var $station = null;

        function eveTransaction($acc, $db, $trans, $owner) {
            $this->typeID = (int)$trans['typeID'];
            $this->transactionID = (int)$trans['transactionID'];
            $this->transactionTime = strtotime((string)$trans['transactionDateTime']) + $acc->timeOffset;
            $this->qty = (int)$trans['quantity'];
            $this->unitPrice = (float)$trans['price'];
            $this->clientID = (int)$trans['clientID'];
            $this->clientName = (string)$trans['clientName'];
            $this->stationID = (int)$trans['stationID'];
            $this->transactionType = (string)$trans['transactionType'];
            $this->transactionFor = (string)$trans['transactionFor'];
            $this->totalPrice = $this->unitPrice * $this->qty;

            if (isset($trans['characterID'])) {
                $this->characterID = (int)$trans['characterID'];
            } else {
                $this->characterID = $owner->characterID;
            }

            if (isset($trans['characterName'])) {
                $this->characterName = (string)$trans['characterName'];
            } else {
                $this->characterName = $owner->name;
            }

            $this->purchase = ($this->transactionType == 'buy');

            $this->item = $db->eveItem($this->typeID);
            $this->station = $db->eveStation($this->stationID);
        }
    }

    class eveJournalItem {
        var $date = 0;
        var $refTypeID = 0;
        var $refType = '';
        var $journalID = 0;
        var $fromID = 0;
        var $fromName = '';
        var $toID = 0;
        var $toName = 0;
        var $amount = 0;
        var $balance = 0;
        var $reason = '';

        function eveJournalItem($acc, $db, $item) {
            $this->date = strtotime((string)$item['date']) + $acc->timeOffset;
            $this->refTypeID = (int)$item['refTypeID'];
            $this->journalID = (int)$item['refID'];
            $this->fromID = (int)$item['ownerID1'];
            $this->fromName = (string)$item['ownerName1'];
            $this->toID = (int)$item['ownerID2'];
            $this->toName = (string)$item['ownerName2'];
            $this->amount = (float)$item['amount'];
            $this->balance = (float)$item['balance'];
            $this->reason = (string)$item['reason'];

            $this->refType = $db->refType($this->refTypeID);
        }
    }

    class eveKillCharacter {
        var $characterID = 0;
        var $characterName = '';
        var $corporationID = 0;
        var $corporationName = '';
        var $allianceID = 0;
        var $allianceName = '';
        var $damageTaken = 0;
        var $damageDone = 0;
        var $shipTypeID = 0;
        var $weaponTypeID = 0;
        var $finalBlow = false;
        var $securityStatus = 0;

        var $ship = null;
        var $weapon = null;

        function eveKillCharacter($acc, $db, $char) {
            $this->characterID = (int)$char['characterID'];
            $this->characterName = (string)$char['characterName'];
            $this->corporationID = (int)$char['corporationID'];
            $this->corporationName = (string)$char['corporationName'];
            $this->allianceID = (int)$char['allianceID'];
            $this->allianceName = (string)$char['allianceName'];

            if (isset($char['damageTaken']))
                $this->damageTaken = (int)$char['damageTaken'];
            if (isset($char['damageDone']))
                $this->damageDone = (int)$char['damageDone'];

            $this->shipTypeID = (int)$char['shipTypeID'];
            $this->weaponTypeID = (int)$char['weaponTypeID'];
            $this->finalBlow = (int)$char['finalBlow'] > 0;
            $this->securityStatus = (float)$char['securityStatus'];

            if ($this->shipTypeID > 0)
                $this->ship = $db->eveItem($this->shipTypeID);
            if ($this->weaponTypeID > 0)
                $this->weapon = $db->eveItem($this->weaponTypeID);

            if ($this->characterID == 0) {
                    $this->characterName = '[NPC]';
            } else if ((strpos($this->characterName, 'GetName') !== false) || (empty($this->characterName))) {
                if ((strpos($this->corporationName, 'GetName') === false) && ($this->ship != null))
                    $this->characterName = $this->corporationName . ' ' . $this->ship->typename;
                else
                    $this->characterName = '[API Error]';
            }

            if (($this->characterID == 0) && ($this->shipTypeID > 0)) {
                $this->characterName = $this->ship->typename . ' ' . $this->characterName;
            }

            if (strpos($this->corporationName, 'GetName') !== false)
                $this->corporationName = '[API Error]';

            if (strpos($this->allianceName, 'GetName') !== false)
                $this->allianceName = '[API Error]';

            if (empty($this->allianceName))
                $this->allianceName = 'None';
        }
    }

    class eveKillDrop {
        var $typeID = 0;
        var $flag = 0;
        var $qtyDropped = 0;
        var $qtyDestroyed = 0;

        var $flagText = '';

        var $item = null;

        function eveKillDrop($acc, $db, $item) {
            $this->typeID = (int)$item['typeID'];
            $this->flag = (int)$item['flag'];
            $this->qtyDropped = (int)$item['qtyDropped'];
            $this->qtyDestroyed = (int)$item['qtyDestroyed'];

            $this->item = $db->eveItem($this->typeID);

            $this->flagText = $db->flagText($this->flag);
        }
    }

    class eveKill {
        var $killID = 0;
        var $date = 0;
        var $solarSystemID = 0;

        var $dropValue = 0;
        var $destroyValue = 0;
        var $shipValue = 0;

        var $solarSystem = null;
        var $victim = null;
        var $attackers = array();

        var $itemsDropped = array();
        var $itemsDestroyed = array();

        function eveKill($acc, $db, $kill) {
            $this->killID = (int)$kill['killID'];
            $this->date = strtotime((string)$kill['killTime']) + $acc->timeOffset;
            $this->solarSystemID = (int)$kill['solarSystemID'];

            $this->solarSystem = $db->eveSolarSystem($this->solarSystemID);

            $this->victim = new eveKillCharacter($acc, $db, $kill->victim);

            foreach ($kill->rowset as $rowGroup) {
                if ($rowGroup['name'] == 'attackers') {
                    foreach ($rowGroup->row as $attacker)
                        $this->attackers[] = new eveKillCharacter($acc, $db, $attacker);
                } else if ($rowGroup['name'] == 'items') {
                    foreach ($rowGroup->row as $item) {
                        if ((int)$item['qtyDropped'] > 0)
                            $this->itemsDropped[] = new eveKillDrop($acc, $db, $item);
                        else
                            $this->itemsDestroyed[] = new eveKillDrop($acc, $db, $item);
                    }
                }
            }
        }

        function getDropValues($regionId = 0) {
            for ($i = 0; $i < count($this->itemsDropped); $i++) {
                $this->itemsDropped[$i]->item->getPricing($regionId);
                $this->dropValue += $this->itemsDropped[$i]->item->pricing->avgSell * $this->itemsDropped[$i]->qtyDropped;
            }

            for ($i = 0; $i < count($this->itemsDestroyed); $i++) {
                $this->itemsDestroyed[$i]->item->getPricing($regionId);
                $this->destroyValue += $this->itemsDestroyed[$i]->item->pricing->avgSell * $this->itemsDestroyed[$i]->qtyDestroyed;
            }

            if ($this->victim->ship) {
                $this->victim->ship->getPricing($regionId);
                $this->shipValue = $this->victim->ship->pricing->avgSell;
            }
        }
    }

?>