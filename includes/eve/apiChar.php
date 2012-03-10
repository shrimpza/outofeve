<?php

    class eveCharacter {
        var $characterID = 0;
        var $name = '';
        var $detail;
        
        function eveCharacter($account, $character, $autoLoad = true) {
            $this->db = $account->db;

            $this->account = $account;
            $this->characterID = (int)$character['characterID'];
            $this->name = (string)$character['name'];
            
            $this->detail = new eveCharacterDetail($account, $this);
        }

        function loadCorporation() {
            $data = new apiRequest('corp/CorporationSheet.xml.aspx', array($this->account->userId,
                                                                           $this->account->apiKey,
                                                                           $this->characterID));
            if ($data->data && !$data->data->error) {
                $this->corporation = new eveCorporation($this->account, $this, $data);
            }
        }

    }

?>