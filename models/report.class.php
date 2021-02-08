<?php 

class report extends db
{
	public $multiSelect;
	
	public function __construct() {
		
		parent::__construct();	
	}

	public function getTemplate($id) {
	
		try {
			$query = '
				select t.id as template_id, t.name as template_name, t.type_id, t.group_id, t.reporter_template_id, t.reporter_template_data, 
				t.data, t.description, t.doc_url, tg.name as group_name
				from '.QR_DB_SCHEMA.'.templates t join '.QR_DB_SCHEMA.'.template_groups tg on t.group_id=tg.id   
				where t.active=true and t.id=:id';
			$params = array('id'=>$id);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function updateDraftReport($paramsObj) {
		
		try {
			$query = '
				update '.QR_DB_SCHEMA.'.draft_reports set
				name=:name, description=:description, params=:params, create_time=now()
				where id=:draftID and owner='.$_SESSION['userID'].' RETURNING id';
			$params = (array)$paramsObj;
			
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}

	public function createDraftReport($paramsObj) {
	
		try {
			$query = '
				insert into '.QR_DB_SCHEMA.'.draft_reports
				(owner, template, name, description, params, create_time)
				values (:owner, :templateID, :name, :description, :params, :create_time)	
				RETURNING id';
			$params = (array)$paramsObj;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	public function deleteDraftReport($draftID) {
		
		try {
			$query = 'delete from '.QR_DB_SCHEMA.'.draft_reports where id=:id and owner='.$_SESSION['userID'].' RETURNING id';		
			$params = array('id'=>$draftID);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
	}
	
	public function getDraftReport($paramsObj) {
						
		try {
			$query = 'select * from '.QR_DB_SCHEMA.'.draft_reports where id=:id and owner=:owner';
			$params = (array)$paramsObj;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
	}

	public function getDraftReportsList($paramsObj) {
	
		try {
			$query = ' 
				select id, name, description, create_time from '.QR_DB_SCHEMA.'.draft_reports 
				where owner=:owner order by create_time desc';
			$params = (array)$paramsObj;
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	
	function getListDataFromTable($param) {
	
		$params=NULL;
		if ($param->dataType == 'org_unit') {
			$this->multiSelect = true;
			
			//Put the home library system at the top
			$query = '
				select id, 2 as depth, ou_type, NULL as library_system, shortname
				from actor.org_unit where parent_ou=(select parent_ou from actor.org_unit where id='.$_SESSION['homeOU'].')
				or id=(select parent_ou from actor.org_unit where id='.$_SESSION['homeOU'].')
				UNION
				select id, 1 as depth, ou_type, NULL as library_system, shortname
				from actor.org_unit where ou_type=1
				UNION
				select c.id, 3 as depth, c.ou_type,
				case when c.ou_type=2 then c.shortname
				when c.ou_type=3 then p.shortname
				end as library_system, c.shortname
				from actor.org_unit p join actor.org_unit c on p.id=c.parent_ou
				where c.parent_ou!=(select parent_ou from actor.org_unit where id='.$_SESSION['homeOU'].')
				and c.id!=(select parent_ou from actor.org_unit where id='.$_SESSION['homeOU'].')
				order by depth, library_system, shortname';
		}
		else {
			$this->multiSelect = false;
					
			switch ($param->table) {
				case 'asset.copy_location':
					$query= '
						select id, name
						from asset.copy_location
						where owning_lib=(select home_ou from actor.usr where id=:userID) or owning_lib=1
						order by name';
					$params = array('userID'=>$_SESSION['userID']);
					break;
	
				case 'permission.grp_tree':
					$query='select id, name from permission.grp_tree order by name';
					break;
				
				case 'config.copy_status':
					$query='select id, name from config.copy_status order by name';
					break;
				
				case 'metabib.rec_descriptor':
					if (NULL == $param->fieldDoc) return NULL; 
					
					//create select list values from fieldDoc as "a","b","c"...
					$fieldList = explode('; ', $param->fieldDoc);
					foreach ($fieldList as $option) {
						$option = trim($option);
						$parts = explode(' ', $option);
						$parts[1] = str_replace('[space]', ' ', $parts[1]);
						$parts[1] = str_replace('(', '', $parts[1]);
						$parts[1] = str_replace(')', '', $parts[1]);
						$fieldDocArray[] = (object) array('id'=>$parts[1], 'name'=>$parts[0]);
					}
					return $fieldDocArray;
					break;

				case 'config.circ_modifier':
					$query = 'select code as id, name from config.circ_modifier order by name';
					break;
				
				case 'config.item_form_map':
					$query = 'select code as id, value as name from config.item_form_map order by value';
					break;
				
				case 'config.language_map':
					$query = 'select code as id, value as name from config.language_map order by value';
					break;
					
				default:	
					return NULL;
					break;
				}
		}
		
		try {
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ALL_ROWS);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	
		return $this->executeQuery($query, $paramsArray, QR_QUERY_RETURN_ALL_ROWS);
	}

	function checkFolders($tableName, $subFolder=NULL) {
	
		try {
			//check if parent folder exists
			$query = "select id from $tableName where name=:name and owner=:id";
			$params = array('name'=>QR_PARENT_FOLDER_NAME, 'id'=>$_SESSION['userID']);
			$folder = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
				
			if (NULL == $folder) {
				//create the parent folder
				$parentFolder = $this->createFolder($tableName, QR_PARENT_FOLDER_NAME, NULL);
				if (NULL == $parentFolder->id) return NULL;
				$parentID = $parentFolder->id;
			}
			else {
				$parentID = $folder->id;
			}
				
			if (NULL == $subFolder) return $parentID;	//do not check for sub folders
				
			$doAll = (strtolower($subFolder) == 'all');
			if ($doAll) {
				//check for sub folders (active template group names)
				$query = 'select name from '.QR_DB_SCHEMA.'.template_groups where active=true';
				$groups = $this->executeQuery($query, NULL, QR_QUERY_RETURN_ALL_ROWS);
			}
			else {
				$groups = array(new stdClass());
				$groups[0]->name = $subFolder;
			}
				
			foreach ($groups as $g) {
				$query = "select id from $tableName where parent=:parent and name=:name and owner=:owner";
				$params = array('parent'=>$parentID, 'name'=>$g->name, 'owner'=>$_SESSION['userID']);
				$folder = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
				if (NULL == $folder) $folder = $this->createFolder($tableName, $g->name, $parentID);	//create the sub folder
			}
				
			if ($doAll)
				return $parentID;	//parent folder ID
			else
				return $folder->id;	//newly created or existing sub folder ID
	
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	function createFolder($tableName, $name, $parentID) {
	
		try {
			$query = "insert into $tableName (parent, owner, create_time, name, shared) values (:parent, :owner, NOW(), :name, false) RETURNING id";
			$params = array('parent'=>$parentID, 'name'=>$name, 'owner'=>$_SESSION['userID']);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
	
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}
	
	function checkUniqueReportName($reportName, $folderID) {
	
		try {
			$query = "select count(*) as cnt from reporter.report where name=:name and folder=:folder and owner=:owner";
			$params = array('name'=>$reportName, 'folder'=>$folderID, 'owner'=>$_SESSION['userID']);
			return $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	}	

}
?>	