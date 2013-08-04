<?php

function standingSort($a, $b) {
    return ($a->standing > $b->standing) ? -1 : 1;
}

class eveStandingsList {

    var $agents = array();
    var $npcCorps = array();
    var $factions = array();
    var $key;

    function eveStandingsList($key) {
        $this->key = $key;
    }

    function load() {
        if (count($this->factions) == 0) {
            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_Standings)) {
                $resultKey = 'corporationNPCStandings';
                $data = new apiRequest('corp/Standings.xml.aspx', $this->key, $this->key->getCharacter());
            } else if ($this->key->hasAccess(CHAR_Standings)) {
                $resultKey = 'characterNPCStandings';
                $data = new apiRequest('char/Standings.xml.aspx', $this->key, $this->key->getCharacter());
            }

            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->$resultKey->rowset as $standingGroup) {
                    foreach ($standingGroup->row as $standing) {
                        $newStanding = new eveStanding($standing);
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
            
            usort($this->agents, 'standingSort');
            usort($this->npcCorps, 'standingSort');
            usort($this->factions, 'standingSort');
        }
    }

}

class eveStanding {

    var $fromID = 0;
    var $fromName = '';
    var $standing = 0;

    function eveStanding($standing) {
        $this->fromID = (int) $standing['fromID'];
        $this->fromName = (string) $standing['fromName'];
        $this->standing = (double) $standing['standing'];
    }

}

?>
