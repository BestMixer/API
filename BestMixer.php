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
    protected function request($action, $data = [], $decodeResp = true)
    {
        if (!($c = curl_init())) {
            throw new Exception('Curl init exception');
        }

        if (!is_array($data)) {
            throw new Exception('Invalid data');
        }

        $data['api_key'] = $this->apiKey;

        $dataStr = json_encode($data);

        $opts = [
            CURLOPT_URL => $this->url . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataStr,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataStr)
            ]
        ];

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
     * @return mixed
     * @throws Exception
     */
    public function createOrder($data)
    {
        $sp = 0;

        foreach ($data['output'] as $r) {
            $sp+= $r['percent'];

            if ($r['delay'] > 2880) {
                throw new Exception('Invalid delay for ' . $r['address']);
            }
        }

        if (round($sp, 8) != 100) {
            throw new Exception('Sum percent != 100');
        }

        return $this->request('/order/create', $data, true);
    }

    /**
     * @param $id
     * @return string|stdClass
     * @throws Exception
     */
    public function getOrder($id)
    {
        $data = [
            'order_id' => $id
        ];

        return $this->request('/order/info', $data, true);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCodeInfo($id)
    {
        $data = [
            'bm_code' => $id
        ];

        return $this->request('/code/info', $data, true);
    }

}

/**
 * Example
 */

$useTor = false;
$proxy = null;
$apiToken = 'XXXXXXXXXX';
$bm_code = 'XXXXXXXXXX';
$coin = 'btc';
$fee = 0.5123;

$mixer = new BestMixer($apiToken, $useTor, $proxy);

// code/info
$resp = $mixer->getCodeInfo($bm_code);
print_r($resp); exit;

// order/create
/*
$resp = $mixer->createOrder([
    'bm_code' => $bm_code,
    'coin' => $coin,
    'fee' => $fee,
    'output' => [
        [
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 9.5,
            'delay' => 30
        ],
        [
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 30.5,
            'delay' => 0
        ],
        [
            'address' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'percent' => 60,
            'delay' => 2
        ]
    ],
]);
print_r($resp); exit;
*/

// order/info
/*
$order = 'XXXXXXXXXX';
$resp = $mixer->getOrder($order);
print_r($resp); exit;
*/
