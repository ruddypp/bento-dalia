<?php
// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug AJAX Response</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .debug-info { margin-bottom: 20px; }
        .button { background: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .button:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>AJAX Response Debugger</h1>
        
        <div class="debug-info">
            <h2>Test AJAX Request</h2>
            <p>Enter an ID to test the AJAX endpoint:</p>
            <input type="number" id="pesanan-id" min="1" value="1">
            <button class="button" onclick="testRequest()">Test Request</button>
        </div>
        
        <div class="debug-info">
            <h2>Response Headers</h2>
            <pre id="response-headers"></pre>
        </div>
        
        <div class="debug-info">
            <h2>Raw Response</h2>
            <pre id="raw-response"></pre>
        </div>
        
        <div class="debug-info">
            <h2>Parsed JSON (if valid)</h2>
            <pre id="parsed-json"></pre>
        </div>
    </div>
    
    <script>
        function testRequest() {
            const id = document.getElementById('pesanan-id').value;
            if (!id) {
                alert('Please enter a valid ID');
                return;
            }
            
            const headersEl = document.getElementById('response-headers');
            const rawResponseEl = document.getElementById('raw-response');
            const parsedJsonEl = document.getElementById('parsed-json');
            
            headersEl.textContent = 'Loading...';
            rawResponseEl.textContent = 'Loading...';
            parsedJsonEl.textContent = 'Loading...';
            
            fetch('ajax_get_pesanan.php?id=' + id)
                .then(response => {
                    // Get response headers
                    let headerText = '';
                    for (const [key, value] of response.headers.entries()) {
                        headerText += `${key}: ${value}\n`;
                    }
                    headersEl.textContent = headerText || 'No headers received';
                    
                    return response.text();
                })
                .then(text => {
                    // Display raw response
                    rawResponseEl.textContent = text;
                    
                    // Try to parse JSON
                    try {
                        const json = JSON.parse(text);
                        parsedJsonEl.textContent = JSON.stringify(json, null, 2);
                    } catch (e) {
                        parsedJsonEl.textContent = 'Error parsing JSON: ' + e.message;
                    }
                })
                .catch(error => {
                    rawResponseEl.textContent = 'Error: ' + error.message;
                    parsedJsonEl.textContent = 'Error: ' + error.message;
                });
        }
    </script>
</body>
</html> 