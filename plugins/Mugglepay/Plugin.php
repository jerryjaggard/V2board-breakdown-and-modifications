<?php

namespace Plugin\Mugglepay;

use App\Services\Plugin\AbstractPlugin;
use App\Contracts\PaymentInterface;

class Plugin extends AbstractPlugin implements PaymentInterface
{
    public function boot(): void
    {
        $this->filter('available_payment_methods', function ($methods) {
            $methods['Mugglepay'] = [
                'name' => $this->getConfig('display_name', 'Mugglepay'),
                'icon' => '💳',
                'plugin_code' => $this->getPluginCode(),
                'type' => 'plugin'
            ];
            return $methods;
        });
    }

    public function form(): array
    {
        return [
            'display_name' => [
                'label' => '显示名称',
                'type' => 'string',
                'required' => true,
                'description' => '支付方式在前端展示的名称',
                'default' => '加密货币支付'
            ],
            'app_secret' => [
                'label' => '应用密钥',
                'type' => 'string',
                'required' => true,
                'description' => '从 Mugglepay 控制面板获取的应用密钥'
            ]
        ];
    }

    public function pay($order): array
    {
        // Original amount in CNY
        $amountCny = ($order['total_amount'] ?? 0) / 100.0;

        // Convert CNY to USD (approx rate, can be updated)
        $usdRate = 7.1; // You can adjust or make dynamic later
        $amountUsd = round($amountCny / $usdRate, 2);

        $params = [
            'merchant_order_id' => (string)$order['trade_no'],
            'price_amount' => $amountUsd,
            'price_currency' => 'USD',
            'title' => '支付单号：' . $order['trade_no'],
            'description' => sprintf('充值 %.2f 元 ≈ %.2f USD', $amountCny, $amountUsd),
            'callback_url' => $order['notify_url'],
            'success_url' => $order['return_url'],
            'cancel_url' => $order['return_url'],
            'type' => 'FIAT'
        ];

        $params['token'] = $this->sign($this->prepareSignId($params['merchant_order_id']));
        $result = $this->mprequest($params);

        if (empty($result['payment_url'])) {
            throw new \Exception('Mugglepay 请求失败或未返回 payment_url');
        }

        return [
            'type' => 1,
            'data' => $result['payment_url']
        ];
    }

    public function notify($params)
    {
        if (!is_array($params) || empty($params)) {
            $input = file_get_contents('php://input');
            $input = str_replace(["\r", "\n", "\t", "\v"], '', (string)$input);
            $data = json_decode($input, true);
        } else {
            $data = $params;
        }

        if (empty($data['merchant_order_id'])) {
            return false;
        }

        $signStr = $this->prepareSignId($data['merchant_order_id']);
        if (empty($data['token']) || !$this->verify($signStr, $data['token'])) {
            return false;
        }

        if (strtoupper($data['status'] ?? '') !== 'PAID') {
            return false;
        }

        return [
            'trade_no' => $data['merchant_order_id'],
            'callback_no' => $data['order_id'] ?? null
        ];
    }

    private function prepareSignId(string $tradeNo): string
    {
        $data = [
            'merchant_order_id' => $tradeNo,
            'secret' => $this->getConfig('app_secret'),
            'type' => 'FIAT'
        ];
        ksort($data);
        return http_build_query($data);
    }

    private function sign(string $data): string
    {
        $secret = $this->getConfig('app_secret');
        return strtolower(md5(md5($data) . $secret));
    }

    private function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), strtolower($signature));
    }

    private function mprequest(array $data): array
    {
        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->getConfig('app_secret')
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.mugglepay.com/v1/orders',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new \Exception('Mugglepay 请求错误: ' . $err);
        }

        $decoded = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Mugglepay 返回非 JSON 格式');
        }

        return $decoded;
    }
}
