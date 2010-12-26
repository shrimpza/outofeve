<?php

    class util_mining extends Plugin {
        var $name = 'Mining Calculator';
        var $level = 1;

        function util_mining($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('util', 'Mining Calculator', '?module=util_mining', 'icon12_08');
        }

        function getContent() {
            $db = $this->site->eveAccount->db;
            $items = array();

            if (!isset($_POST['region']))
                $_POST['region'] = 0;
            if (!isset($_POST['qty']))
                $_POST['qty'] = 1;
            if (!isset($_POST['upgradeqty']))
                $_POST['upgradeqty'] = 0;
            if (!isset($_POST['droneqty']))
                $_POST['droneqty'] = 0;
            if (!isset($_POST['minetime']))
                $_POST['minetime'] = 60;
            if (!isset($_POST['shipbonus']))
                $_POST['shipbonus'] = 0;
            if (!isset($_POST['station']))
                $_POST['station'] = 50;
            if (!isset($_POST['miner']))
                $_POST['miner'] = 0;
            if (!isset($_POST['drone']))
                $_POST['drone'] = 0;
            if (!isset($_POST['roid']))
                $_POST['roid'] = 0;
            if (!isset($_POST['upgrade']))
                $_POST['upgrade'] = 0;

            $region = $_POST['region'];

            $droneTotalAmount = 0;
            $totalCycle = 0;
            $totalAmount =  0;
            $totalBatches = 0;
            $totalBuyValue = 0;
            $totalSellValue = 0;
            $wasteFactor = 0;
            $minerals = array();
            $asteroid = null;

            if ($_POST['miner']) {
                $miner = $db->eveItem($_POST['miner']);
                $upgrade = $db->eveItem($_POST['upgrade']);
                if ($miner) {
                    $roid = $db->eveItem($_POST['roid']);
                    $roid->getPricing($region);
                    if ($roid) {
                        $miningAmount = $db->db->QueryA('SELECT i.typeName, a.valueInt, a.valueFloat, t.attributeName'
                                                        . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t '
                                                        . ' WHERE i.typeID = ? '
                                                        . ' AND i.typeId = a.typeId '
                                                        . ' AND a.attributeId = t.attributeId'
                                                        . ' AND t.attributeName = "miningAmount"', array($miner->typeid));
                        if ($miningAmount)
                            $miningAmount = $miningAmount[0]['valueint'];

                        $harvesterUpgradeBonus = 0;
                        $miningUpgradeBonus = 0;
                        if ($_POST['upgradeqty'] > 0) {
                            $upgradeBonus = $db->db->QueryA('SELECT i.typeName, a.valueInt, a.valueFloat, t.attributeName'
                                                            . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t '
                                                            . ' WHERE i.typeID = ?'
                                                            . ' AND i.typeId = a.typeId '
                                                            . ' AND a.attributeId = t.attributeId'
                                                            . ' AND (t.attributeName = "miningAmountBonus" '
                                                            . '   or t.attributeName = "iceHarvestCycleBonus")', array($upgrade->typeid));
                            if ($upgradeBonus) {
                                if ($upgradeBonus[0]['attributename'] == 'miningAmountBonus')
                                    $miningUpgradeBonus = 1 + ($upgradeBonus[0]['valueint'] / 100);
                                else if ($upgradeBonus[0]['attributename'] == 'iceHarvestCycleBonus')
                                    $harvesterUpgradeBonus = 1 + ($upgradeBonus[0]['valuefloat'] / 100);
                            }

                            if ($miningUpgradeBonus != 0)
                                for ($i = 1; $i <= $_POST['upgradeqty']; $i++)
                                    $miningAmount *= $miningUpgradeBonus;
                        }

                        if (isset($this->site->character->skills['3386']))
                            $miningBonus = 1 + ($this->site->character->skills['3386']->level * 0.05);
                        else
                            $miningBonus = 1;
                            
                        if (isset($this->site->character->skills['3410']))
                            $astrogeoBonus = 1 + ($this->site->character->skills['3410']->level * 0.05);
                        else
                            $astrogeoBonus = 1;

                        $miningAmount = $miningAmount * $miningBonus * $astrogeoBonus * (1+($_POST['shipbonus']/100));
                        $miningAmount = $miningAmount / $roid->volume;
                        
                        $duration = $db->db->QueryA('SELECT i.typeName, a.valueInt, a.valueFloat, t.attributeName'
                                                        . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t '
                                                        . ' WHERE i.typeID = ? '
                                                        . ' AND i.typeId = a.typeId '
                                                        . ' AND a.attributeId = t.attributeId'
                                                        . ' AND t.attributeName = "duration"', array($miner->typeid));
                        if ($duration)
                            $duration = $duration[0]['valuefloat'] / 1000;

                        if (($_POST['upgradeqty'] > 0) && ($harvesterUpgradeBonus != 0)) {
                            if ($miner->groupid == 464)
                                for ($i = 1; $i <= $_POST['upgradeqty']; $i++)
                                    $duration *= $harvesterUpgradeBonus;
                        }

                        if ($_POST['droneqty'] > 0) {
                            $drone = $db->eveItem($_POST['drone']);
                            if ($drone) {
                                $droneMiningAmount = $db->db->QueryA('SELECT i.typeName, a.valueInt, a.valueFloat, t.attributeName'
                                                                . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t '
                                                                . ' WHERE i.typeID = ? '
                                                                . ' AND i.typeId = a.typeId '
                                                                . ' AND a.attributeId = t.attributeId'
                                                                . ' AND t.attributeName = "miningAmount"', array($drone->typeid));
                                if ($droneMiningAmount)
                                    $droneMiningAmount = $droneMiningAmount[0]['valueint'];

                                if (isset($this->site->character->skills['3438']))
                                    $droneMiningBonus = 1 + ($this->site->character->skills['3438']->level * 0.05);
                                else
                                    $droneMiningBonus = 1;

                                $droneMiningAmount *= $droneMiningBonus;
                                $droneMiningAmount = $droneMiningAmount / $roid->volume;

                                $droneDuration = $db->db->QueryA('SELECT i.typeName, a.valueInt, a.valueFloat, t.attributeName'
                                                                . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t '
                                                                . ' WHERE i.typeID = ? '
                                                                . ' AND i.typeId = a.typeId '
                                                                . ' AND a.attributeId = t.attributeId'
                                                                . ' AND t.attributeName = "duration"', array($drone->typeid));
                                if ($droneDuration)
                                    $droneDuration = ($droneDuration[0]['valuefloat'] / 1000) + 30;

                                $droneTotalAmount = floor(($_POST['minetime'] / $droneDuration) * $droneMiningAmount) * $_POST['droneqty'];
                            }
                        }

                        $singleCycle = floor($_POST['minetime'] / $duration);
                        $singleAmount = $singleCycle * $miningAmount;
                        $singleBatches = floor($singleAmount / $roid->portionsize);
                        
                        $totalCycle = $singleCycle * $_POST['qty'];
                        $totalAmount =  ($singleAmount * $_POST['qty']) + $droneTotalAmount;
                        $totalBatches = floor(($totalAmount / $roid->portionsize));

                        $mins = $db->db->QueryA('select mineralTypeID, amountPerBatch from invOreReprocessing where oreTypeID = ?', array($roid->typeid));
                        $minerals = array();

                        if (isset($this->site->character->skills['3385']))
                            $refiningBonus = 1 + ($this->site->character->skills['3385']->level * 0.02);
                        else
                            $refiningBonus = 1;

                        if (isset($this->site->character->skills['3389']))
                            $refineryBonus = 1 + ($this->site->character->skills['3389']->level * 0.04);
                        else
                            $refineryBonus = 1;

                        $wasteFactor = ($_POST['station']/100) + 0.375 * $refiningBonus * $refineryBonus;

                        if ($region < 0)
                            $customPrices = $this->site->user->get_mineralprice_list();

                        for ($i = 0; $i < count($mins); $i++) {
                            $newMin = $db->eveItem($mins[$i]['mineraltypeid']);

                            $prcAvgBuy = 0;
                            $prcAvgSell = 0;
                            if ($region > -1) {
                                $newMin->getPricing($region);
                                $prcAvgBuy = $newMin->pricing->avgBuy;
                                $prcAvgSell = $newMin->pricing->avgSell;
                            } else {
                                for ($j = 0; $j < count($customPrices); $j++) {
                                    if ($customPrices[$j]->typeid == $newMin->typeid) {
                                        $prcAvgBuy = $customPrices[$j]->price;
                                        $prcAvgSell = $customPrices[$j]->price;
                                    }
                                }
                            }


                            $minerals[] = array('item' => objectToArray($newMin, array('DBManager', 'eveDB')), 
                                                'qty' => $mins[$i]['amountperbatch'] * $wasteFactor * $totalBatches,
                                                'buyvalue' => ($mins[$i]['amountperbatch'] * $wasteFactor * $totalBatches) * $prcAvgBuy,
                                                'sellvalue' => ($mins[$i]['amountperbatch'] * $wasteFactor * $totalBatches) * $prcAvgSell);
                            $totalBuyValue += ($mins[$i]['amountperbatch'] * $wasteFactor * $totalBatches) * $prcAvgBuy;
                            $totalSellValue += ($mins[$i]['amountperbatch'] * $wasteFactor * $totalBatches) * $prcAvgSell;
                        }

                        $asteroid = objectToArray($roid, array('DBManager', 'eveDB'));
                    }
                }
            }

            $regions = $db->regionList();
            $miners = $db->db->QueryA('select typeID, typeName from invTypes where marketGroupId in (1038, 1039, 1040) and published > 0 order by typeName', array());
            $upgrades = $db->db->QueryA('select typeID, typeName from invTypes where groupID = 546 and published > 0 order by typeName', array());
            $drones = $db->db->QueryA('select typeID, typeName from invTypes where groupID in (101) and published > 0 order by typeName', array());
            $roids = $db->db->QueryA('SELECT distinct(i.typeId), i.typeName FROM invOreReprocessing r INNER JOIN invTypes i ON i.typeId = r.oreTypeId', array());

            return $this->render('mining', 
                                    array(  'miner' => $_POST['miner'], 
                                            'miners' => $miners,
                                            'qty' => $_POST['qty'],
                                            'upgrade' => $_POST['upgrade'],
                                            'upgrades' => $upgrades,
                                            'upgradeqty' => $_POST['upgradeqty'],
                                            'drone' => $_POST['drone'],
                                            'drones' => $drones,
                                            'droneqty' => $_POST['droneqty'],
                                            'minetime' => $_POST['minetime'],
                                            'station' => $_POST['station'],
                                            'shipbonus' => $_POST['shipbonus'],
                                            'roid' => $_POST['roid'],
                                            'roids' => $roids,
                                            'region' => $region,
                                            'regions' => $regions,
                                            'asteroid' => $asteroid,
                                            'minerals' => $minerals,
                                            'minetotals' => array('totalCycle' => $totalCycle, 'totalAmount' => $totalAmount, 'totalBatches' => $totalBatches, 'droneTotalAmount' => $droneTotalAmount),
                                            'valuetotals' => array('totalBuyValue' => $totalBuyValue, 'totalSellValue' => $totalSellValue),
                                            'wastefactor' => $wasteFactor*100,
                                            'batchsize' => $_POST['roid'] > 0 ? $roid->portionsize : 0,
                                        ));
        }
    }
?>