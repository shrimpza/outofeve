<?php

    class corporation extends Plugin {
        var $name = 'Corporation';
        var $level = 1;

        function corporation($db, $site) {
            $this->Plugin($db, $site);

            if ($this->site->plugins['mainmenu']->hasGroup('corp') || $this->site->plugins['users']->hasForcedMenu('corpSheet'))
                $this->site->plugins['mainmenu']->addLink('corp', 'Corporation', '?module=corporation', 'icon07_06');
        }

        function getContent() {
            if ($this->site->character->corporation) {
                $this->site->character->corporation->loadMembers(true);

                $corp = objectToArray($this->site->character->corporation, array('DBManager', 'eveDB', 'eveCharacter'));

                // no need to duplicate member list, it's not used again
                unset($corp['members']);

                if (!isset($_POST['active']))
                    $_POST['active'] = 4;
                if (!isset($_POST['system']))
                    $_POST['system'] = 0;
                if (!isset($_POST['title']))
                    $_POST['title'] = '';

                $activeDay = 86400;
                $activeWeek = $activeDay * 7;
                $activeMonth = $activeWeek * 4;
                $active3Months = $activeMonth * 3;

                $members = array();

                $active = array(array('min' => 0, 'max' => $activeWeek, 'count' => 0, 'name' => 'Last 7 days'), 
                                array('min' => $activeWeek, 'max' => $activeMonth, 'count' => 0, 'name' => 'Last month'), 
                                array('min' => $activeMonth, 'max' => $active3Months, 'count' => 0, 'name' => 'Last 3 months'), 
                                array('min' => $active3Months, 'max' => $active3Months*1000 /*lol*/, 'count' => 0, 'name' => 'Longer than 3 months'));
                $systems = array();
                $titles = array();

                foreach ($this->site->character->corporation->members as $member) {
                    if ($member->locationID) {
                        $sys = null;
                        if (isset($member->location->stationid)) {
                            $sys = $member->location->solarSystem;
                        } else if (isset($member->location->solarsystemid))
                            $sys = $member->location;

                        if ($sys) {
                            if (isset($systems[$sys->solarsystemid])) {
                                $systems[$sys->solarsystemid]['count'] ++;
                            } else {
                                $systems[$sys->solarsystemid] = array('name' => $sys->solarsystemname, 'count' => 1);
                            }
                        }
                    }
                    if ($member->title) {
                        if (isset($titles[$member->title])) {
                            $titles[$member->title] ++;
                        } else {
                            $titles[$member->title] = 1;
                        }
                    }
                    for ($i = 0; $i < count($active); $i++) {
                        if ((time()-$active[$i]['min'] >= ($member->logoffDateTime-$this->site->user->account->timeOffset)) 
                            && (time()-$active[$i]['max'] <= ($member->logoffDateTime-$this->site->user->account->timeOffset))) {
                            $member->activeTimeSlot = $i;
                            $active[$i]['count'] ++;
                        }
                    }

                    $n = array_push($members, $member);
                    if (($_POST['title'] <> '') && ($member->title != $_POST['title']))
                        array_pop($members);
                    else if (($_POST['system'] > 0) && ($sys->solarsystemid != $_POST['system']))
                        array_pop($members);
                    else if (($_POST['active'] < 4) && ($member->activeTimeSlot != $_POST['active']))
                        array_pop($members);
                }

                $members = objectToArray($members, array('DBManager', 'eveDB', 'eveCharacter'));

                return $this->render('corporation', array('corp' => $corp, 'members' => $members, 'systems' => $systems, 'titles' => $titles, 'active' => $active,
                                    'selSystem' => $_POST['system'], 'selTitle' => $_POST['title'], 'selActive' => $_POST['active']));
            } else
                return '<h1>No corporation!</h1>';
        }
    }
?>