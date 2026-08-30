<?php
$url = 'https://assistant-purple-vcgdakay.edgeone.dev/file.png';
$data = file_get_contents($url);

if ($data !== false && strlen($data) > 1000) {
    file_put_contents(__DIR__ . '/ปก.png', $data);
    if (!is_dir(__DIR__ . '/img')) {
        mkdir(__DIR__ . '/img', 0777, true);
    }
    file_put_contents(__DIR__ . '/img/pp.png', $data);
    echo "Successfully downloaded background image (" . strlen($data) . " bytes) to ปก.png and img/pp.png\n";
} else {
    echo "Failed to download image from {$url}\n";
}
