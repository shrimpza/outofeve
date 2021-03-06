<?php

class standings extends Plugin {

    var $name = 'Standings';
    var $level = 1;

    function standings($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
            && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_Standings)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Standings', '?module=standings', 'standings');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
            && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_Standings)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Standings', '?module=standings&corp=1', 'standings');
        }
    }

    function getContent() {
        if (isset($_GET['corp']) && eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
            $key = eveKeyManager::getKey($this->site->user->corp_apikey_id);
        } else {
            $key = eveKeyManager::getKey($this->site->user->char_apikey_id);
        }

        $standingList = new eveStandingsList($key);
        $standingList->load();

        for ($i = 0; $i < count($standingList->factions); $i++) {
            $standingList->factions[$i]->faction = eveDB::getInstance()->eveFaction($standingList->factions[$i]->fromID);
        }

        for ($i = 0; $i < count($standingList->agents); $i++) {
            $standingList->agents[$i]->agent = eveDB::getInstance()->eveAgent($standingList->agents[$i]->fromID);
        }

        for ($i = 0; $i < count($standingList->npcCorps); $i++) {
            $standingList->npcCorps[$i]->corp = eveDB::getInstance()->eveNpcCorp($standingList->npcCorps[$i]->fromID);
        }

        $standings = objectToArray($standingList);

        return $this->render('standings', array(
            'standings' => $standings,
        ));
    }

}

?>
