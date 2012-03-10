<?php

    function journalSort($a, $b) {
        return ($a->date > $b->date) ? -1 : 1;
    }

    function lowestJournalRef($journalItems) {
        $res = 0;
        for ($i = 0; $i < count($journalItems); $i++) {
            if (($res == 0) || ($journalItems[$i]->journalID < $ref)) {
                $res = $journalItems[$i]->journalID;
            }
        }
        return $res;
    }

    class eveJournal {
        var $journal = array();

        function load($account, $character, $fromID = 0) {
            $params = array();
            $params['rowCount'] = $GLOBALS['config']['eve']['journal_records'];
            if ($fromID > 0) {
                $params['fromID'] = $fromID;
            }

            if ((count($this->journal) == 0) || ($fromID > 0)) {
                $data = new apiRequest('char/WalletJournal.xml.aspx', array($account->userId,
                                                                            $account->apiKey, 
                                                                            $character->characterID),
                                                                      $params);

                if ((!$data->error) && ($data->data)) {
                    $gotRows = 0;
                    foreach ($data->data->result->rowset->row as $journalItem) {
                        $this->journal[] = new eveJournalItem($account, $journalItem);
                        $gotRows ++;
                    }

                    // keep looping journal requests until we receive no more results
                    $lowest = lowestJournalRef($this->journal);
                    if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                        $this->load($account, $character, $lowest);
                    } else {
                        // if this is the last run, sort all the items we have
                        usort($this->journal, 'journalSort');
                    }
                }
            }
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

        function eveJournalItem($acc, $item) {
            $this->date = strtotime((string)$item['date']) + $acc->timeOffset;
            $this->refTypeID = (int)$item['refTypeID'];
            $this->journalID = (int)$item['refID'];
            $this->fromID = (int)$item['ownerID1'];
            $this->fromName = (string)$item['ownerName1'];
            $this->toID = (int)$item['ownerID2'];
            $this->toName = (string)$item['ownerName2'];
            $this->argID1 = (int)$item['argID1'];
            $this->argName1 = (string)$item['argName1'];
            $this->amount = (float)$item['amount'];
            $this->balance = (float)$item['balance'];
            $this->reason = (string)$item['reason'];
            $this->taxReceiverID = (string)$item['taxReceiverID'];
            $this->taxAmount = (float)$item['taxAmount'];

            $this->refType = eveDB::getInstance()->refType($this->refTypeID);
        }
    }
?>