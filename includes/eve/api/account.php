<?php

class eveAccountStatus {

    var $userID = 0;
    var $paidUntil = 0;
    var $createDate = 0;
    var $logonCount = 0;
    var $logonMinutes = 0;
    var $key;

    function eveAccountStatus($key) {
        $this->key = $key;
    }

    function load() {
        if (!$this->key->isCorpKey() && $this->key->hasAccess(CHAR_AccountStatus)) {

            $accData = new apiRequest('account/AccountStatus.xml.aspx', $this->key);

            if (!$accData->data) {
                return;
            }

            if ($accData->data->error) {
                $this->error = (string) $accData->data->error;
            } else {
                $accStatus = $accData->data->result;

                $this->userID = (int) $accStatus->userID;
                $this->paidUntil = eveTimeOffset::getOffsetTime($accStatus->paidUntil);
                $this->createDate = eveTimeOffset::getOffsetTime($accStatus->createDate);
                $this->logonCount = (int) $accStatus->logonCount;
                $this->logonMinutes = (int) $accStatus->logonMinutes;
            }
        } else if ($this->key->isCorpKey()) {
            $this->error = 'No account status available for corporation key.';
        } else {
            $this->error = 'Key does not provide access to account status. Check key access options.';
        }
    }
}

?>
