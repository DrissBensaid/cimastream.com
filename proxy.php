<?php
// API Proxy Script (proxy.php)
// Store this file on your server to handle API requests securely

// Your API key - stored server-side
$apiKey = 'b6b677eb7d4ec17f700e3d4dfc31d005';

// TMDB API base URL
$baseUrl = 'https://api.themoviedb.org/3';

// Get the endpoint from the request
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint parameter']);
    exit;
}

// Start building the API URL
$url = $baseUrl . $endpoint;

// Add the API key
$url .= (strpos($url, '?') !== false) ? '&' : '?';
$url .= 'api_key=' . $apiKey;

// Add any additional parameters from the request
$params = $_GET;
unset($params['endpoint']); // Remove the endpoint parameter

foreach ($params as $key => $value) {
    $url .= '&' . $key . '=' . $value;
}

// Make the API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Return the API response with appropriate headers
header('Content-Type: application/json');
http_response_code($httpCode);
echo $response;
?>