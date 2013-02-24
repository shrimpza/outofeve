<?php

class eveSkillList {

    var $skills = array();
    var $skillPoints = 0;

    function eveSkillList() {
        
    }

    function load($skills) {
        foreach ($skills->row as $skill) {
            $newSkill = new eveKnownSkill($skill);
            $this->skills[] = $newSkill;
            $this->skillPoints += $newSkill->skillPoints;
        }
    }

    function getSkill($skillID) {
        $res = false;
        for ($i = 0; $i < count($this->skills); $i++) {
            if ($this->skills[$i]->typeID == $skillID) {
                $res = $this->skills[$i];
                break;
            }
        }
        return $res;
    }

}

class eveSkillQueue {

    var $queue = array();
    var $key;

    function eveSkillQueue($key) {
        $this->key = $key;
    }

    function load() {
        if (count($this->queue) == 0) {
            if ($this->key->hasAccess(CHAR_SkillQueue)) {
                $data = new apiRequest('char/SkillQueue.xml.aspx', $this->key, $this->key->getCharacter());
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $skill) {
                        $this->queue[] = new eveQueuedSkill($skill);
                    }
                }
            }
        }
    }

}

class eveQueuedSkill {

    var $typeID = 0;
    var $toLevel = 0;
    var $position = 0;
    var $startTime = 0;
    var $endTime = 0;
    var $remainingTime = 0;
    var $skillItem = null;

    function eveQueuedSkill($qskill) {
        $this->typeID = (int) $qskill['typeID'];
        $this->toLevel = (int) $qskill['level'];
        $this->position = (int) $qskill['queuePosition'];
        $this->startTime = (string) $qskill['startTime'];
        $this->endTime = eveTimeOffset::getOffsetTime($qskill['endTime']);

        $this->remainingTime = ($this->endTime - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;

        $this->skillItem = eveDB::getInstance()->eveItem($this->typeID);
    }

}

class eveTrainingSkill {

    var $typeID = 0;
    var $toLevel = 0;
    var $inTraining = false;
    var $startTime = 0;
    var $endTime = 0;
    var $remainingTime = 0;
    var $skillItem = null;

    function eveTrainingSkill($skill) {
        $this->typeID = (int) $skill->trainingTypeID;
        $this->toLevel = (int) $skill->trainingToLevel;
        $this->inTraining = (int) $skill->skillInTraining > 0;
        $this->startTime = eveTimeOffset::getOffsetTime($skill->trainingStartTime);
        $this->endTime = eveTimeOffset::getOffsetTime($skill->trainingEndTime);

        $this->remainingTime = ($this->endTime - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;

        $this->skillItem = eveDB::getInstance()->eveItem($this->typeID);
    }

}

class eveKnownSkill {

    var $name = '';
    var $typeID = 0;
    var $skillPoints = 0;
    var $level = 0;
    var $toLevel = 0;
    var $inTraining = false;

    function eveKnownSkill($skill) {
        $this->typeID = (int) $skill['typeID'];
        $this->skillPoints = (int) $skill['skillpoints'];
        $this->level = (int) $skill['level'];

        $this->skillItem = eveDB::getInstance()->eveItem($this->typeID);
    }

    function getName() {
        $this->name = $this->skillItem->typename;
    }

}

class eveSkillTree {

    var $groups = array();

    function load() {
        if (count($this->groups) == 0) {
            $data = new apiRequest('eve/SkillTree.xml.aspx');
            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->rowset->row as $group) {
                    $this->groups[] = new eveSkillGroup($group);
                }
            }
        }
    }

}

class eveSkillGroup {

    var $groupID = 0;
    var $groupName = 0;
    var $skills = array();

    function eveSkillGroup($group) {
        $this->groupID = (int) $group['groupID'];
        $this->groupName = (string) $group['groupName'];

        foreach ($group->rowset->row as $skill) {
            $this->skills[] = new eveSkill($skill);
        }
    }

}

class eveSkill {

    var $typeName = '';
    var $groupID = 0;
    var $typeID = 0;
    var $description = '';

    function eveSkill($skill) {
        $this->typeID = (int) $skill['typeID'];
        $this->groupID = (int) $skill['groupID'];
        $this->typeName = (string) $skill['typeName'];
        $this->description = (string) $skill->description;

        $this->skillItem = eveDB::getInstance()->eveItem($this->typeID);
    }

}

?>