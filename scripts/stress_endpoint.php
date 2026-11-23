<?php

/**
 * Stress test konkurensi untuk POST /api/seminars/{id}/register.
 *
 * Jalankan server dulu: php artisan serve --host=127.0.0.1 --port=8000
 * Lalu: php scripts/stress_endpoint.php
 */

$baseUrl = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/');
$capacity = (int) (getenv('CAPACITY') ?: 5);
$attempts = (int) (getenv('ATTEMPTS') ?: 20);
$panitiaEmail = getenv('PANITIA_EMAIL') ?: 'panitia@uinsi.test';
$password = getenv('PASSWORD') ?: 'password';

if ($capacity < 1 || $attempts <= $capacity) {
    fail('ATTEMPTS harus lebih besar dari CAPACITY.');
}

function fail(string $message): never
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function httpRequest(string $method, string $url, ?string $token, ?array $payload): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer '.$token;
    }

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($body === false) {
        fail('cURL error: '.$error);
    }

    return [$status, json_decode($body, true)];
}

[$status, $auth] = httpRequest('POST', $baseUrl.'/api/login', null, [
    'email' => $panitiaEmail,
    'password' => $password,
]);

if ($status !== 200 || ! isset($auth['data']['token'])) {
    fail('Login panitia gagal. Cek PANITIA_EMAIL/PASSWORD dan pastikan server jalan.');
}

$panitiaToken = $auth['data']['token'];

[$status, $seminarResponse] = httpRequest('POST', $baseUrl.'/api/seminars', $panitiaToken, [
    'title' => 'Stress Test '.date('Y-m-d H:i:s'),
    'speaker' => 'Panitia UINSI',
    'seminar_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
    'location' => 'Aula UINSI Samarinda',
    'capacity' => $capacity,
]);

if ($status !== 201 || ! isset($seminarResponse['data']['id'])) {
    fail('Gagal membuat seminar: '.$status.' '.json_encode($seminarResponse));
}

$seminarId = $seminarResponse['data']['id'];
$tokens = [];
$prefix = bin2hex(random_bytes(5));

for ($i = 1; $i <= $attempts; $i++) {
    $email = "stress-{$prefix}-{$i}@uinsi.test";

    [$status, $registered] = httpRequest('POST', $baseUrl.'/api/register', null, [
        'name' => 'Stress User '.$i,
        'email' => $email,
        'password' => $password,
        'role' => 'mahasiswa',
    ]);

    if (! in_array($status, [200, 201], true) || ! isset($registered['data']['token'])) {
        fail("Gagal membuat user {$email}: ".$status.' '.json_encode($registered));
    }

    $tokens[] = $registered['data']['token'];
}

echo "Menembak {$attempts} request konkurensi ke seminar #{$seminarId} (kapasitas {$capacity})...\n";

$mh = curl_multi_init();
$handles = [];

foreach ($tokens as $index => $token) {
    $ch = curl_init($baseUrl.'/api/seminars/'.$seminarId.'/register');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer '.$token,
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$index] = $ch;
}

do {
    $multiStatus = curl_multi_exec($mh, $running);
    if ($running) {
        curl_multi_select($mh, 0.1);
    }
} while ($running > 0 && $multiStatus === CURLM_OK);

$success = 0;
$full = 0;
$other = 0;

foreach ($handles as $ch) {
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $body = json_decode(curl_multi_getcontent($ch), true);
    curl_multi_remove_handle($mh, $ch);

    if ($status === 201) {
        $success++;
    } elseif (($body['code'] ?? null) === 'KURSI_PENUH') {
        $full++;
    } else {
        $other++;
    }
}

curl_multi_close($mh);

[$status, $detail] = httpRequest('GET', $baseUrl.'/api/seminars/'.$seminarId, $panitiaToken, null);
$registeredCount = $detail['data']['registered_count'] ?? null;

echo "Hasil: OK={$success}, KURSI_PENUH={$full}, LAIN={$other}, registered_count={$registeredCount}\n";

$valid = $success === $capacity
    && $full === $attempts - $capacity
    && $other === 0
    && (int) $registeredCount === $capacity;

if (! $valid) {
    fail('Overselling terdeteksi atau response tidak sesuai.');
}

echo "PASS: tidak ada overselling.\n";
echo "Catatan: script ini membuat data uji di database utama. Jalankan php artisan migrate:fresh --seed setelah selesai.\n";

exit(0);
