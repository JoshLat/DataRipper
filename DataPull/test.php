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
  protected $L_conn;
  protected $R_conn;
  protected $R_query;
  protected $L_query;
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

  function TransferToMaster($tbl, $sql) {
    self::console($sql);
    if ($this->L_query = sqlsrv_prepare($this->R_conn, $sql, array(NULL))) {
    } else {
      die(sqlsrv_errors());
    }
    sqlsrv_execute($this->L_query);
    if (sqlsrv_errors()) {
      self::console("FAILURE! Copying row to master databse! :(");
      var_dump(sqlsrv_errors());
      return;
    }
    self::console("Successfully Ripped a row of Data!");
  }
  function ExecuteSQLDEPLOY($tbl, $sql) {
    if ($this->R_query = $this->L_conn->prepare($sql)) {
    } else {
      die($this->L_conn->error);
    }
    $this->R_query->execute();
    if ($this->R_query->error) {
      self::console("FAILURE! Deploying row to master databse! :(");
      self::console($this->R_query->error);
    }
    //self::console("Successfully Ripped a row of Data!\n");
  }
  function CheckLocalDataDuplicates($tbl, $field) {
    self::console("Checking Duplicates....");
    //$this->L_query = $this->R_conn->prepare("SELECT * FROM ". $tbl . " WHERE id = '" . $field . "'");
    $this->L_query = sqlsrv_prepare($this->R_conn, "SELECT * FROM " . $tbl . " WHERE id=" . $field . ";");
    //var_dump($this->L_query);
    sqlsrv_execute($this->L_query);
    //SJFEIOGJLGJKSDJFLKDSJFJLKDSJFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF;A
    //var_dump($result);
    //echo $result->num_rows;
    if (sqlsrv_num_rows($this->L_query) == 0) {
      self::console("NON-DUPLICATE");
      return TRUE;
    } else if (sqlsrv_num_rows($this->L_query) > 0) {
      self::console("DUPLICATE");
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

  function GetFieldNames($result) {
    $fields = NULL;
    for ($i = 0; $i <= (sqlsrv_num_fields($result) - 1); $i++) {
      $inst = sqlsrv_field_metadata($result)[$i]["Name"];
      self::console(sqlsrv_field_metadata($result)[$i]["Name"]);
      if ($i == (sqlsrv_num_fields($result) - 1)) {
        $fields = $fields . ", " . $inst . ")";
      } else if ($i > 0) {
        $fields = $fields . ", " . $inst;
      } else {
        $fields = $fields . "(" . $inst;
      }
    }
    self::console("Created FieldNames for insert statement!");
    //self::console($fields);
    return $fields;
  }

  function HandleDataRIP($tbl, $key) {
    self::console("Starting HandleDataRIP");
    $iteration = 0;
    $duplicates = 0;
    //$this->res = $this->R_query->get_result();
    $this->res = $this->R_query;
    self::console($this->res);
    $this->columns = (sqlsrv_num_fields($this->R_query) - 1);
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
      $this->inst = $this->inst . "INSERT INTO " . $tbl . " " . self::GetFieldNames($this->res). " VALUES (";
      $this->duplicate = FALSE;
      for ($i = 0; $i < sizeof($row); $i++) {
        $iteration++;
        if ($i == 1) {
          if (!self::CheckLocalDataDuplicates($tbl, $row[$keyindex])) {
            $duplicates++;
            $this->duplicate = TRUE;
            $this->inst = "";
          }
        }

        if ($i == $this->columns) {
          if ($this->duplicate == FALSE) {
            $data = $row[$i];
            if (is_string($data)) $this->inst = $this->inst . "'" . $data . "'); ";
            if (is_int($data)) $this->inst = $this->inst . $data . '); ';
            if (is_null($data)) $this->inst = $this->inst . "NULL); ";
            $this->duplicate = FALSE;
            self::TransferToMaster($tbl, $this->inst);
          }
        } else if ($this->duplicate == FALSE) {
          $data = $row[$i];
          if (is_string($data)) $this->inst = $this->inst . "'" . $data . "', ";
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
    $this->res = $this->L_query->get_result();
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
      $this->R_query = sqlsrv_prepare($this->L_conn, "SELECT * FROM dbo." . $tbl, array(NULL));
      //$this->R_query = $this->L_conn->prepare("SELECT * FROM " . $tbl);
      sqlsrv_execute($this->R_query);
      if (!$this->R_query) {
        echo sqlsrv_errors();
      }
      if ($tbl == "scoutingdataheatmap") {
		  self::HandleDataRIP($tbl, $key);
	  } else {
		  self::HandleDataRIP($tbl, $key);
	  }
    } else if ($type == DATA::PUSH) {
      self::ExecuteSQLDEPLOY("matchschedule", "TRUNCATE matchschedule");
      $this->R_conn = new mysqli("localhost", "appUser", "4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2", "3098_scouting_2018");
      $this->L_query = $this->R_conn->prepare("SELECT * FROM " . $tbl);
      $this->L_query->execute();
      if (sqlsrv_errors()) {
        echo sqlsrv_errors();
      }
      self::HandleDataDEPLOY($tbl);
    }
  }


  function __construct($params) {
    //mysqli_report(MYSQLI_REPORT_STRICT);

  for ($ip = 1; $ip <= 20; $ip++) {
    echo "Establishing connection to: 10.30.98." . (string)$ip . ":1433 | Ping:";
    try {
      if (self::ping("10.30.98." . (string)$ip, 1433)) {
        $serverName = "10.30.98." . (string)$ip . "\\MSSQLSERVER, 1433";
        $this->L_conn = sqlsrv_connect($serverName, $params->connInfo);
        if (!$this->L_conn) {
          die( print_r( sqlsrv_errors(), true));
          self::console("Failed!");
        } else {
          self::console("connected!");
          $ip=21;
        }
        if (!$this->R_conn) {
          $this->R_conn = sqlsrv_connect("localhost", $params->connInfo);
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
