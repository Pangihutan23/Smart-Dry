<?php
echo "<h3>Current directory: " . __DIR__ . "</h3>";
$files = scandir(__DIR__);
echo "<pre>";
print_r($files);
echo "</pre>";
