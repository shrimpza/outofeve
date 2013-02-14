<?php

    class util_prices extends Plugin {
        var $name = 'Market Prices';
        var $level = 1;

        function util_prices($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('util', 'Market Prices', '?module=util_prices', 'util_prices');
        }

        function getContent() {
            if (!isset($_POST['region'])) {
                $_POST['region'] = 0;
            }
            if (!isset($_POST['items'])) {
                $_POST['items'] = '';
            }

            $items = array();

            $region = $_POST['region'];

            $_POST['items'] = trim(str_replace("\r\n", "\n", $_POST['items']));

            $eft = explode("\n", $_POST['items']);

            $tBase = 0;
            $tMin = 0;
            $tAvg = 0;

            for ($i = 0; $i < count($eft); $i++) {
                $name = explode(',', $eft[$i]);
                $name = mysql_escape_string(trim(stripslashes($name[0])));
                $name = str_replace(array('Drones_Active=', '['), '', $name);

                $newItem = eveDB::getInstance()->eveItem($name, true);
                if ($newItem) {
                    $newItem->getPricing($region);

                    $tBase += $newItem->baseprice;
                    $tMin += $newItem->pricing->minSell;
                    $tAvg += $newItem->pricing->avgSell;

                    $items[] = $newItem;
                }
            }

            $items = objectToArray($items, array('DBManager', 'eveDB'));

            $regions = eveDB::getInstance()->regionList();

            return $this->render('pricing', 
                                    array(  'items' => $items, 
                                            'region' => $region,
                                            'regions' => $regions,
                                            'totals' => array('base' => $tBase, 'min' => $tMin, 'avg' => $tAvg)));
        }
    }
?>