<?php
require_once 'Ping.php';

$host = '8.8.4.4';
$ping = new Ping($host);
$latency = $ping->ping();
if ($latency !== false) {
  print 'Latency is ' . $latency . ' ms';
}
else {
  print 'Host could not be reached.';
}
?>