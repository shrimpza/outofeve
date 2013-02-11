<?php

class mail extends Plugin {

    var $name = 'Mail';
    var $level = 1;

    function mail($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_MailMessages)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Mail', '?module=mail', 'icon94_08');
        }
    }

    function getContent() {
        if (isset($_GET['notifications'])) {
            return $this->getNotifications();
        } else {
            return $this->getMail();
        }
    }

    function getMail() {
        if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
            $ml = new eveMailList(eveKeyManager::getKey($this->site->user->char_apikey_id));
            $ml->load();
            $mail = array();
            foreach ($ml->mail as $m) {
                if (isset($_GET['personal'])) {
                    if ($m->toCorpID == 0 && $m->toListID == 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else if (isset($_GET['corp'])) {
                    if ($m->toCorpID > 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else if (isset($_GET['lists'])) {
                    if ($m->toListID > 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else {
                    $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                }
            }
        }

        return $this->render('mail', array('mail' => $mail));
    }

    function getNotifications() {
        $nl = new eveNotificationsList(eveKeyManager::getKey($this->site->user->char_apikey_id));
        $nl->load();
        $notifications = objectToArray($nl->notifications, array('DBManager', 'eveDB'));
        return $this->render('notifications', array('mail' => $notifications));
    }

    function getContentJson() {
        $message = false;
        if (isset($_GET['messageID'])) {
            $ml = new eveMailList(eveKeyManager::getKey($this->site->user->char_apikey_id));
            $ml->load();
            $message = objectToArray($ml->getMessage($_GET['messageID']), array('DBManager', 'eveDB'));
            $message['headers']['sentDate'] = date('d M Y H:i', $message['headers']['sentDate']);
        } else if (isset($_GET['notificationID'])) {
            $nl = new eveNotificationsList(eveKeyManager::getKey($this->site->user->char_apikey_id));
            $nl->load();
            $message = objectToArray($nl->getNotification($m), array('DBManager', 'eveDB'));
            $message['text'] = $this->spiffyNotificationText($message['text']);
            $message['headers']['sentDate'] = date('d M Y H:i', $message['headers']['sentDate']);
        }
        return json_encode($message);
    }

    function spiffyNotificationText($text) {
        $lines = explode("\n", $text);
        $db = eveDB::getInstance();

        $newLines = array();

        for ($i = 0; $i < count($lines); $i++) {
            $useLine = true;
            $usePrefix = true;

            $str = $lines[$i];
            $pts = explode(": ", $str);

            if (strpos($pts[0], 'should') !== false) {
                if (in_array($pts[1], array('null', '0', 'no', 'false'))) {
                    $pts[1] = 'No';
                } else if (in_array($pts[1], array('1', 'yes', 'true'))) {
                    $pts[1] = 'Yes';
                }
            } else if (strpos($pts[0], 'Date') !== false) {
                // doesn't work... wtf are these date stamps
                //$pts[1] = date('d M Y H:i', substr($pts[1], 0, 10));
                $pts[1] = date('d M Y H:i', $pts[1]);
                echo $pts[1] . "\n";
            } else if ($pts[0] == 'header') {
                $useLine = false;
            } else if ($pts[0] == 'body') {
                $usePrefix = false;
            }

            if (substr($pts[0], strlen($pts[0]) - 2) != 'ID') {
                $pts[0] = ucwords(ereg_replace("([A-Z]|[0-9]+)", " \\0", $pts[0]));
            } else {
                // based on the type of ID we can look up a proper name, icon, etc
                if ($pts[0] == 'itemID') {
                    $useLine = false;
                } else if ($pts[0] == 'typeID') {
                    $pts[1] = $db->typeName($pts[1]);
                } else if ($pts[0] == 'solarSystemID') {
                    $pts[1] = $db->eveSolarSystem($pts[1])->solarsystemname;
                } else if ($pts[0] == 'charID' || $pts[0] == 'characterID'
                        || $pts[0] == 'corpID' || $pts[0] == 'corporationID') {
                    $pts[1] = characterName($pts[1]);
                }
                $pts[0] = ucwords(ereg_replace("([A-Z]|[0-9]+)", " \\0", substr($pts[0], 0, strlen($pts[0]) - 2)));
            }

            if ($useLine) {
                $newLines[] = $usePrefix ? implode(": ", $pts) : $pts[1];
            }
        }

        return implode("<br />", $newLines);
    }

}

?>
