<?php
    class eveMarketOrderList {
        var $orders = array();

        function load($account, $character) {
            if (count($this->orders) == 0) {
                $data = new apiRequest('char/MarketOrders.xml.aspx', array($account->userId,
                                                                           $account->apiKey, 
                                                                           $character->characterID),
                                                                     array('version' => 2));

                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $order) {
                        $this->orders[] = new eveMarketOrder($account, $order);
                    }
                }
            }
        }
    }

    class eveMarketOrder {
        // orderState notes
        //  - 0: Active
        //  - 1: Closed
        //  - 2: Completed
        //  - 3: Cancelled
        //  - 4: Pending
        //  - 5: Deleted

        var $typeID = 0;
        var $orderID = 0;
        var $charID = 0;
        var $stationID = 0;
        var $volEntered = 0;
        var $volRemaining = 0;
        var $orderState = 0;
        var $range = 0;
        var $accountKey = 1000;
        var $duration = 0;
        var $price = 0;
        var $issued = 0;
        var $valRemaining = 0;
        var $buying = false;

        var $remainingTime = 0;

        var $item = null;
        var $station = null;

        function eveMarketOrder($acc, $order) {
            $this->typeID = (int)$order['typeID'];
            $this->orderID = (int)$order['orderID'];
            $this->stationID = (int)$order['stationID'];
            $this->volEntered = (int)$order['volEntered'];
            $this->volRemaining = (int)$order['volRemaining'];
            $this->orderState = (string)$order['orderState'];
            $this->range = (int)$order['range'];
            $this->duration = (float)$order['duration'];
            $this->price = (float)$order['price'];
            $this->issued = strtotime((string)$order['issued']) + $acc->timeOffset;
            $this->buying = (int)$order['bid'] > 0;
            $this->valRemaining = $this->volRemaining * $this->price;

            $this->remainingTime = (($this->issued + ($this->duration*86400)) - $acc->timeOffset) - $GLOBALS['eveTime'];

            if (isset($order['charID'])) {
                $this->charID = (int)$order['charID'];
            }
            if (isset($order['accountKey'])) {
                $this->accountKey = (int)$order['accountKey'];
            }

            $this->item = eveDB::getInstance()->eveItem($this->typeID);
            $this->station = eveDB::getInstance()->eveStation($this->stationID);
        }
    }

?>