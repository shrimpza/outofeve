<?php

class eveKeyManager {

    var $keys = array();
    static $instance = null;

    static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new eveKeyManager();
        }
        return self::$instance;
    }

    static function addKey($reference, $name, $keyID, $vCode, $selectedCharacter = 0) {
        self::getInstance()->keys[$reference] = new eveApiKey($reference, $name, $keyID, $vCode, $selectedCharacter);
    }
    
    static function getKey($reference) {
        return self::getInstance()->keys[$reference];
    }

    static function getCharacterKeys() {
        $keys = self::getInstance()->keys;
        $result = array();
        foreach ($keys as $key) {
            if (!$key->isCorpKey()) {
                $result[] = $key;
            }
        }
        return $result;
    }

    static function getCorporateKeys() {
        $keys = self::getInstance()->keys;
        $result = array();
        foreach ($keys as $key) {
            if ($key->isCorpKey()) {
                $result[] = $key;
            }
        }
        return $result;
    }

}

class eveApiKey {

    var $reference = 0;
    var $name = '';
    var $keyID = 0;
    var $vCode = '';
    var $type = '';
    var $characters = array();
    var $accessMask = 0;
    var $expires = 0;
    var $selectedCharacter = 0;
    var $error = false;

    function eveApiKey($reference, $name, $keyID, $vCode, $selectedCharacter = 0, $autoLoad = true) {
        $this->reference = $reference;
        $this->name = $name;
        $this->keyID = $keyID;
        $this->vCode = $vCode;
        $this->selectedCharacter = $selectedCharacter;

        if ($autoLoad) {
            $this->load();
        }
    }

    function load() {
        $data = new apiRequest('account/APIKeyInfo.xml.aspx', $this);
        if ((!$data->error) && ($data->data)) {
            $key = $data->data->result->key;

            $this->accessMask = (int) $key['accessMask'];
            $this->type = (String) $key['type'];
            $this->expires = empty($key['expires']) ? 0 : strtotime((string) $key['expires']);

            foreach ($key->rowset->row as $char) {
                $char = new eveCharacterInfo($char);
                $this->characters[$char->characterID] = $char;
            }
        } else {
            $this->error = $data->error;
        }
    }

    function getCharacter() {
        if ($this->selectedCharacter == 0) {
            $chars = array_keys($this->characters);
            $this->selectedCharacter = $chars[0];
        }
        return $this->characters[$this->selectedCharacter];
    }

    function isExpired() {
        return ($this->expires > 0) && ($this->expires < time());
    }

    function isCorpKey() {
        return $this->type == 'Corporation';
    }

    function hasAccess($access) {
        return ($this->accessMask & $access) == $access;
    }

}

class eveCharacterInfo {

    var $characterID = 0;
    var $characterName = '';
    var $corporationID = 0;
    var $corporationName = '';

    function eveCharacterInfo($character) {
        $this->characterID = (int) $character['characterID'];
        $this->characterName = (string) $character['characterName'];
        $this->corporationID = (int) $character['corporationID'];
        $this->corporationName = (string) $character['corporationName'];
    }

}

?>