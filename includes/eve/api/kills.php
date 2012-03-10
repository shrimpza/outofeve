<?php

    class eveKillsList {
        var $kills = array();
        var $deaths = array();

        function load($account, $character) {
            if ((count($this->kills) == 0) && (count($this->deaths) == 0)) {
                $data = new apiRequest('char/KillLog.xml.aspx', array($account->userId,
                                                                      $account->apiKey, 
                                                                      $character->characterID),
                                                                array('version' => 2));

                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $kill) {
                        if ((int)$kill->victim['characterID'] == $character->characterID) {
                            $this->deaths[] = new eveKill($account, $kill);
                        } else {
                            $this->kills[] = new eveKill($account, $kill);
                        }
                    }
                }
            }
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

        function eveKillCharacter($acc, $char) {
            $this->characterID = (int)$char['characterID'];
            $this->characterName = (string)$char['characterName'];
            $this->corporationID = (int)$char['corporationID'];
            $this->corporationName = (string)$char['corporationName'];
            $this->allianceID = (int)$char['allianceID'];
            $this->allianceName = (string)$char['allianceName'];

            if (isset($char['damageTaken'])) {
                $this->damageTaken = (int)$char['damageTaken'];
            }
            if (isset($char['damageDone'])) {
                $this->damageDone = (int)$char['damageDone'];
            }

            $this->shipTypeID = (int)$char['shipTypeID'];
            $this->weaponTypeID = (int)$char['weaponTypeID'];
            $this->finalBlow = (int)$char['finalBlow'] > 0;
            $this->securityStatus = (float)$char['securityStatus'];

            if ($this->shipTypeID > 0) {
                $this->ship = eveDB::getInstance()->eveItem($this->shipTypeID);
            }
            if ($this->weaponTypeID > 0) {
                $this->weapon = eveDB::getInstance()->eveItem($this->weaponTypeID);
            }

            if ($this->characterID == 0) {
                    $this->characterName = '[NPC]';
            } else if ((strpos($this->characterName, 'GetName') !== false) || (empty($this->characterName))) {
                if ((strpos($this->corporationName, 'GetName') === false) && ($this->ship != null)) {
                    $this->characterName = $this->corporationName . ' ' . $this->ship->typename;
                } else {
                    $this->characterName = '[API Error]';
                }
            }

            if (($this->characterID == 0) && ($this->shipTypeID > 0)) {
                $this->characterName = $this->ship->typename . ' ' . $this->characterName;
            }

            if (strpos($this->corporationName, 'GetName') !== false) {
                $this->corporationName = '[API Error]';
            }

            if (strpos($this->allianceName, 'GetName') !== false) {
                $this->allianceName = '[API Error]';
            }

            if (empty($this->allianceName)) {
                $this->allianceName = 'None';
            }
        }
    }

    class eveKillDrop {
        var $typeID = 0;
        var $flag = 0;
        var $qtyDropped = 0;
        var $qtyDestroyed = 0;

        var $flagText = '';

        var $item = null;

        function eveKillDrop($acc, $item) {
            $this->typeID = (int)$item['typeID'];
            $this->flag = (int)$item['flag'];
            $this->qtyDropped = (int)$item['qtyDropped'];
            $this->qtyDestroyed = (int)$item['qtyDestroyed'];

            $this->item = eveDB::getInstance()->eveItem($this->typeID);

            $this->flagText = eveDB::getInstance()->flagText($this->flag);
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

        function eveKill($acc, $kill) {
            $this->killID = (int)$kill['killID'];
            $this->date = strtotime((string)$kill['killTime']) + $acc->timeOffset;
            $this->solarSystemID = (int)$kill['solarSystemID'];

            $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->solarSystemID);

            $this->victim = new eveKillCharacter($acc, $kill->victim);

            foreach ($kill->rowset as $rowGroup) {
                if ($rowGroup['name'] == 'attackers') {
                    foreach ($rowGroup->row as $attacker) {
                        $this->attackers[] = new eveKillCharacter($acc, $attacker);
                    }
                } else if ($rowGroup['name'] == 'items') {
                    foreach ($rowGroup->row as $item) {
                        if ((int)$item['qtyDropped'] > 0) {
                            $this->itemsDropped[] = new eveKillDrop($acc, $item);
                        } else {
                            $this->itemsDestroyed[] = new eveKillDrop($acc, $item);
                        }
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