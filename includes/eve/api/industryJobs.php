<?php
    class eveIndustryJobList {
        var $industryJobs = array();

        function load($account, $character) {
            if (count($this->industryJobs) == 0) {
                $data = new apiRequest('char/IndustryJobs.xml.aspx', array($account->userId,
                                                                           $account->apiKey, 
                                                                           $character->characterID),
                                                                     array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $job) {
                        $this->industryJobs[] = new eveIndustryJob($this->account, $job);
                    }
                }
            }
        }
    }

    class eveIndustryJob {
        var $outputTypeID = 0;
        var $inputTypeID = 0;
        var $jobID = 0;
        var $inputLocationID = 0;
        var $outputLocationID = 0;
        var $installerID = 0;
        var $installerName = '';
        var $runs = 0;
        var $outQty = 0;
        var $solarSystemID = 0;
        var $materialMultiplier = 0;
        var $completed = 0;
        var $completedStatusID = 0;
        var $completedStatus = '';
        var $activityID = 0;
        var $installTime = 0;
        var $beginTime = 0;
        var $endTime = 0;
        var $pauseTime = 0;

        var $remainingTime = 0;
        var $percentComplete = 0;

        var $activity = null;
        var $inItem = null;
        var $outItem = null;
        var $inLocation = null;
        var $outLocation = null;

        function eveIndustryJob($acc, $job) {
            $this->outputTypeID = (int)$job['outputTypeID'];
            $this->inputTypeID = (int)$job['installedItemTypeID'];
            $this->jobID = (int)$job['jobID'];
            $this->inputLocationID = (int)$job['installedItemLocationID'];
            $this->outputLocationID = (int)$job['outputLocationID'];
            $this->installerID = (int)$job['installerID'];
            $this->runs = (int)$job['runs'];
            $this->solarSystemID = (int)$job['installedInSolarSystemID'];
            $this->materialMultiplier = (int)$job['charMaterialMultiplier'];
            $this->completed = (int)$job['completed'];
            $this->completedStatusID = (int)$job['completedStatus'];
            $this->completedStatus = eveDB::getInstance()->industryCompleteText($this->completedStatusID);
            $this->activityID = (int)$job['activityID'];
            $this->activity = eveDB::getInstance()->eveIndustryActivity($this->activityID);
            $this->installTime = strtotime((string)$job['installTime']) + $acc->timeOffset;
            $this->beginTime = strtotime((string)$job['beginProductionTime']) + $acc->timeOffset;
            $this->endTime = strtotime((string)$job['endProductionTime']) + $acc->timeOffset;
            $this->pauseTime = strtotime((string)$job['pauseProductionTime']) + $acc->timeOffset;

            $this->inItem = eveDB::getInstance()->eveItem($this->inputTypeID);
            $this->outItem = eveDB::getInstance()->eveItem($this->outputTypeID);
            $this->inLocation = eveDB::getInstance()->eveStation($this->inputLocationID);
            $this->outLocation = eveDB::getInstance()->eveStation($this->outputLocationID);

            if (($this->completed == 0) && ($this->completedStatusID == 0) && ($this->endTime-$acc->timeOffset < $GLOBALS['eveTime'])) {
                $this->completedStatus = 'Ready';
            } else if (($this->completed == 0) && ($this->completedStatusID == 0)) {
                $this->completedStatus = 'In Progress';
            }

            $this->remainingTime = ($this->endTime-$acc->timeOffset) - $GLOBALS['eveTime'];
            if ($this->remainingTime < 0) {
                $this->percentComplete = 100;
            } else {
                $this->percentComplete = 100 - ($this->remainingTime / (($this->endTime-$acc->timeOffset) - ($this->beginTime-$acc->timeOffset)) * 100);
            }
                
            if ($this->activityID == 1) {
                $this->outQty = $this->outItem->portionsize * $this->runs;
            }
        }
    }
?>