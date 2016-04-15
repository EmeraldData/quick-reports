<?php 

class db {
	protected $db; 
	
	public function __construct() {

		try {								
			$this->db= new PDO('pgsql:host='.QR_PGSQL_HOST.';port='.QR_PGSQL_PORT.';dbname='.QR_PGSQL_DBNAME.';user='.QR_PGSQL_USER.';password='.QR_PGSQL_PASSWORD);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e) {
			echo 'Error connecting to database. ';
			echo $e->getMessage();
			die;
		}
	}
	
	
	public function beginTransaction() {
		return $this->db->beginTransaction();
	}
	
	
	public function commit() {
		return $this->db->commit();
	}
	
	
	public function rollback() {
		return $this->db->rollback();
	}
	
	
	public function executeQuery($query, $paramsArray, $returnType) {
		
		try {
	        $stmt = $this->db->prepare($query);
	        $success = $stmt->execute($paramsArray);
	        if(! $success) die('Unable to read from database.');
	        if ($returnType==QR_QUERY_RETURN_ONE_ROW)
	        	$result = $stmt->fetch(PDO::FETCH_OBJ);
	        elseif ($returnType==QR_QUERY_RETURN_ALL_ROWS)	
	        	$result = $stmt->fetchAll(PDO::FETCH_OBJ);
	        else 
	        	$result=null;
			return($result);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}	
	}

	
	public function __destruct() {
            $this->db = null;
    }

    private function handleDatabaseErrors($e) {
    	echo ' Database error. ';
    	echo $e->getMessage();
    	die;
    }
    
}
?>
