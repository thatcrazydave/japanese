<?php
// PHP equivalent of the Node.js/Express proxy server

// Configuration
$PORT = 8080;
$LM_STUDIO_API = 'http://127.0.0.1:1234';
$TIMEOUT = 180; // 180 seconds timeout

// Enable CORS headers for all responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log all requests
$requestTime = date('c');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
error_log("[$requestTime] $requestMethod $requestUri");
error_log("Headers: " . json_encode(getallheaders(), JSON_PRETTY_PRINT));

// For non-OPTIONS requests with a body, log the request body
if ($requestMethod !== 'OPTIONS' && file_get_contents('php://input')) {
    $requestBody = file_get_contents('php://input');
    error_log("Request body: " . $requestBody);
}

// Get the request path
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route handling
switch ($requestPath) {
    case '/api/chat/completions':
        if ($requestMethod === 'POST') {
            handleChatCompletions();
        } else {
            sendMethodNotAllowed();
        }
        break;
        
    case '/v1/chat/completions':
        if ($requestMethod === 'POST') {
            handleV1ChatCompletions();
        } else {
            sendMethodNotAllowed();
        }
        break;
        
    case '/test':
        if ($requestMethod === 'GET') {
            handleTest();
        } else {
            sendMethodNotAllowed();
        }
        break;
        
    case '/test-api-connection':
        if ($requestMethod === 'GET') {
            handleTestApiConnection();
        } else {
            sendMethodNotAllowed();
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'error' => 'Not Found',
            'message' => 'Endpoint not found'
        ]);
        break;
}

// Handler for /api/chat/completions
function handleChatCompletions() {
    global $LM_STUDIO_API, $TIMEOUT;
    
    // Parse request body
    $requestBody = json_decode(file_get_contents('php://input'), true);
    
    // Make sure we have the required fields
    if (!isset($requestBody['model']) || !isset($requestBody['messages'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing required fields',
            'message' => 'The request must include model and messages fields'
        ]);
        return;
    }
    
    error_log("Forwarding to LM Studio at: {$LM_STUDIO_API}/v1/chat/completions");
    
    // Forward to LM Studio
    $ch = curl_init("{$LM_STUDIO_API}/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $TIMEOUT);
    
    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error while forwarding to LM Studio: $error");
        http_response_code(500);
        echo json_encode([
            'error' => 'Proxy Error',
            'message' => $error
        ]);
        return;
    }
    
    error_log("LM Studio response status: $statusCode");
    error_log("LM Studio response: $response");
    
    // Send back the response
    http_response_code($statusCode);
    echo $response;
}

// Handler for /v1/chat/completions
function handleV1ChatCompletions() {
    global $LM_STUDIO_API, $TIMEOUT;
    
    error_log("Forwarding to LM Studio at: {$LM_STUDIO_API}/v1/chat/completions");
    
    // Parse request body
    $requestBody = file_get_contents('php://input');
    
    // Forward to LM Studio
    $ch = curl_init("{$LM_STUDIO_API}/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $TIMEOUT);
    
    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error while forwarding to LM Studio: $error");
        http_response_code(500);
        echo json_encode([
            'error' => 'Proxy Error',
            'message' => $error
        ]);
        return;
    }
    
    error_log("LM Studio response status: $statusCode");
    
    // Send back the response
    http_response_code($statusCode);
    echo $response;
}

// Handler for /test
function handleTest() {
    global $LM_STUDIO_API;
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Proxy server is running',
        'endpoints' => [
            'chatCompletions' => '/api/chat/completions',
            'altChatCompletions' => '/v1/chat/completions',
            'healthCheck' => '/test',
            'apiConnectionTest' => '/test-api-connection'
        ],
        'lmStudioTarget' => $LM_STUDIO_API
    ]);
}

// Handler for /test-api-connection
function handleTestApiConnection() {
    global $LM_STUDIO_API;
    
    error_log("Testing connection to LM Studio API at: {$LM_STUDIO_API}");
    
    // Try to connect to the LM Studio API
    $ch = curl_init("{$LM_STUDIO_API}/v1/models");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout for the test
    
    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error connecting to LM Studio API: $error");
        
        $errorDetails = [
            'error' => 'API Connection Error',
            'message' => $error
        ];
        
        if (strpos($error, 'Connection refused') !== false) {
            $errorDetails['suggestion'] = 'Make sure LM Studio is running and the API is enabled on port 1234';
        }
        
        http_response_code(500);
        echo json_encode($errorDetails);
        return;
    }
    
    error_log("LM Studio API connection test successful");
    error_log("Available models: $response");
    
    // Parse response as JSON
    $models = json_decode($response, true);
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Successfully connected to LM Studio API',
        'models' => $models
    ]);
}

// Helper function for method not allowed responses
function sendMethodNotAllowed() {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method Not Allowed',
        'message' => 'The request method is not allowed for this endpoint'
    ]);
}

// Output server info on direct access (not through API requests)
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_HOST'])) {
    echo "=== LM Studio Proxy Server (PHP) ===\n";
    echo "Proxy server should be run with a PHP server on port $PORT\n";
    echo "Proxying requests to $LM_STUDIO_API\n";
    echo "Available endpoints:\n";
    echo "- API Endpoint: http://localhost:$PORT/api/chat/completions\n";
    echo "- Alt Endpoint: http://localhost:$PORT/v1/chat/completions\n";
    echo "- Health Check: http://localhost:$PORT/test\n";
    echo "=============================\n";
}
?> 