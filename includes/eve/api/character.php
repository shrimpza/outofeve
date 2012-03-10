<?php

    class eveCharacterDetail {
        var $character = null;
        
        var $characterID = 0;
        var $name = '';
        var $dob = 0;
        var $race = '';
        var $bloodLine = '';
        var $ancestry = '';
        var $gender = '';
        var $corporationName = '';
        var $corporationID = 0;
        var $allianceName = '';
        var $allianceID = 0;
        var $cloneName = '';
        var $cloneSkillPoints = 0;
        var $balance = 0;

        var $attributes = array();
        var $skills = array();
        var $trainingSkill = null;
        var $skillQueue = null;

        var $skillTree = null;

        var $certificates = array();

        var $certificateTree = null;

        var $outpostList = null;

        var $faction = null;

        var $db = null;

        function eveCharacterDetail($account, $character) {
            $this->db = $account->db;

            $this->account = $account;
            $this->character = $character;
            
            $this->characterID = $character->characterID;
            $this->name = $character->name;

            //$this->load($account, $characterID);
        }

        function load() {//)$account, $characterID) {
            $data = new apiRequest('char/CharacterSheet.xml.aspx', array($this->account->userId,
                                                                         $this->account->apiKey, 
                                                                         $this->characterID));

            if ((!$data->error) && ($data->data)) {
                $result = $data->data->result;
                $this->characterID = (int)$result->characterID;
                $this->name = (string)$result->name;
                $this->dob = strtotime((string)$result->DoB) + $account->timeOffset;
                $this->race = (string)$result->race;
                $this->bloodLine = (string)$result->bloodLine;
                $this->ancestry = (string)$result->ancestry;
                $this->gender = (string)$result->gender;
                $this->corporationName = (string)$result->corporationName;
                $this->corporationID = (int)$result->corporationID;
                $this->allianceName = (string)$result->allianceName;
                $this->allianceID = (int)$result->allianceID;
                $this->cloneName = (string)$result->cloneName;
                $this->cloneSkillPoints = (int)$result->cloneSkillPoints;
                $this->balance = (float)$result->balance;

                foreach ($result->rowset as $rowset) {
                    if ($rowset['name'] == 'skills') {
                        $this->skills = new eveSkillList($this->db);
                        $this->skills->load($rowset, $account);
                    } else if ($rowset['name'] == 'certificates') {
                        $this->certificates = new eveCertificateList($this->db);
                        $this->certificates->load($rowset, $account);
                    }
                }
                
                $this->attributes = new eveAttributeList();
                $this->attributes->load($this, $result->attributes, $result->attributeEnhancers);
            } else if ($data->error) {
                apiError('char/CharacterSheet.xml.aspx', $data->data->error);
            }
            
            $this->skillQueue = new eveSkillQueue($this->db);
            $this->skillQueue->load($this->account, $this->character);

            $trainingData = new apiRequest('char/SkillInTraining.xml.aspx', array($this->account->userId,
                                                                                  $this->account->apiKey, 
                                                                                  $this->characterID));
            if ($trainingData->data) {
                $this->trainingSkill = new eveTrainingSkill($this->account, $this->db, $trainingData->data->result);
            }


            $training = $this->getSkill($this->trainingSkill->typeID);
            if ($training && ($training != $this->trainingSkill)) {
                $training->inTraining = $this->trainingSkill->inTraining;
                $training->toLevel = $this->trainingSkill->toLevel;
            }

        }
        
        function loadFaction() {
            $data = new apiRequest('char/FacWarStats.xml.aspx', array($this->account->userId,
                                                                      $this->account->apiKey, 
                                                                      $this->characterID), null, false);
            if ((!$data->error) && ($data->data)) {
                    $this->faction = new eveCharacterFaction($this->account, $this->db, $factionData->data->result);
            }
        }

        function loadSkillTree() {
            $this->skillTree = new eveSkillTree();
            $this->skillTree->load();
        }

//        function loadSkillQueue() {
//            if ($this->skillQueue == null) {
//                $skillQueueData = new apiRequest('char/SkillQueue.xml.aspx', array($this->account->userId,
//                                                                             $this->account->apiKey,
//                                                                             $this->characterID),
//                                                                             array('version' => 2));
//              if ($skillQueueData->data) {
//                    if (!$skillQueueData->data->error) {
//                        $this->skillQueue = new eveSkillQueue($this->account, $this->db, $skillQueueData->data->result);
//                    } else {
//                        apiError('char/SkillQueue.xml.aspx', $skillQueueData->data->error);
//                    }
//                }
//            }
//        }
        
        function loadCertificateTree() {
            $this->certificateTree = new eveCertificateTree($this->db);
            $this->certificateTree->load();
        }

        function getSkill($typeID) {
            //$result = isset($this->skills[$typeID]) ? $this->skills[$typeID] : false;
            //if (!$result)
            ////    if ($this->trainingSkill->typeID == $typeID)
            //        $result = $this->trainingSkill;
            $result = $this->skills->getSkill($typeID);
            return $result;
        }

        function knownSkills() {
            $result = array();
            for ($i = 0; $i < count($this->skillTree->groups); $i++) {
                for ($j = 0; $j < count($this->skillTree->groups[$i]->skills); $j++) {
                    $knownSkill = $this->getSkill($this->skillTree->groups[$i]->skills[$j]->typeID);
                    if (($knownSkill) || ($this->trainingSkill->typeID == $this->skillTree->groups[$i]->skills[$j]->typeID)) {
                        if (!isset($result[$this->skillTree->groups[$i]->groupID])) {
                            $theSkill = array();
                            $theSkill['name'] = $this->skillTree->groups[$i]->groupName;
                            $theSkill['skillpoints'] = 0;
                            $theSkill['skills'] = array();
                            $result[$this->skillTree->groups[$i]->groupID] = $theSkill;
                        }

                        $result[$this->skillTree->groups[$i]->groupID]['skillpoints'] += (int)$knownSkill->skillPoints;

                        $result[$this->skillTree->groups[$i]->groupID]['skills'][] = array(
                            'typeID' => $this->skillTree->groups[$i]->skills[$j]->typeID,
                            'name' => $this->skillTree->groups[$i]->skills[$j]->typeName,
                            'description' => $this->skillTree->groups[$i]->skills[$j]->description,
                            'level' => (int)$knownSkill->level,
                            'skillpoints' => (int)$knownSkill->skillPoints,
                            'training' => (int)$knownSkill->inTraining,
                            'toLevel' => (int)$knownSkill->toLevel);
                    }
                }
            }

            return $result;
        }

        function knownCertificates() {
            $result = array();

            foreach ($this->certificates->certificates as $knownCert) {
                $cert = $this->certificateTree->getCertificate($knownCert->certificateID);
                $catId = $cert->cclass->category->categoryID;
                $clsId = $cert->cclass->classID;
                if (!isset($result[$catId])) {
                    $result[$catId] = array();
                    $result[$catId]['name'] = $cert->cclass->category->categoryName;
                    $result[$catId]['classes'] = array();
                }

                if (!isset($result[$catId]['classes'][$clsId])) {
                    $result[$catId]['classes'][$clsId] = array();
                    $result[$catId]['classes'][$clsId]['name'] = $cert->cclass->className;
                    $result[$catId]['classes'][$clsId]['grade'] = $cert->grade;
                    $result[$catId]['classes'][$clsId]['icon'] = $cert->icon;
                }

                if ($cert->grade > $result[$catId]['classes'][$clsId]['grade']) {
                    $result[$catId]['classes'][$clsId]['grade'] = $cert->grade;
                    $result[$catId]['classes'][$clsId]['icon'] = $cert->icon;
                }
            }

            return $result;
        }
    }
    
    class eveAttributeList {
        var $attributes = array();
        
        function load($character, $attributes, $implants) {
            foreach (get_object_vars($attributes) as $var => $val) {
                $implantName = $var . 'Bonus';
                $this->attributes[] = new eveAttribute($var, (float)$val, $implants->$implantName, $character);
            }
        }
        
        function getAttribute($name) {
            $res = false;
            
            foreach ($this->attributes as $attr) {
                if ($attr->name == $name) {
                    $res = $attr;
                    break;
                }
            }
            
            return $res;
        }
    }
    
    class eveAttribute {
        var $name = '';
        var $value = 0;
        var $implant = '';
        var $bonus = 0;

        function eveAttribute($name, $value, $implant, $character) {
            global $attributeMods;

            $this->name = $name;
            $this->value = $value;
            if (isset($implant)) {
                $this->implant = (string)$implant->augmentatorName;
                $this->bonus = (float)$implant->augmentatorValue;
                $this->value += $this->bonus;
            }

            // apply bonuses from trained learning skills
            if ($attributeMods[$this->name]) {
                foreach ($attributeMods[$this->name] as $mod) {
                    if ($character->skills->getSkill($mod['skill'])) {
                        $this->value += $character->skills->getSkill($mod['skill'])->level * $mod['bonus'];
                    }
                }
            }
        }
    }?>