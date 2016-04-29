<?php

class eveStarbaseList {

    var $starbases = array();
    var $key;

    function eveStarbaseList($key) {
        $this->key = $key;
    }

    function load($loadDetail = true) {

        if ($this->key->hasAccess(CORP_StarbaseList)) {
            $data = new apiRequest('corp/StarbaseList.xml.aspx', $this->key, $this->key->getCharacter());

            if ($data->data && !$data->data->error) {
                foreach ($data->data->result->rowset->row as $starbase) {
                    $sb = new eveStarbase($this->key, $starbase);
                    if ($this->key->hasAccess(CORP_StarbaseDetail) && $loadDetail) {
                        $sb->loadDetail();
                    }
                    $this->starbases[] = $sb;
                }
            }
        }
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
    var $key;

    function eveStarbase($key, $starbase) {
        $this->key = $key;

        $this->itemID = (int) $starbase['itemID'];
        $this->typeID = (int) $starbase['typeID'];
        $this->locationID = (int) $starbase['locationID'];
        $this->moonID = (int) $starbase['moonID'];
        $this->state = (int) $starbase['state'];
        $this->stateTimestamp = eveTimeOffset::getOffsetTime($starbase['stateTimestamp']);
        $this->onlineTimestamp = eveTimeOffset::getOffsetTime($starbase['onlineTimestamp']);

        $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->locationID);
        $this->moon = eveDB::getInstance()->eveCelestial($this->moonID);
        $this->tower = eveDB::getInstance()->eveItem($this->typeID);
    }

    function loadDetail() {
        $this->loadFuelRequired();

        if ($this->key->hasAccess(CORP_StarbaseDetail)) {
            $data = new apiRequest('corp/StarbaseDetail.xml.aspx', $this->key, $this->key->getCharacter(), array('itemID' => $this->itemID));

            if ($data->data && !$data->data->error) {
                $this->state = (int) $data->data->result->state;
                $this->stateTimestamp = eveTimeOffset::getOffsetTime($data->data->result->stateTimestamp);
                $this->onlineTimestamp = eveTimeOffset::getOffsetTime($data->data->result->onlineTimestamp);

                $this->generalSettings['usageFlags'] = (int) $data->data->result->generalSettings->usageFlags;
                $this->generalSettings['deployFlags'] = (int) $data->data->result->generalSettings->deployFlags;
                $this->generalSettings['allowCorporationMembers'] = (int) $data->data->result->generalSettings->allowCorporationMembers;
                $this->generalSettings['allowAllianceMembers'] = (int) $data->data->result->generalSettings->allowAllianceMembers;
                $this->generalSettings['claimSovereignty'] = (int) $data->data->result->generalSettings->claimSovereignty;

                $this->combatSettings['onStandingDrop']['enabled'] = (int) $data->data->result->combatSettings->onStandingDrop['enabled'];
                $this->combatSettings['onStandingDrop']['standing'] = (float) $data->data->result->combatSettings->onStandingDrop['standing'];
                $this->combatSettings['onStatusDrop']['enabled'] = (int) $data->data->result->combatSettings->onStatusDrop['enabled'];
                $this->combatSettings['onStatusDrop']['standing'] = (float) $data->data->result->combatSettings->onStatusDrop['standing'];
                $this->combatSettings['onAggression'] = (int) $data->data->result->combatSettings->onAggression['enabled'];
                $this->combatSettings['onCorporationWar'] = (int) $data->data->result->combatSettings->onCorporationWar['enabled'];

                foreach ($data->data->result->rowset as $rowset) {
                    if ($rowset['name'] == 'fuel') {
                        foreach ($rowset->row as $fuel) {
                            $this->setupFuel((int) $fuel['typeID'], (int) $fuel['quantity']);
                        }
                    }
                }
            }

            if (in_array($this->state, array(3))) {
                $this->remainingTime = ($this->stateTimestamp - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;
            } else if (in_array($this->state, array(2, 4))) {
                $this->remainingTime = ($this->onlineTimestamp - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;
            }
        }
    }

    function loadFuelRequired() {
        $tmpFuel = eveDB::getInstance()->eveFuelRequirements($this->typeID);
        for ($i = 0; $i < count($tmpFuel); $i++) {
            if (((int) $tmpFuel[$i]['factionid'] == 0)
                || (((int) $tmpFuel[$i]['factionid'] > 0)
                    && ((int) $tmpFuel[$i]['factionid'] == (int) $this->solarSystem->factionid))) {
                $this->fuelRequired[] = $tmpFuel[$i];
            }
            $this->setupFuel($this->fuelRequired[count($this->fuelRequired) - 1]['resource']->typeid, 0);
        }
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
                    'quantity' => $this->fuelRequired[$i]['quantity'] * (24 * 7),
                    'value' => 0,
                    'volume' => $this->fuelRequired[$i]['quantity'] * (24 * 7) * $this->fuelRequired[$i]['resource']->volume,
                );
                $this->fuelRequired[$i]['30days'] = array(
                    'quantity' => $this->fuelRequired[$i]['quantity'] * (24 * 30),
                    'value' => 0,
                    'volume' => $this->fuelRequired[$i]['quantity'] * (24 * 30) * $this->fuelRequired[$i]['resource']->volume,
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
