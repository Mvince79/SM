<?php
$ch = curl_init('http://127.0.0.1:8000/api/v1/clues-test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'missing_id' => '1',
    'description' => 'Automated test',
    'is_anonymous' => '1',
));
$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
echo $info['http_code'] . "\n" . $res;
