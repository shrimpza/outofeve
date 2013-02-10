<?php

class users extends Plugin {

    var $name = '';
    var $forceMenus = array();

    function users($db, $site) {
        $this->Plugin($db, $site);

        if (isset($_POST['login'])) {
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                setcookie('user[username]', $_POST['username'], time() + (60 * 60 * 24 * 30), '/');
                setcookie('user[password]', md5($_POST['password']), time() + (60 * 60 * 24 * 30), '/');

                $_COOKIE['user']['username'] = $_POST['username'];
                $_COOKIE['user']['password'] = md5($_POST['password']);
            }
        } else if (isset($_GET['logout'])) {
            setcookie('user[username]', '', time() - (60 * 60 * 24 * 30), '/');
            setcookie('user[password]', '', time() - (60 * 60 * 24 * 30), '/');

            unset($_COOKIE['user']);
        }

        if (!empty($_COOKIE['user']['username']) && !empty($_COOKIE['user']['password'])) {
            $checkUser = $db->QueryA('select id from user where username = ? and password = ?', array($_COOKIE['user']['username'], $_COOKIE['user']['password']));
        } else {
            $checkUser = null;
        }

        // user is logged in
        if (is_array($checkUser)) {
            $this->site->user = $db->getObject('user', $checkUser[0]['id']);

            // the user is valid
            if ($this->site->user->id > 0) {

                // optional proxy
                if (trim($this->site->user->proxy) <> '') {
                    $GLOBALS['config']['eve']['api_url'] = trim($this->site->user->proxy);
                }

                // timezone adjustment
                $tz = new DateTimeZone($this->site->user->timezone);
                $dt = new DateTime("now", $tz);
                $timeOffset = $tz->getOffset($dt) / 3600;
                eveTimeOffset::$offset = $timeOffset;

                if (isset($_POST['theme'])) {
                    $tmpTheme = $_POST['theme'];
                } else {
                    $tmpTheme = $this->site->user->theme;
                }

                if (!empty($tmpTheme) && (is_dir($GLOBALS['config']['templates']['theme_dir'] . '/' . $tmpTheme))) {
                    $GLOBALS['config']['templates']['theme'] = $tmpTheme;
                }

                $this->loadApiKeys();

                if (isset($_GET['setCharKey'])) {
                    $key = $db->getObject('apikey', $_GET['setCharKey'], 'keyid');
                    if ($key->user_id != $this->site->user->id) {
                        echo '<div class="apierror">Selected key does not belong to you!</div>';
                    } else {
                        $this->site->user->char_apikey_id = $key->id;
                    }
                }

                if (isset($_GET['setCorpKey'])) {
                    $key = $db->getObject('apikey', $_GET['setCorpKey'], 'keyid');
                    if ($key->user_id != $this->site->user->id) {
                        echo '<div class="apierror">Selected key does not belong to you!</div>';
                    } else {
                        $this->site->user->corp_apikey_id = $key->id;
                    }
                }

                if (isset($_GET['setChar'])) {
                    $key = $db->getObject('apikey', $this->site->user->char_apikey_id);
                    $key->character_id = $_GET['setChar'];
                    $key->save();

                    eveKeyManager::getInstance()->keys[$key->id]->selectedCharacter = $key->character_id;
                }

                if (isset($_GET['setCorpChar'])) {
                    $key = $db->getObject('apikey', $this->site->user->corp_apikey_id);
                    $key->character_id = $_GET['setCorpChar'];
                    $key->save();

                    eveKeyManager::getInstance()->keys[$key->id]->selectedCharacter = $key->character_id;
                }

                // assign template variables to allow selecting of character and corporation keys and characters
                $this->site->tplVars['charKeys'] = objectToArray(eveKeyManager::getCharacterKeys());
                $this->site->tplVars['corpKeys'] = objectToArray(eveKeyManager::getCorporateKeys());
                $this->site->tplVars['currentCharKey'] = objectToArray(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $this->site->tplVars['currentCorpKey'] = objectToArray(eveKeyManager::getKey($this->site->user->corp_apikey_id));

                $this->site->user->activetime = date('Y-m-d H:i:s');
                $this->site->user->save();
            }
        } else {
            $this->site->user = $db->getObject('user', 0);
        }
    }

    function loadApiKeys() {
        $keys = $this->site->user->get_apikey_list('name');
        foreach ($keys as $key) {
            eveKeyManager::addKey($key->id, $key->name, $key->keyid, decryptKey($key->vcode), $key->character_id);
        }

        if ($this->site->user->char_apikey_id == 0) {
            $charKeys = eveKeyManager::getCharacterKeys();
            if ($charKeys) {
                $this->site->user->char_apikey_id = $charKeys[0]->reference;
            }
        }

        if ($this->site->user->corp_apikey_id == 0) {
            $corpKeys = eveKeyManager::getCorporateKeys();
            if ($corpKeys) {
                $this->site->user->corp_apikey_id = $corpKeys[0]->reference;
            }
        }
    }

    function getSideBox() {
        if ($this->site->user->id == 0) {
            return $this->render('side_login', array('register' => $GLOBALS['config']['site']['registration']));
        } else {
            return $this->render('side_logged_in', array('user' => $this->site->user->row, 'noKeys' => count(eveKeyManager::getInstance()->keys) == 0));
        }
    }

    function getContent() {
        if (!isset($_GET['mode'])) {
            $_GET['mode'] = 'welcome';
        }

        switch ($_GET['mode']) {
            case 'register':
                $this->name = 'User Registration';
                return $this->register();
            case 'accounts':
                $this->name = 'Eve API Keys';
                return $this->keys();
            case 'edit':
                $this->name = 'Preferences';
                return $this->edit();
            default:
                return $this->welcome();
        }
    }

    function register() {
        if (!$GLOBALS['config']['site']['registration']) {
            return '<h1>New user registrations are disabled!</h1>';
        } else {
            $_POST['username'] = trim($_POST['username']);
            $_POST['password'] = trim($_POST['password']);

            if (empty($_POST['username']) || empty($_POST['password'])) {
                return $this->render('register', array());
            } else {
                $user = $this->db->getObject('user', $_POST['username'], 'username');
                if ($user->id > 0) {
                    return $this->render('register', array('error' => array('message' => 'User name ' . $_POST['username'] . ' already in use.')));
                } else {
                    $user->username = $_POST['username'];
                    $user->password = md5($_POST['password']);
                    $user->email = $_POST['email'] . ' ';
                    $user->level = 1;
                    $user->theme = $GLOBALS['config']['templates']['theme'];
                    $user->timezone = 'GMT';
                    $user->account_id = 0;
                    $user->proxy = ' ';
                    $user->activetime = date('Y-m-d H:i:s');
                    $user->smallicons = 0;
                    $user->save();
                    return $this->render('register_ok', array('name' => $_POST['username']));
                }
            }
        }
    }

    function edit() {
        if ($this->site->user->id == 0) {
            return '<h2>DENIED!</h2>';
        }

        if (isset($_POST['save'])) {
            if (!empty($_POST['password'])) {
                $this->site->user->password = md5($_POST['password']);
            }
            $this->site->user->email = $_POST['email'];
            $this->site->user->theme = $_POST['theme'];
            $this->site->user->proxy = $_POST['proxy'];
            $this->site->user->timezone = $_POST['timezone'];
            $this->site->user->smallicons = max(0, $_POST['smallicons']);
            $this->site->user->save();

            $myMins = $this->site->user->get_mineralprice_list();
            if ($myMins) {
                for ($i = 0; $i < count($myMins); $i++) {
                    $myMins[$i]->delete();
                }
            }

            $res = eveDB::getInstance()->db->QueryA('select typeid from invTypes where groupid = 18 order by typeid', array());
            for ($i = 0; $i < count($res); $i++) {
                $newPrice = $this->db->getObject('mineralprice', 0);
                $newPrice->user_id = $this->site->user->id;
                $newPrice->typeid = $res[$i]['typeid'];
                $newPrice->price = str_replace(',', '', $_POST['min_' . $res[$i]['typeid']]);
                $newPrice->save();
            }
        }

        $zonelist = timezone_identifiers_list();
        $timezones = array();
        $i = 0;
        foreach ($zonelist as $zone) {
            $zoneParts = explode('/', $zone);
            if (in_array($zoneParts[0], array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'))) {
                $timezones[$i]['zone'] = $zone;
                $timezones[$i]['desc'] = str_replace('_', ' ', $zone);
                $i++;
            }
        }
        usort($timezones, 'tzSort');

        $themelist = scandir($GLOBALS['config']['templates']['theme_dir']);
        $themes = array();
        for ($i = 0; $i < count($themelist); $i++) {
            if (file_exists($GLOBALS['config']['templates']['theme_dir'] . '/' . $themelist[$i] . '/style.css')) {
                $themes[] = array('name' => $themelist[$i], 'desc' => ucwords($themelist[$i]));
            }
        }

        $myMins = $this->site->user->get_mineralprice_list();
        $mins = array();
        $res = eveDB::getInstance()->db->QueryA('select typeid from invTypes where groupid = 18 order by typeid', array());
        for ($i = 0; $i < count($res); $i++) {
            $newMin = eveDB::getInstance()->eveItem($res[$i]['typeid']);
            if ($myMins) {
                for ($j = 0; $j < count($myMins); $j++) {
                    if ($myMins[$j]->typeid == $newMin->typeid) {
                        $newMin->customPrice = $myMins[$j]->price;
                    }
                }
            }
            $mins[] = objectToArray($newMin, array('DBManager', 'eveDB'));
        }

        $vars = array();
        $vars['email'] = $this->site->user->email;
        $vars['timezone'] = $this->site->user->timezone;
        $vars['usertheme'] = $this->site->user->theme;
        $vars['timezones'] = $timezones;
        $vars['themes'] = $themes;
        $vars['proxy'] = $this->site->user->proxy;
        $vars['smallicons'] = $this->site->user->smallicons;
        $vars['custommins'] = $mins;

        return $this->render('edit', $vars);
    }

    function keys() {
        if ($this->site->user->id == 0) {
            return '<h2>DENIED!</h2>';
        }

        if (isset($_GET['delete'])) {
            $k = $this->db->getObject('apikey', $_GET['delete']);
            if (($k->id > 0) && ($k->user_id != $this->site->user->id)) {
                return $this->keysList("Selected API key doesn't belong to you!");
            }

            $k->delete();

            if ($this->site->user->char_apikey_id == $_GET['delete']) {
                $this->site->user->char_apikey_id = 0;
            } else if ($this->site->user->corp_apikey_id == $_GET['delete']) {
                $this->site->user->corp_apikey_id = 0;
            }
            $this->site->user->save();

            return $this->keysList();
        } else if (isset($_GET['edit'])) {
            $k = $this->db->getObject('apikey', $_GET['edit']);

            if (($k->id > 0) && ($k->user_id != $this->site->user->id)) {
                return $this->keysList("Selected API key doesn't belong to you!");
            }

            $k->vcode = decryptKey($k->vcode);
            $k = objectToArray($k, array('DBManager'));
            return $this->render('account_edit', array('k' => $k, 'error' => false));
        } else if (isset($_POST['id'])) {
            $k = $this->db->getObject('apikey', $_POST['id']);
            $k->vcode = decryptKey($k->vcode);
            if (($k->id > 0) && ($k->user_id != $this->site->user->id)) {
                return $this->keysList("Selected API key doesn't belong to you!");
            }

            $_POST['keyid'] = trim($_POST['keyid']);
            $_POST['vcode'] = trim($_POST['vcode']);

            $eveKey = new eveApiKey(0, $_POST['name'], $_POST['keyid'], $_POST['vcode']);
            if ($eveKey->error) {
                return $this->render('account_edit', array('k' => objectToArray($k, array('DBManager')), 'error' => $eveKey->error->errorText));
            }
            if (!$eveKey->error) {
                $k->user_id = $this->site->user->id;
                $k->name = $_POST['name'];
                $k->keyid = $_POST['keyid'];
                $k->vcode = encryptKey($_POST['vcode']);

                $k->save();

                $this->loadApiKeys();

                return $this->keysList();
            }
        } else {
            return $this->keysList();
        }
    }

    function keysList($error = false) {
        $keys = $this->site->user->get_apikey_list('name');
        $keys = objectToArray($keys, array('DBManager'));

        $wasErrors = $GLOBALS['EVEAPI_NO_ERRORS'];
        $GLOBALS['EVEAPI_NO_ERRORS'] = true;
        for ($i = 0; $i < count($keys); $i++) {
            $eveAcc = new eveAccount(eveKeyManager::getKey($keys[$i]['row']['id']));
            $eveAcc->getAccountStatus();
            $keys[$i]['account'] = objectToArray($eveAcc);
        }
        $GLOBALS['EVEAPI_NO_ERRORS'] = $wasErrors;

        return $this->render('accounts', array('keys' => $keys, 'error' => $error));
    }

    function welcome() {
        include_once('magpierss/rss_fetch.inc');
        define('MAGPIE_CACHE_DIR', $GLOBALS['config']['eve']['cache_dir']);

        $feeds = array();
        for ($i = 0; $i < count($GLOBALS['config']['rss']); $i++) {
            $feeds[] = fetch_rss($GLOBALS['config']['rss'][$i]);
        }

        $feeds = objectToArray($feeds);

        return $this->render('rsspage', array('feeds' => $feeds, 'about' => file_exists('about/index.html')));
    }

    function hasForcedMenu($menu) {
        return false;
    }
}

function tzSort($a, $b) {
    if ($a['zone'] == $b['zone']) {
        return 0;
    }
    return ($a['zone'] < $b['zone']) ? -1 : 1;
}

?>