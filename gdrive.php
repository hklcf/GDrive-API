<?php
/*
    Version: 1.4
    Author: HKLCF
    Copyright: HKLCF
    Last Modified: 28/02/2017
*/

$url = htmlspecialchars($_GET['url']);
$support_domain = 'drive.google.com';
// active api
$get_video_info = 'https://drive.google.com/get_video_info?mobile=true&docid=';
//$get_video_info = 'https://drive.google.com/e/get_video_info?mobile=true&docid=';
//$get_video_info = 'https://docs.google.com/get_video_info?mobile=true&docid=';
//$get_video_info = 'https://docs.google.com/e/get_video_info?mobile=true&docid=';
//$get_video_info = 'https://mail.google.com/e/get_video_info?mobile=true&docid=';
//$get_video_info = 'https://docs.google.com/feeds/get_video_info?formats=ios&mobile=true&docid=';
// outdated api
//$get_video_info = 'https://spreadsheets.google.com/e/get_video_info?docid=';

if(empty($url)) {
  $url = 'https://drive.google.com/file/d/0123456789abcdefghijklmnopqr/view?usp=sharing'; // sample link
}
if($url) {
  preg_match('@^(?:https?://)?([^/]+)@i', $url, $matches);
  $host = $matches[1];
  if($host != $support_domain) {
    echo 'Please input a valid google drive url.';
    exit;
  }
}

preg_match('/(file\/d\/)(.*)(\/)/', $url, $matches);
$docid = $matches[2];

$ip = htmlspecialchars($_GET['ip']);
if(empty($ip)) {
  $ip = 'v4';
}
if($ip) {
  if($ip == 'v4') {
    $result = file_get_contents($get_video_info.$docid, false, stream_context_create(['socket' => ['bindto' => '[::]:0']])); // force IPv6
  } else {
    $result = file_get_contents($get_video_info.$docid, false, stream_context_create(['socket' => ['bindto' => '0:0']])); // force IPv4
  }
}
preg_match('/(&fmt_stream_map=)(.*)(&fmt_list)/', $result, $matches);
$result = urldecode($matches[2]);
$result = preg_replace('/[^\/]+\.(drive|docs|mail)\.google\.com/', 'redirector.googlevideo.com', $result);

$quality = [
  '37' => ['label' => '1080p', 'type' => 'video/mp4'],
  '22' => ['label' => '0720p', 'type' => 'video/mp4'],
  '59' => ['label' => '0480p', 'type' => 'video/mp4'],
  '18' => ['label' => '0360p', 'type' => 'video/mp4']
];

$links = explode(',', $result);
$output = [];
foreach($links as $direct_link) {
  $direct_link = urldecode($direct_link);
  preg_match('/https.*/', $direct_link, $matches);
  $matches = preg_replace('/&driveid=.*/', '', $matches); // remove driveid
  preg_match('/(.*)(\|)/', $direct_link, $itag);
  if(!is_null($itag[1]) || !is_null($matches[0])) {
    if(!is_null($quality[$itag[1]])) {
      $output[] = ['label' => $quality[$itag[1]]['label'], 'file' => $matches[0], 'type' => $quality[$itag[1]]['type']];
    }
  }
}

rsort($output);

$output = json_encode($output);
$output = preg_replace('/(0)(720|480|360)(p)/', '$2$3', $output); // sort fix

echo $output;
?>
