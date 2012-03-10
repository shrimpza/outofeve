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
                $this->site->character->detail->load();
                $details = $this->site->character->detail;
                
                $char = array();
                $char['name'] = $details->name;
                $char['characterID'] = $details->characterID;
                $char['race'] = $details->race;
                $char['bloodLine'] = $details->bloodLine;
                $char['gender'] = $details->gender;
                $char['corporationName'] = $details->corporationName;
                $char['balance'] = $details->balance;
                $char['skillPoints'] = $details->skills->skillPoints;
                $char['training'] = objectToArray($details->trainingSkill, array('DBManager', 'eveDB'));
                $char['faction'] = objectToArray($details->faction, array('DBManager', 'eveDB'));
                $char['attributes'] = objectToArray($details->attributes->attributes, array('DBManager', 'eveDB'));
                $char['raceInfo'] = $details->db->bloodlineInfo($details->bloodLine);

                //$details->loadSkillQueue();
                $details->loadSkillTree();
                $details->loadCertificateTree();
                
                if ($details->skillQueue != null) {
                    $queue = objectToArray($details->skillQueue->queue, array('DBManager', 'eveDB'));
                } else {
                    $queue = false;
                }

                $skills = $details->knownSkills();
                $certificates = $details->knownCertificates();

                return $this->render('character', array('character' => $char, 'skills' => $skills, 'certificates' => $certificates, 'queue' => $queue));
            } else
                return '<h1>No character!</h1>';
        }
    }

?>
