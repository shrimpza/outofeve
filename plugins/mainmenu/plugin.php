<?php

class mainmenu extends Plugin {

    var $name = 'Main Menu';
    var $level = 1;
    var $links = array();

    function mainmenu($db, $site) {
        $this->Plugin($db, $site);

        // add the menu group for character stuff
        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
            $this->addGroup('Character', 'main');
        }

        // add menu group for corp stuff
        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
            $this->addGroup('Corporation', 'corp');
        }

        // similar again, since we want utils at the end of the menu, not before corp items
        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
            $this->addGroup('Utilities', 'util');
        }
    }

    function getSideBox() {
        $smallicons = 0;
        if ($this->site->user) {
            $smallicons = $this->site->user->smallicons;
        }
        return $this->render('menu', array('links' => $this->links, 'smallicons' => $smallicons));
    }

    function addGroup($title, $name) {
        if (!isset($this->links[$name])) {
            $this->links[$name] = array();
            $this->links[$name]['links'] = array();
        }
        $this->links[$name]['title'] = $title;
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