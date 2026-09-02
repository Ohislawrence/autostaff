<?php

// Standalone smoke test for the Nomdal Connect WordPress plugin client.
// Stubs the minimal WordPress API so the client runs outside of WordPress,
// and verifies the security-critical request signing logic.

error_reporting(E_ALL);

// --- WordPress function stubs (global) ---
function get_option($key, $default = '')
{
    global $OPTIONS;

    return $OPTIONS[$key] ?? $default;
}

function wp_json_encode($data)
{
    return json_encode($data);
}

function add_query_arg($args, $url)
{
    if (empty($args)) {
        return $url;
    }

    return $url.(strpos($url, '?') === false ? '?' : '&').http_build_query($args);
}

function is_wp_error($thing)
{
    return false;
}

function wp_remote_request($url, $args)
{
    global $LAST_REQUEST;
    $LAST_REQUEST = ['url' => $url, 'args' => $args];

    return ['response' => ['code' => 200], 'body' => json_encode(['status' => 'ok'])];
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'];
}

function wp_remote_retrieve_body($response)
{
    return $response['body'];
}

require __DIR__.'/nomdal-connect/includes/ApiResponse.php';
require __DIR__.'/nomdal-connect/includes/ApiClient.php';

use Nomdal\ApiClient;

$OPTIONS = [
    'nomdal_base_url' => 'https://app.nomdal.com',
    'nomdal_api_key' => 'ak_test.test',
    'nomdal_signing_secret' => 'shhh-secret',
];

$GLOBALS['assertions'] = 0;

function assert_true($condition, string $message): void
{
    $GLOBALS['assertions']++;
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "ok - {$message}\n";
}

$client = ApiClient::fromSettings();
$response = $client->request('POST', '/leads', ['first_name' => 'Jane']);

$req = $GLOBALS['LAST_REQUEST'];
$headers = $req['args']['headers'];

assert_true($req['url'] === 'https://app.nomdal.com/api/v1/leads', 'URL is correct');
assert_true($headers['Authorization'] === 'Bearer ak_test.test', 'Authorization header is set');
assert_true(isset($headers['X-Timestamp']), 'X-Timestamp header is present');
assert_true(isset($headers['X-Signature']), 'X-Signature header is present');
assert_true($headers['Content-Type'] === 'application/json', 'Content-Type is JSON');

$timestamp = $headers['X-Timestamp'];
$body = json_encode(['first_name' => 'Jane']);
$canonical = implode("\n", ['POST', '/api/v1/leads', $timestamp, $body]);
$expected = hash_hmac('sha256', $canonical, 'shhh-secret');

assert_true(hash_equals($expected, $headers['X-Signature']), 'HMAC signature matches expected value');
assert_true($response->success === true, 'Successful response is parsed as success');

// Heartbeat: POST with no body must also sign correctly.
$GLOBALS['LAST_REQUEST'] = null;
$client->heartbeat();
$req = $GLOBALS['LAST_REQUEST'];
$headers = $req['args']['headers'];
$timestamp = $headers['X-Timestamp'];
$canonical = implode("\n", ['POST', '/api/v1/plugins/heartbeat', $timestamp, '']);
$expected = hash_hmac('sha256', $canonical, 'shhh-secret');

assert_true(hash_equals($expected, $headers['X-Signature']), 'Heartbeat HMAC signature matches');

echo "\nAll {$GLOBALS['assertions']} assertions passed.\n";
