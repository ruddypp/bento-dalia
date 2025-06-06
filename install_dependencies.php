<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Dependencies</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .warning {
            color: orange;
            font-weight: bold;
        }
        .code {
            background-color: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Install Dependencies for Export Feature</h1>
    
    <p>This script will check if Composer is installed and then install the required dependencies for the PDF and Excel export features.</p>
    
    <h2>Check Composer Installation</h2>
    <?php
    // Check if Composer is installed
    exec('composer --version', $output, $return_var);
    
    if ($return_var !== 0) {
        echo '<div class="error">Composer is not installed or not in the system PATH.</div>';
        echo '<p>Please install Composer first:</p>';
        echo '<ol>';
        echo '<li>Download Composer from <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></li>';
        echo '<li>Install it following the instructions on the website</li>';
        echo '<li>Make sure Composer is in your system PATH</li>';
        echo '<li>Refresh this page after installation</li>';
        echo '</ol>';
    } else {
        echo '<div class="success">Composer is installed: ' . htmlspecialchars($output[0]) . '</div>';
        
        // Check if composer.json exists
        if (!file_exists('composer.json')) {
            echo '<div class="error">composer.json not found in the current directory.</div>';
            echo '<p>Please create a composer.json file with the following content:</p>';
            echo '<pre>{
    "require": {
        "tecnickcom/tcpdf": "^6.6",
        "phpoffice/phpspreadsheet": "^1.29"
    }
}</pre>';
        } else {
            echo '<div class="success">composer.json found.</div>';
            
            // Check if vendor directory exists
            if (!is_dir('vendor')) {
                echo '<div class="warning">Vendor directory not found. Need to install dependencies.</div>';
                
                // Install dependencies
                echo '<h2>Installing Dependencies</h2>';
                echo '<pre>';
                
                // Execute composer install
                $cmd = 'composer install';
                echo "Executing: $cmd\n\n";
                
                // Output the command result
                $descriptorspec = array(
                    0 => array("pipe", "r"),
                    1 => array("pipe", "w"),
                    2 => array("pipe", "w")
                );
                
                $process = proc_open($cmd, $descriptorspec, $pipes);
                
                if (is_resource($process)) {
                    while ($s = fgets($pipes[1])) {
                        echo htmlspecialchars($s);
                        flush();
                    }
                    while ($s = fgets($pipes[2])) {
                        echo htmlspecialchars($s);
                        flush();
                    }
                    
                    $return_value = proc_close($process);
                    echo "\nCommand returned with value: $return_value";
                }
                
                echo '</pre>';
                
                if (is_dir('vendor')) {
                    echo '<div class="success">Dependencies installed successfully!</div>';
                } else {
                    echo '<div class="error">Failed to install dependencies. Please run <span class="code">composer install</span> manually in the terminal.</div>';
                }
            } else {
                echo '<div class="success">Vendor directory found. Dependencies are already installed.</div>';
                
                // Check if we need to update
                echo '<h2>Checking for Updates</h2>';
                echo '<p>You can run the following command to update dependencies:</p>';
                echo '<div class="code">composer update</div>';
            }
        }
    }
    ?>
    
    <h2>Next Steps</h2>
    <p>Once the dependencies are installed, you should be able to use the PDF and Excel export features. If you encounter any issues, please check the following:</p>
    
    <ul>
        <li>Make sure the vendor directory has the required packages: <span class="code">tecnickcom/tcpdf</span> and <span class="code">phpoffice/phpspreadsheet</span></li>
        <li>Check that the export files exist and are properly configured:
            <ul>
                <li><span class="code">export_bahan_baku.php</span> - for exporting raw materials</li>
                <li><span class="code">export_transaksi_terbaru.php</span> - for exporting transactions</li>
            </ul>
        </li>
        <li>Ensure your web server has write permissions in the directory where the export files will be created</li>
    </ul>
    
    <p><a href="bahan_baku.php">Return to Bahan Baku page</a></p>
    <p><a href="laporan_penjualan.php">Return to Laporan Penjualan page</a></p>
</body>
</html> 