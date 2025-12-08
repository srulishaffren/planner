<?php
// oauth-callback.php - Handles Atlassian OAuth 2.0 callback

require_once __DIR__ . '/config.php';

session_start();

// Check if user is logged in
if (empty($_SESSION['planner_logged_in'])) {
    die('Not logged in');
}

// Check for errors from Atlassian
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $desc = htmlspecialchars($_GET['error_description'] ?? 'Unknown error');
    die("OAuth Error: $error - $desc");
}

// Check for authorization code
if (!isset($_GET['code'])) {
    die('No authorization code received');
}

$code = $_GET['code'];

// Exchange authorization code for tokens
$tokenUrl = 'https://auth.atlassian.com/oauth/token';
$postData = [
    'grant_type' => 'authorization_code',
    'client_id' => $jiraClientId,
    'client_secret' => $jiraClientSecret,
    'code' => $code,
    'redirect_uri' => $jiraCallbackUrl,
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Token exchange failed (HTTP $httpCode): $response");
}

$tokens = json_decode($response, true);

if (!isset($tokens['access_token'])) {
    die('No access token in response: ' . $response);
}

// Get the cloud ID (needed for API calls)
$ch = curl_init('https://api.atlassian.com/oauth/token/accessible-resources');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $tokens['access_token'],
        'Accept: application/json',
    ],
]);

$resourcesResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Failed to get accessible resources (HTTP $httpCode): $resourcesResponse");
}

$resources = json_decode($resourcesResponse, true);

if (empty($resources)) {
    die('No accessible Jira sites found. Make sure you have access to at least one Jira site.');
}

// Find uszoom.atlassian.net or use the first available
$cloudId = null;
$siteName = null;
foreach ($resources as $resource) {
    if (strpos($resource['url'], 'uszoom.atlassian.net') !== false) {
        $cloudId = $resource['id'];
        $siteName = $resource['name'];
        break;
    }
}

// Fall back to first site if uszoom not found
if (!$cloudId && !empty($resources)) {
    $cloudId = $resources[0]['id'];
    $siteName = $resources[0]['name'];
}

// Store tokens with metadata
$tokenData = [
    'access_token' => $tokens['access_token'],
    'refresh_token' => $tokens['refresh_token'] ?? null,
    'expires_at' => time() + ($tokens['expires_in'] ?? 3600),
    'cloud_id' => $cloudId,
    'site_name' => $siteName,
    'created_at' => time(),
];

// Save to file (secure it with restrictive permissions)
$saved = file_put_contents($jiraTokenFile, json_encode($tokenData, JSON_PRETTY_PRINT));
if ($saved !== false) {
    chmod($jiraTokenFile, 0600);
}

// Redirect back to planner
header('Location: index.php?jira=connected');
exit;
