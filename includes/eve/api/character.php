<?php

function getCharacterName($id) {
    $charData = new apiRequest('eve/CharacterName.xml.aspx', array(), array('ids' => $id));
    if (!$charData->data) {
        return 'Lookup Error';
    }

    if ($charData->data->error) {
        return 'Lookup Error';
    } else {
        return (string) $charData->data->result->rowset->row['name'];
    }
}

class eveCharacterDetail {

    var $key = null;
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

    function eveCharacterDetail($key) {
        $this->key = $key;
    }

    function load() {
        if ($this->key->hasAccess(CHAR_CharacterInfo_FULL) || $this->key->hasAccess(CHAR_CharacterInfo)) {

            $data = new apiRequest('char/CharacterSheet.xml.aspx', $this->key, $this->key->getCharacter());

            if ((!$data->error) && ($data->data)) {
                $result = $data->data->result;
                $this->characterID = (int) $result->characterID;
                $this->name = (string) $result->name;
                $this->dob = eveTimeOffset::getOffsetTime($result->DoB);
                $this->race = (string) $result->race;
                $this->bloodLine = (string) $result->bloodLine;
                $this->ancestry = (string) $result->ancestry;
                $this->gender = (string) $result->gender;
                $this->corporationName = (string) $result->corporationName;
                $this->corporationID = (int) $result->corporationID;
                $this->allianceName = (string) $result->allianceName;
                $this->allianceID = (int) $result->allianceID;
                $this->cloneName = (string) $result->cloneName;
                $this->cloneSkillPoints = (int) $result->cloneSkillPoints;
                $this->balance = (float) $result->balance;

                foreach ($result->rowset as $rowset) {
                    if ($rowset['name'] == 'skills') {
                        $this->skills = new eveSkillList();
                        $this->skills->load($rowset);
                    } else if ($rowset['name'] == 'certificates') {
                        $this->certificates = new eveCertificateList();
                        $this->certificates->load($rowset);
                    }
                }

                $this->attributes = new eveAttributeList();
                $this->attributes->load($this, $result->attributes, $result->attributeEnhancers);
            }
        }

        $this->skillQueue = new eveSkillQueue($this->key);
        $this->skillQueue->load();

        if ($this->key->hasAccess(CHAR_SkillInTraining)) {
            $trainingData = new apiRequest('char/SkillInTraining.xml.aspx', $this->key, $this->key->getCharacter());
            if ($trainingData->data) {
                $this->trainingSkill = new eveTrainingSkill($trainingData->data->result);
            }
        }


        $training = $this->getSkill($this->trainingSkill->typeID);
        if ($training && ($training != $this->trainingSkill)) {
            $training->inTraining = $this->trainingSkill->inTraining;
            $training->toLevel = $this->trainingSkill->toLevel;
        }
    }

    function loadFaction() {
        if ($this->key->hasAccess(CHAR_FacWarStats)) {
            $data = new apiRequest('char/FacWarStats.xml.aspx', $this->key, $this->key->getCharacter());
            if ((!$data->error) && ($data->data)) {
                $this->faction = new eveCharacterFaction($data->result);
            }
        }
    }

    function loadSkillTree() {
        $this->skillTree = new eveSkillTree();
        $this->skillTree->load();
    }

    function loadCertificateTree() {
        $this->certificateTree = new eveCertificateTree();
        $this->certificateTree->load();
    }

    function getSkill($typeID) {
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

                    $result[$this->skillTree->groups[$i]->groupID]['skillpoints'] += (int) $knownSkill->skillPoints;

                    $result[$this->skillTree->groups[$i]->groupID]['skills'][] = array(
                        'typeID' => $this->skillTree->groups[$i]->skills[$j]->typeID,
                        'name' => $this->skillTree->groups[$i]->skills[$j]->typeName,
                        'description' => $this->skillTree->groups[$i]->skills[$j]->description,
                        'level' => (int) $knownSkill->level,
                        'skillpoints' => (int) $knownSkill->skillPoints,
                        'training' => (int) $knownSkill->inTraining,
                        'toLevel' => (int) $knownSkill->toLevel,
                        'skillItem' => $knownSkill->skillItem);
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
            $this->attributes[] = new eveAttribute($var, (float) $val, $implants->$implantName, $character);
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
            $this->implant = (string) $implant->augmentatorName;
            $this->bonus = (float) $implant->augmentatorValue;
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

}

class eveCharacterFaction {

    var $factionID = 0;
    var $factionName = '';
    var $enlisted = 0;
    var $currentRank = 0;
    var $highestRank = 0;
    var $killsYesterday = 0;
    var $killsLastWeek = 0;
    var $killsTotal = 0;
    var $victoryPointsYesterday = 0;
    var $victoryPointsLastWeek = 0;
    var $victoryPointsTotal = 0;

    function eveCharacterFaction($faction) {
        $this->factionID = (int) $faction->trainingTypeID;
        $this->factionName = (string) $faction->factionName;
        $this->enlisted = eveTimeOffset::getOffsetTime($faction->enlisted);
        $this->currentRank = (int) $faction->currentRank;
        $this->highestRank = (int) $faction->highestRank;
        $this->killsYesterday = (int) $faction->killsYesterday;
        $this->killsLastWeek = (int) $faction->killsLastWeek;
        $this->killsTotal = (int) $faction->killsTotal;
        $this->victoryPointsYesterday = (int) $faction->victoryPointsYesterday;
        $this->victoryPointsLastWeek = (int) $faction->victoryPointsLastWeek;
        $this->victoryPointsTotal = (int) $faction->victoryPointsTotal;
    }

}

?>