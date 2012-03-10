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
        
        function load($account, $character, $fromID = 0) {
            $params = array();
            $params['rowCount'] = $GLOBALS['config']['eve']['transaction_records'];
            if ($fromID > 0) {
                $params['fromID'] = $fromID;
            }

            if ((count($this->transactions) == 0) || ($fromID > 0)) {
                $data = new apiRequest('char/WalletTransactions.xml.aspx', array($account->userId,
                                                                                 $account->apiKey, 
                                                                                 $character->characterID),
                                                                           $params);
                if ((!$data->error) && ($data->data)) {
                    $gotRows = 0;
                    foreach ($data->data->result->rowset->row as $transaction) {
                        $this->transactions[] = new eveTransaction($account, $transaction, $character);
                        $gotRows ++;
                    }

                    // keep looping requests until we receive no more results
                    $lowest = lowestTransactionRef($this->transactions);
                    if (($lowest != $fromID) && ($gotRows == $params['rowCount'])) {
                        $this->loadTransactions($account, $character, $lowest);
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

        function eveTransaction($acc, $trans, $owner) {
            $this->typeID = (int)$trans['typeID'];
            $this->transactionID = (int)$trans['transactionID'];
            $this->transactionTime = strtotime((string)$trans['transactionDateTime']) + $acc->timeOffset;
            $this->qty = (int)$trans['quantity'];
            $this->unitPrice = (float)$trans['price'];
            $this->clientID = (int)$trans['clientID'];
            $this->clientName = (string)$trans['clientName'];
            $this->stationID = (int)$trans['stationID'];
            $this->transactionType = (string)$trans['transactionType'];
            $this->transactionFor = (string)$trans['transactionFor'];
            $this->totalPrice = $this->unitPrice * $this->qty;

            if (isset($trans['characterID'])) {
                $this->characterID = (int)$trans['characterID'];
            } else {
                $this->characterID = $owner->characterID;
            }

            if (isset($trans['characterName'])) {
                $this->characterName = (string)$trans['characterName'];
            } else {
                $this->characterName = $owner->name;
            }

            $this->purchase = ($this->transactionType == 'buy');

            $this->item = eveDB::getInstance()->eveItem($this->typeID);
            $this->station = eveDB::getInstance()->eveStation($this->stationID);
        }
    }

?>