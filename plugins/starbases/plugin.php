<?php

    class starbases extends Plugin {
        var $name = 'Starbases';
        var $level = 1;

        function starbases($db, $site) {
            $this->Plugin($db, $site);

            if (($this->site->plugins['mainmenu']->hasGroup('corp')) 
                    && ($this->site->character->corpMember->hasRole('corpRoleDirector')
                        || $this->site->plugins['users']->hasForcedMenu('corpStarbases')))
                $this->site->plugins['mainmenu']->addLink('corp', 'Starbases', '?module=starbases', 'icon40_14');
        }

        function getContent() {
            if (!isset($_GET['p']))
                $_GET['p'] = 0;

            $this->site->character->corporation->loadStarbases();
            $starbases = $this->site->character->corporation->starbases;

            for ($i = 0; $i < count($starbases); $i++) {
                $starbases[$i]->setupFuelPricing();

                $fuelGroups = array();
                for ($j = 0; $j < count($starbases[$i]->fuelRequired); $j++) {
                    if (!isset($fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']])) {
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']] = array();
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['fuels'] = array();
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['valueHour'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volumeHour'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['valueCurrent'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volumeCurrent'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['value7Days'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volume7Days'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['value30Days'] = 0;
                        $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volume30Days'] = 0;
                    }
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['fuels'][] = $starbases[$i]->fuelRequired[$j];

                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['valueHour'] += $starbases[$i]->fuelRequired[$j]['resource']->pricing->avgSell * $starbases[$i]->fuelRequired[$j]['quantity'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volumeHour'] += $starbases[$i]->fuelRequired[$j]['resource']->volume * $starbases[$i]->fuelRequired[$j]['quantity'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['valueCurrent'] += $starbases[$i]->fuelRequired[$j]['current']['value'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volumeCurrent'] += $starbases[$i]->fuelRequired[$j]['current']['volume'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['value7Days'] += $starbases[$i]->fuelRequired[$j]['7days']['value'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volume7Days'] += $starbases[$i]->fuelRequired[$j]['7days']['volume'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['value30Days'] += $starbases[$i]->fuelRequired[$j]['30days']['value'];
                    $fuelGroups[$starbases[$i]->fuelRequired[$j]['purposetext']]['volume30Days'] += $starbases[$i]->fuelRequired[$j]['30days']['volume'];
                }

                $starbases[$i]->fuelGroups = $fuelGroups;
            }
 
            if (count($starbases) > 10) {
                $starbases = array_chunk($starbases, 10);

                $pageCount = count($starbases);
                $pageNum = max((int)$_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $starbases = $starbases[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $starbases = objectToArray($starbases, array('DBManager', 'eveDB'));

            return $this->render('starbases', array('starbases' => $starbases,
                                 'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage));
        }
    }

?>