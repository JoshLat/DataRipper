<?php


//MS SQL Iteration
//Written by Josh Latimer 2019
//FRC Team 3098
//Do not edit unless you know what youre doing
//All settings that require change can be found in "pull.php"



abstract class DATA {
  const PUSH = 0;
  const PULL = 1;
}

class Pull extends DATA {
  protected $IP;
  protected $conn;
  protected $conn2;
  protected $query;
  protected $query2;
  protected $inst;
  protected $tbltype;
  protected $columns;

  protected static function console($data) {
    //ChromePhp::log("[PHP LOG] " . $data);
    //echo($data . " NEWLN ");
    echo($data. "<br>");
  }
  protected static function ping($host, $port, $timeout=0.1) {
    try {
      $fsock = @fsockopen($host, $port, $errno, $errstr, $timeout);
      if ($fsock) {
        echo " SUCCESS! | SQL: ";
        return TRUE;
      }
      if (!$fsock) {
        echo " FAILURE! | SQL: ";
        return FALSE;
      }
    }
    catch (Exception $e) {
      self::console(" Failed!");
    }
  }

  function ExecuteSQLGET($tbl, $sql) {
    if ($this->query2 = sqlsrv_prepare($this->conn2, "SELECT * FROM dbo." . $tbl, array(NULL))) {
    } else {
      die(sqlsrv_errors());
    }
    sqlsrv_execute($this->query2);
    if (!$this->query2) {
      self::console("FAILURE! Copying row to master databse! :(");
      self::console(sqlsrv_errors());
    }
    //self::console("Successfully Ripped a row of Data!\n");
  }
  function ExecuteSQLDEPLOY($tbl, $sql) {
    if ($this->query = $this->conn->prepare($sql)) {
    } else {
      die($this->conn->error);
    }
    $this->query->execute();
    if ($this->query->error) {
      self::console("FAILURE! Deploying row to master databse! :(");
      self::console($this->query->error);
    }
    //self::console("Successfully Ripped a row of Data!\n");
  }
  function CheckLocalDataDuplicates($tbl, $field) {
    //$this->query2 = $this->conn2->prepare("SELECT * FROM ". $tbl . " WHERE id = '" . $field . "'");
    $this->query2 = sqlsrv_prepare($this->conn2, "SELECT * FROM " . $tbl . " WHERE id=" . $field . ";");
    //var_dump($this->query2);
    sqlsrv_execute($this->query2);
    //SJFEIOGJLGJKSDJFLKDSJFJLKDSJFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF;A
    if ($this->conn2->error) {
      echo $this->conn2->error;
    }
    $result = $this->query2->get_result();
    //var_dump($result);
    //echo $result->num_rows;
    if ($result->num_rows == 0) {
      return TRUE;
    } else if ($result->num_rows > 0) {
      return FALSE;
    }
  }

  function GetKeyIndex($result, $key) {
    for ($i = 0; $i <= (sqlsrv_num_fields($result) - 1); $i++) {
      if (sqlsrv_field_metadata($result)[$i]["Name"] == $key) {
        self::console("Found a field matching specified primary key at index: " . $i);
        return $i;
      }
    }
  }

  function HandleDataRIP($tbl, $key) {
    self::console("Starting HandleDataRIP");
    $iteration = 0;
    $duplicates = 0;
    //$this->res = $this->query->get_result();
    $this->res = $this->query;
    self::console($this->res);
    $this->columns = (sqlsrv_num_fields($this->query) - 1);
    self::console($this->columns);
    self::console("Parsing Data.... DONE!");
    self::console("Removing Duplicates.... DONE!");
    $rowcount = 0;
    //var_dump($this->res->fetch_field_direct(2));
    //echo $this->GetKeyIndex($this->res);
    //break;
    self::console("Getting Key's Index.... DONE!");
    $keyindex = $this->GetKeyIndex($this->res, $key);

    while ($row = sqlsrv_fetch_array($this->res, SQLSRV_FETCH_NUMERIC)) {
      self::console("Iterations: " . $rowcount);
      $rowcount++;
      $this->inst = $this->inst . "INSERT INTO " . $tbl . " VALUES (";
      $this->duplicate = FALSE;
      for ($i = 0; $i < sizeof($row); $i++) {
        $iteration++;
        if ($i == 1) {
          /*if (!self::CheckLocalDataDuplicates($tbl, $row[$keyindex])) {
            $duplicates++;
            $this->duplicate = TRUE;
            $this->inst = "";
          }*/
        }

        if ($i == $this->columns) {
          if ($this->duplicate == FALSE) {
            $data = $row[$i];
            if (is_string($data)) $this->inst = $this->inst . '"' . $data . '"); ';
            if (is_int($data)) $this->inst = $this->inst . $data . "); ";
            if (is_null($data)) $this->inst = $this->inst . "NULL); ";
            $this->duplicate = FALSE;
            self::ExecuteSQLGET($tbl, $this->inst);
          }
        } else if ($this->duplicate == FALSE) {
          $data = $row[$i];
          if (is_string($data)) $this->inst = $this->inst . '"' . $data . '", ';
          if (is_int($data)) $this->inst = $this->inst . $data . ", ";
          if (is_null($data)) $this->inst = $this->inst . "NULL, ";
        }
      }
      $this->inst = "";
    }
    self::console("\nDuplicate Rows Removed: ROWSB" . $duplicates);
    self::console("\nFINISH: Transferred ROWSA" . ($rowcount - $duplicates) . " Rows to the Master Database!");
    self::console("-----------------------------------------END-------------------------------------------------");
  }
  function HandleDataDEPLOY($tbl) {
    $iteration = 0;
    $duplicates = 0;
    $this->res = $this->query2->get_result();
    $this->columns = ($this->res->field_count - 1);
    self::console("Parsing Data.... DONE!");
    self::console("Removing Duplicates.... DONE!");
    $rowcount = 0;
    while ($row = $this->res->fetch_row()) {
      $rowcount++;
      $this->inst = $this->inst . "INSERT INTO " . $tbl . " VALUES (";
      $this->duplicate = FALSE;
      for ($i = 0; $i < sizeof($row); $i++) {
        $iteration++;

        if ($i == ($this->columns - 1)) {
          $data = $row[$i];
          if (is_string($data)) $this->inst = $this->inst . '"' . $data . '"); ';
          if (is_int($data)) $this->inst = $this->inst . $data . "); ";
          if (is_null($data)) $this->inst = $this->inst . "NULL); ";
          $this->duplicate = FALSE;
          self::ExecuteSQLDEPLOY($tbl, $this->inst);
        } else if ($this->duplicate == FALSE) {
          $data = $row[$i];
          if (is_string($data)) $this->inst = $this->inst . '"' . $data . '", ';
          if (is_int($data)) $this->inst = $this->inst . $data . ", ";
          if (is_null($data)) $this->inst = $this->inst . "NULL, ";
        }
      }
      $this->inst = "";
    }
    self::console("\nFINISH: Transferred ROWSA" . ($rowcount - $duplicates) . " Rows to the Client Database!");
    self::console("-----------------------------------------END-------------------------------------------------");
  }

  function MainSQL($tbl, $type, $key) {
    self::console("Starting MainSQL");
    if ($type == DATA::PULL) {
      $this->query = sqlsrv_prepare($this->conn, "SELECT * FROM dbo." . $tbl, array(NULL));
      //$this->query = $this->conn->prepare("SELECT * FROM " . $tbl);
      sqlsrv_execute($this->query);
      if (!$this->query) {
        echo sqlsrv_errors();
      }
      if ($tbl == "scoutingdataheatmap") {
		  self::HandleDataRIP($tbl, $key);
	  } else {
		  self::HandleDataRIP($tbl, $key);
	  }
    } else if ($type == DATA::PUSH) {
      self::ExecuteSQLDEPLOY("matchschedule", "TRUNCATE matchschedule");
      $this->conn2 = new mysqli("127.0.0.1", "appUser", "4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2", "3098_scouting_2018");
      $this->query2 = $this->conn2->prepare("SELECT * FROM " . $tbl);
      $this->query2->execute();
      if (sqlsrv_errors()) {
        echo sqlsrv_errors();
      }
      self::HandleDataDEPLOY($tbl);
    }
  }


  function __construct($params) {
    //mysqli_report(MYSQLI_REPORT_STRICT);

  for ($ip = 2; $ip <= 20; $ip++) {
    echo "Establishing Connection to: 10.30.98." . (string)$ip . ":1433 | Ping:";
    try {
      if (self::ping("10.30.98." . (string)$ip, 1433)) {
        $serverName = "10.30.98." . (string)$ip . "\\MSSQLSERVER, 1433";
        $this->conn = sqlsrv_connect($serverName, $params->connInfo);
        if (!$this->conn) {
          die( print_r( sqlsrv_errors(), true));
          self::console("Failed!");
        } else {
          self::console("Connected!");
          $ip=21;
        }
        if (!$this->conn2) {
          $this->conn2 = sqlsrv_connect("localhost", $params->connInfo);
        }
        if ($params->tablename == "matchschedule") {
          //$this->MainSQL($params->tablename, DATA::PUSH, $params->key);
        } else {

          $this->MainSQL($params->tablename, DATA::PULL, $params->key);
        }
        //break;
        } else {
          self::console("Failed!");
        }
      }
      catch (Exception $e) {
        self::console($e->getMessage());
      }
    }
  }


}

//$pull = new Pull(JSON_decode(file_get_contents("php://input"))->tblname);
//$pull = new Pull("pitscoutingdata");

?>
