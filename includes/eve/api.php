<?php

require_once('curl.class.php');
require_once('api/account.php');
require_once('api/assets.php');
require_once('api/marketOrders.php');
require_once('api/marketTransactions.php');
require_once('api/industryJobs.php');
require_once('api/journal.php');
require_once('api/kills.php');
require_once('api/skills.php');
require_once('api/certificates.php');
require_once('api/mail.php');
require_once('api/character.php');
require_once('api/corporation.php');
require_once('apiCorpClasses.php');
require_once('apidb.php');
require_once('apimarket.php');
require_once('apiConstants.php');

//    $cacheDelays = array(
//        101, 103, 115, 116, 117, 119
//    );
//
//    $eveTime = time() - date('Z');

$GLOBALS['EVEAPI_ERRORS'] = array();

$GLOBALS['EVEAPI_COUNT'] = 0;
$GLOBALS['EVEAPI_CACHECOUNT'] = 0;
$GLOBALS['EVEAPI_REQUESTS'] = array();

function apiError($method, $error) {
    if (!isset($GLOBALS['EVEAPI_NO_ERRORS']) || (isset($GLOBALS['EVEAPI_NO_ERRORS']) && !$GLOBALS['EVEAPI_NO_ERRORS']))
        $GLOBALS['EVEAPI_ERRORS'][] = 'API "' . $method . '": [' . (string) $error['code'] . '] ' . (string) $error;
}

function otherError($method, $error) {
    if (!isset($GLOBALS['EVEAPI_NO_ERRORS']) || (isset($GLOBALS['EVEAPI_NO_ERRORS']) && !$GLOBALS['EVEAPI_NO_ERRORS']))
        $GLOBALS['EVEAPI_ERRORS'][] = $method . ': ' . $error;
}

class ApiError {

    var $errorCode = 0;
    var $errorText = '';

    function ApiError($errorCode, $errorText) {
        $this->errorCode = $errorCode;
        $this->errorText = $errorText;
    }

}

class apiRequest {

    var $data = false;
    var $error;

    function apiRequest($method, $apiKey, $forCharacter = false) {
        $result = false;

        $start = microtime(true);

        $http = new cURL();
        $http->setOption('CURLOPT_USERAGENT', 'Out of Eve (shrimp@shrimpworks.za.net)');
        $http->setOption('CURLOPT_TIMEOUT', 45);

        $apiUrl = $GLOBALS['config']['eve']['api_url'];
        $fetchMethod = $GLOBALS['config']['eve']['method'];

        $params = array();

        if (isset($apiKey)) {
            $params['keyID'] = $apiKey->keyID;
            $params['vCode'] = $apiKey->vCode;
            if ($forCharacter) {
                $params['characterID'] = $forCharacter;
            }
        }

        $cacheTimeAdd = $GLOBALS['config']['eve']['cache_time_add'];

        $cacheSum = md5($method . implode('.', $params));
        $cacheFile = $GLOBALS['config']['eve']['cache_dir'] . $cacheSum;

        $cacheResult = $this->checkCache($cacheFile);

        if (!$cacheResult) {
            if (strtoupper($fetchMethod == 'GET')) {
                $apiResponse = $http->get($apiUrl . '/' . $method . $this->queryString($params));
            } else {
                $apiResponse = $http->post($apiUrl . '/' . $method, $params);
            }
            $GLOBALS['EVEAPI_COUNT']++;
            $httpResponse = $http->getInfo();

            /**
             * Ensure we received no HTTP errors, and we received actual data
             */
            if (($httpResponse['http_code'] >= 200) && ($httpResponse['http_code'] <= 300) && (!empty($apiResponse))) {
                try {
                    $result = new SimpleXMLElement($apiResponse);
                } catch (Exception $e) {
                    $result = false;
                    $this->error = new ApiError($e->getCode(), $e->getMessage());
                }

                /**
                 * Loading of results from the API was successful
                 */
                if ($result) {
                    /**
                     * Received an error from the API, try to fall back to cached data which may work...
                     */
                    if (isset($result->error) && !isset($cacheResult->error)) {
                        $this->error = new ApiError((int) $result->error['code'], (string) $result->error);
                        $cacheResult = $this->checkCache($cacheFile, true);
                        if ($cacheResult) {
                            if (in_array($this->error->errorCode, $GLOBALS['cacheDelays'])) {
                                $cacheResult->cachedUntil = (string) $result->cachedUntil;
                                $this->saveCache2($cacheFile, $cacheResult->asXML(), strtotime($cacheResult->cachedUntil) + date('Z') + $cacheTimeAdd);
                            }
                            $result = $cacheResult;
                        }
                    } else {
                        /**
                         * Everything went well, save the result to cache if needed.
                         */
                        if (array_key_exists($method, $GLOBALS['config']['eve']['cache_override'])) {
                            $this->saveCache($cacheFile, $apiResponse, time() + $GLOBALS['config']['eve']['cache_override'][$method]);
                        } else if (isset($result->cachedUntil)) {
                            $this->saveCache($cacheFile, $apiResponse, strtotime($result->cachedUntil) + date('Z') + $cacheTimeAdd);
                        }
                    }
                }
            } else {
                $this->error = new ApiError(1, 'HTTP error: ' + $httpResponse['http_code']);
            }
        } else {
            $GLOBALS['EVEAPI_CACHECOUNT']++;
            $result = $cacheResult;
        }

        $GLOBALS['EVEAPI_REQUESTS'][] = array(
            'method' => $method,
            'time' => microtime(time) - $start,
            'cache' => $result == $cacheResult,
            'cacheUntil' => isset($result->cachedUntil) ? strtotime($result->cachedUntil) + date('Z') + $cacheTimeAdd : 0,
        );

        $this->data = $result;
    }

    function queryString($params) {
        $res = '?';
        foreach ($params as $key => $value) {
            $res .= $key . '=' . urlencode($value) . '&';
        }
        return substr($res, 0, -1);
    }

    function checkCache($cacheFile, $force = false) {
        $res = false;
        if (file_exists($cacheFile)) {
            if ($force || (time() <= filemtime($cacheFile))) {
                $cachedResponse = file_get_contents($cacheFile);
                if (!empty($cachedResponse)) {
                    $res = new SimpleXMLElement($cachedResponse);
                }
            }
        }

        return $res;
    }

    function saveCache($cacheFile, $cacheContent, $cachedUntil) {
        file_put_contents($cacheFile, $cacheContent);
        touch($cacheFile, $cachedUntil);
    }

    function clearOldCache() {
        // maximum cache age is 7 days - one week
        $maxAge = 3600 * 24 * 7;

        $files = scandir($GLOBALS['config']['eve']['cache_dir']);
        foreach ($files as $file) {
            $file = $GLOBALS['config']['eve']['cache_dir'] . $file;
            if (is_file($file)) {
                if (time() - (filemtime($file)) > $maxAge) {
                    unlink($file);
                }
            }
        }
    }

}

function characterName($id) {
    $charData = new apiRequest('eve/CharacterName.xml.aspx', array(), array('ids' => $id));
    if (!$charData->data) {
        return 'Lookup Error';
    }

    if ($charData->data->error) {
        apiError('eve/CharacterName.xml.aspx', $charData->data->error);
        return 'Lookup Error';
    } else {
        return (string) $charData->data->result->rowset->row['name'];
    }
}

?>