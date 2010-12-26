<?php

    require_once('curl.class.php');
    require_once('apiClasses.php');
    require_once('apiCorpClasses.php');
    require_once('apiChar.php');
    require_once('apiCorp.php');
    require_once('apidb.php');
    require_once('apimarket.php');
    require_once('attributeMods.php');

    $cacheDelays = array(
        101, 103, 115, 116, 117, 119
    );

    $eveTime = time() - date('Z');

    $GLOBALS['EVEAPI_ERRORS'] = array();

    function apiError($method, $error) {
        if (!isset($GLOBALS['EVEAPI_NO_ERRORS']) || (isset($GLOBALS['EVEAPI_NO_ERRORS']) && !$GLOBALS['EVEAPI_NO_ERRORS']))
            $GLOBALS['EVEAPI_ERRORS'][] = 'API "' . $method . '": [' . (string)$error['code'] . '] ' . (string)$error;
    }

    function otherError($method, $error) {
        if (!isset($GLOBALS['EVEAPI_NO_ERRORS']) || (isset($GLOBALS['EVEAPI_NO_ERRORS']) && !$GLOBALS['EVEAPI_NO_ERRORS']))
            $GLOBALS['EVEAPI_ERRORS'][] = $method . ': ' . $error;
    }

    class apiRequest {
        var $cachedResponse = '';
        var $response = '';
        var $data = null;

        /**
        * This is probably the most disgusting function you have ever seen in your entire life.
        * GLHF.
        */
        function apiRequest($method, $user = null, $params = null, $printErrors = true) {
            $http = new cURL();
            $http->setOption('CURLOPT_USERAGENT', 'Out of Eve (dev; shrimp@shrimpworks.za.net)');
            $http->setOption('CURLOPT_TIMEOUT', 45);

            $apiUrl = $GLOBALS['config']['eve']['api_url'];
            $fetchMethod = $GLOBALS['config']['eve']['method'];

            if (!$params)
                $params = array();

            if ($user) {
                $params['userID'] = $user[0];
                $params['apiKey'] = $user[1];
                if (isset($user[2]))
                    $params['characterID'] = $user[2];
            }

            $hadError = false;
            $cachedError = false;

            $cached = $this->checkCache($method, $params);

            if (!empty($this->cachedResponse)) {
                try {
                    $tmp = @new SimpleXMLElement($this->cachedResponse);
                } catch (Exception $e) {
                }
                $cachedError = isset($tmp->error);
                $cachedDate = $tmp->cachedUntil;
                if (isset($tmp) && isset($tmp->cachedUntil) && ((strtotime($tmp->cachedUntil) + date('Z') + 300) > time())) {
                    $this->response = $this->cachedResponse;
                    $this->data = $tmp;
                }
            }

            if (!isset($this->data)) {
                $logFile = @fopen($GLOBALS['config']['eve']['cache_dir'] . '_api.log', 'a+');

                if (!$cached) {
                    if (strtoupper($fetchMethod == 'GET')) {
                        $this->response = $http->get($apiUrl . '/' . $method . $this->queryString($params));
                        echo $apiUrl . '/' . $method . $this->queryString($params);
                    } else
                        $this->response = $http->post($apiUrl . '/' . $method, $params);
                }
                $resonseInfo = $http->getInfo();

                if (($resonseInfo['http_code'] >= 200) && ($resonseInfo['http_code'] <= 300) && (!empty($this->response))) {
                    try {
                        $this->data = @new SimpleXMLElement($this->response);
                    } catch (Exception $e) {
                        $hadError = true;
                        @fwrite($logFile, date('Y-d-m H:i:s: ') . "\t" . $method . "\t" . $e->getMessage() . "\n");
                        if ($printErrors)
                            otherError($method, $e->getMessage() . '. Using local cache.');
                        if (!empty($this->cachedResponse)) {
                            try {
                                $this->data = @new SimpleXMLElement($this->cachedResponse);
                            } catch (Exception $e) {
                                @fwrite($logFile, date('Y-d-m H:i:s: ') . "\tCache load failed\t" . $e->getMessage() . "\n");
                                if ($printErrors)
                                    otherError($method, 'Superhypermegaultrabbqfail: ' . $e->getMessage());
                            }
                        }
                    }

                    if (isset($this->data->error) && (!empty($this->cachedResponse) && !$cachedError)) {
                        $hadError = true;
                        @fwrite($logFile, date('Y-d-m H:i:s: ') . "\t" . $method . "\t" . (string)$this->data->error . "\n");
                        $errorCode = (int)$this->data->error['code'];
                        $errorCache = (string)$this->data->cachedUntil;
                        if ($printErrors) {
                            apiError($method, $this->data->error);
                        }
                        try {
                            $this->data = @new SimpleXMLElement($this->cachedResponse);
                            $this->data->gotError = $errorCode;
                            if (in_array($errorCode, $GLOBALS['cacheDelays'])) {
                                $this->data->cachedUntil = $errorCache;
                                $this->response = $this->data->asXML();
                                $this->saveCache($method, $params, strtotime($this->data->cachedUntil) + date('Z') + 300);
                            }
                        } catch (Exception $e) {
                            if ($printErrors)
                                otherError($method, 'Superhypermegaultrabbqfail: ' . $e->getMessage());
                            @fwrite($logFile, date('Y-d-m H:i:s: ') . "\tCache load failed\t" . $e->getMessage() . "\n");
                        }
                    }

                    if (!$hadError && !$cached && isset($this->data->cachedUntil))
                        $this->saveCache($method, $params, strtotime($this->data->cachedUntil) + date('Z') + 300);
                } else {
                    @fwrite($logFile, date('Y-d-m H:i:s: ') . "\tHTTP error: $method\t" . $this->response . "\n");
                    if ((!empty($this->cachedResponse) && !$cachedError)) {
                        if ($printErrors)
                            otherError($method, 'API error (HTTP ' . $resonseInfo['http_code'] . '); using local cache -- expired ' . $cachedDate);
                        try {
                            $this->data = @new SimpleXMLElement($this->cachedResponse);
                        } catch (Exception $e) {
                        }
                    } else {
                        @fwrite($logFile, date('Y-d-m H:i:s: ') . "\tFailed with no cache: $method\t" . $this->response . "\n");
                        otherError($method, 'Failed to get API data from '.$method.': ' . $this->response);
                    }
                }
                @fclose($logFile);
            }
        }

        function queryString($params) {
            $res = '?';
            foreach ($params as $key => $value) {
                $res .= $key . '=' . urlencode($value) . '&';
            }
            return substr($res, 0, -1);
        }

        function checkCache($method, $params)
        {
            $cacheSum = md5($method . implode('.', $params));
            $this->cacheFile = $GLOBALS['config']['eve']['cache_dir'] . $cacheSum;

            if (file_exists($this->cacheFile)) {
                $this->cachedResponse = file_get_contents($this->cacheFile);
                if (time() <= (filemtime($this->cacheFile))) {
                    $this->response = $this->cachedResponse;
                    return true;
                }
            }

            return false;
        }

        function saveCache($method, $params, $cachedUntil) {
            $cacheSum = md5($method . implode('.', $params));
            $this->cacheFile = $GLOBALS['config']['eve']['cache_dir'] . $cacheSum;

            file_put_contents($this->cacheFile, $this->response);
            touch($this->cacheFile, $cachedUntil);
        }

        function clearOldCache() {
            // maximum cache age is 7 days - one week
            $maxAge = 3600 * 24 * 7;

            $files = scandir($GLOBALS['config']['eve']['cache_dir']);
            foreach ($files as $file) {
                $file = $GLOBALS['config']['eve']['cache_dir'] . $file;
                if (is_file($file)) {
                    echo 'heh: ' . (time() - (filemtime($file)));
                    break;
                    if (time() - (filemtime($file)) > $maxAge) {
                        unlink($file);
                    }
                }
            }
        }
    }

    class eveAccount {
        var $userId = '';
        var $apiKey = '';
        var $characters = array();
        var $error = false;
        var $timeOffset = 0;

        var $db = null;

        function eveAccount($userId, $apiKey, $timeOffset = 0, $autoLoad = true) {
            $this->userId = $userId;
            $this->apiKey = $apiKey;
            $this->timeOffset = $timeOffset * 3600;

            $this->db = new eveDB();

            if ($autoLoad)
                $this->getCharacters();
        }

        function getCharacters() {
            $charData = new apiRequest('account/Characters.xml.aspx', array($this->userId, $this->apiKey));
            if ($charData->data) {
                if ($charData->data->error)
                    $this->error = array('code' => (int)$charData->data->error['code'], 'message' => (string)$charData->data->error);

                if (!$this->error)
                    foreach ($charData->data->result->rowset->row as $char)
                        $this->characters[] = new eveCharacter($this, (int)$char['characterID']);

                if (!$this->error && count($this->characters) == 0)
                    $this->error = array('code' => 1, 'message' => 'No characters (WTF?)!');
            }
        }

        function checkFullAccess() {
            $balanceTest = new apiRequest('char/AccountBalance.xml.aspx', array($this->userId, $this->apiKey, $this->characters[0]->characterID));
            if ($balanceTest->data->error)
                $this->error = array('code' => (int)$balanceTest->data->error['code'], 'message' => (string)$balanceTest->data->error);
        }
    }

?>