<?php

class itemGraphic {

    private static $iconCache = null;

    var $icon16;
    var $icon32;
    var $icon64;
    var $icon128;

    static function getItemGraphic($typeId, $icon) {
        if (self::$iconCache == null) {
            self::$iconCache = array();
        }

        $k = $typeId . '.' . $icon;
        if (in_array($k, array_keys(self::$iconCache))) {
            return self::$iconCache[$k];
        } else {
            self::$iconCache[$k] = new itemGraphic($typeId, $icon);
            return self::$iconCache[$k];
        }
    }

    function itemGraphic($typeId, $icon) {
        $basePath = dirname(__FILE__) . '/../../';
        $iconParts = false;
        if (isset($icon) && !empty($icon)) {
            $iconParts = explode('_', $icon);
            if (count($iconParts) != 2) $iconParts = false;
        }

        if (isset($typeId) && $typeId) {
            $name16 = sprintf($GLOBALS['config']['images']['types'], $typeId, 16);
            if (file_exists($basePath . $name16)) {
                $this->icon16 = $name16;
                $this->icon32 = $name16;
                $this->icon64 = $name16;
                $this->icon128 = $name16;
            }

            $name32 = sprintf($GLOBALS['config']['images']['types'], $typeId, 32);
            if (file_exists($basePath . $name32)) {
                $this->icon16 = empty($this->icon16) ? $name32 : $this->icon16;
                $this->icon32 = $name32;
                $this->icon64 = $name32;
                $this->icon128 = $name32;
            }

            $name64 = sprintf($GLOBALS['config']['images']['types'], $typeId, 64);
            if (file_exists($basePath . $name64)) {
                $this->icon16 = empty($this->icon16) ? $name64 : $this->icon16;
                $this->icon32 = empty($this->icon32) ? $name64 : $this->icon32;
                $this->icon64 = $name64;
                $this->icon128 = $name64;
            }

            $name128 = sprintf($GLOBALS['config']['images']['types'], $typeId, 128);
            if (file_exists($basePath . $name128)) {
                $this->icon16 = empty($this->icon16) ? $name128 : $this->icon16;
                $this->icon32 = empty($this->icon32) ? $name128 : $this->icon32;
                $this->icon64 = empty($this->icon64) ? $name128 : $this->icon64;
                $this->icon128 = $name128;
            }
        }

        if ($iconParts) {
            if (!isset($this->icon16)) {
                $name16 = sprintf($GLOBALS['config']['images']['icons'], $iconParts[0], $iconParts[1], 16);
                if (file_exists($basePath . $name16)) {
                    $this->icon16 = $name16;
                    $this->icon32 = $name16;
                    $this->icon64 = $name16;
                    $this->icon128 = $name16;
                }
            }

            if (!isset($this->icon32)) {
                $name32 = sprintf($GLOBALS['config']['images']['icons'], $iconParts[0], $iconParts[1], 32);
                if (file_exists($basePath . $name32)) {
                    $this->icon16 = empty($this->icon16) ? $name32 : $this->icon16;
                    $this->icon32 = $name32;
                    $this->icon64 = $name32;
                    $this->icon128 = $name32;
                }
            }

            if (!isset($this->icon64)) {
                $name64 = sprintf($GLOBALS['config']['images']['icons'], $iconParts[0], $iconParts[1], 64);
                if (file_exists($basePath . $name64)) {
                    $this->icon16 = empty($this->icon16) ? $name64 : $this->icon16;
                    $this->icon32 = empty($this->icon32) ? $name64 : $this->icon32;
                    $this->icon64 = $name64;
                    $this->icon128 = $name64;
                }
            }

            if (!isset($this->icon128)) {
                $name128 = sprintf($GLOBALS['config']['images']['icons'], $iconParts[0], $iconParts[1], 128);
                if (file_exists($basePath . $name128)) {
                    $this->icon16 = empty($this->icon16) ? $name128 : $this->icon16;
                    $this->icon32 = empty($this->icon32) ? $name128 : $this->icon32;
                    $this->icon64 = empty($this->icon64) ? $name128 : $this->icon64;
                    $this->icon128 = $name128;
                }
            }
        }
    }

}

class eveDB {

    var $cache = array();
    var $db = null;
    static $instance = null;

    function eveDB() {
        if (!isset($this->db)) {
            $this->db = new DBManager($GLOBALS['config']['evedatabase']);
        }
    }

    static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new eveDB();
        }
        return self::$instance;
    }

    function bloodlineInfo($bloodlineName) {
        $res = $this->db->QueryA("select b.bloodlineName, r.raceName, ib.iconFile as bicon, ir.iconFile as ricon
                                        from chrBloodlines b
                                        inner join chrRaces r on r.raceId = b.raceId
                                        inner join eveIcons ib on ib.iconId = b.iconId
                                        inner join eveIcons ir on ir.iconId = r.iconId
                                        where b.bloodlineName = ?", array($bloodlineName));
        if ($res) {
            $res = $res[0];
            $res['ricon'] = itemGraphic::getItemGraphic(0, $res['ricon']);
            $res['bicon'] = itemGraphic::getItemGraphic(0, $res['bicon']);
            return $res;
        } else {
            return false;
        }
    }

    function getCache($cacheSet, $id, $className = '') {
        $res = null;

        $id = (string) $id;

        if (isset($this->cache[$cacheSet]) && isset($this->cache[$cacheSet][$id])) {
            $res = $this->cache[$cacheSet][$id];
        }

        if ($res == null && !empty($className)) {
            $this->putCache($cacheSet, $id, new $className($id));
            $res = $this->getCache($cacheSet, $id);
        }

        return $res;
    }

    function putCache($cacheSet, $id, $value) {
        $this->cache[$cacheSet][$id] = $value;
    }

    function typeName($id) {
        $id = (string) $id;

        $res = $this->getCache('eveItem', $id);

        if ($res != null) {
            $res = $res->typename;
        } else {
            if (($res = $this->getCache(__FUNCTION__, $id)) == null) {
                $res = $this->db->QueryA('select typeName from invTypes where typeID = ?', array($id));
                if ($res) {
                    $this->putCache(__FUNCTION__, $id, $res[0]['typename']);
                }
            }
        }

        return $res;
    }

    function flagText($id) {
        $id = (string) $id;

        if ($this->getCache(__FUNCTION__, $id) == null) {
            $res = $this->db->QueryA('select flagText from invFlags where flagID = ?', array($id));
            if ($res) {
                $this->putCache(__FUNCTION__, $id, $res[0]['flagtext']);
            }
        }

        return $this->getCache(__FUNCTION__, $id);
    }

    function getTypeId($name) {
        $id = 0;
        $res = $this->db->QueryA('select typeID from invTypes where UCASE(typeName) = UCASE(?)', array($name));
        if ($res) {
            $id = $res[0]['typeid'];
        }

        return $id;
    }

    function getRegionId($name) {
        $regionId = 0;
        $res = $this->db->QueryA('select regionID from mapRegions where UCASE(regionName) = UCASE(?)', array($name));
        if ($res) {
            $regionId = $res[0]['regionid'];
        }

        return $regionId;
    }

    function eveItem($id, $byName = false) {
        $id = (string) $id;

        if ($byName) {
            $id = $this->getTypeId($id);
        }

        if ($id != '0') {
            return $this->getCache(__FUNCTION__, $id, 'eveItem');
        } else {
            return false;
        }
    }

    function eveItemFlag($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveItemFlag');
    }

    function eveItemGroup($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveItemGroup');
    }

    function eveItemCategory($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveItemCategory');
    }

    function eveItemBlueprint($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveItemBlueprint');
    }

    function eveIndustryActivity($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveIndustryActivity');
    }

    /**
     * retrieves the item a blueprint produces, based on a blueprint type from
     * invTypes, NOT invBlueprintTypes.
     */
    function eveItemFromBlueprintType($typeId) {
        $res = $this->db->QueryA('select producttypeid from invBlueprintTypes where blueprinttypeid = ?', array($typeId));
        if ($res) {
            return $this->eveItem($res[0]['producttypeid']);
        } else {
            return null;
        }
    }

    function eveCertificate($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveCertificate');
    }

    function regionList() {
        return $this->db->QueryA("select regionID, regionName from mapRegions where RegionName <> 'Unknown' order by regionName", array());
    }

    function eveStation($id) {
        $id = (int) $id;

        // see http://wiki.eve-id.net/APIv2_Corp_AssetList_XML
        if (($id >= 66000000) && ($id < 67000000)) {
            $id -= 6000001;
        }

        $station = $this->getCache(__FUNCTION__, $id, 'eveStation');

        if ($station == null || $station->stationid == 0) {
            $outpost = eveOutpostList::getOutpost($id);
            if ($outpost) {
                $this->putCache(__FUNCTION__, $id, $outpost);
                $station = $this->getCache(__FUNCTION__, $id);
            }
        }

        if ($station != null) {
            $station->stationname = str_replace('Moon ', 'M', $station->stationname);
        }

        return $station;
    }

    function eveSolarSystem($id) {
        if (is_array($id)) {
            $id = $id['solarsystemid'];
        }

        return $this->getCache(__FUNCTION__, $id, 'eveSolarSystem');
    }

    function eveRegion($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveRegion');
    }

    function eveCelestial($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveCelestial');
    }

    function eveAllSystems($regionID = 0) {
        $res = array();

        $regionLimit = '';
        if ($regionID > 0) {
            $regionLimit = ' where regionID = ' . $regionID;
        }

        $sysList = $this->db->QueryA('select solarsystemid, regionid, solarsystemname, security, x, z, factionid
                                          from mapSolarSystems ' . $regionLimit . '
                                          order by solarSystemName', array());
        for ($i = 0; $i < count($sysList); $i++) {
            $sys = $this->getCache('eveSolarSystem', $sysList[$i], 'eveSolarSystem');
            $res[] = $sys;
        }

        for ($i = 0; $i < count($res); $i++) {
            $res[$i]->getJumps();
        }

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
            foreach ($open as $value) {
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
                    foreach ($path as $backsys) {
                        $result['systems'][] = $this->getCache('eveSolarSystem', $backsys, 'eveSolarSystem');
                    }

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

    function eveFuelRequirements($towerId) {
        $towerId = (string) $towerId;

        if ($this->getCache(__FUNCTION__, $towerId) == null) {
            $towerFuel = $this->db->QueryA('select r.resourcetypeid, r.purpose, r.quantity, p.purposeText, r.factionid
                                                                     from invControlTowerResources r, invControlTowerResourcePurposes p
                                                                     where r.controltowertypeid = ? and p.purpose = r.purpose
                                                                     order by r.purpose, r.resourcetypeid', array($towerId));
            for ($i = 0; $i < count($towerFuel); $i++) {
                $towerFuel[$i]['resource'] = $this->getCache('eveItem', $towerFuel[$i]['resourcetypeid'], 'eveItem');
            }

            $this->putCache(__FUNCTION__, $towerId, $towerFuel);
        }

        return $this->getCache(__FUNCTION__, $towerId);
    }

    function eveNpcCorp($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveNpcCorp');
    }

    function eveFaction($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveFaction');
    }

    function eveAgent($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveAgent');
    }

    function eveNpcDivision($id) {
        return $this->getCache(__FUNCTION__, $id, 'eveNpcDivision');
    }

    function agentTypeText($id) {
        $id = (string) $id;

        if ($this->getCache(__FUNCTION__, $id) == null) {
            $res = $this->db->QueryA('select agenttype from agtAgentTypes where agentTypeId = ?', array($id));
            if ($res) {
                $this->putCache(__FUNCTION__, $id, $res[0]['agenttype']);
            }
        }

        return $this->getCache(__FUNCTION__, $id);
    }

}

?>