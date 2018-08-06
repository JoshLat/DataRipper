<?php

abstract class DATA {
  const PUSH = 0;
  const PULL = 1;
  const KEY1 = 0;
  const KEY2 = 1;
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
    echo($data . " NEWLN ");
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
    if ($this->query2 = $this->conn2->prepare($sql)) {
    } else {
      die($this->conn2->error);
    }
    $this->query2->execute();
    if ($this->query2->error) {
      self::console("FAILURE! Copying row to master databse! :(");
      self::console($this->query2->error);
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
    $this->conn2 = new mysqli("127.0.0.1", "appUser", "4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2", "3098_scouting_2018");
    //$this->query2 = $this->conn2->prepare("SELECT * FROM ". $tbl . " WHERE id = '" . $field . "'");
    $this->query2 = $this->conn2->prepare("SELECT * FROM " . $tbl . " WHERE id=?;");
    $this->query2->bind_param("s", $field);
    //var_dump($this->query2);
    $this->query2->execute();
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

  function HandleDataRIP($tbl, $idcol) {
    $iteration = 0;
    $duplicates = 0;
    $this->res = $this->query->get_result();
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
        if ($i == 1) {
          if (!self::CheckLocalDataDuplicates($tbl, $row[$idcol])) {
            $duplicates++;
            $this->duplicate = TRUE;
            $this->inst = "";
          }
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

  function MainSQL($tbl, $type) {
    if ($type == DATA::PULL) {
      $this->query = $this->conn->prepare("SELECT * FROM " . $tbl);
      $this->query->execute();
      if ($this->conn->error) {
        echo $this->conn->error;
      }
      if ($tbl == "scoutingdataheatmap") {
		  self::HandleDataRIP($tbl, DATA::KEY1);
	  } else {
		  self::HandleDataRIP($tbl, DATA::KEY2);
	  }
    } else if ($type == DATA::PUSH) {
      self::ExecuteSQLDEPLOY("matchschedule", "TRUNCATE matchschedule");
      $this->conn2 = new mysqli("127.0.0.1", "appUser", "4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2", "3098_scouting_2018");
      $this->query2 = $this->conn2->prepare("SELECT * FROM " . $tbl);
      $this->query2->execute();
      if ($this->conn2->error) {
        echo $this->conn2->error;
      }
      self::HandleDataDEPLOY($tbl);
    }
  }


  function __construct($tblname) {
    $this->IP = [
      "10.30.98.1",
      "10.30.98.2",
      "10.30.98.3",
      "10.30.98.4",
      "10.30.98.5",
      "10.30.98.6",
      "10.30.98.7",
      "10.30.98.8",
      "10.30.98.9",
      "10.30.98.10",
      "10.30.98.11",
      "10.30.98.12",
      "10.30.98.13",
	  "10.30.98.14"
    ];
    mysqli_report(MYSQLI_REPORT_STRICT);
    while (list(, $val) = each($this->IP)) {
      echo "Establishing Connection to: " . $val . ":3306 | Ping:";
      try {
        if (self::ping($val, 3306)) {
          $this->conn = new mysqli($val, "appUser", "4E12486C3A0F8FA2DAE48D8DBCE2A52E30DB7AC114ACDADF2357C28ACE86C1A2", "3098_scouting_2018");
          self::console("Connected!");
          if ($tblname == "matchschedule") {
            $this->MainSQL($tblname, DATA::PUSH);
          } else {
            $this->MainSQL($tblname, DATA::PULL);
          }
          //break;
        } else {
          self::console("Failed!");
        }
      }
      catch (Exception $e) {
        self::console($e->message);
      }
    }
  }


}

$pull = new Pull(JSON_decode(file_get_contents("php://input"))->tblname);

?>
