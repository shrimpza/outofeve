<?php

class eveCorporationStandingsList {

    var $agents = array();
    var $npcCorps = array();
    var $factions = array();
    var $key;

    function eveCorporationStandingsList($key) {
        $this->key = $key;
    }

    function load() {
        if ($this->key->hasAccess(CORP_Standings)) {
            $data = new apiRequest('corp/Standings.xml.aspx', $this->key, $this->key->getCharacter());

            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->corporationNPCStandings->rowset as $standingGroup) {
                    foreach ($standingGroup->row as $standing) {
                        $newStanding = new eveCorporationStanding($standing);
                        if ($standingGroup['name'] == 'agents') {
                            $this->agents[] = $newStanding;
                        } else if ($standingGroup['name'] == 'NPCCorporations') {
                            $this->npcCorps[] = $newStanding;
                        } else if ($standingGroup['name'] == 'factions') {
                            $this->factions[] = $newStanding;
                        }
                    }
                }
            }
        }
    }

}

class eveCorporationStanding {

    var $fromID = 0;
    var $fromName = '';
    var $standing = 0;

    function eveCorporationStanding($standing) {
        $this->fromID = (int) $standing['fromID'];
        $this->fromName = (string) $standing['fromName'];
        $this->standing = (double) $standing['standing'];
    }

}

?>
