<?php
// Target server configuration
$targetHost = 'localhost';
$targetPort = 8000; // Change this to the port your target server is running on

// Extract the requested URI and query string
$requestUri = $_SERVER['REQUEST_URI'];
$queryString = $_SERVER['QUERY_STRING'];

// Construct the target URL
$targetUrl = "http://{$targetHost}:{$targetPort}{$requestUri}";
if (!empty($queryString)) {
    $targetUrl .= '?' . $queryString;
}

// Initialize a cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Forward the request headers
$headers = getallheaders();
$curlHeaders = [];
foreach ($headers as $key => $value) {
    $curlHeaders[] = "{$key}: {$value}";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Execute the cURL session
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo 'Proxy error: ' . curl_error($ch);
} else {
    // Forward the response
    $responseStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    http_response_code($responseStatusCode);
    echo $response;
}

// Close the cURL session
curl_close($ch);
?>
