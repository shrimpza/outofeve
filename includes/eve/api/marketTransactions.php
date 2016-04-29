<?php

function transactionsSort($a, $b) {
    return ($a->transactionTime > $b->transactionTime) ? -1 : 1;
}

function lowestTransactionRef($transItems) {
    $res = 0;
    for ($i = 0; $i < count($transItems); $i++) {
        if (($res == 0) || ($transItems[$i]->transactionID < $ref)) {
            $res = $transItems[$i]->transactionID;
        }
    }
    return $res;
}

class eveTransactionList {

    var $transactions = array();
    var $key = null;
    var $accountKey = 0;

    function eveTransactionList($key, $accountKey = 0) {
        $this->key = $key;
        $this->accountKey = $accountKey;
    }

    function load($fromID = 0) {
        $params = array();
        $params['rowCount'] = $GLOBALS['config']['eve']['transaction_records'];
        if ($fromID > 0) {
            $params['fromID'] = $fromID;
        }

        if ((count($this->transactions) == 0) || ($fromID > 0)) {
            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_WalletTransactions)) {
                if ($this->accountKey > 0) {
                    $params['accountKey'] = $this->accountKey;
                }
                $data = new apiRequest('corp/WalletTransactions.xml.aspx', $this->key, $this->key->getCharacter(), $params);
            } else if ($this->key->hasAccess(CHAR_WalletTransactions)) {
                $data = new apiRequest('char/WalletTransactions.xml.aspx', $this->key, $this->key->getCharacter(), $params);
            }

            if ((!$data->error) && ($data->data)) {
                $gotRows = 0;
                foreach ($data->data->result->rowset->row as $transaction) {
                    $this->transactions[] = new eveTransaction($transaction, $this->key->getCharacter());
                    $gotRows++;
                }

                // keep looping requests until we receive no more results
                $lowest = lowestTransactionRef($this->transactions);
                if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                    $this->load($lowest);
                } else {
                    // if this is the last run, sort all the items we have
                    usort($this->transactions, 'transactionsSort');
                }
            }
        }
    }

}

class eveTransaction {

    var $typeID = 0;
    var $transactionID = 0;
    var $transactionTime = 0;
    var $qty = 0;
    var $unitPrice = 0;
    var $totalPrice = 0;
    var $clientID = 0;
    var $clientName = '';
    var $characterID = 0;
    var $characterName = '';
    var $stationID = 0;
    var $transactionType = '';
    var $transactionFor = '';
    var $purchase = false;
    var $item = null;
    var $station = null;

    function eveTransaction($trans, $owner) {
        $this->typeID = (int) $trans['typeID'];
        $this->transactionID = (int) $trans['transactionID'];
        $this->transactionTime = eveTimeOffset::getOffsetTime($trans['transactionDateTime']);
        $this->qty = (int) $trans['quantity'];
        $this->unitPrice = (float) $trans['price'];
        $this->clientID = (int) $trans['clientID'];
        $this->clientName = (string) $trans['clientName'];
        $this->stationID = (int) $trans['stationID'];
        $this->transactionType = (string) $trans['transactionType'];
        $this->transactionFor = (string) $trans['transactionFor'];
        $this->totalPrice = $this->unitPrice * $this->qty;

        if (isset($trans['characterID'])) {
            $this->characterID = (int) $trans['characterID'];
        } else {
            $this->characterID = $owner->characterID;
        }

        if (isset($trans['characterName'])) {
            $this->characterName = (string) $trans['characterName'];
        } else {
            $this->characterName = $owner->characterName;
        }

        $this->purchase = ($this->transactionType == 'buy');

        $this->item = eveDB::getInstance()->eveItem($this->typeID);
        $this->station = eveDB::getInstance()->eveStation($this->stationID);
    }

}

?>
