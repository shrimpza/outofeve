<?php

class assets extends Plugin {

    var $name = 'Assets';
    var $level = 1;

    function assets($db, $site) {
        $this->Plugin($db, $site);

        $this->site->plugins['mainmenu']->addLink('main', 'Assets', '?module=assets', 'icon07_13');

        if (($this->site->plugins['mainmenu']->hasGroup('corp'))
                && ($this->site->character->corpMember->hasRole('corpRoleDirector')
                || $this->site->plugins['users']->hasForcedMenu('corpAssets'))) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Assets', '?module=assets&corp=1', 'icon07_13');
        }
    }

    function getContent() {
        if (!isset($_GET['p']))
            $_GET['p'] = 0;

        if (isset($_GET['corp'])) {
            $this->site->character->corporation->loadAssets();
            $fullAssetList = $this->site->character->corporation->assets;
        } else {
            $al = new eveAssetList();
            $al->load($this->site->eveAccount, $this->site->character);
            $fullAssetList = $al->assets;
        }

        if (isset($_GET['type']) && ($_GET['type'] == 'find')) {
            $_GET['item'] = trim($_GET['item']);

            $assets = $this->searchAsset($fullAssetList, $_GET['item']);
            usort($assets, 'assetNameSort');

            $assetList = objectToArray($assets, array('DBManager', 'eveDB'));

            return $this->render('find', array('assets' => $assetList, 'search' => $_GET['item'], 'corp' => isset($_GET['corp'])));
        } else if (isset($_GET['type']) && ($_GET['type'] == 'ships')) {
            $this->name .= ': My Ships';
            $ships = $this->searchAssetCategory($fullAssetList, 6);
            usort($ships, 'assetNameSort');
            for ($i = 0; $i < count($ships); $i++)
                if ($ships[$i]->contents)
                    usort($ships[$i]->contents, 'assetSlotSort');


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

            $shipList = objectToArray($ships, array('DBManager', 'eveDB'));

            return $this->render('ships', array('ships' => $shipList, 'pageCount' => $pageCount,
                        'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
        } else {
            $assets = array();

            foreach ($fullAssetList as $asset) {
                if (!empty($asset->locationID)) {
                    if (!isset($assets[(string) $asset->locationID])) {
                        $assets[(string) $asset->locationID] = array();
                        $assets[(string) $asset->locationID]['location'] = $asset->location;
                        $assets[(string) $asset->locationID]['locationId'] = $asset->locationID;
                        $assets[(string) $asset->locationID]['locationName'] = $asset->locationName;
                        $assets[(string) $asset->locationID]['assets'] = array();
                    }
                    if ($asset->contents)
                        usort($asset->contents, 'assetSlotSort');
                    $assets[(string) $asset->locationID]['assets'][] = $asset;

                    usort($assets[(string) $asset->locationID]['assets'], 'assetSlotSort');
                }
            }
            usort($assets, 'assetStationSort');


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

            $assetList = objectToArray($assets, array('DBManager', 'eveDB'));

            return $this->render('assets', array('assets' => $assetList, 'pageCount' => $pageCount,
                        'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
        }
    }

    function searchAsset($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            if ($ass[$i]->contents)
                $result = array_merge($result, $this->searchAsset($ass[$i]->contents, $search));
            if ((stripos($ass[$i]->item->typename, $search) !== false) || (stripos($ass[$i]->locationName, $search) !== false))
                array_push($result, $ass[$i]);
        }

        return $result;
    }

    function searchAssetCategory($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            $ass[$i]->item->getGroup();
            if (($ass[$i]->item->group) && ($ass[$i]->item->group->category) && ($ass[$i]->item->group->category->categoryid == $search))
                if (($search <> 6) || (($search == 6) && ($ass[$i]->item->groupid <> 31)))      // nasty way to filter shuttles from the ships list
                    $result[] = $ass[$i];
            if ($ass[$i]->contents)
                $result = array_merge($result, $this->searchAssetCategory($ass[$i]->contents, $search));
        }

        return $result;
    }

}

function assetStationSort($a, $b) {
    if ($a['locationName'] == $b['locationName'])
        return 0;
    return ($a['locationName'] < $b['locationName']) ? -1 : 1;
}

function assetSlotSort($a, $b) {
    if ($a->flagText == $b->flagText)
        return 0;
    return ($a->flagText < $b->flagText) ? -1 : 1;
}

function assetNameSort($a, $b) {
    if ($a->item->typename == $b->item->typename)
        return 0;
    return ($a->item->typename < $b->item->typename) ? -1 : 1;
}

?>
