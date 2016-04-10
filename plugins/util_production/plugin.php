<?php

class util_production extends Plugin {

    var $name = 'Production Calculator';
    var $level = 1;

    function util_production($db, $site) {
        $this->Plugin($db, $site);

        $this->site->plugins['mainmenu']->addLink('util', 'Production Cost', '?module=util_production', 'util_production');
    }

    function getContent() {
        if (!isset($_POST['region'])) {
            $_POST['region'] = 0;
        }
        if (!isset($_POST['meLevel'])) {
            $_POST['meLevel'] = 0;
        }
        if (!isset($_POST['customprice'])) {
            $_POST['customprice'] = 0;
        }
        if (!isset($_POST['item'])) {
            $_POST['item'] = '';
        }

        $region = $_POST['region'];
        $meLevel = $_POST['meLevel'];

        $tPerfect = 0;
        $tYou = 0;

        if (!empty($_POST['item'])) {

            $_POST['item'] = trim(stripslashes($_POST['item']));

            $item = eveDB::getInstance()->eveItem($_POST['item'], true);
            if ($item) {
                if ($_POST['customprice']) {
                    $customPrices = $this->site->user->get_mineralprice_list();
                }

                $_POST['item'] = $item->typename;
                $item->getBlueprint();
                $item->getPricing($region);
                if ($item->blueprint) {
                    for ($i = 0; $i < count($item->blueprint->materials); $i++) {
                        $prcAvgSell = 0;
                        if ($_POST['customprice']) {
                            for ($j = 0; $j < count($customPrices); $j++) {
                                if ($customPrices[$j]->typeid == $item->blueprint->materials[$i]['item']->typeid) {
                                    $prcAvgSell = $customPrices[$j]->price;
                                }
                            }
                        }

                        if (!$_POST['customprice'] || ($prcAvgSell == 0)) {
                            $item->blueprint->materials[$i]['item']->getPricing($region);
                            $prcAvgSell = $item->blueprint->materials[$i]['item']->pricing->avgSell;
                        }


                        $item->blueprint->materials[$i]['qty_perfect'] = $item->blueprint->materials[$i]['quantity']
                                                                 - floor($item->blueprint->materials[$i]['quantity'] * (10 / 100));
                        $item->blueprint->materials[$i]['qty_you'] = $item->blueprint->materials[$i]['quantity']
                                                                 - floor($item->blueprint->materials[$i]['quantity'] * ($meLevel / 100));

                        $item->blueprint->materials[$i]['price_perfect'] = $item->blueprint->materials[$i]['qty_perfect'] * $prcAvgSell;
                        $item->blueprint->materials[$i]['price_you'] = $item->blueprint->materials[$i]['qty_you'] * $prcAvgSell;

                        $tPerfect += $item->blueprint->materials[$i]['price_perfect'];
                        $tYou += $item->blueprint->materials[$i]['price_you'];
                    }
                }
                $item = objectToArray($item, array('DBManager', 'eveDB'));
            }
        } else {
            $item = false;
        }

        $regions = eveDB::getInstance()->regionList();

        return $this->render('production', array('item' => $_POST['item'],
                    'region' => $region,
                    'regions' => $regions,
                    'proditem' => $item,
                    'customprice' => $_POST['customprice'],
                    'meLevel' => $meLevel,
                    'totals' => array('perfect' => $tPerfect, 'you' => $tYou)));
    }

}

?>