<?php

    class users extends Plugin {
        var $name = '';
        var $forceMenus = array();

        function users($db, $site) {
            $this->Plugin($db, $site);

            if (isset($_POST['login'])) {
                if (!empty($_POST['username']) && !empty($_POST['password'])) {
                    setcookie('user[username]', $_POST['username'], time()+(60*60*24*30), '/');
                    setcookie('user[password]', md5($_POST['password']), time()+(60*60*24*30), '/');

                    $_COOKIE['user']['username'] = $_POST['username'];
                    $_COOKIE['user']['password'] = md5($_POST['password']);
                }
            } else if (isset($_GET['logout'])) {
                setcookie('user[username]', '', time()-(60*60*24*30), '/');
                setcookie('user[password]', '', time()-(60*60*24*30), '/');

                unset($_COOKIE['user']);
            }

            if (!empty($_COOKIE['user']['username']) && !empty($_COOKIE['user']['password']))
                $checkUser = $db->QueryA('select id from user where username = ? and password = ?', array($_COOKIE['user']['username'], $_COOKIE['user']['password']));
            else
                $checkUser = null;

            if (is_array($checkUser)) {
                $this->site->user = $db->getObject('user', $checkUser[0]['id']);
                if ($this->site->user->id > 0) {
                    if (isset($_GET['setacc'])) {
                        $a = $db->getObject('account', $_GET['setacc']);
                        if ($a->user_id != $this->site->user->id) {
                            echo '<div class="apierror">Selected account does not belong to you!</div>';
                        } else {
                            $this->site->user->account_id = $_GET['setacc'];
                            $this->site->user->save();
                        }
                    }
                    $this->site->user->account = $db->getObject('account', $this->site->user->account_id);

                    if (isset($_GET['setchar'])) {
                        $this->site->user->account->character_id = $_GET['setchar'];
                        $this->site->user->account->save();
                    }

                    if (trim($this->site->user->proxy) <> '')
                        $GLOBALS['config']['eve']['api_url'] = trim($this->site->user->proxy);

                    $tz = new DateTimeZone($this->site->user->timezone);
                    $dt = new DateTime("now", $tz);
                    $timeOffset = $tz->getOffset($dt) / 3600;
                    
                    if ($this->site->user->account->id > 0) {
                        if (substr($this->site->user->account->apikey, -1) != '=') {
                            $this->site->user->account->apikey = encryptKey($this->site->user->account->apikey);
                            $this->site->user->account->save();
                        }
                    }

                    $this->site->eveAccount = new eveAccount(trim($this->site->user->account->apiuser), trim(decryptKey($this->site->user->account->apikey)), $timeOffset);

                    if ($this->site->user->account->id > 0)
                        $this->forceMenus = $this->site->user->account->get_showmenus_list('id');

                    if (isset($_POST['theme']))
                        $tmpTheme = $_POST['theme'];
                    else
                        $tmpTheme = $this->site->user->theme;
                    if (!empty($tmpTheme) && (is_dir($GLOBALS['config']['templates']['theme_dir'] . '/' . $tmpTheme)))
                        $GLOBALS['config']['templates']['theme'] = $tmpTheme;

                    for ($i = 0; $i < count($this->site->eveAccount->characters); $i++)
                        if ($this->site->eveAccount->characters[$i]->characterID == $this->site->user->account->character_id)
                            $this->site->character = $this->site->eveAccount->characters[$i];

                    if (!isset($this->site->character))
                        $this->site->character = $this->site->eveAccount->characters[0];

                    if (isset($this->site->character))
                        $this->site->character->loadCorporation();

                    $accounts = $this->site->user->get_account_list('name');
                    $accounts = objectToArray($accounts, array('DBManager'));

                    $this->site->tplVars['accounts'] = $accounts;
                    $this->site->tplVars['characters'] = $this->charactersLite();
                    $this->site->tplVars['curchar'] = $this->site->character->characterID;
                    $this->site->tplVars['curacc'] = $this->site->user->account_id;

                    $this->site->user->activetime = date('Y-m-d H:i:s');
                    $this->site->user->save();
                }
            } else
                $this->site->user = $db->getObject('user', 0);
        }

        function charactersLite() {
            $res = array();
            for ($i = 0; $i < count($this->site->eveAccount->characters); $i++)
                $res[] = array('characterID' => $this->site->eveAccount->characters[$i]->characterID,
                               'name' => $this->site->eveAccount->characters[$i]->name);
            return $res;
        }

        function getSideBox() {
            if ($this->site->user->id == 0)
                return $this->render('side_login', array('register' => $GLOBALS['config']['site']['registration']));
            else
                return $this->render('side_logged_in', array('user' => $this->site->user->row, 'noAccount' => ($this->site->user->account->id == 0)));
        }

        function getContent() {
            if (!isset($_GET['mode']))
                $_GET['mode'] = 'welcome';
            switch ($_GET['mode']) {
                case 'register':
                    $this->name = 'User Registration';
                    return $this->register();
                case 'accounts':
                    $this->name = 'Eve Accounts';
                    return $this->accounts();
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
            if ($this->site->user->id == 0)
                return '<h2>DENIED!</h2>';

            if (isset($_POST['save'])) {
                if (!empty($_POST['password']))
                    $this->site->user->password = md5($_POST['password']);
                $this->site->user->email = $_POST['email'];
                $this->site->user->theme = $_POST['theme'];
                $this->site->user->proxy = $_POST['proxy'];
                $this->site->user->timezone = $_POST['timezone'];
                $this->site->user->smallicons = max(0, $_POST['smallicons']);
                $this->site->user->save();

                if ($this->site->eveAccount) {
                    $myMins = $this->site->user->get_mineralprice_list();
                    if ($myMins)
                        for ($i = 0; $i < count($myMins); $i++)
                            $myMins[$i]->delete();

                    $res = $this->site->eveAccount->db->db->QueryA('select typeid from invTypes where groupid = 18 order by typeid', array());
                    for ($i = 0; $i < count($res); $i++) {
                        $newPrice = $this->db->getObject('mineralprice', 0);
                        $newPrice->user_id = $this->site->user->id;
                        $newPrice->typeid = $res[$i]['typeid'];
                        $newPrice->price = str_replace(',', '', $_POST['min_'.$res[$i]['typeid']]);
                        $newPrice->save();
                    }
                }

            }

            $zonelist = timezone_identifiers_list();
            $timezones = array();
            $i = 0;
            foreach($zonelist as $zone) {
                $zoneParts = explode('/', $zone);
                if(in_array($zoneParts[0], array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'))) {
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
            if ($this->site->eveAccount) {
                $res = $this->site->eveAccount->db->db->QueryA('select typeid from invTypes where groupid = 18 order by typeid', array());
                for ($i = 0; $i < count($res); $i++) {
                    $newMin = $this->site->eveAccount->db->eveItem($res[$i]['typeid']);
                    if ($myMins) {
                        for ($j = 0; $j < count($myMins); $j++) {
                            if ($myMins[$j]->typeid == $newMin->typeid) {
                                $newMin->customPrice = $myMins[$j]->price;
                            }
                        }
                    }
                    $mins[] = objectToArray($newMin, array('DBManager', 'eveDB'));
                }
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

        function accounts() {
            if ($this->site->user->id == 0)
                return '<h2>DENIED!</h2>';

            if (isset($_GET['delete'])) {
                $a = $this->db->getObject('account', $_GET['delete']);
                if (($a->id > 0) && ($a->user_id != $this->site->user->id))
                    return $this->accountsList("Selected account doesn't belong to you!");

                $a->delete();

                if ($this->site->user->account_id == $_GET['delete']) {
                    $this->site->user->account_id = 0;
                    $this->site->user->save();
                }
                
                return $this->accountsList();
            } else if (isset($_GET['edit'])) {
                $a = $this->db->getObject('account', $_GET['edit']);
                if (($a->id > 0) && ($a->user_id != $this->site->user->id))
                    return $this->accountsList("Selected account doesn't belong to you!");

                $forceMenus = array(
                        'corpSheet' => array('Corporation Sheet', $this->hasForcedMenu('corpSheet')),
                        'corpAssets' => array('Corporation Assets', $this->hasForcedMenu('corpAssets')),
                        'corpTransactions' => array('Corporation Market Transactions', $this->hasForcedMenu('corpTransactions')),
                        'corpJournal' => array('Corporation Journal', $this->hasForcedMenu('corpJournal')),
                        'corpOrders' => array('Corporation Market Orders', $this->hasForcedMenu('corpOrders')),
                        'corpIndustry' => array('Corporation Manufacture &amp; Research', $this->hasForcedMenu('corpIndustry')),
                        'corpKills' => array('Corporation Kills and Deaths', $this->hasForcedMenu('corpKills')),
                        'corpStarbases' => array('Corporation Starbases', $this->hasForcedMenu('corpStarbases')),
                    );

                $a->apikey = decryptKey($a->apikey);
                $a = objectToArray($a, array('DBManager'));
                return $this->render('account_edit', array('a' => $a, 'error' => false, 'forcemenus' => $forceMenus));
            } else if (isset($_POST['id'])) {
                $a = $this->db->getObject('account', $_POST['id']);
                $a->apikey = decryptKey($a->apikey);
                if (($a->id > 0) && ($a->user_id != $this->site->user->id))
                    return $this->accountsList("Selected account doesn't belong to you!");

                $_POST['apiuser'] = trim($_POST['apiuser']);
                $_POST['apikey'] = trim($_POST['apikey']);

                $eveAcc = new eveAccount($_POST['apiuser'], $_POST['apikey']);
                if ($eveAcc->error)
                    return $this->render('account_edit', array('a' => objectToArray($a, array('DBManager')), 'error' => $eveAcc->error));
                $eveAcc->checkFullAccess();
                if ($eveAcc->error)
                    return $this->render('account_edit', array('a' => objectToArray($a, array('DBManager')), 'error' => $eveAcc->error));
                if (!$eveAcc->error) {
                    $a->user_id = $this->site->user->id;
                    $a->name = $_POST['name'];
                    $a->apiuser = $_POST['apiuser'];
                    $a->apikey = encryptKey($_POST['apikey']);
                    $a->precache = isset($_POST['precache']) ? 1 : 0;

                    if ($a->id > 0) {
                        if ($this->forceMenus) {
                            for ($i = 0; $i < count($this->forceMenus); $i++) {
                                $this->forceMenus[$i]->delete();
                            }
                        }

                        for ($i = 0; $i < count($_POST['forcemenus']); $i++) {
                            $m = $this->db->getObject('showmenus', 0);
                            $m->account_id = $a->id;
                            $m->menu = $_POST['forcemenus'][$i];
                            $m->save();
                        }

                        $this->forceMenus = $a->get_showmenus_list();
                    }

                    $a->save();

                    $this->site->user->account_id = $a->id;
                    $this->site->user->save();

                    return $this->accountsList();
                }
            } else {
                return $this->accountsList();
            }
        }

        function accountsList($error = false) {
            $accounts = $this->site->user->get_account_list('name');
            $accounts = objectToArray($accounts, array('DBManager'));

            $wasErrors = $GLOBALS['EVEAPI_NO_ERRORS'];
            $GLOBALS['EVEAPI_NO_ERRORS'] = true;
            for ($i = 0; $i < count($accounts); $i++) {
                $eveAcc = new eveAccount(trim($accounts[$i]['row']['apiuser']), trim(decryptKey($accounts[$i]['row']['apikey'])), 0, false);
                $eveAcc->getAccountStatus();
                $accounts[$i]['account'] = objectToArray($eveAcc, array('DBManager'));
            }
            $GLOBALS['EVEAPI_NO_ERRORS'] = $wasErrors;

            return $this->render('accounts', array('accounts' => $accounts, 'error' => $error));
        }

        function welcome() {
            include_once('magpierss/rss_fetch.inc');
            define('MAGPIE_CACHE_DIR', $GLOBALS['config']['eve']['cache_dir']);

            $feeds = array();
            for ($i = 0; $i < count($GLOBALS['config']['rss']); $i++)
                $feeds[] = fetch_rss($GLOBALS['config']['rss'][$i]);

            $feeds = objectToArray($feeds);

            return $this->render('rsspage', array('feeds' => $feeds, 'about' => file_exists('about/index.html')));
        }

        function hasForcedMenu($menu) {
            for ($i = 0; $i < count($this->forceMenus); $i++) {
                if (isset($this->forceMenus[$i]) && ($this->forceMenus[$i]->menu == $menu)) {
                    return true;
                }
            }

            return false;
        }
    }

    function tzSort($a, $b) {
        if ($a['zone'] == $b['zone'])
            return 0;
        return ($a['zone'] < $b['zone']) ? -1 : 1;
    }
?>