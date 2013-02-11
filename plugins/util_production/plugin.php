<?php

    class util_production extends Plugin {
        var $name = 'Production Calculator';
        var $level = 1;

        function util_production($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('util', 'Production Cost', '?module=util_production', 'util_production');
        }

        function getContent() {
            if (!isset($_POST['region']))
                $_POST['region'] = 0;
            if (!isset($_POST['meLevel']))
                $_POST['meLevel'] = 0;
            if (!isset($_POST['customprice']))
                $_POST['customprice'] = 0;
            if (!isset($_POST['item']))
                $_POST['item'] = '';

            $db = $this->site->eveAccount->db;
            $items = array();

            $region = $_POST['region'];
            $meLevel = $_POST['meLevel'];

            $tPerfect = 0;
            $tYou = 0;

            if (!empty($_POST['item'])) {
                $_POST['item'] = mysql_escape_string(trim(stripslashes($_POST['item'])));

                $item = $db->eveItem($_POST['item'], true);
                if ($item) {
                    if ($_POST['customprice'])
                        $customPrices = $this->site->user->get_mineralprice_list();

                    $_POST['item'] = $item->typename;
                    $item->getBlueprint();
                    $item->getPricing($region);
                    if ($item->blueprint) {
                        for ($i = 0; $i < count($item->blueprint->materials); $i++) {
                            $prcAvgSell = 0;
                            if ($_POST['customprice']) {
                                for ($j = 0; $j < count($customPrices); $j++) {
                                    if ($customPrices[$j]->typeid == $item->blueprint->materials[$i]['item']->typeid)
                                        $prcAvgSell = $customPrices[$j]->price;
                                }
                            }

                            if (!$_POST['customprice'] || ($prcAvgSell == 0)) {
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
                            $item->blueprint->extraMaterials[$i]['qtyscale'] = $qtyScale;

                            $tPerfect += $item->blueprint->extraMaterials[$i]['item']->pricing->avgSell * $qtyScale;
                            $tYou += $item->blueprint->extraMaterials[$i]['item']->pricing->avgSell * $qtyScale;
                        }
                    }
                    $item = objectToArray($item, array('DBManager', 'eveDB'));
                }
            }
            else
                $item = false;

            $regions = $db->regionList();

            return $this->render('production', 
                                    array(  'item' => $_POST['item'], 
                                            'region' => $region,
                                            'regions' => $regions,
                                            'proditem' => $item,
                                            'customprice' => $_POST['customprice'],
                                            'meLevel' => $meLevel,
                                            'totals' => array('perfect' => $tPerfect, 'you' => $tYou)));
        }
    }
?>