<?php

    class util_prodprofit extends Plugin {
        var $name = 'Production Profitability';
        var $level = 1;

        function util_prodprofit($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('util', 'Production Profitability', '?module=util_prodprofit', 'util_prodprofit');
        }

        function productionCost($item, $meLevel, $region) {
            $tPerfect = 0;
            $tYou = 0;

            $item->getBlueprint(true);
            if ($item->blueprint) {
                for ($i = 0; $i < count($item->blueprint->materials); $i++) {
                    $prcAvgSell = 0;
                    if ($_GET['customprice']) {
                        for ($j = 0; $j < count($this->customPrices); $j++) {
                            if ($this->customPrices[$j]->typeid == $item->blueprint->materials[$i]['item']->typeid)
                                $prcAvgSell = $this->customPrices[$j]->price;
                        }
                    }

                    if (!$_GET['customprice'] || ($prcAvgSell == 0)) {
                        $item->blueprint->materials[$i]['item']->getPricing($region);
                        $prcAvgSell = $item->blueprint->materials[$i]['item']->pricing->avgSell;
                    }

                    if (isset($this->site->character->skills['3388']))
                        $pe = $this->site->character->skills['3388']->level;
                    else
                        $pe = 0;
                    $peFactor = 1.25 - (0.05 * $pe);
                    $meFactor = $item->blueprint->wastefactor / (1 + $meLevel);

                    $item->blueprint->materials[$i]['waste'] = floor($item->blueprint->materials[$i]['quantity'] * ($meFactor / 100));
                    $item->blueprint->materials[$i]['qty_perfect'] = $item->blueprint->materials[$i]['quantity'] + $item->blueprint->materials[$i]['waste'];
                    $item->blueprint->materials[$i]['qty_you'] = floor($item->blueprint->materials[$i]['qty_perfect'] * $peFactor);

                    $item->blueprint->materials[$i]['price_perfect'] = $item->blueprint->materials[$i]['qty_perfect'] * $prcAvgSell;
                    $item->blueprint->materials[$i]['price_you'] = $item->blueprint->materials[$i]['qty_you'] * $prcAvgSell;

                    $tPerfect += $item->blueprint->materials[$i]['price_perfect'];
                    $tYou += $item->blueprint->materials[$i]['price_you'];
                }

                for ($i = 0; $i < count($item->blueprint->extraMaterials); $i++) {
                    $item->blueprint->extraMaterials[$i]['item']->getPricing($region);

                    $qtyScale = $item->blueprint->extraMaterials[$i]['quantity'] * $item->blueprint->extraMaterials[$i]['damageperjob'];

                    $tPerfect += $item->blueprint->extraMaterials[$i]['item']->pricing->avgSell * $qtyScale;
                    $tYou += $item->blueprint->extraMaterials[$i]['item']->pricing->avgSell * $qtyScale;
                }
            }

            return array('perfect' => $tPerfect, 'you' => $tYou);
        }

        function getContent() {
            if (!isset($_GET['p']))
                $_GET['p'] = 0;
            if (!isset($_GET['group']))
                $_GET['group'] = 0;
            if (!isset($_GET['region']))
                $_GET['region'] = 0;
            if (!isset($_GET['customprice']))
                $_GET['customprice'] = 0;
            if (!isset($_GET['meLevel']))
                $_GET['meLevel'] = 0;
            if (!isset($_GET['corp']))
                $_GET['corp'] = 0;
            
            $db = $this->site->eveAccount->db;

            $bps = array();

            $group = $_GET['group'];
            $region = $_GET['region'];
            $meLevel = $_GET['meLevel'];

            if ($_GET['customprice'])
                $this->customPrices = $this->site->user->get_mineralprice_list();

            if ($_GET['corp'] > 0) {
                $this->site->character->corporation->loadAssets();
                $fullAssetList = $this->site->character->corporation->assets;
            } else {
                $this->site->character->loadAssets();
                $fullAssetList = $this->site->character->assets;
            }
            $allBlueprints = $this->blueprintAssets($fullAssetList, 9);

            $groups = array();
            $blueprints = array();
            for ($i = 0; $i < count($allBlueprints); $i++) {
                if (!isset($groups[$allBlueprints[$i]->item->group->groupid])) {
                    $groups[$allBlueprints[$i]->item->group->groupid] = $allBlueprints[$i]->item->group->groupname;
                }
                if (($group == 0) || (($group > 0) && ($allBlueprints[$i]->item->group->groupid == $group)))
                    $blueprints[] = $allBlueprints[$i];
            }

            if (count($blueprints) > 20) {
                $blueprints = array_chunk($blueprints, 20);

                $pageCount = count($blueprints);
                $pageNum = max((int)$_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $blueprints = $blueprints[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            
            for ($i = 0; $i < count($blueprints); $i++) {
                $item = $db->eveItemFromBlueprintType($blueprints[$i]->item->typeid);
                $item->getPricing($region);
                $prod = $this->productionCost($item, $meLevel, $region);

                $bps[] = array('item' => objectToArray($item, array('DBManager', 'eveDB')), 'production' => $prod);
            }

            $regions = $db->regionList();

            return $this->render('prodprofit', 
                                    array(  'items' => $bps, 
                                            'groups' => $groups,
                                            'group' => $group,
                                            'region' => $region,
                                            'regions' => $regions,
                                            'customprice' => $_GET['customprice'],
                                            'meLevel' => $meLevel, 
                                            'hasCorp' => $this->site->plugins['mainmenu']->hasLink('corp', 'Assets'), 'corp' => $_GET['corp'],
                                            'pageCount' => $pageCount, 'pageNum' => $pageNum, 
                                            'nextPage' => $nextPage, 'prevPage' => $prevPage));
        }

        function blueprintAssets($ass, $categoryid) {
            $result = array();

            for ($i = 0; $i < count($ass); $i++) {
                if ($ass[$i]->contents)
                    $result = array_merge($result, $this->blueprintAssets($ass[$i]->contents, $categoryid));
                $ass[$i]->item->getGroup();
                if (($ass[$i]->item->group) && ($ass[$i]->item->group->category) && ($ass[$i]->item->group->category->categoryid == $categoryid))
                    $result[] = $ass[$i];
            }

            return $result;
        }
    }
?>