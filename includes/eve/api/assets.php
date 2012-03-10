<?php
    class eveAssetList {
        var $assets = array();
        
        function load($account, $character) {
            if (count($this->assets) == 0) {
                $data = new apiRequest('char/AssetList.xml.aspx', array($account->userId,
                                                                        $account->apiKey, 
                                                                        $character->characterID),
                                                                  array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $asset) {
                        $this->assets[] = new eveAsset($account, $asset);
                    }
                }
            }
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

        // internal use id. seems the api duplicates the same ID multiple items within a /single/ result set.
        var $_ooe_id = 0;

        function eveAsset($acc, $asset, $parentLocation = null) {
            $this->typeID = (int)$asset['typeID'];
            $this->itemID = (int)$asset['itemID'];
            $this->flag = (int)$asset['flag'];
            if (isset($asset['locationID'])) {
                $this->locationID = (int)$asset['locationID'];
                $this->location = eveDB::getInstance()->eveStation($this->locationID);
                $this->locationName = $this->location->stationname;
                if ($this->location->stationid == 0) {
                    $this->location = eveDB::getInstance()->eveSolarSystem($this->locationID);
                    $this->locationName = $this->location->solarsystemname;
                }
            } else if (isset($parentLocation)) {
                $this->location = $parentLocation;
                if (isset($parentLocation->stationid))
                    $this->locationID = $parentLocation->stationid;
                else
                    $this->locationID = $parentLocation->solarsystemid;
            }
            $this->item = eveDB::getInstance()->eveItem($this->typeID);
            $this->qty = (int)$asset['quantity'];

            $this->flagText = eveDB::getInstance()->flagText($this->flag);

            if (isset($asset->rowset) && ($asset->rowset['name'] == 'contents')) {
                $this->contents = array();
                foreach ($asset->rowset->row as $subAsset)
                    $this->contents[] = new eveAsset($acc, $subAsset, $this->location);
            }
            
            $this->_ooe_id = $this->itemID . '_' . $this->typeID . '_' . mt_rand();
        }
    }
    
?>