<?php 
class templateDecoder
{

	function __construct( ){}

	function createTemplateObject ($reporterTemplateID) {
		
		$templateObj = new stdClass();
		$templateObj->id = $result['id'];
		$templateObj->name = $result['name'];
		$templateObj->description = $result['description'];
		$templateObj->owner = $result['owner'];
		$templateObj->folder = $result['folder'];
		$templateObj->createTime = $result['create_time'];
		$templateObj->data = $result['data'];
		$templateObj->dataDecoded = $this->getDecodedTemplateData($result['data']);		
	}
	
	public function decodeTemplateData ($templateData) {
		
		$userParamsArray=array();
		$staticParamsArray=array();
		$reportColumnsArray=array();
		$returnObj=new stdClass();

		$jsonData=json_decode($templateData, false);
		if (NULL == $jsonData) {
			new displayMessageView('JSON format error decoding template data.');
			return NULL;
		}
				
		$version=$jsonData->version;
		if ($version != '3' && $version != '4') {
			new displayMessageView('Error: Invalid template version: '.$version);
			return NULL;
		}

		
		$select=$jsonData->select;
		$relCache=$jsonData->rel_cache;
		$where=(isset($jsonData->where) ? $jsonData->where : NULL);
		$having=(isset($jsonData->having) ? $jsonData->having : NULL);
				
		foreach ($select as $s )
		{
			$columnName=$s->column->colname;
			$r=$s->relation;
			$displayAggregate = isset($relCache->$r->fields->dis_tab->$columnName->aggregate) ? $relCache->$r->fields->dis_tab->$columnName->aggregate : NULL;
			$displayTransformLabel = isset($relCache->$r->fields->dis_tab->$columnName->transform_label) ? $relCache->$r->fields->dis_tab->$columnName->transform_label : NULL;
			$columnArray=array('name'=>$s->alias, 'aggregate'=>$displayAggregate, 'transformLabel'=>$displayTransformLabel);
			$reportColumnsArray[] = (object) $columnArray;
		}
		
		$clauses = array('where', 'having');	//look for params in both where and having clauses
		foreach ($clauses as $c) {
			
			if ('where' == $c) {
				if (NULL == $where) continue;
				$clause = $where;
			}
			else {
				if (NULL == $having) continue;
				$clause = $having;
			}
		
			foreach ($clause as $cl) {
				$relation=$cl->relation;
				$colName=$cl->column->colname;
				
				if ('where' == $c)
					$filterTab=$relCache->$relation->fields->filter_tab;
				else
					$filterTab=$relCache->$relation->fields->aggfilter_tab;
				
				$columnLabel = str_replace('::','->',$relCache->$relation->label) .' -> '. $cl->alias;
			
				//Transform
				//*** Some templates do not have a transform label
				$transform = $cl->column->transform;
				if (isset($cl->column->transform_label)) {
					$transformLabel = $cl->column->transform_label;
				}
				else {
					$transformLabel = '';
				}
			
				//Data Type
				$dataType=$filterTab->$colName->datatype;
				$tableName=$relCache->$relation->table;
			
				//Action / Operation		
				$op=$filterTab->$colName->op;
				$opLabel=$filterTab->$colName->op_label;
				$fieldDoc = isset($filterTab->$colName->field_doc) ? $filterTab->$colName->field_doc : NULL;
				$aggregate = isset($filterTab->$colName->aggregate) ? $filterTab->$colName->aggregate : NULL;				
				$P=$cl->condition;	

				list($key, $opValue) = each($P);							//get the first (and only) value
				if (is_array($opValue)) $opValue = implode(',', $opValue);	//if array convert it back to a string
			
				$paramsArray = array(
						 	'column' => $columnLabel
							,'transform' => $transform
							,'transformLabel' => $transformLabel
							,'op' => $op
							,'opLabel' => $opLabel
							,'param'=> $opValue
							,'fieldDoc' => $fieldDoc
							,'aggregate' => $aggregate
							,'dataType' => $dataType
							,'table' => $relCache->$relation->table
							);
			
				//if (mb_substr($opValue,0,3,'UTF-8')=='::P') {
				if (substr($opValue,0,3) == '::P') {
					$userParamsArray[] = (object) $paramsArray;
				}
				else {
					$staticParamsArray[] = (object) $paramsArray;
				}
			}
		}
				
		$returnObj->docURL = isset($jsonData->doc_url) ? $jsonData->doc_url : NULL;		//version 4 templates only
		$returnObj->reportColumns = (object) $reportColumnsArray;
		$returnObj->userParams = (object) $userParamsArray;
		$returnObj->staticParams = (object) $staticParamsArray;

		return($returnObj);
	}
}	
?>
