<?php
// Script to fix timezone issues in the database
require_once 'config/database.php';

// Set timezone to Western Indonesia Time (WIB)
date_default_timezone_set('Asia/Jakarta');

// Get current PHP timezone information
$timezone = date('P'); // Gets timezone offset in format +07:00
$timezone_name = date_default_timezone_get(); // Gets timezone name (Asia/Jakarta)
$current_time_php = date('Y-m-d H:i:s');

echo "<h1>Timezone Fix Script</h1>";
echo "<p>This script will fix timezone issues in the database.</p>";

echo "<h2>Current Settings:</h2>";
echo "<p>PHP Timezone: $timezone_name ($timezone)</p>";
echo "<p>Current PHP Time: $current_time_php</p>";

// Try to set MySQL timezone
$set_timezone = mysqli_query($conn, "SET time_zone='$timezone'");
if ($set_timezone) {
    echo "<p style='color:green'>✅ Successfully set MySQL timezone to: $timezone</p>";
} else {
    echo "<p style='color:red'>❌ Failed to set MySQL timezone: " . mysqli_error($conn) . "</p>";
}

// Check MySQL timezone
$mysql_timezone_query = mysqli_query($conn, "SELECT @@session.time_zone, NOW()");
$mysql_timezone_row = mysqli_fetch_array($mysql_timezone_query);
$mysql_timezone = $mysql_timezone_row[0];
$mysql_current_time = $mysql_timezone_row[1];

echo "<p>MySQL Session Timezone: $mysql_timezone</p>";
echo "<p>Current MySQL Time: $mysql_current_time</p>";

// Check if times match
$time_diff = strtotime($mysql_current_time) - strtotime($current_time_php);
echo "<p>Time difference between PHP and MySQL: $time_diff seconds</p>";

if ($time_diff == 0) {
    echo "<p style='color:green'>✅ PHP and MySQL times match! Timezone is properly configured.</p>";
} else {
    echo "<p style='color:red'>❌ PHP and MySQL times don't match. There may still be timezone issues.</p>";
}

// Update the global MySQL timezone setting
echo "<h2>Permanent MySQL Timezone Configuration:</h2>";
echo "<p>To permanently set the MySQL timezone, add the following to your my.cnf or my.ini file:</p>";
echo "<pre>
[mysqld]
default-time-zone='+07:00'
</pre>";

echo "<h2>Next Steps:</h2>";
echo "<p>1. The PHP application timezone has been set to Asia/Jakarta.</p>";
echo "<p>2. The MySQL session timezone has been set to match PHP.</p>";
echo "<p>3. For permanent MySQL timezone configuration, update your MySQL configuration file.</p>";
echo "<p>4. Restart your web server and MySQL server for changes to take full effect.</p>";
?> 