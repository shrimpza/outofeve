<?php

    class character extends Plugin {
        var $name = 'Character';
        var $level = 1;

        function character($db, $site) {
            $this->Plugin($db, $site);

            if (isset($this->site->character) && ($this->site->character->characterID > 0)) {
                $this->site->plugins['mainmenu']->addGroup('Character Data', 'main');

                if (isset($this->site->character->corpMember)) {
                    $this->site->plugins['mainmenu']->addGroup('Corporation Data', 'corp');
                }

                $this->site->plugins['mainmenu']->addGroup('Utilities', 'util');
            }

            $this->site->plugins['mainmenu']->addLink('main', 'Character', '?module=character', 'icon02_16');
        }

        function getContent() {
            if ($this->site->character) {
                $char = array();
                $char['name'] = $this->site->character->name;
                $char['characterID'] = $this->site->character->characterID;
                $char['race'] = $this->site->character->race;
                $char['bloodLine'] = $this->site->character->bloodLine;
                $char['gender'] = $this->site->character->gender;
                $char['corporationName'] = $this->site->character->corporationName;
                $char['balance'] = $this->site->character->balance;
                $char['skillPoints'] = $this->site->character->skillPoints;
                $char['training'] = objectToArray($this->site->character->trainingSkill, array('DBManager', 'eveDB'));
                $char['faction'] = objectToArray($this->site->character->faction, array('DBManager', 'eveDB'));
                $char['attributes'] = objectToArray($this->site->character->attributes, array('DBManager', 'eveDB'));
                $char['raceInfo'] = $this->site->character->db->bloodlineInfo($this->site->character->bloodLine);

                $this->site->character->loadSkillTree();
                $this->site->character->loadCertificateTree();
                $skills = $this->site->character->knownSkills();
                $certificates = $this->site->character->knownCertificates();

                return $this->render('character', array('character' => $char, 'skills' => $skills, 'certificates' => $certificates));
            } else
                return '<h1>No character!</h1>';
        }
    }

?>
