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
    var $titles = array();
    var $starbases = array();

    function eveCorporation($key) {
        $this->key = $key;
    }

    function load() {
        if ($this->key->hasAccess(CORP_CorporationSheet)) {
            $data = new apiRequest('corp/CorporationSheet.xml.aspx', $this->key, $this->key->getCharacter());

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
    var $key;

    function eveCorporationWalletList($key) {
        $this->key = $key;
    }

    function load($walletDivisions) {
        if (count($this->wallets) == 0) {
            if ($this->key->hasAccess(CORP_AccountBalance)) {
                $data = new apiRequest('corp/AccountBalance.xml.aspx', $this->key, $this->key->getCharacter());

                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $wallet) {
                        $this->wallets[] = new eveCorporationWallet($wallet, $walletDivisions);
                    }
                }
            }
        }
    }

}

class eveCorporationMemberList {

    var $members = array();
    var $key;

    function eveCorporationMemberList($key) {
        $this->key = $key;
    }

    function load($full = true, $extended = true) {
        if (count($this->members) == 0) {
            if ($this->key->hasAccess(CORP_MemberTrackingExtended)) {
                $data = new apiRequest('corp/MemberTracking.xml.aspx', $this->key, $this->key->getCharacter(), array('extended' => $extended ? 1 : 0));

                if ($data->data && !$data->data->error) {
                    foreach ($data->data->result->rowset->row as $member) {
                        $this->members[] = new eveCorporationMember($member);
                    }
                }
            }
        }

        for ($i = 0; $i < count($this->members); $i++) {
            if ($full) {
                $this->members[$i]->loadDetail();
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
    var $shipType = '';
    var $roles = 0;
    var $grantableRoles = 0;
    var $locationName = '';
    var $roleList = array();
    var $base = null;
    var $location = null;
    var $ship = null;

    function eveCorporationMember($member) {
        $this->characterID = (int) $member['characterID'];
        $this->name = (string) $member['name'];
        $this->startDateTime = eveTimeOffset::getOffsetTime($member['startDateTime']);
        $this->baseID = (int) $member['baseID'];
        $this->title = (string) $member['title'];
        $this->logonDateTime = eveTimeOffset::getOffsetTime($member['logonDateTime']);
        $this->logoffDateTime = eveTimeOffset::getOffsetTime($member['logoffDateTime']);
        $this->locationID = (int) $member['locationID'];
        $this->shipTypeID = (int) $member['shipTypeID'];
        $this->shipType = (string) $member['shipType'];
        $this->roles = (int) $member['roles'];
        $this->grantableRoles = (int) $member['grantableRoles'];

        $this->setRoles();
    }

    function setRoles() {
        global $corpRoles;

        foreach ($corpRoles as $role => $mask) {
            if ($this->roles & $mask) {
                $this->roleList[$corpRoles[$i]['rolename']] = $role;
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