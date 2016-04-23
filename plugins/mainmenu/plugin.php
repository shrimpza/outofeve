<?php

class mainmenu extends Plugin {

    var $name = 'Main Menu';
    var $level = 1;
    var $links = array();

    function mainmenu($db, $site) {
        $this->Plugin($db, $site);

        if ($this->site->user && $this->site->user->id > 0) {
            // add menu group for users
            $this->addGroup('User', 'user', 'menu_user.png');

            // add the menu group for character stuff
            if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                    && eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $id = eveKeyManager::getKey($this->site->user->char_apikey_id)->selectedCharacter;
                $this->addGroup('Character', 'main', 'http://image.eveonline.com/Character/'.$id.'_32.jpg');
            }

            // add menu group for corp stuff
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                    && eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                // $this->addGroup('Corporation', 'corp', 'http://image.eveonline.com/Corporation/{$corp.corporationID}_32.png');
                $this->addGroup('Corporation', 'corp', 'menu_corp.png');
            }

            // similar again, since we want utils at the end of the menu, not before corp items
            if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                    && eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $this->addGroup('Utilities', 'util', 'menu_utils.png');
            }
        }
    }

    function getSideBox() {
        return $this->render('menu', array('links' => $this->links));
    }

    function addGroup($title, $name, $icon) {
        if (!isset($this->links[$name])) {
            $this->links[$name] = array();
            $this->links[$name]['links'] = array();
        }
        $this->links[$name]['title'] = $title;
        $this->links[$name]['icon'] = $icon;
    }

    function addLink($group, $title, $url, $icon = '', $ext = false) {
        if (!$ext) {
            $url = $GLOBALS['config']['site']['url'] . '/' . $url;
        }
        if (isset($this->links[$group])) {
            $this->links[$group]['links'][] = array('t' => $title, 'l' => $url, 'i' => $icon);
        }
    }

    function hasGroup($name) {
        return isset($this->links[$name]);
    }

    function hasLink($group, $title) {
        if (isset($this->links[$group])) {
            for ($i = 0; $i < count($this->links[$group]['links']); $i++) {
                if (strtolower($this->links[$group]['links'][$i]['t']) == strtolower($title)) {
                    return true;
                }
            }
        }
        return false;
    }

}

?>