<?php

require_once('../includes/site.php');
$GLOBALS['EVEAPI_NO_ERRORS'] = true;

class journalFeed {

    var $updated = false;
    var $feed = "";

    function journalFeed($site, $id) {
        $this->cacheDir = $GLOBALS['config']['eve']['cache_dir'] . 'feeds/';
        if (!is_dir($this->cacheDir))
            mkdir($this->cacheDir);

        $this->db = $site->db;
        $this->site = $site;

        $query = "select id, user_id, apiuser from account where md5(concat('journal', id, 'u', user_id)) = ?";
        $acc = $this->db->QueryA($query, array($id));

        if ($acc) {
            // check if we have the feed cached
            $this->cacheFile = $this->cacheDir . md5(implode('.', $acc[0]));
            if (isset($_GET['corp']))
                $this->cacheFile .= '_c' . $_GET['corp'];

            $feedExists = file_exists($this->cacheFile);
            if ($feedExists)
                $feedAge = time() - (filemtime($this->cacheFile));

            if ($feedExists && ($feedAge < (60 * 15))) {
                // if the feed is already cached and is less than 15 minutes old, 
                // serve the cache rather than hitting the API and our database
                $this->feed = file_get_contents($this->cacheFile);
            } else {
                if ($feedExists && ($feedAge > (60 * 15))) {
                    // if the feed exists, and the cache is older than 15 minutes
                    // get it's entry id as a point of reference for where we should
                    // show new entries up until
                    $oldFeed = new SimpleXMLElement(file_get_contents($this->cacheFile));
                    $lastIdUrl = (string) $oldFeed->entry->id;
                    $lastIdBits = explode('=', $lastIdUrl);
                    $lastId = $lastIdBits[count($lastIdBits) - 1];
                } else
                    $lastId = '-';

                // load up all the journal and API stuff
                $this->site->user = $this->db->getObject('user', $acc[0]['user_id']);
                $this->site->user->account = $this->db->getObject('account', $acc[0]['id']);
                $this->site->eveAccount = new eveAccount(trim($this->site->user->account->apiuser), trim(decryptKey($this->site->user->account->apikey)), 0);
                for ($i = 0; $i < count($this->site->eveAccount->characters); $i++)
                    if ($this->site->eveAccount->characters[$i]->characterID == $this->site->user->account->character_id)
                        $this->site->character = $this->site->eveAccount->characters[$i];
                if (!isset($this->site->character))
                    $this->site->character = $this->site->eveAccount->characters[0];


                if (isset($_GET['corp'])) {
                    $this->site->character->loadCorporation();
                    $this->site->character->corporation->loadJournal($_GET['corp']);
                    $journalItems = $this->site->character->corporation->journalItems;
                } else {
                    $this->site->character->loadJournal();
                    $journalItems = $this->site->character->journalItems;
                }

                // build a list of the latest entries from the journal, up to the 
                // last item from the previous entry, or at most, 50 entries
                $journal = array();
                for ($i = 0; $i < min(50, count($journalItems)); $i++) {
                    if ($journalItems[$i]->journalID == $lastId)
                        break;

                    $newItem = array();
                    $newItem['date'] = $journalItems[$i]->date;
                    $newItem['refType'] = $journalItems[$i]->refType;
                    $newItem['amount'] = $journalItems[$i]->amount;
                    $newItem['balance'] = $journalItems[$i]->balance;
                    if ($newItem['amount'] > 0)
                        $newItem['name'] = $journalItems[$i]->fromName;
                    else
                        $newItem['name'] = $journalItems[$i]->toName;

                    $journal[] = $newItem;
                }

                $latestId = $journalItems[0]->journalID;


                if ($_SERVER['HTTPS'])
                    $protocol = 'https://';
                else
                    $protocol = 'http://';

                // construct the atom feed XML. times are GMT
                $atom = new SimpleXMLElement('<feed xmlns="http://www.w3.org/2005/Atom" />');
                $atom->addChild('id', $protocol . $_SERVER['HTTP_HOST'] . urlencode($_SERVER['REQUEST_URI']));

                if (isset($_GET['corp']))
                    $atom->addChild('title', $this->site->character->corporation->ticker . " Journal");
                else
                    $atom->addChild('title', $this->site->character->name . "'s Journal");

                $atom->addChild('updated', gmdate('Y-m-d\TH:i:s\Z'));
                $atom->addChild('generator', $GLOBALS['config']['site']['title']);
                $link = $atom->addChild('link');
                $link->addAttribute('rel', 'self');
                $link->addAttribute('href', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

                if (count($journal) > 0) {
                    $entry = $atom->addChild('entry');
                    if (isset($_GET['corp']))
                        $entry->addChild('id', $protocol . $_SERVER['HTTP_HOST'] . $GLOBALS['config']['site']['url'] . '?module=journal&amp;corp=1&amp;accountKey=' . $_GET['corp'] . '&amp;last=' . $latestId);
                    else
                        $entry->addChild('id', $protocol . $_SERVER['HTTP_HOST'] . $GLOBALS['config']['site']['url'] . '?module=journal&amp;last=' . $latestId);
                    $entry->addChild('title', 'Latest journal transactions');
                    $author = $entry->addChild('author');
                    $author->addChild('name', $this->site->character->corporation->corporationName);
                    $entry->addChild('updated', gmdate('Y-m-d\TH:i:s\Z'));


                    // render the journal entries table using a Smarty template
                    $tpl = new Smarty();
                    $tpl->register_modifier('eveNum', 'eveNum');
                    $tpl->register_modifier('eveNumInt', 'eveNumInt');
                    $tpl->register_modifier('eveRoman', 'eveRoman');
                    $tpl->register_modifier('formatTime', 'formatTime');
                    $tpl->template_dir = $GLOBALS['config']['templates']['theme_dir'] . '/feeds';
                    $tpl->compile_dir = $GLOBALS['config']['templates']['compile_dir'];
                    $tpl->assign('journal', $journal);

                    $content = $entry->addChild('content', $tpl->fetch('journal_feed.html'));
                    $content->addAttribute('type', 'html');

                    $this->feed = $atom->asXML();

                    // cache for later use
                    file_put_contents($this->cacheFile, $this->feed);
                } else {
                    if ($oldFeed)
                        $this->feed = $oldFeed->asXML();
                    else
                        $this->feed = $atom->asXML();
                }
            }
        }
    }

}

header('Content-type: application/atom+xml');
ob_start("ob_gzhandler");
$site = new Site();
if (!empty($_GET['id'])) {
    $j = new journalFeed($site, mysql_escape_string($_GET['id']));
    echo $j->feed;
}
ob_end_flush();
?>