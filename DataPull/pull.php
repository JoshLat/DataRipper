<?php


//MS SQL Iteration
//Written by Josh Latimer 2019
//FRC Team 3098


require './test.php';

$Parameters = new stdClass();
//$Parameters->tablename = (string)JSON_decode(file_get_contents("php://input"))->tblname;
$Parameters->tablename = (string)"";
$Parameters->key = (string)"id";
$Parameters->connInfo = array(
  "Database"=>"ScoutingData",
  "UID"=>"sa",
  "PWD"=>'Pa$$w0rd'
);


$Pull = new Pull($Parameters);

?>
