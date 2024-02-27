<?php
// Target server configuration
$targetHost = '127.0.0.1';
$targetPort = 3000; // Change this to the port your target server is running on

$targetDomain = 'eswayer.com';
$hostDomain = 'git.eswayer.com';

// Extract the requested URI and query string
$requestUri = $_SERVER['REQUEST_URI'];
$queryString = $_SERVER['QUERY_STRING'];

// Construct the target URL
$targetUrl = "http://{$targetHost}:{$targetPort}{$requestUri}";

// Initialize a cURL session
$ch = curl_init();

// Set the option to return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // We handle output directly


// Set cURL options
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

curl_setopt($ch, CURLOPT_REFERER, 'http://'.$targetDomain.':'.$targetPort);

// Forward the request headers
$headers = getallheaders();
$curlHeaders = [];
foreach ($headers as $key => $value) {
    if($key == 'Host') $value = $targetDomain;
    $curlHeaders[] = "{$key}: {$value}";
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders); // disable it if the target server is confused by the request

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Initialize a flag to track whether headers have been fully parsed
$headersParsed = false;
// Buffer for storing the initial part of the response until headers are parsed
$responseBuffer = '';

curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$headersParsed, &$responseBuffer) {
    if (!$headersParsed) {
        // Append the new chunk to the buffer for parsing headers
        $responseBuffer .= $chunk;
        // Check if the end of headers (a blank line) is in the buffer
        $endOfHeadersPos = strpos($responseBuffer, "\r\n\r\n");
        if ($endOfHeadersPos !== false) {
            // Headers are complete; process them
            $headersPart = substr($responseBuffer, 0, $endOfHeadersPos);
            $bodyPart = substr($responseBuffer, $endOfHeadersPos + 4); // +4 to skip the blank line
            // Reset buffer as it's no longer needed
            $responseBuffer = '';
            // Mark headers as parsed
            $headersParsed = true;

            // Process headers
            $headers = explode("\r\n", $headersPart);
            foreach ($headers as $header) {
                if (!empty($header)) { // Skip empty lines
                    header($header);
                }
            }

            // Output the body part that was in the buffer
            echo $bodyPart;
            flush();
        }
        else {
            // Force headers
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
            header("Access-Control-Expose-Headers: Content-Length, X-Kuma-Revision");

        }
    } else {
        // Headers are parsed, directly output the chunk as body
        echo $chunk;
        flush();
    }

    return strlen($chunk); // Indicate the full chunk was handled
});

// Since CURLOPT_RETURNTRANSFER is false, curl_exec() will output the response directly.
curl_exec($ch);

// Close the cURL session
curl_close($ch);