<?php
ini_set("display_errors", "On");
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit("UTF-8");
}

function post($url, $data)
{
  $opts = array('http' =>
           array(
             'method' => 'POST',
             'header' => 'Content-type: application/x-www-form-urlencoded',
             'content' => $data
           )
  );
  $context = stream_context_create($opts);
  $result = file_get_contents($url, false, $context);
  print_r($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = file_get_contents("php://input");
    post("http://127.0.0.1:65530/proxy", $post_data);
}

?>