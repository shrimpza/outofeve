<?php

class eveItem {

    var $typeid = 0;
    var $typename = '';
    var $marketgroupid = 0;
    var $groupid = 0;
    var $volume = 0;
    var $capacity = 0;
    var $portionsize = 0;
    var $baseprice = 0;
    var $icon = null;
    var $_description = false;
    var $metagroupid = 0;
    var $pricing = null;
    var $blueprint = null;
    var $group = null;

    function eveItem($typeId) {
        $res = eveDB::getInstance()->db->QueryA('select t.groupid, t.typeid, t.typename, t.marketgroupid, t.volume,
                                               t.capacity, t.portionsize, t.baseprice, m.metagroupid,
                                               \'\' as icon
                                             from invTypes t
                                               left outer join invMetaTypes m on m.typeid = t.typeid
                                             where t.typeID = ?', array($typeId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->icon = itemGraphic::getItemGraphic($this->typeid, $this->icon);
    }

    function __get($name) {
        if ($name == 'description') {
            return $this->getDescription();
        }
    }

    function getDescription() {
        if ($this->_description == false) {
            $res = eveDB::getInstance()->db->QueryA('select description from invTypes where typeID = ?', array($this->typeid));
            if ($res) {
                $this->_description = $res[0]['description'];
            }
        }

        return $this->_description;
    }

    function getBlueprint() {
        if ($this->blueprint == null) {
            $this->blueprint = eveDB::getInstance()->eveItemBlueprint($this->typeid);
        }

        return $this->blueprint;
    }

    function getGroup() {
        if (($this->groupid) && ($this->group == null)) {
            $this->group = eveDB::getInstance()->eveItemGroup($this->groupid);
        }

        return $this->group;
    }

    function getPricing($regionId = 0) {
        if (($this->pricing == null) && ($this->marketgroupid > 0)) {
            $this->pricing = new ItemPricing($this->typeid, $regionId);
        } else if (!$this->marketgroupid) {
            $this->pricing = new ItemPricing(0, $regionId);
        }
    }

}

class eveItemFlag {

    var $flagid = 0;
    var $flagname = '';
    var $flagtext = '';

    function eveItemFlag($flagId) {
        $res = eveDB::getInstance()->db->QueryA('select flagid, flagname, flagtext from invFlags where flagid = ?', array($flagId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }
    }

}

class eveItemGroup {

    var $groupid = 0;
    var $categoryid = 0;
    var $groupname = '';
    var $icon = '';
    var $category = null;

    function eveItemGroup($groupId) {
        $res = eveDB::getInstance()->db->QueryA('select t.groupid, t.categoryid, t.groupname, i.iconFile as icon
                                       from invGroups t
                                         left outer join eveIcons i on i.iconId = t.iconId
                                       where t.groupid = ?', array($groupId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->icon = itemGraphic::getItemGraphic(0, $this->icon);

        $this->category = eveDB::getInstance()->eveItemCategory($this->categoryid);
    }

}

class eveItemCategory {

    var $categoryid = 0;
    var $categoryname = '';

    function eveItemCategory($categoryId) {
        $res = eveDB::getInstance()->db->QueryA('select categoryid, categoryname from invCategories where categoryid = ?', array($categoryId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }
    }

}

class eveItemBlueprint {

    var $typeid = 0;
    var $producttypeid = 0;
    var $materials = array();
    var $extraMaterials = array();
    var $skills = array();
    var $blueprintItem = null;

    function eveItemBlueprint($typeId) {

        $res = eveDB::getInstance()->db->QueryA('select typeid, producttypeid
                                                 from industryActivityProducts
                                                 where producttypeid = ?', array($typeId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }

            $this->blueprintItem = eveDB::getInstance()->eveItem($this->typeid);

            /*
             * First, get raw materials required - for manufacture only, maybe support other activityIDs later.
             */
            $this->materials = eveDB::getInstance()->db->QueryA('select materialTypeID, quantity
                                                                 from industryActivityMaterials
                                                                 where typeID = ? and activityID = 1', array($this->typeid));
            for ($i = 0; $i < count($this->materials); $i++) {
                $this->materials[$i]['item'] = eveDB::getInstance()->eveItem($this->materials[$i]['materialtypeid']);
            }

            /*
             * Load skills required
             */
            $skillz = eveDB::getInstance()->db->QueryA('select skillID, level
                                                        from industryActivitySkills
                                                        where typeID = ? and activityID = 1', array($this->typeid));
            foreach ($skillz as $skill) {
              $this->skills[] = array('item' => eveDB::getInstance()->eveItem($skill['skillid']), 'level' => $skill['level']);
            }
        }
    }

    function reduceMaterials($typeId) {
        $bp = eveDB::getInstance()->eveItemBlueprint($typeId);
        $newMaterials = array();
        for ($i = 0; $i < count($this->materials); $i++) {
            for ($j = 0; $j < count($bp->materials); $j++) {
                if ($this->materials[$i]['materialtypeid'] == $bp->materials[$j]['materialtypeid']) {
                    $this->materials[$i]['quantity'] -= $bp->materials[$i]['quantity'];
                }
            }
            if ($this->materials[$i]['quantity'] > 0) {
                $newMaterials[] = $this->materials[$i];
            }
        }
        $this->materials = $newMaterials;
    }

}

class eveIndustryActivity {

    var $activityid = 0;
    var $activityname = '';
    var $iconno = '';
    var $icon;

    function eveIndustryActivity($activityId) {
        $res = eveDB::getInstance()->db->QueryA('select activityid, activityname, iconno from ramActivities where activityid = ?', array($activityId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }
        $this->icon = itemGraphic::getItemGraphic(0, $this->iconno);
    }

}

class eveStation {

    var $stationid = 0;
    var $solarsystemid = 0;
    var $regionid = 0;
    var $stationname = '';
    var $stationtypeid = 0;
    var $icon;
    var $solarSystem = null;
    var $region = null;

    function eveStation($stationId) {
        $res = eveDB::getInstance()->db->QueryA('select stationid, solarsystemid, regionid, stationname, stationtypeid
                                       from staStations
                                       where stationID = ?', array($stationId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }

            $this->icon = itemGraphic::getItemGraphic($this->stationtypeid, '');
        }

        if ($this->solarsystemid) {
            $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->solarsystemid);
        }
    }

}

class eveSolarSystem {

    var $solarsystemid = 0;
    var $regionid = 0;
    var $solarsystemname = '';
    var $security = 0;
    var $x = 0;
    var $z = 0;
    var $factionid = 0;
    var $jumps = false;
    var $region = null;

    function eveSolarSystem($systemId) {
        if (is_array($systemId)) {
            $res = array($systemId);
        } else {
            $res = eveDB::getInstance()->db->QueryA('select s.solarsystemid, s.regionid, s.solarsystemname, s.security, s.x, s.z,
                                                 coalesce(s.factionid, r.factionid) as factionid
                                                 from mapSolarSystems s, mapRegions r
                                                 where solarSystemID = ? and r.regionID = s.regionID', array($systemId));
        }

        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->security = round(max(0, $this->security), 1);

        if ($this->regionid) {
            $this->region = eveDB::getInstance()->eveRegion($this->regionid);
        }
    }

    function getJumps() {
        if (!$this->jumps) {
            $this->jumps = array();
            $jumps = eveDB::getInstance()->db->QueryA('select toSolarSystemID from mapSolarSystemJumps where fromSolarSystemID = ?', array($this->solarsystemid));
            if ($jumps) {
                for ($i = 0; $i < count($jumps); $i++) {
                    $this->jumps[] = eveDB::getInstance()->eveSolarSystem($jumps[$i]['tosolarsystemid']);
                }
            }
        }
    }

}

class eveRegion {

    var $regionid = 0;
    var $regionname = '';

    function eveRegion($regionId) {
        $res = eveDB::getInstance()->db->QueryA('select regionid, regionname from mapRegions where regionID = ?', array($regionId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }
    }

}

class eveCelestial {

    var $itemid = 0;
    var $typeid = 0;
    var $solarsystemid = 0;
    var $regionid = 0;
    var $x = 0;
    var $z = 0;
    var $itemname = '';
    var $security = 0;
    var $solarsystem = null;
    var $region = null;

    function eveCelestial($itemId) {
        $res = eveDB::getInstance()->db->QueryA('select itemid, typeid, solarsystemid, regionid, x, z, itemname, security
                                       from mapDenormalize
                                       where itemID = ?', array($itemId));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->security = round(max(0, $this->security), 1);

        if ($this->solarsystemid) {
            $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->solarsystemid);
        }

        if ($this->regionid) {
            $this->region = eveDB::getInstance()->eveRegion($this->regionid);
        }
    }

}

class eveNpcCorp {

    var $corporationid = 0;
    var $solarsystemid = 0;
    var $factionid = 0;
    var $description = '';
    var $corporationName = '';
    var $icon = null;
    var $faction = null;
    var $solarSystem = null;

    function eveNpcCorp($id) {
        $res = eveDB::getInstance()->db->QueryA('select corporationid, solarsystemid, factionid, description, iconid as icon
                                             from crpNPCCorporations
                                             where corporationID = ?', array($id));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->corporationName = getCharacterName($id);

        $this->icon = itemGraphic::getItemGraphic(0, $this->icon);
        $this->faction = eveDB::getInstance()->eveFaction($this->factionid);
        $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->solarsystemid);
    }

}

class eveFaction {

    var $factionid = 0;
    var $factionname = '';
    var $description = '';
    var $solarsystemid = 0;
    var $corporationid = 0;
    var $militiacorporationid = 0;
    var $icon = null;
    var $solarSystem = null;

    function eveFaction($id) {
        $res = eveDB::getInstance()->db->QueryA('select factionid, factionname, description, solarsystemid, corporationid, iconid as icon
                                             from chrFactions
                                             where factionID = ?', array($id));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->icon = itemGraphic::getItemGraphic(0, $this->icon);
        $this->solarSystem = eveDB::getInstance()->eveSolarSystem($this->solarsystemid);
    }

}

class eveAgent {

    var $agentid = 0;
    var $divisionid = 0;
    var $corporationid = 0;
    var $locationid = 0;
    var $level = 0;
    var $agenttypeid = 0;
    var $islocator = 0;
    var $corporation = null;
    var $division = null;
    var $agentType = null;
    var $station = null;
    var $agentName = '';

    function eveAgent($id) {
        $res = eveDB::getInstance()->db->QueryA('select agentid, divisionid, corporationid, locationid, level, agenttypeid, islocator
                                             from agtAgents
                                             where agentid = ?', array($id));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }

        $this->station = eveDB::getInstance()->eveStation($this->locationid);
        $this->corporation = eveDB::getInstance()->eveNpcCorp($this->corporationid);
        $this->division = eveDB::getInstance()->eveNpcDivision($this->divisionid);
        $this->agentType = eveDB::getInstance()->agentTypeText($this->agenttypeid);
    }

    function getName() {
        $this->agentName = getCharacterName($id);
        return $this->agentName;
    }
}

class eveNpcDivision {

    var $divisionid = 0;
    var $divisionname = '';
    var $description = '';
    var $leadertype = '';

    function eveNpcDivision($id) {
        $res = eveDB::getInstance()->db->QueryA('select divisionid, divisionname, description, leadertype
                                             from crpNPCDivisions
                                             where divisionID = ?', array($id));
        if ($res) {
            foreach ($res[0] as $var => $val) {
                $this->$var = $val;
            }
        }
    }

}

?>
