<?php


//Written by Josh Latimer 2018
//FRC Team 3098


require './API.php';

$Parameters = new stdClass();
$Parameters->IParray = (array)[
  "192.168.1.154"
];
$Parameters->tablename = (string)JSON_decode(file_get_contents("php://input"))->tblname;
$Parameters->username = (string)"appUser";
$Parameters->password = (string)"4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2";
$Parameters->database = (string)"3098_scouting_2018";
$Parameters->key = (string)"id";


$Pull = new Pull($Parameters);


?>
