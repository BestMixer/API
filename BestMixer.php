<?php

/**
 * Created by BestMixer.io
 */
class BestMixer
{
    protected $apiKey, $proxyType, $proxy, $url;
    
    /**
     * BestMixer constructor.
     * @param $apiKey
     * @param bool $useTor
     * @param null $proxy
     * @param int $proxyType
     * @throws Exception
     */
    public function __construct($apiKey, $useTor = false, $proxy = null, $proxyType = null)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('CURL not loaded');
        }
        $this->url = ($useTor ? 'http://bestmixer7o57mba.onion' : 'https://bestmixer.io') . '/api/ext';
        $this->apiKey = $apiKey;
        if ($proxy) {
            $this->proxy = $proxy;
            if ($useTor) {
                $this->proxyType  = 7;
            } elseif ($proxyType) {
                $this->proxyType  = $proxyType;
            } else {
                $this->proxyType = CURLPROXY_SOCKS5;
            }
        }
    }
    
    /**
     * @param $action
     * @param array $data
     * @param bool $decodeResp
     * @return mixed
     * @throws Exception
     */
    protected function request($action, $data = array(), $decodeResp = true)
    {
        if (!($c = curl_init())) {
            throw new Exception('Curl init exception');
        }
        if (!is_array($data)) {
            throw new Exception('Invalid data');
        }
        $data['api_key'] = $this->apiKey;
        $dataStr = json_encode($data);
        $opts = array(
            CURLOPT_URL => $this->url . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataStr,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataStr)
            )
        );
        if ($this->proxy) {
            $opts[CURLOPT_PROXY] = $this->proxy;
            $opts[CURLOPT_PROXYTYPE] = $this->proxyType;
        }
        curl_setopt_array($c, $opts);
        $resp = curl_exec($c);
        $error = null;
        if (curl_error($c)) {
            $error = curl_error($c);
        }
        curl_close($c);
        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }
        if ($decodeResp) {
            $jsonObj = json_decode($resp, true);
            if ($jsonObj === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to decode response: ' . $resp);
            }
            $resp = $jsonObj;
        }
        return $resp;
    }
    
    /**
     * @param $data
     * @return json order/create
     * @throws Exception
     */
    public function createOrder($data)
    {
        $sp = 0;
        foreach ($data['output'] as $r) {
            $sp += $r['percent'];
            if ($r['delay'] > 4320) {
                throw new Exception('Invalid delay for ' . $r['address']);
            }
        }
        if (round($sp, 8) != 100) {
            throw new Exception('Sum percent != 100');
        }
        return $this->request('/order/create', $data, true);
    }
    
    /**
     * @param $data
     * @return json order/create_fixed
     * @throws Exception
     */
    public function createOrderFixed($data)
    {
        $sp = 0;
        foreach ($data['output'] as $r) {
            $sp += $r['percent'];
            if ($r['delay'] > 4320) {
                throw new Exception('Invalid delay for ' . $r['address']);
            }
        }
        return $this->request('/order/create_fixed', $data, true);
    }
    
    /**
     * @param $id
     * @return json order/info
     */
    public function getOrder($id)
    {
        $data = array(
            'order_id' => $id
        );
        return $this->request('/order/info', $data, true);
    }
    
    /**
     * @param $id
     * @return json code/info
     */
    public function getCodeInfo($id)
    {
        $data = array(
            'bm_code' => $id
        );
        return $this->request('/code/info', $data, true);
    }
    
    /**
     * @return json fee/info
     */
    public function getFeeInfo()
    {
        $data = array();
        return $this->request('/fee/info', $data, true);
    }
}

/**
 * Example
 */
$useTor = false;
$proxy = null;
$apiToken = 'XXXXXXXXXX'; // API key
$bm_code = 'XXXXXXXXXX'; // BestMixer code
$coin = 'btc'; // btc, bch, ltc, eth
$fee = '1.2345'; // 1.0000 - 5.0000
$mixer = new BestMixer($apiToken, $useTor, $proxy);

// code/info
/*
$resp = $mixer->getCodeInfo($bm_code);
print_r($resp); exit;
*/

// fee/info
/*
$resp = $mixer->getFeeInfo();
print_r($resp); exit;
*/

// order/create
/*
$resp = $mixer->createOrder(array(
    'bm_code' => $bm_code,
    'coin' => $coin,
    'fee' => $fee,
    'output' => array(
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 9.5,
            'delay' => 4320
        ),
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 30.5,
            'delay' => 0
        ),
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 60,
            'delay' => 120
        )
    ),
));
print_r($resp); exit;
*/

// order/create_fixed
/*
$resp = $mixer->createOrderFixed(array(
    'bm_code' => $bm_code,
    'coin' => $coin,
    'fee' => $fee,
    'output' => array(
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'amount' => 0.1,
            'delay' => 4320
        ),
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'amount' => 0.1,
            'delay' => 0
        ),
        array(
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'amount' => 0.1,
            'delay' => 120
        )
    ),
));
print_r($resp); exit;
*/

// order/info
/*
$order = 'XXXXXXXXXX';
$resp = $mixer->getOrder($order);
print_r($resp); exit;
*/
