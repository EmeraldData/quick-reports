<?php 
    
    $data = file_get_contents( "ver5.json" );

    $data = json_encode( decodeTemplateData( $data ) ); 
    print_r( $data );

	function decodeTemplateData ($templateData) {

		$userParamsArray = array();
		$staticParamsArray = array();
		$reportColumnsArray = array();
		$returnObj = new stdClass();

		$jsonData = json_decode( $templateData, false );
        $select = $jsonData->select;
        // $returnObj->select = $select;

        $where = ( isset( $jsonData->where ) ? $jsonData->where : NULL );
        $having = ( isset( $jsonData->having ) ? $jsonData->having : NULL );

        // $returnObj->where = $where;
        // $returnObj->having = $having;
			foreach ( $select as $s ) {
				$columnName = $s->column->colname;
				$r = $s->relation;
				$relCol = $s->column;
				$displayAggregate = isset( $relCol->aggregate ) ? $relCol->aggregate : NULL;
				$displayTransformLabel = isset ( $relCol->transform_label ) ? $relCol->transform_label : NULL;
	
				$columnArray = array(
					"name" => $s->alias,
					"aggregate" => $displayAggregate,
					"transformLabel"=>$displayTransformLabel
				);
				
				$reportColumnsArray[] = (object) $columnArray;

				$where = isset( $where ) ?
					array(
						"name" => "where",
						"columns" => $where
					) : NULL;
				$having = isset( $having ) ?
					array(
						"name" => "having",
						"columns" => $having
					) : NULL;

			// 	foreach ( array( "where", "having" ) as $c ) {
			// 		if ( $c == "where" ) {
			// 			if ( !isset( $where ) ) continue;
			// 			$clause = $where;
			// 		}
			// 		if ( $c == "having" ) {
			// 			if ( !isset( $having ) ) continue;
			// 			$clause = $having;
			// 		}

			// 		foreach ( $clause as $cl ) {
			// 			$relation = isset( $cl->relation ) ? $cl->relation : null;
			// 			$colName = isset( $cl->column->colname ) ? $cl->column->colname : null;

			// 			$columnLabel = "";
			// 			$this->pullLabel( $jsonData->from, $relation, $columnLabel, "alias", "join", "label" );
			// 			$columnLabel = str_replace( '::', '->', $columnLabel);

			// 			$transform = isset( $cl->column->transform ) ? $cl->column->transform : null;
			// 			$transformLabel = isset( $cl->column->transform_label ) ? $cl->column->transform_label : "";

			// 			$dataType = "";
			// 			$op = "";
			// 			$opLabel = "";
			// 			$fieldDoc = "";
			// 			$aggregate = "";
			// 			foreach ( $jsonData->filter_cols as $fc ) {
			// 				if ( $fc->name === $colName ) {
			// 					$dataType = $fc->datatype;
			// 					$op = $fc->operator->op;
			// 					$opLabel = $fc->operator->label;
			// 					isset( $fc->doc_text ) ? $fieldDoc = $fc->doc_text : false;
			// 					if ( isset( $fc->transform->aggregate ) ) {
			// 						if ( $fc->transform->aggregate != "undefined" ) {
			// 							$aggregate = $fc->transform->aggregate;
			// 						}
			// 					}
			// 					break;
			// 				}
			// 			}
			// 			$tableName = "";
			// 			$this->pullLabel( $jsonData->from, $relation, $tableName, "alias", "join", "table" );
			// 			$P = isset( $cl->condition ) ? $cl->condition : null;

			// 			if ( isset( $P ) ) {
			// 				list($key, $opValue) = each($P);  //get the first (and only) value
			// 			}
			// 			if ( isset( $opValue ) ) {
			// 				if (is_array($opValue)) $opValue = implode(',', $opValue);	//if array convert it back to a string
			// 			} else {
			// 				$opValue = null;
			// 			}

			// 			$paramsArray = array(
			// 			   'column' => $columnLabel
			// 			   ,'transform' => $transform
			// 			   ,'transformLabel' => $transformLabel
			// 			   ,'op' => $op
			// 			   ,'opLabel' => $opLabel
			// 			   ,'param'=> $opValue
			// 			   ,'fieldDoc' => $fieldDoc
			// 			   ,'aggregate' => $aggregate
			// 			   ,'dataType' => $dataType
			// 			   ,'table' => $tableName
			// 			   );
			// 		//if (mb_substr($opValue,0,3,'UTF-8')=='::P') {
			// 			if (substr($opValue,0,3) == '::P') {
			// 				$userParamsArray[] = (object) $paramsArray;
			// 			}
			// 			else {
			// 				$staticParamsArray[] = (object) $paramsArray;
			// 			}					
			// 		}
			}
			// 	$returnObj->docURL = isset($jsonData->doc_url) ? $jsonData->doc_url : NULL;		//version 4 templates only
				$returnObj->reportColumns = (object) $reportColumnsArray;
			// 	$returnObj->userParams = (object) $userParamsArray;
			// 	$returnObj->staticParams = (object) $staticParamsArray;
		
				return($returnObj);
	}

	// function convertXULTemplate ( $template ) {
	// 	$data = ( object )[
	// 		"version" => 5,
	// 		"core_class" => $template->core_class,
	// 		"select" => $template->select,
	// 		"from" => $template->from,
	// 		"where" => $template->where,
	// 		"having" => $template->having,
	// 		"display_cols" => array(),
	// 		"filter_cols" => array()
	// 	];
	
	// 	$rels = [];
	// 	$order_by = "";
	
	// 	foreach( $template->rel_cache as $k => $v ) {
	// 		$k == "order_by" ? $order_by = $v : $rels{$k} = $v; //array_push( $rels, $v );
	// 	}
	
	// 	$select = $template->select;
	// 	$sel_order = array();
	// 	$idx = 0;
	// 	foreach ( $select as $s ) {
	// 		$sel_order[strval($s->relation) . strval($s->column->colname)] = $idx;
	// 		$idx++;
	// 	}
	
	// 	$idx = 0;

	// 	$IDL = json_decode( file_get_contents( 'config/IDL.json' ) );
                
	// 	foreach ( $rels as $r ) {
	// 		if ( is_array($r) || is_object($r) ) {
	// 			$this->buildCols( $r, 'dis_tab', $sel_order, $data->display_cols, $IDL);
	// 			$this->buildCols( $r, 'filter_tab', NULL, $data->filter_cols, $IDL);
	// 			$this->buildCols( $r, 'aggfilter_tab', NULL, $data->filter_cols, $IDL);
	// 		}
	
	// 	}

	// 	return $data;
	// }

// 	function buildCols( $r, $tt, $sel_order, &$d, $IDL ) {
// 		$colType = $tt == 'dis_tab' ? 'display_cols' : 'filter_cols';
// 		$ci = 0;
// 		foreach ( $r->fields->{$tt} as $n =>$c ) {
// 			$orig = $r->fields->{$tt}->$n;
// 			$col = ( object ) [
// 				"name" => $c->colname,
// 				"path" => $this->convertPath( $orig, $r , $IDL),	//needs fixed
// 				"label" => $orig->alias,
// 				"datatype" => $c->datatype,
// 				"doc_text" => $c->field_doc,
// 				"transform" => ( object ) [
// 					"label" => $orig->transform_label,
// 					"transform" => $orig->transform,
// 					"aggregate" => !isset( $orig->aggregate ) ? NULL : $orig->aggregate
// 				],
// 				"path_label" => preg_replace( "/\:\:/", "->", $r->label),
// 				"index" => false
// 			];
// 			if ( $colType == "filter_cols" ) {
// 				@$col->operator = [
// 					"op" => $orig->op,
// 					"label" => $orig->op_label
// 				];
// 				@$col->index = $ci++;
// 				isset( $orig->op_value->value ) ? @$col->value = $orig->op_value->value : FALSE;
// 			} else {
// 				isset( $sel_order[$r->alias . $orig->colname] ) ? @$col->index = $sel_order[$r->alias . $orig->colname] : @$col->index = false;
// 			}
// 			array_push( $d, $col );
// 		}			
// 	}

// 	function buildNode( $cls, $args, $IDL ) {
// 		if ( !isset( $cls ) || !isset( $args ) ) {
// 			return null;
// 		} else {
// 			$n = isset( $IDL->{ $cls } ) ? $IDL->{ $cls } : null;
// 		}

// 		if ( !$n ) {
// 			return null;
// 		} else {
// 			if ( !$args ) {
// 				$args = ( object ) [
// 					"label" => $n->label
// 				];
// 			}
// 			$args->id = $cls;
// 			if ( isset( $args->from ) ) {
// 				$args->id = $args->from . "." . $args->id;
// 			}
// 			$links = array();
// 			foreach( $n->fields as $x ) {
//                             if ( isset( $x->type ) ) {
// 				$x->type == "link" ? array_push( $links, $x ) : false;
//                             }
// 			}

// 			$args->idl = $this->service($cls, null);
// 			$args->uplink = isset( $args->link ) ? $args->link : null;
// 			$args->classname = $cls;
// 			$args->struct = $n;
// 			$args->table = isset( $n->table ) ? $n->table : null;
// 			$args->fields = $this->_sort_class_fields($n->fields);
// 			$args->links = $this->_sort_class_fields($links);
// 			$args->children = [];

// 			$args = json_encode( $args );

// 			return $args;
// 		}
// 	}

// 	function convertPath( $orig, $rel, $IDL ) {
// 		$new_path = [];
// 		$table_path = preg_split( "/\./", $rel->path );
// 		if ( count( $table_path ) > 1 || strpos( $rel->path, "-" ) ) {
// 			array_push( $table_path, $rel->idlclass );
// 		}
// 		$prev_type= "";
// 		$prev_link = "";

// 		foreach( $table_path as $tp ) {
// 			$cl_split = preg_split( "/-/", $tp);
// 			$cls = $cl_split[0];
// 			$fld = isset( $cl_split[1] ) ? $cl_split[1] : null;
// 			$args = ( object )[
// 				"label" => isset( $IDL->{$cls}->label ) ? $IDL->{$cls}->label : null
// 			];

// 			if( $prev_link != "" ) {
// 				$link_parts = preg_split( "/-/", $prev_link );
// 				$args->from = $link_parts[0];
// 				$join_parts = preg_split( "/>/", $link_parts[1]);
// 				$prev_col = $join_parts[0];
// 				foreach( $IDL->{$link_parts[0]}->fields as $f ) {
// 					if ( $prev_col == $f->name ) {
// 						$args->link = $f;
// 					}
// 				}
// 				$args->jtype = isset( $join_parts[1] ) ? $join_parts[1] : null;
// 			}

// 			array_push( $new_path, $this->buildNode($cls, $args, $IDL));
// 			$prev_link = $tp;
// 		}
// 	}

// 	function pullLabel ( $d, $str, &$out, $tag, $subtag, $val ) {
// 		if ( $d->$tag == $str ) {
// 			$out = $d->$val;
// 			return;
// 		} else {
// 			if ( isset( $d->$subtag ) ) {
// 				if ( $d->$subtag ) {
// 					foreach ( $d->$subtag as $j ) {
// 						$this->pullLabel( $j, $str, $out, $tag, $subtag, $val );
// 					}
// 				}
// 			} else {
// 				return;
// 			}
// 		}
// 	}

// 	function service( $cls, $seed) {
// 		return ( object ) [
// 			"a" => ( $seed ? $seed : ( object )[] ),
// 			"classname" => $cls,
// 			"_isfieldmapper" => true
// 		];
// 	}

// 	function _sort_class_fields( $arr ) {
// 		$out = array();
// 		if ( isset( $arr[0] ) ) {
// 		    array_push( $out, $arr[0] );
// 		    array_shift($arr);
// 		    $i = 0;
// 		    while ( count($arr) > 0 ) {
// 			$arrLabel = isset( $arr[0]->label  ) ? $arr[0]->label : "";
//                         $arrName = isset( $arr[0]->name ) ? $arr[0]->name : "";

//                         //$aname = $arr[0]->label ? $arr[0]->label : $arr[0]->name;

//                         $bName = isset( $out[$i]->label  ) ? $out[$i]->label : "";
//                         $bLabel = isset( $out[$i]->label ) ? $out[$i]->label : $bName;
// 			$bName = isset( $bLabel ) ? $bLabel : $bName; //$out[$i]->label ? $out[$i]->label : $out[$i]->name;
// 			while ( strcmp( $arrName, $bName) > 0 && $i < count( $arr ) ) {
// 				$i++;
// 			}
// 			array_splice( $out, $i, 0, array( $arr[0] ) );
// 			array_shift( $arr );
// 			$i = 0;
// 		    }
//                 }
// 		return $out;
// 	}
// }	
?>
