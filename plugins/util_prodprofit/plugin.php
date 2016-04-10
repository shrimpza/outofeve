<?php

class util_prodprofit extends Plugin {

    var $name = 'Production Profitability';
    var $level = 1;

    function util_prodprofit($db, $site) {
        $this->Plugin($db, $site);

        $this->site->plugins['mainmenu']->addLink('util', 'Production Profitability', '?module=util_prodprofit', 'util_prodprofit');
    }

    function productionCost($blueprint, $item, $region) {
        $total = 0;

        $item->getBlueprint(true);
        if ($item->blueprint) {
            for ($i = 0; $i < count($item->blueprint->materials); $i++) {
                $prcAvgSell = 0;
                if ($_GET['customprice']) {
                    for ($j = 0; $j < count($this->customPrices); $j++) {
                        if ($this->customPrices[$j]->typeid == $item->blueprint->materials[$i]['item']->typeid) {
                            $prcAvgSell = $this->customPrices[$j]->price;
                        }
                    }
                }

                if (!$_GET['customprice'] || ($prcAvgSell == 0)) {
                    $item->blueprint->materials[$i]['item']->getPricing($region);
                    $prcAvgSell = $item->blueprint->materials[$i]['item']->pricing->avgSell;
                }

                $item->blueprint->materials[$i]['qty'] = $item->blueprint->materials[$i]['quantity']
                                                         - floor($item->blueprint->materials[$i]['quantity'] * ($blueprint->materialEfficiency / 100));
                $item->blueprint->materials[$i]['price'] = $item->blueprint->materials[$i]['qty'] * $prcAvgSell;

                $total += $item->blueprint->materials[$i]['price'];
            }

            for ($i = 0; $i < count($item->blueprint->extraMaterials); $i++) {
                $item->blueprint->extraMaterials[$i]['item']->getPricing($region);

                $qtyScale = $item->blueprint->extraMaterials[$i]['quantity'] * $item->blueprint->extraMaterials[$i]['damageperjob'];

                $total += $item->blueprint->extraMaterials[$i]['item']->pricing->avgSell * $qtyScale;
            }
        }

        return $total;
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }
        if (!isset($_GET['group'])) {
            $_GET['group'] = 0;
        }
        if (!isset($_GET['region'])) {
            $_GET['region'] = 0;
        }
        if (!isset($_GET['customprice'])) {
            $_GET['customprice'] = 0;
        }
        if (!isset($_GET['corp'])) {
            $_GET['corp'] = 0;
        }

        $bps = array();

        $group = $_GET['group'];
        $region = $_GET['region'];

        if ($_GET['customprice']) {
            $this->customPrices = $this->site->user->get_mineralprice_list();
        }

        if ($_GET['corp'] > 0) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $bpl = new eveBlueprintList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $bpl->load(true);
                $allBlueprints = $bpl->blueprints;
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $bpl = new eveBlueprintList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $bpl->load(true);
                $allBlueprints = $bpl->blueprints;
            }
        }

        $groups = array();
        $blueprints = array();
        for ($i = 0; $i < count($allBlueprints); $i++) {
            if (!isset($groups[$allBlueprints[$i]->item->group->groupid])) {
                $groups[$allBlueprints[$i]->item->group->groupid] = $allBlueprints[$i]->item->group->groupname;
            }
            if (($group == 0) || (($group > 0) && ($allBlueprints[$i]->item->group->groupid == $group))) {
                $blueprints[] = $allBlueprints[$i];
            }
        }

        $p = new Paginator($blueprints, 20, $_GET['p']);

        foreach ($p->pageData as $blueprint) {
            $item = eveDB::getInstance()->eveItemFromBlueprintType($blueprint->typeID);
            $item->getPricing($region);
            $cost = $this->productionCost($blueprint, $item, $region);

            $bps[] = array(
                    'bp' => objectToArray($blueprint),
                    'item' => objectToArray($item, array('DBManager', 'eveDB')),
                    'cost' => $cost);
        }

        $regions = eveDB::getInstance()->regionList();

        return $this->render('prodprofit', array('items' => $bps,
                    'groups' => $groups,
                    'group' => $group,
                    'region' => $region,
                    'regions' => $regions,
                    'customprice' => $_GET['customprice'],
                    'hasCorp' => $this->site->plugins['mainmenu']->hasLink('corp', 'Assets'), 'corp' => $_GET['corp'],
                    'pageCount' => $p->pageCount, 'pageNum' => $p->pageNum,
                    'nextPage' => $p->nextPage, 'prevPage' => $p->prevPage));
    }

}

?>