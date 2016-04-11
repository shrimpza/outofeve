<?php

class character extends Plugin {

    var $name = 'Character';
    var $level = 1;

    function character($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id) &&
                eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_CharacterInfo_FULL)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Character', '?module=character', 'char');
        }
    }

    function getContent() {
        if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
            $character = new eveCharacterDetail(eveKeyManager::getKey($this->site->user->char_apikey_id));
            $character->load();

            $char = array();
            $char['name'] = $character->name;
            $char['characterID'] = $character->characterID;
            $char['race'] = $character->race;
            $char['bloodLine'] = $character->bloodLine;
            $char['gender'] = $character->gender;
            $char['corporationName'] = $character->corporationName;
            $char['balance'] = $character->balance;
            $char['skillPoints'] = $character->skills->skillPoints;
            $char['training'] = objectToArray($character->trainingSkill, array('DBManager', 'eveDB'));
            $char['faction'] = objectToArray($character->faction, array('DBManager', 'eveDB'));
            $char['attributes'] = objectToArray($character->attributes->attributes, array('DBManager', 'eveDB'));
            $char['raceInfo'] = objectToArray(eveDB::getInstance()->bloodlineInfo($character->bloodLine), array('DBManager', 'eveDB'));

            $character->loadSkillTree();

            if ($character->skillQueue != null) {
                $queue = objectToArray($character->skillQueue->queue, array('DBManager', 'eveDB'));
            } else {
                $queue = false;
            }

            $skills = objectToArray($character->knownSkills(), array('DBManager', 'eveDB'));

            return $this->render('character', array('character' => $char, 'skills' => $skills, 'queue' => $queue));
        } else {
            return '<h1>No character!</h1>';
        }
    }

}

?>