<?php

function jobSort($a, $b) {
    return ($a->installTime > $b->installTime) ? -1 : 1;
}

class eveIndustryJobList {

    var $industryJobs = array();
    var $key;

    function eveIndustryJobList($key) {
        $this->key = $key;
    }

    function load() {
        if (count($this->industryJobs) == 0) {

            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_IndustryJobs)) {
                $data = new apiRequest('corp/IndustryJobs.xml.aspx', $this->key, $this->key->getCharacter());
            } else if ($this->key->hasAccess(CHAR_IndustryJobs)) {
                $data = new apiRequest('char/IndustryJobs.xml.aspx', $this->key, $this->key->getCharacter());
            }

            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->rowset->row as $job) {
                    $this->industryJobs[] = new eveIndustryJob($job);
                }
            }
            usort($this->industryJobs, 'jobSort');
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

    function eveIndustryJob($job) {
        $this->outputTypeID = (int) $job['outputTypeID'];
        $this->inputTypeID = (int) $job['installedItemTypeID'];
        $this->jobID = (int) $job['jobID'];
        $this->inputLocationID = (int) $job['installedItemLocationID'];
        $this->outputLocationID = (int) $job['outputLocationID'];
        $this->installerID = (int) $job['installerID'];
        $this->runs = (int) $job['runs'];
        $this->solarSystemID = (int) $job['installedInSolarSystemID'];
        $this->materialMultiplier = (int) $job['charMaterialMultiplier'];
        $this->completed = (int) $job['completed'];
        $this->completedStatusID = (int) $job['completedStatus'];
        $this->completedStatus = eveDB::getInstance()->industryCompleteText($this->completedStatusID);
        $this->activityID = (int) $job['activityID'];
        $this->activity = eveDB::getInstance()->eveIndustryActivity($this->activityID);
        $this->installTime = eveTimeOffset::getOffsetTime($job['installTime']);
        $this->beginTime = eveTimeOffset::getOffsetTime($job['beginProductionTime']);
        $this->endTime = eveTimeOffset::getOffsetTime($job['endProductionTime']);
        $this->pauseTime = eveTimeOffset::getOffsetTime($job['pauseProductionTime']);

        $this->inItem = eveDB::getInstance()->eveItem($this->inputTypeID);
        $this->outItem = eveDB::getInstance()->eveItem($this->outputTypeID);
        $this->inLocation = eveDB::getInstance()->eveStation($this->inputLocationID);
        $this->outLocation = eveDB::getInstance()->eveStation($this->outputLocationID);

        if (($this->completed == 0) && ($this->completedStatusID == 0) && ($this->endTime - eveTimeOffset::$offset < eveTimeOffset::$eveTime)) {
            $this->completedStatus = 'Ready';
        } else if (($this->completed == 0) && ($this->completedStatusID == 0)) {
            $this->completedStatus = 'In Progress';
        }

        $this->remainingTime = ($this->endTime - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;
        if ($this->remainingTime < 0) {
            $this->percentComplete = 100;
        } else {
            $this->percentComplete = 100 - ($this->remainingTime / (($this->endTime - eveTimeOffset::$offset) - ($this->beginTime - eveTimeOffset::$offset)) * 100);
        }

        if ($this->activityID == 1) {
            $this->outQty = $this->outItem->portionsize * $this->runs;
        }
    }

}

?>