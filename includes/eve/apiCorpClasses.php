<?php

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

        var $db;

        function eveCorporationMember($acc, $corp, $db, $member) {
            $this->db = $db;

            $this->characterID = (int)$member['characterID'];
            $this->name = (string)$member['name'];
            $this->startDateTime = strtotime((string)$member['startDateTime']) + $acc->timeOffset;
            $this->baseID = (int)$member['baseID'];
            $this->title = (string)$member['title'];
            $this->logonDateTime = strtotime((string)$member['logonDateTime']) + $acc->timeOffset;
            $this->logoffDateTime = strtotime((string)$member['logoffDateTime']) + $acc->timeOffset;
            $this->locationID = (int)$member['locationID'];
            $this->shipTypeID = (int)$member['shipTypeID'];
            $this->roles = (int)$member['roles'];
            $this->grantableRoles = (int)$member['grantableRoles'];

            $this->setRoles($db);
        }

        function setRoles() {
            $corpRoles = $this->db->corpRoleList();

            for ($i = 0; $i < count($corpRoles); $i++) {
                if ($this->roles & (int)$corpRoles[$i]['rolebit']) {
                    $this->roleList[$corpRoles[$i]['rolename']] = $corpRoles[$i];
                }
            }
        }

        function hasRole($roleName) {
            return isset($this->roleList[$roleName]);
        }

        function loadDetail() {
            if ($this->baseID > 0)
                $this->base = $this->db->eveStation($this->baseID);

            if ($this->locationID > 0) {
                $this->location = $this->db->eveStation($this->locationID);
                $this->locationName = $this->location->stationname;
                if ($this->location->stationid == 0) {
                    $this->location = $this->db->eveSolarSystem($this->locationID);
                    $this->locationName = $this->location->solarsystemname;
                }
            }
            
            if ($this->shipTypeID > 0)
                $this->ship = $this->db->eveItem($this->shipTypeID);
        }
    }

    class eveStarbase {
        var $itemID = 0;
        var $typeID = 0;
        var $locationID = 0;
        var $moonID = 0;
        var $state = 0;
        var $stateTimestamp = 0;
        var $onlineTimestamp = 0;
        var $generalSettings = array(
                'usageFlags' => 0,
                'deployFlags' => 0,
                'allowCorporationMembers' => 0,
                'allowAllianceMembers' => 0,
                'claimSovereignty' => 0,
            );
        var $combatSettings = array(
                'onStandingDrop' => array('enabled' => 0, 'standing' => 0),
                'onStatusDrop' => array('enabled' => 0, 'standing' => 0),
                'onAggression' => 0,
                'onCorporationWar' => 0,
            );

        var $fuelRequired = array();
        var $fuel = array();

        var $solarSystem = null;
        var $moon = null;
        var $tower = null;

        var $remainingTime = 0;

        function eveStarbase($acc, $db, $starbase, $corp, $loadDetail = false) {
            $this->itemID = (int)$starbase['itemID'];
            $this->typeID = (int)$starbase['typeID'];
            $this->locationID = (int)$starbase['locationID'];
            $this->moonID = (int)$starbase['moonID'];
            $this->state = (int)$starbase['state'];
            $this->stateTimestamp = strtotime((string)$starbase['stateTimestamp']) + $acc->timeOffset;
            $this->onlineTimestamp = strtotime((string)$starbase['onlineTimestamp']) + $acc->timeOffset;

            $this->solarSystem = $db->eveSolarSystem($this->locationID);
            $this->moon = $db->eveCelestial($this->moonID);
            $this->tower = $db->eveItem($this->typeID);

            if ($loadDetail) {
                $this->loadDetail($db, $corp);
            }
        }

        function loadFuelRequired($db) {
            $tmpFuel = $db->eveFuelRequirements($this->typeID);
            for ($i = 0; $i < count($tmpFuel); $i++) {
                if (((int)$tmpFuel[$i]['factionid'] == 0) 
                    || (((int)$tmpFuel[$i]['factionid'] > 0) && ((int)$tmpFuel[$i]['factionid'] == (int)$this->solarSystem->factionid))) {
                    $this->fuelRequired[] = $tmpFuel[$i];
                }
                $this->setupFuel($this->fuelRequired[count($this->fuelRequired)-1]['resource']->typeid, 0);
            }
        }

        function loadDetail($db, $corp) {
            $this->loadFuelRequired($db);

            $starbaseData = new apiRequest('corp/StarbaseDetail.xml.aspx', array($corp->account->userId,
                                                                                 $corp->account->apiKey, 
                                                                                 $corp->character->characterID),
                                                                           array('version' => 2, 'itemID' => $this->itemID));

            if ($starbaseData->data) {
                if (!$starbaseData->data->error) {
                    $this->state = (int)$starbaseData->data->result->state;
                    $this->stateTimestamp = strtotime((string)$starbaseData->data->result->stateTimestamp) + $acc->timeOffset;
                    $this->onlineTimestamp = strtotime((string)$starbaseData->data->result->onlineTimestamp) + $acc->timeOffset;

                    $this->generalSettings['usageFlags'] = (int)$starbaseData->data->result->generalSettings->usageFlags;
                    $this->generalSettings['deployFlags'] = (int)$starbaseData->data->result->generalSettings->deployFlags;
                    $this->generalSettings['allowCorporationMembers'] = (int)$starbaseData->data->result->generalSettings->allowCorporationMembers;
                    $this->generalSettings['allowAllianceMembers'] = (int)$starbaseData->data->result->generalSettings->allowAllianceMembers;
                    $this->generalSettings['claimSovereignty'] = (int)$starbaseData->data->result->generalSettings->claimSovereignty;

                    $this->combatSettings['onStandingDrop']['enabled'] = (int)$starbaseData->data->result->combatSettings->onStandingDrop['enabled'];
                    $this->combatSettings['onStandingDrop']['standing'] = (float)$starbaseData->data->result->combatSettings->onStandingDrop['standing'];
                    $this->combatSettings['onStatusDrop']['enabled'] = (int)$starbaseData->data->result->combatSettings->onStatusDrop['enabled'];
                    $this->combatSettings['onStatusDrop']['standing'] = (float)$starbaseData->data->result->combatSettings->onStatusDrop['standing'];
                    $this->combatSettings['onAggression'] = (int)$starbaseData->data->result->combatSettings->onAggression['enabled'];
                    $this->combatSettings['onCorporationWar'] = (int)$starbaseData->data->result->combatSettings->onCorporationWar['enabled'];

                    foreach ($starbaseData->data->result->rowset as $rowset) {
                        if ($rowset['name'] == 'fuel') {
                            foreach ($rowset->row as $fuel) {
                                $this->setupFuel((int)$fuel['typeID'], (int)$fuel['quantity']);
                            }
                        }
                    }
                } else {
                    apiError($starbaseData->data->error);
                }
            }

            if (in_array($this->state, array(3)))
                $this->remainingTime = ($this->stateTimestamp-$acc->timeOffset) - $GLOBALS['eveTime'];
            else if (in_array($this->state, array(2, 4)))
                $this->remainingTime = ($this->onlineTimestamp-$acc->timeOffset) - $GLOBALS['eveTime'];
        }

        function setupFuel($fuelTypeID, $currentQty) {
            for ($i = 0; $i < count($this->fuelRequired); $i++) {
                if ($this->fuelRequired[$i]['resource']->typeid == $fuelTypeID) {
                    $this->fuelRequired[$i]['current'] = array(
                            'quantity' => $currentQty,
                            'value' => 0,
                            'volume' => $currentQty * $this->fuelRequired[$i]['resource']->volume,
                            'remaining' => ($currentQty / $this->fuelRequired[$i]['quantity']) * 60 * 60,
                        );
                    $this->fuelRequired[$i]['7days'] = array(
                            'quantity' => $this->fuelRequired[$i]['quantity'] * (24*7),
                            'value' => 0,
                            'volume' => $this->fuelRequired[$i]['quantity'] * (24*7) * $this->fuelRequired[$i]['resource']->volume,
                        );
                    $this->fuelRequired[$i]['30days'] = array(
                            'quantity' => $this->fuelRequired[$i]['quantity'] * (24*30),
                            'value' => 0,
                            'volume' => $this->fuelRequired[$i]['quantity'] * (24*30) * $this->fuelRequired[$i]['resource']->volume,
                        );
                }
            }
        }

        function setupFuelPricing($regionID = 0) {
            for ($i = 0; $i < count($this->fuelRequired); $i++) {
                $this->fuelRequired[$i]['resource']->getPricing($regionID);
                $this->fuelRequired[$i]['current']['value'] = $this->fuelRequired[$i]['current']['quantity'] * $this->fuelRequired[$i]['resource']->pricing->avgSell;
                $this->fuelRequired[$i]['7days']['value'] = $this->fuelRequired[$i]['7days']['quantity'] * $this->fuelRequired[$i]['resource']->pricing->avgSell;
                $this->fuelRequired[$i]['30days']['value'] = $this->fuelRequired[$i]['30days']['quantity'] * $this->fuelRequired[$i]['resource']->pricing->avgSell;
            }
        }
    }

?>