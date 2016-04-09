<?php

class eveBlueprintList {

    var $blueprints = array();
    var $key = null;

    function eveBlueprintList($key) {
        $this->key = $key;
    }

    function load($loadGroups = false) {
        if (count($this->blueprints) == 0) {
            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_AssetList)) {
                $data = new apiRequest('corp/Blueprints.xml.aspx', $this->key, $this->key->getCharacter());
            } else if ($this->key->hasAccess(CHAR_AssetList)) {
                $data = new apiRequest('char/Blueprints.xml.aspx', $this->key, $this->key->getCharacter());
            }

            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->rowset->row as $asset) {
                    $this->blueprints[] = new eveBlueprint($asset, $loadGroups);
                }
            }
        }
    }

}

class eveBlueprint {

    var $typeID = 0;
    var $itemID = 0;
    var $flag = 0;
    var $qty = 0;
    var $materialEfficiency = 0;
    var $timeEfficiency = 0;
    var $runs = 0;
    var $locationID = 0;
    var $locationName = '';
    var $location = null;
    var $item = null;
    var $itemName = '';
    var $flagText = '';

    function eveBlueprint($blueprint, $loadGroup = false) {
        $this->typeID = (int) $blueprint['typeID'];
        $this->itemID = (int) $blueprint['itemID'];
        $this->flag = (int) $blueprint['flag'];
        if (isset($blueprint['locationID'])) {
            $this->locationID = (int) $blueprint['locationID'];
            $this->location = eveDB::getInstance()->eveStation($this->locationID);
            $this->locationName = $this->location->stationname;
            if ($this->location->stationid == 0) {
                $this->location = eveDB::getInstance()->eveSolarSystem($this->locationID);
                $this->locationName = $this->location->solarsystemname;
            }
        }
        $this->item = eveDB::getInstance()->eveItem($this->typeID);
        $this->qty = (int) $blueprint['quantity'];
        $this->materialEfficiency = (int) $blueprint['materialEfficiency'];
        $this->timeEfficiency = (int) $blueprint['timeEfficiency'];
        $this->runs = (int) $blueprint['runs'];

        $this->flagText = eveDB::getInstance()->flagText($this->flag);

        if ($this->item && $loadGroup) {
            $this->item->getGroup();
        }
    }

}

?>