<?php

class starbases extends Plugin {

    var $name = 'Starbases';
    var $level = 1;

    function starbases($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_StarbaseList)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Starbases', '?module=starbases', 'starbases');
        }
    }

    function getContent() {
        if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
            if (!isset($_GET['p'])) {
                $_GET['p'] = 0;
            }

            if (eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_StarbaseList)) {
                $sl = new eveStarbaseList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $sl->load();
                $starbases = $sl->starbases;
            }

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

            $p = new Paginator($starbases, 10, $_GET['p']);

            $starbases = objectToArray($starbases, array('DBManager', 'eveDB'));

            return $this->render('starbases', array('starbases' => $p->pageData,
                        'pageCount' => $p->pageCount, 'pageNum' => $p->pageNum, 'nextPage' => $p->nextPage, 'prevPage' => $p->prevPage));
        } else {
            return '<h1>No corporation!</h1>';
        }
    }

}

?>