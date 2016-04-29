<?php

class eveJournal {

    var $journal = array();
    var $key = null;
    var $accountKey = 0;
    static $refTypes = array();

    function eveJournal($key, $accountKey = 0) {
        $this->key = $key;
        $this->accountKey = $accountKey;
    }

    function load($fromID = 0) {
        $params = array();
        $params['rowCount'] = $GLOBALS['config']['eve']['journal_records'];
        if ($fromID > 0) {
            $params['fromID'] = $fromID;
        }

        if ((count($this->journal) == 0) || ($fromID > 0)) {
            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_WalletJournal)) {
                if ($this->accountKey > 0) {
                    $params['accountKey'] = $this->accountKey;
                }
                $data = new apiRequest('corp/WalletJournal.xml.aspx', $this->key, $this->key->getCharacter(), $params);
            } else if ($this->key->hasAccess(CHAR_WalletJournal)) {
                $data = new apiRequest('char/WalletJournal.xml.aspx', $this->key, $this->key->getCharacter(), $params);
            }

            if ((!$data->error) && ($data->data)) {
                $gotRows = 0;
                foreach ($data->data->result->rowset->row as $journalItem) {
                    $this->journal[] = new eveJournalItem($journalItem);
                    $gotRows++;
                }

                // keep looping journal requests until we receive no more results
                $lowest = eveJournal::lowestJournalRef($this->journal);
                if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                    $this->load($lowest);
                } else {
                    // if this is the last run, sort all the items we have
                    usort($this->journal, array('eveJournal', 'sortJorunal'));
                }
            }
        }
    }

    static function sortJorunal($a, $b) {
        return ($a->date > $b->date) ? -1 : 1;
    }

    static function refType($refTypeId) {
        $refTypeId = (string) $refTypeId;
        if (!isset(eveJournal::$refTypes[$refTypeId])) {
            $eveRefTypes = new apiRequest('eve/RefTypes.xml.aspx');
            foreach ($eveRefTypes->data->result->rowset->row as $refType) {
                eveJournal::$refTypes[(string) $refType['refTypeID']] = (string) $refType['refTypeName'];
            }
        }
        return eveJournal::$refTypes[$refTypeId];
    }

    static function lowestJournalRef($journalItems) {
        $res = 0;
        for ($i = 0; $i < count($journalItems); $i++) {
            if (($res == 0) || ($journalItems[$i]->journalID < $res)) {
                $res = $journalItems[$i]->journalID;
            }
        }
        return $res;
    }

}

class eveJournalItem {

    var $date = 0;
    var $refTypeID = 0;
    var $refType = '';
    var $journalID = 0;
    var $fromID = 0;
    var $fromName = '';
    var $toID = 0;
    var $toName = 0;
    var $argID1 = 0;
    var $argName1 = 0;
    var $amount = 0;
    var $balance = 0;
    var $reason = '';
    var $taxReceiverID = 0;
    var $taxAmount = 0;

    function eveJournalItem($item) {
        $this->date = eveTimeOffset::getOffsetTime($item['date']);
        $this->refTypeID = (int) $item['refTypeID'];
        $this->journalID = (int) $item['refID'];
        $this->fromID = (int) $item['ownerID1'];
        $this->fromName = (string) $item['ownerName1'];
        $this->toID = (int) $item['ownerID2'];
        $this->toName = (string) $item['ownerName2'];
        $this->argID1 = (int) $item['argID1'];
        $this->argName1 = (string) $item['argName1'];
        $this->amount = (float) $item['amount'];
        $this->balance = (float) $item['balance'];
        $this->reason = (string) $item['reason'];
        $this->taxReceiverID = (string) $item['taxReceiverID'];
        $this->taxAmount = (float) $item['taxAmount'];

        $this->refType = eveJournal::refType($this->refTypeID);
    }

}

?>
