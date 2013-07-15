<?php

class eveOutpostList {

    var $outposts = array();
    static $instance = null;

    function load() {
        if ($this->outpostList == null) {
            $outpostData = new apiRequest('eve/ConquerableStationList.xml.aspx');
            if ($outpostData->data && !$outpostData->data->error) {
                foreach ($outpostData->data->result->rowset->row as $outpost) {
                    $this->outposts[] = new eveOutpost($outpost);
                }
            }
        }
    }

    static function getOutpost($stationId) {
        if (eveOutpostList::$instance == null) {
            eveOutpostList::$instance = new eveOutpostList();
            eveOutpostList::$instance->load();
        }

        foreach (eveOutpostList::$instance->outposts as $outpost) {
            if ($outpost->stationid == $stationId) {
                $outpost->loadDetail();
                return $outpost;
            }
        }

        return false;
    }

}

/**
 * This outpost class contains exactly the same structure as a regular station
 * so they are interchangable with no changes required elsewhere.
 */
class eveOutpost {

    var $stationid = 0;
    var $solarsystemid = 0;
    var $regionid = 0;
    var $stationname = '';
    var $stationtypeid = 0;
    var $solarSystem = null;
    var $region = null;

    function eveOutpost($outpost) {
        $this->stationid = (int) $outpost['stationID'];
        $this->stationname = (string) $outpost['stationName'];
        $this->stationtypeid = (int) $outpost['stationTypeID'];
        $this->solarsystemid = (int) $outpost['solarSystemID'];
    }

    function loadDetail() {
        if ($this->solarsystem && $this->solarsystem == null) {
            $this->solarsystem = eveDB::getInstance()->eveSolarSystem($this->solarsystemid);
            $this->regionid = $this->solarsystem->regionid;
            $this->region = eveDB::getInstance()->eveRegion($this->regionid);

            $this->stationname = $this->solarsystem->solarsystemname . ' - ' . $this->stationname;
        }
    }

}

?>
