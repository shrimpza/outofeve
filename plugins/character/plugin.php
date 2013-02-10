<?php

class character extends Plugin {

    var $name = 'Character';
    var $level = 1;

    function character($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_CharacterInfo_FULL)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Character', '?module=character', 'icon02_16');
        }

        /* if (isset($this->site->character) && ($this->site->character->characterID > 0)) {
          $this->site->plugins['mainmenu']->addGroup('Character Data', 'main');

          $corporation = new eveCorporation($this->site->eveAccount, $this->site->character);
          $corporation->load();

          if ($corporation->corporationID > 0) {
          $this->site->plugins['mainmenu']->addGroup('Corporation Data', 'corp');
          }

          $this->site->plugins['mainmenu']->addGroup('Utilities', 'util');
          }

          $this->site->plugins['mainmenu']->addLink('main', 'Character', '?module=character', 'icon02_16'); */
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
            $character->loadCertificateTree();

            if ($character->skillQueue != null) {
                $queue = objectToArray($character->skillQueue->queue, array('DBManager', 'eveDB'));
            } else {
                $queue = false;
            }

            $skills = objectToArray($character->knownSkills(), array('DBManager', 'eveDB'));
            $certificates = $character->knownCertificates();

            return $this->render('character', array('character' => $char, 'skills' => $skills, 'certificates' => $certificates, 'queue' => $queue));
        } else {
            return '<h1>No character!</h1>';
        }
    }

}

?>