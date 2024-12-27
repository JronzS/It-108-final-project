<?php
require_once(LIB_PATH_INC.DS."config.php");

class database {

    public $con;       // make it public so we can reference $db->con if needed
    public $query_id;

    function __construct() {
      $this->db_connect();
    }

    /*--------------------------------------------------------------*/
    /* Function for Open database connection
    /*--------------------------------------------------------------*/
    public function db_connect()
    {
      $conn_string = sprintf(
        "host=%s dbname=%s user=%s password=%s",
        DB_HOST,
        DB_NAME,
        DB_USER,
        DB_PASS
      );

      $this->con = pg_connect($conn_string);

      if (!$this->con) {
        die("Database connection failed: " . pg_last_error());
      }
    }

    /*--------------------------------------------------------------*/
    /* Function for Close database connection
    /*--------------------------------------------------------------*/
    public function db_disconnect()
    {
      if (isset($this->con)) {
        pg_close($this->con);
        unset($this->con);
      }
    }

    /*--------------------------------------------------------------*/
    /* Function for pg_query
    /*--------------------------------------------------------------*/
    public function query($sql)
    {
      if (trim($sql) != "") {
        $this->query_id = pg_query($this->con, $sql);
      }
      if (!$this->query_id) {
        // Log the error
        error_log("SQL Error: " . pg_last_error($this->con) . "\nQuery: " . $sql);
        // Return false instead of killing the script
        return false;
      }
      return $this->query_id;
    }
    
    
    /*--------------------------------------------------------------*/
    /* Function for Query Helper
    /*--------------------------------------------------------------*/
    public function fetch_array($statement)
    {
      return pg_fetch_array($statement);
    }

    public function fetch_object($statement)
    {
      return pg_fetch_object($statement);
    }

    public function fetch_assoc($statement)
    {
      return pg_fetch_assoc($statement);
    }

    public function num_rows($statement)
    {
      return pg_num_rows($statement);
    }


    public function insert_id()
    {
      $result = pg_query($this->con, "SELECT LASTVAL()");
      if ($result) {
        $row = pg_fetch_row($result);
        return $row[0]; // This should be the last inserted sequence value
      }
      return null;
    }

    public function affected_rows()
    {
      // pg_affected_rows() works on the result resource
      return pg_affected_rows($this->query_id);
    }

    /*--------------------------------------------------------------*/
    /* Function for escaping strings
    /*--------------------------------------------------------------*/
    public function escape($str){
      return pg_escape_string($this->con, $str);
    }

    /*--------------------------------------------------------------*/
    /* Function for while loop
    /*--------------------------------------------------------------*/
    public function while_loop($loop){
      $results = array();
      while ($result = $this->fetch_array($loop)) {
        $results[] = $result;
      }
      return $results;
    }
}

$db = new database();

$con = $db->con;
?>
