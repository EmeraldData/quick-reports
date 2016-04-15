<?php 

class template extends db
{
	
	public function __construct() {
		parent::__construct();			
	}
		
	
	public function getReporterTemplate($id) {
		
		try {		
			$query = ' 
				select t.id, t.id as reporter_template_id, name, description, owner, folder, create_time, data, u.usrname 
				from reporter.template t left join actor.usr u on t.owner=u.id  
				where t.id=:id';
			$params = array('id'=>$id);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
	}

	public function getTemplateInfoForList($id) {
		
		try {		
			$query = '
				select t.id, t.name, t.description, t.create_time as linked_time, t.data, t.type_id, t.group_id, t.doc_url,
				t.active, u.usrname, rt.owner, rt.create_time, t.reporter_template_id, uLink.usrname as linked_by 
				from '.QR_DB_SCHEMA.'.templates t 
				left join reporter.template rt on t.reporter_template_id=rt.id 
				left join actor.usr u on rt.owner=u.id
				left join actor.usr uLink on t.creator=uLink.id	  
				where t.id=:id';
			$params = array('id'=>$id);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
	} 
	
	public function getAllTemplates($activeOnly) {
	
		try {
			$query = '
				select t.id, t.name, t.description, t.active, t.creator, t.create_time, t.type_id, t.group_id, 
				t.reporter_template_id, t.doc_url, tt.name as type, tg.name as group, u.usrname 
				from '.QR_DB_SCHEMA.'.templates t 
			  	join '.QR_DB_SCHEMA.'.template_groups tg on t.group_id=tg.id 
				join '.QR_DB_SCHEMA.'.template_types tt on t.type_id=tt.id 
				left join actor.usr u on t.creator=u.id ';		
			if ($activeOnly) $query .= ' where t.active=true ';
			$query .= ' order by tg.display_order, tt.name, t.active desc, t.name';
			$params = null;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function getTemplateByReporterTemplateID($id) {
	
		try {
			$query = 'select * from '.QR_DB_SCHEMA.'.templates where reporter_template_id=:id';
			$params = array('id'=>$id);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
		
	public function getTemplateTypesList() {
	
		try {
			$query = 'select id, name from '.QR_DB_SCHEMA.'.template_types where active=true order by display_order';
			$params=null;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function getTemplateGroupsList() {
	
		try {
			$query = 'select id, name from '.QR_DB_SCHEMA.'.template_groups where active=true order by display_order';
			$params=null;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function createNewTemplate($paramsObj) {
		try {
			$query = '
				insert into '.QR_DB_SCHEMA.'.templates (name,description,doc_url,active,creator,create_time,type_id,group_id,reporter_template_id,reporter_template_data,data) 
				values (:name,:description,:doc_url,:active,:creator,:create_time,:type_id,:group_id,:reporter_template_id,:reporter_template_data,:data)
				RETURNING id';
			$params = (array)$paramsObj;		
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
	}

	public function editTemplate($paramsObj) {
		try {
			$query = '
				update '.QR_DB_SCHEMA.'.templates set 
				name=:name,description=:description,doc_url=:doc_url,active=:active,type_id=:type_id,group_id=:group_id 
				where id=:id RETURNING id';
			$params = (array)$paramsObj;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
}
?>
