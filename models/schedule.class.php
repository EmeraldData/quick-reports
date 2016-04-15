<?php 

class schedule extends db
{
	
	public function __construct() {
		
		parent::__construct();	
	}

	public function submitReport($reportObj, $scheduleObj, $updateIDs=NULL) {

		try {
			
			$returnObj = new stdClass();
			
			if (!$this->beginTransaction()) {
				$returnObj->success = false;
				$returnObj->errorMessage = 'Begin transaction failed.';
				return $returnObj; 
			}
			
			if (NULL == $updateIDs) {	
				$query = '
					insert into reporter.report
					(owner, create_time, name, description, template, data, folder, recur, recurrence)
					values (:owner, now(), :name, :description, :template, :data, :folder, :recur, :recurrence)
					RETURNING id';
			}
			else {	
				$query = '
					update reporter.report set
					owner=:owner, create_time=now(), name=:name, description=:description, template=:template, data=:data, folder=:folder, recur=:recur, recurrence=:recurrence		
					where id='.$updateIDs['rid'].' RETURNING id';
			}
			
			$params = (array)$reportObj;
			$reportResultObj = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
			
			if (NULL == $reportResultObj->id) {
				$rollBackStatus = $this->rollback();
				$returnObj->success = false;
				$returnObj->errorMessage = 'Unable to insert into reporter.report table. Rollback ' .($rollBackStatus)?'succeeded.' : 'failed.';
				return $returnObj; 
			}
			
			$scheduleObj->report = $reportResultObj->id;
			if (NULL == $updateIDs) {
				$query = '
					insert into reporter.schedule
					(report, folder, runner, run_time, email, excel_format, html_format, csv_format, chart_bar, chart_line)
					values (:report, :folder, :runner, :runTime, :email, :excelFormat, :htmlFormat, :csvFormat, :barChart, :lineChart)
					RETURNING id';
			}
			else {
				$query = '
					update reporter.schedule set
					report=:report, folder=:folder, runner=:runner, run_time=:runTime, email=:email, excel_format=:excelFormat, html_format=:htmlFormat, csv_format=:csvFormat, chart_bar=:barChart, chart_line=:lineChart
					where id='.$updateIDs['sid'].' RETURNING id';
			}	
			
			$params = (array)$scheduleObj;
			$scheduleResultObj = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
			
			if (NULL == $scheduleResultObj->id) {
				$rollBackStatus = $this->rollback();
				$returnObj->success = false;
				$returnObj->errorMessage = 'Unable to insert into reporter.schedule table. Rollback ' .($rollBackStatus)?'succeeded.' : 'failed.';
				return $returnObj;
			}
						
			$commitStatus = $this->commit();
			if ($commitStatus) {
				$returnObj->success = true;
				$returnObj->errorMessage = NULL;
				$returnObj->reportID = $reportResultObj->id;
				$returnObj->scheduleID = $scheduleResultObj->id;
			}
			else {
				$rollBackStatus = $this->rollBack();
				$returnObj->success = false;
				$returnObj->errorMessage = 'Unable to commit transaction. Rollback ' .($rollBackStatus)?'succeeded.' : 'failed.';
			}
			
			return $returnObj;
		}
		catch (PDOException $e) {
			$this->rollBack();
			$this->handleDatabaseErrors($e);
		}
		
	}
	
	public function getScheduledReportByID($id, $adminView) {
	
		try {
			$query = '
					select s.id as sID, r.id as rID, s.folder as sFolder, r.folder as rFolder, 
					s.*, r.*, srt.id as srtID,  srt.reporter_template_id 
					from reporter.schedule s join reporter.report r on s.report=r.id
					join reporter.template t on r.template=t.id
					join '.QR_DB_SCHEMA.'.templates srt on srt.reporter_template_id=t.id
					join '.QR_DB_SCHEMA.'.template_groups tg on tg.id=srt.group_id
					where s.id=:sid';
			if (!$adminView) $query .= ' and s.runner='.$_SESSION['userID'];
			$params = array('sid'=>$id);
	
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function getScheduledReportsList($id, $adminView) {
	
		try {
			$query = '
				select s.id as sID, s.runner, s.run_time, s.complete_time, s.email, s.error_code, s.error_text,
				s.start_time, s.complete_time, r.id as rID, r.name, r.description, r.recur, r.recurrence, u.usrname, 
				tg.name as group, t.id as tID, t.name as templateName,
				case when (start_time is null) then 1 else 0 end as is_queued,
				case when (start_time is not null and complete_time is null ) then 1 else 0 end as is_running,
				case when (complete_time is not null) then 1 else 0 end as is_complete		
				from reporter.schedule s join reporter.report r on s.report=r.id
				join reporter.template t on r.template=t.id
				join '.QR_DB_SCHEMA.'.templates srt on srt.reporter_template_id=t.id
				join '.QR_DB_SCHEMA.'.template_groups tg on tg.id=srt.group_id
				join actor.usr u on u.id=s.runner';
				
			if ($adminView) {
				$query .= " 
					where s.start_time is NULL or (start_time is not NULL and complete_time is NULL)
					or (s.error_code is not null and now()-s.run_time<='".QR_ADMIN_SCHEDULED_REPORTS_QUERY_DAYS." days') ";
				$params = NULL;
			}
			else {
				$query .= ' where s.runner=:runner';
				$params = array('runner'=>$id);
			}
			
			$query .= ' order by tg.display_order, is_running desc, is_complete asc, date(s.run_time) desc, r.name';
						
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function deleteScheduledReport($id, $adminView) {
	
		try {
			$query = 'delete from reporter.schedule where id=:id';
			if (!$adminView) $query .= ' and runner='.$_SESSION['userID'];
			$query .= ' RETURNING id';
			
			$params = array('id'=>$id);			
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
}
?>	