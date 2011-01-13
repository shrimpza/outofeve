<?php

    require_once('curl.class.php');

    class marketRequest {
        var $cachedResponse = '';
        var $response = '';
        var $data = null;

        function marketRequest($typeId, $regionId = 0) {
            $http = new cURL();

            $params = 'typeid=' . $typeId;
            if ($regionId > 0)
                $params .= '&regionlimit=' . $regionId;

            $cached = $this->checkCache($params);

            $http->setOption('CURLOPT_USERAGENT', 'Out of Eve (shrimp@shrimpworks.za.net)');
            $http->setOption('CURLOPT_TIMEOUT', 15);

            if (!$cached) {
                $this->response = $http->get('http://eve-central.com/api/marketstat?'.$params);
            }

            try {
                $this->data = @new SimpleXMLElement($this->response);
            } catch (Exception $e) {
                if (!empty($this->cachedResponse)) {
                    try {
                        $this->data = @new SimpleXMLElement($this->cachedResponse);
                    } catch (Exception $e) {
                    }
                }
            }

            if (!$cached) {
                $this->saveCache($params, time() + (3600*6));
            }
        }

        function checkCache($params) {
            $cacheSum = md5($params);
            $cacheFile = $GLOBALS['config']['eve']['cache_dir'] . 'market/' . $cacheSum;

            if (file_exists($cacheFile)) {
                $this->cachedResponse = file_get_contents($cacheFile);
                if (time() <= (filemtime($cacheFile))) {
                    $this->response = $this->cachedResponse;
                    return true;
                }
            }

            return false;
        }

        function saveCache($params, $cachedUntil) {
            $cacheSum = md5($params);
            $cacheFile = $GLOBALS['config']['eve']['cache_dir'] . 'market/' . $cacheSum;

            file_put_contents($cacheFile, $this->response);
            touch($cacheFile, $cachedUntil);
        }
    }

    class ItemPricing {
        var $typeID = 0;
        var $regionID = 0;

        var $minSell = 0;
        var $maxSell = 0;
        var $avgSell = 0;
        var $qtySell = 0;

        var $minBuy = 0;
        var $maxBuy = 0;
        var $avgBuy = 0;
        var $qtyBuy = 0;

        function ItemPricing($typeId, $regionId = 0) {
            $this->typeID = $typeId;
            $this->regionID = $regionId;

            if ($typeId > 0) {
                $priceReq = new marketRequest($typeId, $regionId);

                $this->minSell = (float)$priceReq->data->marketstat->type->sell->min;
                $this->maxSell = (float)$priceReq->data->marketstat->type->sell->max;
                $this->avgSell = (float)$priceReq->data->marketstat->type->sell->avg;
                $this->medSell = (float)$priceReq->data->marketstat->type->sell->median;
                $this->qtySell = (int)$priceReq->data->marketstat->type->sell->volume;

                $this->minBuy = (float)$priceReq->data->marketstat->type->buy->min;
                $this->maxBuy = (float)$priceReq->data->marketstat->type->buy->max;
                $this->avgBuy = (float)$priceReq->data->marketstat->type->buy->avg;
                $this->medBuy = (float)$priceReq->data->marketstat->type->buy->median;
                $this->qtyBuy = (int)$priceReq->data->marketstat->type->buy->volume;
            }
        }
    }

?>