<?php

class eveMarketOrderList {

    var $orders = array();
    var $key;
    var $accountKey = 0;

    function eveMarketOrderList($key, $accountKey = 0) {
        $this->key = $key;
        $this->accountKey = $accountKey;
    }

    function load() {
        if (count($this->orders) == 0) {
            if ($this->key->isCorpKey() && $this->key->hasAccess(CORP_MarketOrders)) {
                $params = array();
                if ($this->accountKey > 0) {
                    $params['accountKey'] = $this->accountKey;
                }
                $data = new apiRequest('corp/MarketOrders.xml.aspx', $this->key, $this->key->getCharacter(), $params);
            } else if ($this->key->hasAccess(CHAR_MarketOrders)) {
                $data = new apiRequest('char/MarketOrders.xml.aspx', $this->key, $this->key->getCharacter());
            }


            if ((!$data->error) && ($data->data)) {
                foreach ($data->data->result->rowset->row as $order) {
                    $this->orders[] = new eveMarketOrder($order);
                }
            }

            usort($this->orders, 'orderSort');
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

    function eveMarketOrder($order) {
        $this->typeID = (int) $order['typeID'];
        $this->orderID = (int) $order['orderID'];
        $this->stationID = (int) $order['stationID'];
        $this->volEntered = (int) $order['volEntered'];
        $this->volRemaining = (int) $order['volRemaining'];
        $this->orderState = (string) $order['orderState'];
        $this->range = (int) $order['range'];
        $this->duration = (float) $order['duration'];
        $this->price = (float) $order['price'];
        $this->issued = eveTimeOffset::getOffsetTime($order['issued']);
        $this->buying = (int) $order['bid'] > 0;
        $this->valRemaining = $this->volRemaining * $this->price;

        $this->remainingTime = (($this->issued + ($this->duration * 86400)) - eveTimeOffset::$offset) - eveTimeOffset::$eveTime;

        if (isset($order['charID'])) {
            $this->charID = (int) $order['charID'];
        }
        if (isset($order['accountKey'])) {
            $this->accountKey = (int) $order['accountKey'];
        }

        $this->item = eveDB::getInstance()->eveItem($this->typeID);
        $this->station = eveDB::getInstance()->eveStation($this->stationID);
    }

}

function orderSort($a, $b) {
    return ($a->issued > $b->issued) ? -1 : 1;
}

?>
