<?php

    class eveSkillList {
        var $skills = array();
        var $db = null;
        var $skillPoints = 0;
        
        function eveSkillList($db) {
            $this->db = $db;
        }

        function load($skills, $account) {
            foreach ($skills->row as $skill) {
                $newSkill = new eveKnownSkill($account, $this->db, $skill);
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
        var $db = null;

        function eveSkillQueue($db) {
            $this->db = $db;
        }

        function load($account, $character) {
            if (count($this->queue) == 0) {
                $data = new apiRequest('char/SkillQueue.xml.aspx', array($account->userId,
                                                                         $account->apiKey,
                                                                         $character->characterID),
                                                                   array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $skill) {
                        $this->queue[] = new eveQueuedSkill($account, $this->db, $skill);
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

        function eveQueuedSkill($acc, $db, $qskill) {
            $this->typeID = (int)$qskill['typeID'];
            $this->toLevel = (int)$qskill['level'];
            $this->position = (int)$qskill['queuePosition'];
            $this->startTime = (string)$qskill['startTime'];
            $this->endTime = strtotime((string)$qskill['endTime']) + $acc->timeOffset;

            $this->remainingTime = ($this->endTime - $acc->timeOffset) - $GLOBALS['eveTime'];

            $this->skillItem = $db->eveItem($this->typeID);
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

        function eveTrainingSkill($acc, $db, $skill) {
            $this->typeID = (int)$skill->trainingTypeID;
            $this->toLevel = (int)$skill->trainingToLevel;
            $this->inTraining = (int)$skill->skillInTraining > 0;
            $this->startTime = strtotime((string)$skill->trainingStartTime) + $acc->timeOffset;
            $this->endTime = strtotime((string)$skill->trainingEndTime) + $acc->timeOffset;

            $this->remainingTime = ($this->endTime-$acc->timeOffset) - $GLOBALS['eveTime'];

            $this->skillItem = $db->eveItem($this->typeID);
        }
    }

    class eveKnownSkill {
        var $name = '';
        var $typeID = 0;
        var $skillPoints = 0;
        var $level = 0;
        var $toLevel = 0;
        var $inTraining = false;
        
        function eveKnownSkill($acc, $db, $skill) {
            $this->typeID = (int)$skill['typeID'];
            $this->skillPoints = (int)$skill['skillpoints'];
            $this->level = (int)$skill['level'];
        }

        function getName($db) {
            $this->name = $db->typeName($this->typeID);
        }
    }

    class eveSkillTree {
        var $groups = array();
        
//        function eveSkillTree() {
//            foreach ($tree->rowset->row as $group)
//                $this->groups[] = new eveSkillGroup($acc, $group);
//        }
        
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
            $this->groupID = (int)$group['groupID'];
            $this->groupName = (string)$group['groupName'];

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
            $this->typeID = (int)$skill['typeID'];
            $this->groupID = (int)$skill['groupID'];
            $this->typeName = (string)$skill['typeName'];
            $this->description = (string)$skill->description;
        }
    }
?>