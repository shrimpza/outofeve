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

            $data = null;

            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_IndustryJobs)) {
                $data = new apiRequest('corp/IndustryJobs.xml.aspx', $this->key, $this->key->getCharacter());
            } else if ($this->key->hasAccess(CHAR_IndustryJobs)) {
                $data = new apiRequest('char/IndustryJobs.xml.aspx', $this->key, $this->key->getCharacter());
            }

            if ($data != null && !$data->error && $data->data) {
                foreach ($data->data->result->rowset->row as $job) {
                    $this->industryJobs[] = new eveIndustryJob($job);
                }
            }
            usort($this->industryJobs, 'jobSort');
        }
    }

    function getJob($jobID) {
        foreach ($this->industryJobs as $k => $j) {
            if ($j->jobID == $jobID) {
                return $j;
            }
        }
        return null;
    }

}

class eveIndustryJob {

    var $jobID = 0;
    var $installerID = 0;
    var $installerName = '';
    var $facilityID = 0;
    var $solarSystemID = 0;
    var $solarSystemName = '';
    var $stationID = 0;
    var $activityID = 0;
    var $blueprintID = 0;
    var $blueprintTypeID = 0;
    var $blueprintTypeName = '';
    var $blueprintLocationID = 0;
    var $outputLocationID = 0;
    var $runs = 0;
    var $cost = 0.0;
    var $licensedRuns = 0;
    var $probability = 0.0;
    var $productTypeID = 0;
    var $productTypeName = '';
    var $status = 0;
    var $timeInSeconds = 0;
    var $startDate = 0;
    var $endDate = 0;
    var $pauseDate = 0;
    var $completedDate = 0;
    var $completedCharacterID = 0;
    var $successfulRuns = 0;

    var $outQty = 0;

    var $remainingTime = 0;
    var $percentComplete = 0;
    var $activity = null;
    var $inItem = null;
    var $outItem = null;
    var $inLocation = null;
    var $outLocation = null;

    function eveIndustryJob($job) {
        global $industryStatuses;

      /*
       * main attributes from the api
       */
        $this->jobID = (int) $job['jobID'];
        $this->installerID = (int) $job['installerID'];
        $this->installerName = (string) $job['installerName'];
        $this->facilityID = (int) $job['facilityID'];
        $this->solarSystemID = (int) $job['solarSystemID'];
        $this->solarSystemName = (string) $job['solarSystemName'];
        $this->stationID = (int) $job['stationID'];
        $this->activityID = (int) $job['activityID'];
        $this->blueprintID = (int) $job['blueprintID'];
        $this->blueprintTypeID = (int) $job['blueprintTypeID'];
        $this->blueprintTypeName = (string) $job['blueprintTypeName'];
        $this->blueprintLocationID = (int) $job['blueprintLocationID'];
        $this->outputLocationID = (int) $job['outputLocationID'];
        $this->runs = (int) $job['runs'];
        $this->cost = (double) $job['cost'];
        $this->licensedRuns = (int) $job['licensedRuns'];
        $this->probability = (double) $job['probability'];
        $this->productTypeID = (int) $job['productTypeID'];
        $this->productTypeName = (string) $job['productTypeName'];
        $this->status = (int) $job['status'];
        $this->timeInSeconds = (int) $job['timeInSeconds'];
        $this->startDate = eveTimeOffset::getOffsetTime($job['startDate']);
        $this->endDate = eveTimeOffset::getOffsetTime($job['endDate']);
        $this->pauseDate = eveTimeOffset::getOffsetTime($job['pauseDate']);
        $this->completedDate = eveTimeOffset::getOffsetTime($job['completedDate']);
        $this->completedCharacterID = eveTimeOffset::getOffsetTime($job['completedCharacterID']);
        $this->successfulRuns = (int) $job['successfulRuns'];

        // get proper status information
        $this->activity = eveDB::getInstance()->eveIndustryActivity($this->activityID);
        $this->statusName = $industryStatuses[$this->status];

        /*
         * Load up the various linked entities
         */
        $this->inItem = eveDB::getInstance()->eveItem($this->blueprintTypeID);
        $this->outItem = eveDB::getInstance()->eveItem($this->productTypeID);
        $this->inLocation = eveDB::getInstance()->eveStation($this->stationID);
        $this->outLocation = eveDB::getInstance()->eveStation($this->outputLocationID);

        // if (($this->completed == 0) && ($this->completedStatusID == 0) && ($this->endTime - eveTimeOffset::$offset < eveTimeOffset::$eveTime)) {
        //     $this->completedStatus = 'Ready';
        // } else if (($this->completed == 0) && ($this->completedStatusID == 0)) {
        //     $this->completedStatus = 'In Progress';
        // }

        /*
         * Convenience parameters
         */
        $this->remainingTime = ($this->endDate - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;
        if ($this->remainingTime < 0) {
            $this->percentComplete = 100;
            $this->statusName = 'Ready';
        } else {
            $this->percentComplete = 100 - ($this->remainingTime / (($this->endDate - eveTimeOffset::$offset) - ($this->startDate - eveTimeOffset::$offset)) * 100);
        }

        if ($this->activityID == 1) {
            $this->outQty = $this->outItem->portionsize * $this->runs;
        }
    }

}

?>