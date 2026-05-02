<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$apiKey = '3ARy2dnyXBxldIiORFO2wnUsG9u5gMSc8XaGU5TK';
$merchantId = '177771843239';
$baseUrl = 'https://klikqris.com/api';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'create') {
    $orderId = $input['orderNum'];
    $amount = (int) $input['amount'];
    $keterangan = $input['productDetail'] . ' - ' . $input['method'];
    
    $payload = [
        'order_id'      => $orderId,
        'id_merchant'   => $merchantId,
        'amount'        => $amount,
        'keterangan'    => $keterangan
    ];
    
    $ch = curl_init($baseUrl . '/qris/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'id_merchant: ' . $merchantId
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Terima HTTP 200 atau 201 sebagai sukses
    if (($httpCode === 200 || $httpCode === 201) && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] === true) {
            echo json_encode([
                'success' => true,
                'qris_url' => $data['data']['qris_url'],
                'signature' => $data['data']['signature'],
                'total_amount' => $data['data']['total_amount'],
                'expired_at' => $data['data']['expired_at']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $data['message'] ?? 'Gagal membuat transaksi'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => "HTTP $httpCode - " . substr($response, 0, 200)
        ]);
    }
    
} elseif ($action === 'status') {
    $orderId = $input['orderNum'];
    
    $ch = curl_init($baseUrl . '/qris/status/' . urlencode($orderId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $apiKey,
        'id_merchant: ' . $merchantId
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (($httpCode === 200 || $httpCode === 201) && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['status']) && $data['status'] === true) {
            echo json_encode([
                'success' => true,
                'status' => $data['data']['status'],
                'paid_at' => $data['data']['paid_at'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $data['message'] ?? 'Gagal cek status'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => "HTTP $httpCode"
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>