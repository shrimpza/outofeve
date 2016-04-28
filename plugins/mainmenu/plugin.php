<?php

class mainmenu extends Plugin {

    var $name = 'Main Menu';
    var $level = 1;
    var $links = array();

    var $characterKey = null;
    var $corpKey = null;

    function mainmenu($db, $site) {
        $this->Plugin($db, $site);

        if ($this->site->user && $this->site->user->id > 0) {
            // add menu group for users
            $this->addGroup('User', 'user', 'menu_user.png');

            // add the menu group for character stuff
            $this->characterKey = eveKeyManager::getKey($this->site->user->char_apikey_id);
            if ($this->characterKey && $this->characterKey != null) {
                foreach ($this->characterKey->characters as $char) {
                  $this->addGroup($char->characterName, 'main_' . $char->characterID, 'http://image.eveonline.com/Character/' . $char->characterID . '_32.jpg');
                }
            }

            // add menu group for corp stuff
            $this->corpKey = eveKeyManager::getKey($this->site->user->corp_apikey_id);
            if ($this->corpKey && $this->corpKey != null) {
                foreach ($this->characterKey->characters as $char) {
                    $this->addGroup($char->corporationName, 'corp_' . $char->corporationID, 'http://image.eveonline.com/Corporation/' . $char->corporationID . '_32.png');
                }
            }

            // add menu group for utility modules
            if ($this->characterKey && $this->characterKey != null) {

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

    function addLink($group, $title, $url, $icon = '', $external = false) {
        if ($group == 'main' && $this->characterKey) {
            foreach ($this->characterKey->characters as $char) {
                $this->addLink('main_' . $char->characterID, $title, $this->addUrlParam($url, 'setChar=' . $char->characterID), $icon, $external);
            }
            return;
        }

        if ($group == 'corp' && $this->corpKey) {
            foreach ($this->corpKey->characters as $char) {
                $this->addLink('corp_' . $char->corporationID, $title, $this->addUrlParam($url, 'setCorpChar=' . $char->characterID), $icon, $external);
            }
            return;
        }

        if (!$external && $url) {
            $url = $GLOBALS['config']['site']['url'] . '/' . $url;
        }

        if (isset($this->links[$group])) {
            $this->links[$group]['links'][] = array('t' => $title, 'l' => $url, 'i' => $icon);
        }
    }

    function addUrlParam($url, $param) {
      if (strstr($url, '?') === false) {
        return $url . '?' . $param;
      } else {
        return $url . '&' . $param;
      }
    }
}

?>