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
            $assets = $this->searchAsset($fullAssetList, $_GET['item']);
            usort($assets, array('assets', 'assetNameSort'));

            $assetList = objectToArray($assets);

            return $this->render('find', array('assets' => $assetList, 'search' => $_GET['item'], 'corp' => $_GET['corp']));
        } else if ($_GET['type'] == 'ships') {
            $this->name .= ': My Ships';
            $ships = $this->searchAssetCategory($fullAssetList, 6);
            usort($ships, array('assets', 'assetNameSort'));
            for ($i = 0; $i < count($ships); $i++) {
                if ($ships[$i]->contents) {
                    usort($ships[$i]->contents, array('assets', 'assetSlotSort'));
                }
            }

            if (count($ships) > 10) {
                $ships = array_chunk($ships, 10);

                $pageCount = count($ships);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $ships = $ships[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $shipList = objectToArray($ships);

            return $this->render('ships', array('ships' => $shipList, 'pageCount' => $pageCount,
                        'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => $_GET['corp']));
        } else {
            $assets = array();

            $allGroups = $this->getAssetGroups($fullAssetList);
            $groups = array();
            foreach ($allGroups as $g) {
                if (!in_array($g, $groups, true)) {
                    $groups[] = $g;
                }
            }
            usort($groups, function ($a, $b) {
                return ($a->groupname == $b->groupname) ? 0 : ($a->groupname < $b->groupname) ? -1 : 1;
            });

            if ($_GET['group'] > 0) {
                $this->filterAssetGroup($fullAssetList, $_GET['group']);
            }

            foreach ($fullAssetList as $asset) {
                if (!$asset->hide) {
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
            usort($assets, function ($a, $b) {
                return ($a['locationName'] == $b['locationName']) ? 0 : ($a['locationName'] < $b['locationName']) ? -1 : 1;
            });

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

            if (count($assets) > 15) {
                $assets = array_chunk($assets, 15);

                $pageCount = count($assets);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $assets = $assets[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $assetList = objectToArray($assets);
            $groups = objectToArray($groups);

            return $this->render('assets', array('assets' => $assetList, 'groups' => $groups, 'group' => $_GET['group'],
                        'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage,
                        'corp' => $_GET['corp']));
        }
    }

    function getAssetGroups($ass) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->getAssetGroups($ass[$i]->contents));
            }
            if (!in_array($ass[$i]->item->group, $result)) {
                $result[] = $ass[$i]->item->group;
            }
        }

        return $result;
    }

    function filterAssetGroup($ass, $groupId) {
        $removeCount = 0;
        for ($i = 0; $i < count($ass); $i++) {
            $ass[$i]->hide = false;
            if ($ass[$i]->item->groupid != $groupId && !$ass[$i]->contents) {
                $ass[$i]->hide = true;
                $removeCount++;
            } else if ($ass[$i]->contents) {
                if ($this->filterAssetGroup($ass[$i]->contents, $groupId) && $ass[$i]->item->groupid != $groupId) {
                    $ass[$i]->hide = true;
                    $removeCount++;
                }
            }
        }
        return $removeCount == count($ass);
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

}

?>
