<?php

class ajaxtest extends Plugin {

    var $name = "AJAX/JSON Demo";

    function getContent() {
        return $this->render('test', array());
    }

    function getContentJson() {
        $typeID = eveDB::getInstance()->getTypeId($_GET['item']);
        if ($typeID == 0) {
            $response = array('error' => 'Item not found: ' . $_GET['item']);
        } else {
            $item = eveDB::getInstance()->eveItem($typeID);
            $item->getDescription();
            $item->description = nl2br($item->_description);
            $item->getPricing();

            // when serialising a database object to send to the client,
            // always pass it through the objectToArray function first.
            $response = objectToArray($item, array('DBManager', 'eveDB'));
        }

        return json_encode($response);
    }

}

?>
