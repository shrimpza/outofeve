<?php

    class eveDB {
        var $typeNameCache = array();
        var $itemCache = array();
        var $itemGroupCache = array();
        var $itemCategoryCache = array();
        var $itemFlagCache = array();
        var $blueprintCache = array();
        var $activityCache = array();
        var $stationCache = array();
        var $solarSystemCache = array();
        var $regionCache = array();
        var $celestialCache = array();
        var $flagTextCache = array();
        var $industryCompleteTextCache = array();
        var $certificateCache = array();
        var $towerFuelCache = array();

        var $refTypes = array();

        var $outpostList = null;

        var $corpRoleList = array();

        var $db = null;

        function eveDB() {
            if (!isset($this->db))
                $this->db = new DBManager($GLOBALS['config']['evedatabase']);
        }

        function bloodlineInfo($bloodlineName) {
            $res = $this->db->QueryA("select b.bloodlineName, r.raceName, ib.iconFile as bicon, ir.iconFile as ricon
                                        from chrBloodlines b 
                                        inner join chrRaces r on r.raceId = b.raceId
                                        inner join eveIcons ib on ib.iconId = b.iconId
                                        inner join eveIcons ir on ir.iconId = r.iconId
                                        where b.bloodlineName = ?", array($bloodlineName));
            if ($res)
                return $res[0];
            else
                return false;
        }

        function typeName($typeId) {
            $typeId = (string)$typeId;

            if (!isset($this->typeNameCache[$typeId])) {
                $res = $this->db->QueryA('select typeName from invTypes where typeID = ?', array($typeId));
                if ($res)
                    $this->typeNameCache[$typeId] = $res[0]['typename'];
            }

            return $this->typeNameCache[$typeId];
        }

        function flagText($flagId) {
            $flagId = (string)$flagId;

            if (!isset($this->flagTextCache[$flagId])) {
                $res = $this->db->QueryA('select flagText from invFlags where flagID = ?', array($flagId));
                if ($res)
                    $this->flagTextCache[$flagId] = $res[0]['flagtext'];
            }

            return $this->flagTextCache[$flagId];
        }

        function industryCompleteText($completedStatus) {
            $completedStatus = (string)$completedStatus;

            if (!isset($this->industryCompleteTextCache[$completedStatus])) {
                $res = $this->db->QueryA('select completedStatusText from ramCompletedStatuses where completedStatus = ?', array($completedStatus));
                if ($res)
                    $this->industryCompleteTextCache[$completedStatus] = $res[0]['completedstatustext'];
            }

            return $this->industryCompleteTextCache[$completedStatus];
        }

        function getTypeId($typeName) {
            $typeId = 0;
            $res = $this->db->QueryA('select typeID from invTypes where UCASE(typeName) = UCASE(?)', array($typeName));
            if ($res)
                $typeId = $res[0]['typeid'];

            return $typeId;
        }

        function getRegionId($regionName) {
            $regionId = 0;
            $res = $this->db->QueryA('select regionID from mapRegions where UCASE(regionName) = UCASE(?)', array($regionName));
            if ($res)
                $regionId = $res[0]['regionid'];

            return $regionId;
        }

        function refType($refTypeId) {
            $refTypeId = (string)$refTypeId;

            if (!isset($this->refTypes[$refTypeId])) {
                $eveRefTypes = new apiRequest('eve/RefTypes.xml.aspx');
                foreach ($eveRefTypes->data->result->rowset->row as $refType)
                    $this->refTypes[(string)$refType['refTypeID']] = (string)$refType['refTypeName'];
            }

            return $this->refTypes[$refTypeId];
        }

        function eveItem($typeId, $byName = false) {
            $typeId = (string)$typeId;

            if ($byName)
                $typeId = $this->getTypeId($typeId);

            if ($typeId != '0') {
                if (!isset($this->itemCache[$typeId]))
                    $this->itemCache[$typeId] = new eveItem($this, $typeId);
            }
            else
                return false;

            return $this->itemCache[$typeId];
        }

        function eveItemFlag($flagId) {
            $flagId = (string)$flagId;

            if (!isset($this->itemFlagCache[$flagId]))
                $this->itemFlagCache[$flagId] = new eveItemFlag($this, $flagId);

            return $this->itemFlagCache[$flagId];
        }

        function eveItemGroup($groupId) {
            $groupId = (string)$groupId;

            if (!isset($this->itemGroupCache[$groupId]))
                $this->itemGroupCache[$groupId] = new eveItemGroup($this, $groupId);

            return $this->itemGroupCache[$groupId];
        }

        function eveItemCategory($categoryId) {
            $categoryId = (string)$categoryId;

            if (!isset($this->itemCategoryCache[$categoryId]))
                $this->itemCategoryCache[$categoryId] = new eveItemCategory($this, $categoryId);

            return $this->itemCategoryCache[$categoryId];
        }

        function eveItemBlueprint($typeId) {
            $typeId = (string)$typeId;

            if (!isset($this->blueprintCache[$typeId]))
                $this->blueprintCache[$typeId] = new eveItemBlueprint($this, $typeId);

            return $this->blueprintCache[$typeId];
        }

        function eveIndustryActivity($activityId) {
            $activityId = (string)$activityId;

            if (!isset($this->activityCache[$activityId]))
                $this->activityCache[$activityId] = new eveIndustryActivity($this, $activityId);

            return $this->activityCache[$activityId];
        }

        // retrieves the item a blueprint produces, based on a blueprint type
        // from invTypes, NOT invBlueprintTypes.
        function eveItemFromBlueprintType($typeId) {
            $res = $this->db->QueryA('select producttypeid from invBlueprintTypes where blueprinttypeid = ?', array($typeId));
            if ($res)
                return $this->eveItem($res[0]['producttypeid']);
            else
                return null;
        }

        function eveCertificate($certificateId) {
            $certificateId = (string)$certificateId;

            if (!isset($this->certificateCache[$certificateId]))
                $this->certificateCache[$certificateId] = new eveCertificate($this, $certificateId);

            return $this->certificateCache[$certificateId];
        }

        function regionList() {
            return $this->db->QueryA("select regionID, regionName from mapRegions where RegionName <> 'Unknown' order by regionName", array());
        }

        function eveStation($stationId) {
            $stationId = (int)$stationId;

            // see http://wiki.eve-id.net/APIv2_Corp_AssetList_XML
            if (($stationId >= 66000000) && ($stationId < 67000000)) {
                $stationId -= 6000001;
            }

            $stationId = (string)$stationId;

            if (!isset($this->stationCache[$stationId]))
                $this->stationCache[$stationId] = new eveStation($this, $stationId);

            $theStation = $this->stationCache[$stationId];

            if ($theStation->stationid == 0) {
                if ($this->outpostList == null)
                    $this->loadOutpostList();

                $outpost = $this->outpostList->getOutpost($stationId);
                if ($outpost) {
                    $this->stationCache[$stationId] = $outpost;
                    $theStation = $this->stationCache[$stationId];
                }
            }

            $theStation->stationname = str_replace('Moon ', 'M', $theStation->stationname);

            return $theStation;
        }

        function eveSolarSystem($solarSystemId) {
            if (is_array($solarSystemId)) {
                if (!isset($this->solarSystemCache[$solarSystemId['solarsystemid']]))
                    $this->solarSystemCache[$solarSystemId['solarsystemid']] = new eveSolarSystem($this, $solarSystemId);
                $solarSystemId = $solarSystemId['solarsystemid'];
            } else {
                $solarSystemId = (string)$solarSystemId;

                if (!isset($this->solarSystemCache[$solarSystemId]))
                    $this->solarSystemCache[$solarSystemId] = new eveSolarSystem($this, $solarSystemId);
            }

            return $this->solarSystemCache[$solarSystemId];
        }

        function eveRegion($regionId) {
            $regionId = (string)$regionId;

            if (!isset($this->regionCache[$regionId]))
                $this->regionCache[$regionId] = new eveRegion($this, $regionId);

            return $this->regionCache[$regionId];
        }

        function eveCelestial($itemId) {
            $itemId = (string)$itemId;

            if (!isset($this->celestialCache[$itemId]))
                $this->celestialCache[$itemId] = new eveCelestial($this, $itemId);

            return $this->celestialCache[$itemId];
        }

        function eveAllSystems($regionID = 0) {
            $res = array();

            $regionLimit = '';
            if ($regionID > 0)
                $regionLimit = ' where regionID = ' . $regionID;

            $sysList = $this->db->QueryA('select solarsystemid, regionid, solarsystemname, security, x, z, factionid 
                                          from mapSolarSystems ' . $regionLimit . '
                                          order by solarSystemName', array());
            for ($i = 0; $i < count($sysList); $i++) {
                $sys = $this->eveSolarSystem($sysList[$i]);
                $res[] = $sys;
            }

            for ($i = 0; $i < count($res); $i++)
                $res[$i]->getJumps();

            return $res;
        }

        function calcJumps($fromSystemID, $toSystemID, $minSec = 0) {
            $result = array('jumps' => 0, 'systems' => array());

            $source = $fromSystemID; 
            $destination = $toSystemID;

            $sid = $source; 
            $did = $destination;

            $open[$sid]['weight'] = 0; 
            $open[$sid]['parent'] = null; 
            $open[$sid]['sid'] = $sid;
            do {
                foreach($open as $value) {
                    $sid = $value['sid']; 
                    $weight = $value['weight']; 
                    $parent = $value['parent'];

                    $closed[$sid]['weight'] = $weight; 
                    $closed[$sid]['parent'] = $parent; 
                    $closed[$sid]['sid'] = $sid;

                    // found path to destination
                    if ($sid == $did) {
                        $result['jumps'] = $weight;

                        unset($path); 
                        $backparent = $sid;
                        while ($backparent != '') {
                            $path[] = $backparent;
                            $backparent = $closed[$backparent]['parent'];
                        }

                        $path = array_reverse($path);
                        foreach($path as $backsys)
                            $result['systems'][] = $this->eveSolarSystem($backsys);

                        unset($open); 
                        break;
                    } else {
                        $jumps = $this->db->QueryA('select toSolarSystemID, security
                                                    from mapSolarSystemJumps, mapSolarSystems
                                                    where solarSystemID = toSolarSystemID and fromSolarSystemID = ?', array($sid));
                        for ($i = 0; $i < count($jumps); $i++) {
                            $nsid = $jumps[$i]['tosolarsystemid']; 
                            $nweight = $weight + 1; 
                            $nparent = $sid; 
                            $nsec = $jumps[$i]['security'];

                            if (($minSec == 0) || ($nsec >= $minSec)) {
                                if (!isset($closed[$nsid]['weight']) || ($closed[$nsid]['weight'] >= $nweight)) {
                                    $open[$nsid]['weight'] = $nweight; 
                                    $open[$nsid]['parent'] = $sid; 
                                    $open[$nsid]['sid'] = $nsid;
                                }
                            }
                        }
                        unset($jumps);
                        unset($open[$sid]);
                    }
                }
            } while (count($open) > 0);

            return $result;
        }

        function loadOutpostList() {
            if ($this->outpostList == null) {
                $outpostData = new apiRequest('eve/ConquerableStationList.xml.aspx');
                if ($outpostData->data) {
                    if (!$outpostData->data->error) {
                        $this->outpostList = new eveOutpostList($this, $outpostData->data->result);
                    }
                }
            }
        }

        function eveFuelRequirements($towedId) {
            $towedId = (string)$towedId;

            if (!isset($this->towerFuelCache[$towedId])) {
                $this->towerFuelCache[$towedId] = $this->db->QueryA('select r.resourcetypeid, r.purpose, r.quantity, p.purposeText, r.factionid
                                                                     from invControlTowerResources r, invControlTowerResourcePurposes p
                                                                     where r.controltowertypeid = ? and p.purpose = r.purpose
                                                                     order by r.purpose, r.resourcetypeid', array($towedId));
                for ($i = 0; $i < count($this->towerFuelCache[$towedId]); $i++) {
                    $this->towerFuelCache[$towedId][$i]['resource'] = $this->eveItem($this->towerFuelCache[$towedId][$i]['resourcetypeid']);
                }
            }

            return $this->towerFuelCache[$towedId];
        }

        function corpRoleList() {
            if (count($this->corpRoleList) == 0) {
                $this->corpRoleList = $this->db->QueryA('select roleBit, roleName from crpRoles order by roleBit', array());
            }

            return $this->corpRoleList;
        }
    }

    class eveItem {
        var $typeid = 0;
        var $typename = '';
        var $marketgroupid = 0;
        var $groupid = 0;
        var $volume = 0;
        var $capacity = 0;
        var $portionsize = 0;
        var $baseprice = 0;
        var $icon = '74_14';
        var $typeGraphic = false;
        var $_description = false;
        var $metagroupid = 0;

        var $pricing = null;
        var $blueprint = null;
        var $group = null;

        var $evedb = null;

        function eveItem($evedb, $typeId) {
            $this->evedb = $evedb;

            $res = $this->evedb->db->QueryA('select t.groupid, t.typeid, t.typename, t.marketgroupid, t.volume, 
                                               t.capacity, t.portionsize, t.baseprice, i.iconFile as icon, m.metagroupid
                                             from invTypes t
                                               left outer join eveIcons i on i.iconId = t.iconId
                                               left outer join invMetaTypes m on m.typeid = t.typeid
                                             where t.typeID = ?', array($typeId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

            $this->typeGraphic = file_exists(dirname(__FILE__).'/../../eveimages/types/32/' . $this->typeid . '.png');
            if (!$this->typeGraphic && empty($this->icon))
                $this->icon = '74_14';
        }

        function __get($name) {
            if ($name == 'description')
                return $this->getDescription();
        }

        function getDescription() {
            if ($this->_description == false) {
                $res = $this->evedb->db->QueryA('select description from invTypes where typeID = ?', array($this->typeid));
                if ($res)
                    $this->_description = $res[0]['description'];
            }

            return $this->_description;
        }

        function getBlueprint() {
            if ($this->blueprint == null)
                $this->blueprint = $this->evedb->eveItemBlueprint($this->typeid);

            return $this->blueprint;
        }

        function getGroup() {
            if (($this->groupid) && ($this->group == null))
                $this->group = $this->evedb->eveItemGroup($this->groupid);

            return $this->group;
        }

        function getPricing($regionId = 0) {
            if (($this->pricing == null) && ($this->marketgroupid > 0))
                $this->pricing = new ItemPricing($this->typeid, $regionId);
            else if (!$this->marketgroupid)
                $this->pricing = new ItemPricing(0, $regionId);
        }
    }

    class eveItemFlag {
        var $flagid = 0;
        var $flagname = '';
        var $flagtext = '';

        function eveItemFlag($evedb, $flagId) {
            $res = $evedb->db->QueryA('select flagid, flagname, flagtext from invFlags where flagid = ?', array($flagId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;
        }
    }

    class eveItemGroup {
        var $groupid = 0;
        var $categoryid = 0;
        var $groupname = '';
        var $icon = '';

        var $category = null;

        function eveItemGroup($evedb, $groupId) {
            $res = $evedb->db->QueryA('select t.groupid, t.categoryid, t.groupname, i.iconFile as icon
                                       from invGroups t
                                         left outer join eveIcons i on i.iconId = t.iconId
                                       where t.groupid = ?', array($groupId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

            $this->category = $evedb->eveItemCategory($this->categoryid);
        }
    }

    class eveItemCategory {
        var $categoryid = 0;
        var $categoryname = '';

        function eveItemCategory($evedb, $categoryId) {
            $res = $evedb->db->QueryA('select categoryid, categoryname from invCategories where categoryid = ?', array($categoryId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;
        }
    }

    class eveItemBlueprint {
        var $blueprinttypeid = 0;
        var $producttypeid = 0;
        var $productiontime = 0;
        var $wastefactor = 0;

        var $materials = array();
        var $extraMaterials = array();
        var $skills = array();

        var $blueprintItem = null;

        function eveItemBlueprint($evedb, $typeId) {
            $this->evedb = $evedb;

            $res = $this->evedb->db->QueryA('select blueprinttypeid, producttypeid, productiontime, wastefactor 
                                             from invBlueprintTypes
                                             where producttypeid = ?', array($typeId));
            if ($res) {
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

                $this->blueprintItem = $this->evedb->eveItem($this->blueprinttypeid);

                /*
                 * As of Dominion, this became hectic.
                 * First, get raw materials required
                 */
                $this->materials = $this->evedb->db->QueryA('select materialTypeID, quantity
                                                             from invTypeMaterials
                                                             where typeID = ?', array($this->producttypeid));
                for ($i = 0; $i < count($this->materials); $i++) {
                    $this->materials[$i]['item'] = $this->evedb->eveItem($this->materials[$i]['materialtypeid']);
                }

                /*
                 * Load additional parts (RAM bits, T1 base types, etc) and skills
                 */
                $tmp = $this->evedb->db->QueryA('select t.typeID, t.typeName, r.quantity, r.damagePerJob, g.categoryID, 
                                                   coalesce(b.blueprintTypeID, 0) as invBlueprintTypeID
                                                 from ramTypeRequirements r
                                                   inner join invTypes t on r.requiredTypeID = t.typeID
                                                   inner join invGroups g on t.groupID = g.groupID
                                                   left join invBlueprintTypes b on b.productTypeID = t.typeID
                                                 where r.activityID = 1
                                                   and r.typeID = ?', array($this->blueprinttypeid));
                for ($i = 0; $i < count($tmp); $i++) {
                    $tmp[$i]['item'] = $this->evedb->eveItem($tmp[$i]['typeid']);
                    if ($tmp[$i]['categoryid'] == 16) {
                        /*
                         * Skillz go into their own list for better orginisation
                         */
                        $this->skills[] = $tmp[$i];
                    } else {
                        $this->extraMaterials[] = $tmp[$i];
                        /*
                         * If this component has it's own BP, we need to reduce
                         * this BP's raw material requirements by the materials
                         * required for the componont's construction. *boggle*
                         */
                        if ($tmp[$i]['invblueprinttypeid'] > 0) {
                            $this->reduceMaterials($evedb, $tmp[$i]['typeid']);
                        }
                    }
                }
            }
        }

        function reduceMaterials($evedb, $typeId) {
            $bp = $evedb->eveItemBlueprint($typeId);
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

        function eveIndustryActivity($evedb, $activityId) {
            $res = $evedb->db->QueryA('select activityid, activityname, iconno from ramActivities where activityid = ?', array($activityId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;
        }
    }

    class eveCertificate {
        var $certificateid = 0;
        var $categoryid = 0;
        var $classid = 0;
        var $corpid = 0;
        var $icon = 0;
        var $grade = 0;
        var $description = '';

        function eveCertificate($evedb, $certificateId) {
            $res = $evedb->db->QueryA('select c.certificateid, c.categoryid, c.classid, c.corpid, i.iconFile as icon, c.grade, c.description
                                       from crtCertificates c
                                         left outer join eveIcons i on i.iconId = c.iconId
                                       where c.certificateid = ?', array($certificateId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;
        }
    }

    class eveStation {
        var $stationid = 0;
        var $solarsystemid = 0;
        var $regionid = 0;
        var $stationname = '';
        var $stationtypeid = 0;

        var $solarSystem = null;
        var $region = null;

        function eveStation($evedb, $stationId) {
            $res = $evedb->db->QueryA('select stationid, solarsystemid, regionid, stationname, stationtypeid 
                                       from staStations
                                       where stationID = ?', array($stationId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

            if ($this->solarsystemid)
                $this->solarSystem = $evedb->eveSolarSystem($this->solarsystemid);
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

        function eveSolarSystem($evedb, $systemId) {
            $this->evedb = $evedb;
            
            if (is_array($systemId))
                $res = array($systemId);
            else
                $res = $this->evedb->db->QueryA('select s.solarsystemid, s.regionid, s.solarsystemname, s.security, s.x, s.z,
                                                 coalesce(s.factionid, r.factionid) as factionid
                                                 from mapSolarSystems s, mapRegions r
                                                 where solarSystemID = ? and r.regionID = s.regionID', array($systemId));

            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

            $this->security = round(max(0, $this->security), 1);

            if ($this->regionid)
                $this->region = $evedb->eveRegion($this->regionid);
        }

        function getJumps() {
            if (!$this->jumps) {
                $this->jumps = array();
                $jumps = $this->evedb->db->QueryA('select toSolarSystemID from mapSolarSystemJumps where fromSolarSystemID = ?', array($this->solarsystemid));
                if ($jumps)
                    for ($i = 0; $i < count($jumps); $i++)
                        $this->jumps[] = $this->evedb->eveSolarSystem($jumps[$i]['tosolarsystemid']);
            }
        }
    }

    class eveRegion {
        var $regionid = 0;
        var $regionname = '';

        function eveRegion($evedb, $regionId) {
            $res = $evedb->db->QueryA('select regionid, regionname from mapRegions where regionID = ?', array($regionId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;
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

        function eveCelestial($evedb, $itemId) {
            $res = $evedb->db->QueryA('select itemid, typeid, solarsystemid, regionid, x, z, itemname, security 
                                       from mapDenormalize
                                       where itemID = ?', array($itemId));
            if ($res)
                foreach ($res[0] as $var => $val)
                    $this->$var = $val;

            $this->security = round(max(0, $this->security), 1);

            if ($this->solarsystemid)
                $this->solarSystem = $evedb->eveSolarSystem($this->solarsystemid);

            if ($this->regionid)
                $this->region = $evedb->eveRegion($this->regionid);
        }
    }


    class eveOutpostList {
        var $outposts = null;

        var $db = null;

        function eveOutpostList($evedb, $outposts) {
            $this->db = $evedb;
            foreach ($outposts->rowset->row as $outpost)
                $this->outposts[] = new eveOutpost($this->db, $outpost);
        }

        function getOutpost($stationId) {
            foreach ($this->outposts as $outpost) {
                if ($outpost->stationid == $stationId) {
                    $outpost->loadDetail($this->db);
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

        function eveOutpost($evedb, $outpost) {
            $this->stationid = (int)$outpost['stationID'];
            $this->stationname = (string)$outpost['stationName'];
            $this->stationtypeid = (int)$outpost['stationTypeID'];
            $this->solarsystemid = (int)$outpost['solarSystemID'];
        }

        function loadDetail($evedb) {
            if ($this->solarsystem) {
                $this->solarsystem = $evedb->eveSolarSystem($this->solarsystemid);
                $this->regionid = $this->solarsystem->regionid;
                $this->region = $evedb->eveRegion($this->regionid);

                $this->stationname = $this->solarsystem->solarsystemname . ' - ' . $this->stationname;
            }
        }
    }

?>