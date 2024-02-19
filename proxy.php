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
curl_setopt($ch, CURLOPT_HEADER, true);

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

// Capture and forward the response headers
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
foreach (explode("\r\n", $headers) as $header) {
    if (strpos($header, ':')) {
        header($header);
    }
}

// Return the body of the response
echo substr($response, $headerSize);

// Close the cURL session
curl_close($ch);
?>
