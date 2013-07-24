<?php

class corporation extends Plugin {

    var $name = 'Corporation';
    var $level = 1;

    function corporation($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id) && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_CorporationSheet)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Corporation', '?module=corporation', 'corp');
        }
    }

    function getContent() {
        if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
            $corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
            $corporation = new eveCorporation($corpKey);
            $corporation->load();

            return $this->corpSheet($corporation, $corpKey);
        } else {
            return '<h1>No corporation!</h1>';
        }
    }

    function corpSheet($corporation, $corpKey) {
        if ($corpKey->hasAccess(CORP_AccountBalance)) {
            $balances = new eveCorporationWalletList($corpKey);
            $balances->load($corporation->walletDivisions);

            $bals = objectToArray($balances);
        }

        if (!isset($_POST['active'])) {
            $_POST['active'] = 4;
        }
        if (!isset($_POST['system'])) {
            $_POST['system'] = 0;
        }
        if (!isset($_POST['title'])) {
            $_POST['title'] = '';
        }

        $activeDay = 86400;
        $activeWeek = $activeDay * 7;
        $activeMonth = $activeWeek * 4;
        $active3Months = $activeMonth * 3;

        $active = array(array('min' => 0, 'max' => $activeWeek, 'count' => 0, 'name' => 'Last 7 days'),
            array('min' => $activeWeek, 'max' => $activeMonth, 'count' => 0, 'name' => 'Last month'),
            array('min' => $activeMonth, 'max' => $active3Months, 'count' => 0, 'name' => 'Last 3 months'),
            array('min' => $active3Months, 'max' => $active3Months * 1000 /* lol */, 'count' => 0, 'name' => 'Longer than 3 months'));
        $systems = array();
        $titles = array();

        if ($corpKey->hasAccess(CORP_MemberTrackingExtended)) {
            $memberList = new eveCorporationMemberList($corpKey);
            $memberList->load();

            $members = array();

            foreach ($memberList->members as $member) {
                if ($member->locationID) {
                    $sys = null;
                    if (isset($member->location->stationid)) {
                        $sys = $member->location->solarSystem;
                    } else if (isset($member->location->solarsystemid)) {
                        $sys = $member->location;
                    }

                    if ($sys) {
                        if (isset($systems[$sys->solarsystemid])) {
                            $systems[$sys->solarsystemid]['count']++;
                        } else {
                            $systems[$sys->solarsystemid] = array('name' => $sys->solarsystemname, 'count' => 1);
                        }
                    }
                }
                if ($member->title) {
                    if (isset($titles[$member->title])) {
                        $titles[$member->title]++;
                    } else {
                        $titles[$member->title] = 1;
                    }
                }
                for ($i = 0; $i < count($active); $i++) {
                    if ((time() - $active[$i]['min'] >= ($member->logoffDateTime - $this->site->user->account->timeOffset)) && (time() - $active[$i]['max'] <= ($member->logoffDateTime - $this->site->user->account->timeOffset))) {
                        $member->activeTimeSlot = $i;
                        $active[$i]['count']++;
                    }
                }

                array_push($members, $member);
                if (($_POST['title'] <> '') && ($member->title != $_POST['title'])) {
                    array_pop($members);
                } else if (($_POST['system'] > 0) && ($sys->solarsystemid != $_POST['system'])) {
                    array_pop($members);
                } else if (($_POST['active'] < 4) && ($member->activeTimeSlot != $_POST['active'])) {
                    array_pop($members);
                }
            }

            $members = objectToArray($members);
        }

        return $this->render('corporation', array(
                    'corp' => objectToArray($corporation),
                    'balances' => $bals,
                    'members' => $members,
                    'systems' => $systems,
                    'titles' => $titles,
                    'active' => $active,
                    'selSystem' => $_POST['system'], 'selTitle' => $_POST['title'], 'selActive' => $_POST['active']));
    }

}

?>