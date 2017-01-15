<?php 
class executiveReport extends db
{
	
	public function __construct() {
		parent::__construct();			
	}
		
	public function getExecutiveReportsList() {
		//Define all of the executive reports
		
		$report = array();	
		include 'config/executiveReports.config.php';
		return $report;
	}


	function getOUInfo($OUList, $rowsReturnedType, $includeName=false) {
		
		$name = ($includeName) ? 'name,' : '';
		try {
			//Put library system at the top
			$query = "
			select id, shortname, ou_type, parent_ou, $name case when ou_type=1 then 1 else 0 end as isSystem
			from actor.org_unit where id in ($OUList) order by isSystem desc, shortname";
			return $this->executeQuery($query, NULL, $rowsReturnedType);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	
	public function getExecutiveReportData($orgID, $yearMonthList) {
		//get the data for the report output
		//must order by org_unit,year_month desc, create_time desc
		//use select * - allows reports to be added without modifying this query 
		try {
			$query = '
				select *
				from '.QR_DB_SCHEMA.".executive_reports_data where year_month in ($yearMonthList) 
				and	org_unit in (:orgID,(select parent_ou from actor.org_unit where id=:orgID),
				(select id from actor.org_unit where ou_type=1))
				order by org_unit,year_month desc, create_time desc";
			$params = array('orgID'=>$orgID);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
}
?>

