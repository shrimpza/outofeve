<?php

    class kills extends Plugin {
        var $name = 'Kills';
        var $level = 1;

        function kills($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('main', 'Kills', '?module=kills', 'icon26_05');
            $this->site->plugins['mainmenu']->addLink('main', 'Deaths', '?module=kills&deaths=1', 'icon04_07');

            if (($this->site->plugins['mainmenu']->hasGroup('corp')) 
                    && ($this->site->character->corpMember->hasRole('corpRoleDirector')
                        || $this->site->plugins['users']->hasForcedMenu('corpKills'))) {
                $this->site->plugins['mainmenu']->addLink('corp', 'Kills', '?module=kills&corp=1', 'icon26_05');
                $this->site->plugins['mainmenu']->addLink('corp', 'Deaths', '?module=kills&deaths=1&corp=1', 'icon04_07');
            }
        }

        function getContent() {
            if (!isset($_GET['p']))
                $_GET['p'] = 0;
            if (!isset($_GET['find']))
                $_GET['find'] = '';

            if (isset($_GET['corp'])) {
                $this->site->character->corporation->loadKills();
                $killList = $this->site->character->corporation->kills;
                $deathList = $this->site->character->corporation->deaths;
            } else {
                $this->site->character->loadKills();
                $killList = $this->site->character->kills;
                $deathList = $this->site->character->deaths;
            }

            if (isset($_GET['deaths'])) {
                $this->name = 'Deaths';
                $deaths = array();
                for ($i = 0; $i < count($deathList); $i++) {
                    if (empty($_GET['find']) || $this->filterKill($deathList[$i], $_GET['find'])) {
                        $deathList[$i]->getDropValues();
                        $deaths[] = objectToArray($deathList[$i], array('DBManager', 'eveDB'));
                    }
                }

                if (count($deaths) > 10) {
                    $deaths = array_chunk($deaths, 10);

                    $pageCount = count($deaths);
                    $pageNum = max((int)$_GET['p'], 0);
                    $nextPage = min($pageNum + 1, $pageCount);
                    $prevPage = max($pageNum - 1, 0);

                    $deaths = $deaths[$pageNum];
                } else {
                    $pageCount = 0;
                    $pageNum = 0;
                    $nextPage = 0;
                    $prevPage = 0;
                }

                return $this->render('deaths', array('deaths' => $deaths, 'find' => $_GET['find'],
                                'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
            } else {
                $kills = array();
                for ($i = 0; $i < count($killList); $i++) {
                    if (empty($_GET['find']) || $this->filterKill($killList[$i], $_GET['find']))
                        $killList[$i]->getDropValues();
                        $kills[] = objectToArray($killList[$i], array('DBManager', 'eveDB'));
                }

                if (count($kills) > 10) {
                    $kills = array_chunk($kills, 10);

                    $pageCount = count($kills);
                    $pageNum = max((int)$_GET['p'], 0);
                    $nextPage = min($pageNum + 1, $pageCount);
                    $prevPage = max($pageNum - 1, 0);

                    $kills = $kills[$pageNum];
                } else {
                    $pageCount = 0;
                    $pageNum = 0;
                    $nextPage = 0;
                    $prevPage = 0;
                }

                return $this->render('kills', array('kills' => $kills, 'find' => $_GET['find'],
                                'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
            }
        }

        function filterKill($kill, $filter) {
            $accept = false;

            if (stripos($kill->victim->characterName, $filter) !== false)
                $accept = true;
            else if (stripos($kill->victim->corporationName, $filter) !== false)
                $accept = true;
            else if (stripos($kill->victim->allianceName, $filter) !== false)
                $accept = true;
            else if ($kill->victim->ship && (stripos($kill->victim->ship->typename, $filter) !== false))
                $accept = true;
            else if (stripos($kill->solarSystem->solarsystemname, $filter) !== false)
                $accept = true;

            if (!$accept) {
                for ($i = 0; $i < count($kill->attackers); $i++) {
                    if (stripos($kill->attackers[$i]->characterName, $filter) !== false)
                        $accept = true;
                    else if (stripos($kill->attackers[$i]->corporationName, $filter) !== false)
                        $accept = true;
                    else if (stripos($kill->attackers[$i]->allianceName, $filter) !== false)
                        $accept = true;
                    else if ($kill->attackers[$i]->ship && (stripos($kill->attackers[$i]->ship->typename, $filter) !== false))
                        $accept = true;
                }
            }

            return $accept;
        }
    }

?>