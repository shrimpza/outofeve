<?php

class assets extends Plugin {

    var $name = 'Assets';
    var $level = 1;

    function assets($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id) && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_AssetList)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Assets', '?module=assets', 'assets');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id) && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_AssetList)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Assets', '?module=assets&corp=1', 'assets');
        }
    }

    function getContent() {
        $_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 0;
        $_GET['group'] = isset($_GET['group']) ? $_GET['group'] : 0;
        $_GET['corp'] = isset($_GET['corp']) ? true : false;

        $_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 'list';
        $_GET['item'] = isset($_GET['item']) ? trim($_GET['item']) : '';

        if ($_GET['corp']) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $al = new eveAssetList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $al->load(true);
                $fullAssetList = $al->assets;
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $al = new eveAssetList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $al->load(true);
                $fullAssetList = $al->assets;
            }
        }

        if ($_GET['type'] == 'find') {
            return $this->findAssets($fullAssetList, $_GET['item']);
        } else if ($_GET['type'] == 'ships') {
            return $this->showShips($fullAssetList);
        } else {
            return $this->assetList($fullAssetList);
        }
    }

    function findAssets($assets, $search) {
        $searchResult = $this->searchAsset($assets, $search);
        usort($searchResult, array('assets', 'assetNameSort'));

        return $this->render('find', array(
            'assets' => objectToArray($searchResult),
            'search' => $search, 'corp' => $_GET['corp']
        ));
    }

    function showShips($assets) {
        $this->name .= ': My Ships';
        $ships = $this->searchAssetCategory($assets, 6);
        usort($ships, array('assets', 'assetNameSort'));

        for ($i = 0; $i < count($ships); $i++) {
            if ($ships[$i]->contents) {
                usort($ships[$i]->contents, array('assets', 'assetSlotSort'));

                $ships[$i]->high = array();
                $ships[$i]->mid = array();
                $ships[$i]->low = array();
                $ships[$i]->rigs = array();
                $ships[$i]->drones = array();
                foreach ($ships[$i]->contents as $asset) {
                    if ($asset->flag >= 11 && $asset->flag <= 18) {
                        $ships[$i]->low[] = $asset;
                    } else if ($asset->flag >= 19 && $asset->flag <= 26) {
                        $ships[$i]->mid[] = $asset;
                    } else if ($asset->flag >= 27 && $asset->flag <= 34) {
                        $ships[$i]->high[] = $asset;
                    } else if ($asset->flag >= 92 && $asset->flag <= 99) {
                        $ships[$i]->rigs[] = $asset;
                    } else if ($asset->flag == 87) {
                        if (isset($ships[$i]->drones[$asset->item->typeid])) {
                            $ships[$i]->drones[$asset->item->typeid]->qty += $asset->qty;
                        } else {
                            $ships[$i]->drones[$asset->item->typeid] = $asset;
                        }
                    }
                }

                $attr = eveDB::getInstance()->itemAttributes($ships[$i]->item->typeid);
                while (count($ships[$i]->low) < $attr['lowSlots']['valuefloat']) $ships[$i]->low[] = false;
                while (count($ships[$i]->mid) < $attr['medSlots']['valuefloat']) $ships[$i]->mid[] = false;
                while (count($ships[$i]->high) < $attr['hiSlots']['valuefloat']) $ships[$i]->high[] = false;
                if (count($ships[$i]->rigs) == 0) $ships[$i]->rigs = array(false, false, false); // 3 place-holder rigs
                if (count($ships[$i]->drones) == 0) $ships[$i]->drones = array(false); // place-holder drone
            }
        }

        $p = new Paginator($ships, 10, $_GET['p']);

        return $this->render('ships', array(
            'ships' => objectToArray($p->pageData),
            'pageCount' => $p->pageCount, 'pageNum' => $p->pageNum, 'nextPage' => $p->nextPage, 'prevPage' => $p->prevPage,
            'corp' => $_GET['corp'], 'search' => ''
        ));
    }

    function assetList($fullAssetList) {
        $assets = array();

        $allGroups = $this->getAssetGroups($fullAssetList);
        $groups = array();
        foreach ($allGroups as $g) {
            if (!in_array($g, $groups) && !empty($g->groupname)) {
                $groups[] = $g;
            }
        }
        usort($groups, array('assets', 'assetGroupSort'));

        if ($_GET['group'] > 0) {
            $this->filterAssetGroup($fullAssetList, $_GET['group']);
        }

        foreach ($fullAssetList as $asset) {
            if (!isset($asset->hide) || !$asset->hide) {
                if (!empty($asset->locationID)) {
                    $locationKey = (string) $asset->locationID;
                    if (!isset($assets[$locationKey])) {
                        $assets[$locationKey] = array();
                        $assets[$locationKey]['location'] = $asset->location;
                        $assets[$locationKey]['locationId'] = $asset->locationID;
                        $assets[$locationKey]['locationName'] = $asset->locationName;
                        $assets[$locationKey]['assets'] = array();
                    }
                    if ($asset->contents) {
                        usort($asset->contents, array('assets', 'assetSlotSort'));
                    }
                    $assets[$locationKey]['assets'][] = $asset;

                    usort($assets[$locationKey]['assets'], array('assets', 'assetSlotSort'));
                }
            }
        }
        usort($assets, array('assets', 'assetLocationSort'));

        foreach ($assets as $k => $v) {
            $ships = array();
            $containers = array();
            $shuttles = array();
            $items = array();

            usort($v['assets'], array('assets', 'assetNameSort'));

            foreach ($v['assets'] as $ass) {
                if ($ass->item->groupid == 31) {
                    $shuttles[] = $ass;
                } else if (($ass->item->group) && ($ass->item->group->category) && ($ass->item->group->category->categoryid == 6)) {
                    if ($ass->contents) {
                        usort($ass->contents, array('assets', 'assetSlotSort'));
                    }
                    $ships[] = $ass;
                } else if ($ass->contents) {
                    usort($ass->contents, array('assets', 'assetNameSort'));
                    $containers[] = $ass;
                } else {
                    $items[] = $ass;
                }
            }

            $assets[$k]['assets'] = array_merge($ships, $containers, $shuttles, $items);
        }

        $p = new Paginator($assets, 15, $_GET['p']);

        $groups = objectToArray($groups);

        return $this->render('assets', array(
            'assets' => objectToArray($p->pageData), 'groups' => $groups, 'group' => $_GET['group'],
            'pageCount' => $p->pageCount, 'pageNum' => $p->pageNum, 'nextPage' => $p->nextPage, 'prevPage' => $p->prevPage,
            'corp' => $_GET['corp'], 'search' => ''
        ));
    }

    // --- utility, sort and filtering methods follow

    function getAssetGroups($assets) {
        $result = array();

        foreach ($assets as $asset) {
            if ($asset->contents) {
                $result = array_merge($result, $this->getAssetGroups($asset->contents));
            }
            if (!in_array($asset->item->group, $result)) {
                $result[] = $asset->item->group;
            }
        }

        return $result;
    }

    function filterAssetGroup($assets, $groupId) {
        $removeCount = 0;
        foreach ($assets as $asset) {
            $asset->hide = false;
            if ($asset->item->groupid != $groupId && !$asset->contents) {
                $asset->hide = true;
                $removeCount++;
            } else if ($asset->contents) {
                if ($this->filterAssetGroup($asset->contents, $groupId) && $asset->item->groupid != $groupId) {
                    $asset->hide = true;
                    $removeCount++;
                }
            }
        }
        return $removeCount == count($assets);
    }

    function searchAsset($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->searchAsset($ass[$i]->contents, $search));
            }
            if ((stripos($ass[$i]->item->typename, $search) !== false) || (stripos($ass[$i]->locationName, $search) !== false)) {
                array_push($result, $ass[$i]);
            }
        }

        return $result;
    }

    function searchAssetCategory($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            $ass[$i]->item->getGroup();
            if (($ass[$i]->item->group) && ($ass[$i]->item->group->category) && ($ass[$i]->item->group->category->categoryid == $search)) {
                if (($search <> 6) || (($search == 6) && ($ass[$i]->item->groupid <> 31))) {     // nasty way to filter shuttles from the ships list
                    $result[] = $ass[$i];
                }
            }
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->searchAssetCategory($ass[$i]->contents, $search));
            }
        }

        return $result;
    }

    static function assetSlotSort($a, $b) {
        return ($a->flagText == $b->flagText) ? 0 : ($a->flagText < $b->flagText) ? -1 : 1;
    }

    static function assetNameSort($a, $b) {
        return ($a->item->typename == $b->item->typename) ? 0 : ($a->item->typename < $b->item->typename) ? -1 : 1;
    }

    static function assetGroupSort($a, $b) {
        return ($a->groupname == $b->groupname) ? 0 : ($a->groupname < $b->groupname) ? -1 : 1;
    }

    static function assetLocationSort($a, $b) {
        return ($a['locationName'] == $b['locationName']) ? 0 : ($a['locationName'] < $b['locationName']) ? -1 : 1;
    }

}

?>
