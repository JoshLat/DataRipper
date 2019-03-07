<?php


//MS SQL Iteration
//Written by Josh Latimer 2019
//FRC Team 3098


require './test.php';

$Parameters = new stdClass();
//$Parameters->tablename = (string)JSON_decode(file_get_contents("php://input"))->tblname;
$Parameters->tablename = (string)"scoutingData";
$Parameters->key = (string)"scoutingData_DateTime";
$Parameters->connInfo = array(
  "Database"=>"ScoutingData",
  "UID"=>"sa",
  "PWD"=>'saPa$$w0rd',
  "ReturnDatesAsStrings"=>"true"
);


$Pull = new Pull($Parameters);

?>
