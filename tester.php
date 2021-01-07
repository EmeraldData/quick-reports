<?php

    $data = file_get_contents( "/openils/var/web/report-creator/ver5.json" );
    $data = json_encode( decodeTemplateData( $data ) );

    function decodeTemplateData ($templateData) {

        $userParamsArray = array();
        $staticParamsArray = array();
        $reportColumnsArray = array();
        $returnObj = new stdClass();

        $jsonData = json_decode( $templateData, false );
        $select = $jsonData->select;
        $where = ( isset( $jsonData->where ) ? $jsonData->where : NULL );
        $having = ( isset( $jsonData->having ) ? $jsonData->having : NULL );

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
        }

        foreach( array( $where, $having ) as $clause ) {
                if ( isset( $clause ) ) {
                    foreach ( $clause as $cl ) {
                        $paramsArray = returnClause( $cl, $jsonData );
                        if ( substr( $paramsArray->param, 0, 3) == "::P" ) {
                                $userParamsArray[] = (object) $paramsArray;
                        } else {
                                $staticParamsArray[] = (object) $paramsArray;
                        }
                    }
                }
        }

        $returnObj->docURL = isset($jsonData->doc_url) ? $jsonData->doc_url : NULL;             //version 4 templates only
        $returnObj->reportColumns = (object) $reportColumnsArray;
        $returnObj->userParams = (object) $userParamsArray;
        $returnObj->staticParams = (object) $staticParamsArray;

	print_r( $returnObj );

        return($returnObj);
    }

    function pullLabel ( $d, $str, &$out, $tag, $subtag, $val ) {
        if ( $d->$tag == $str ) {
                $out = $d->$val;
                return;
        } else {
            if ( isset( $d->$subtag ) ) {
                if ( $d->$subtag ) {
                        foreach ( $d->$subtag as $j ) {
                            pullLabel( $j, $str, $out, $tag, $subtag, $val );
                        }
                }
            } else {
                    return;
            }
        }
    }
    
    function returnClause( $cl, $jsonData ) {
        $relation = isset( $cl->relation ) ? $cl->relation : null;
        $colName = isset( $cl->column->colname ) ? $cl->column->colname : null;

        $columnLabel = "";
        pullLabel( $jsonData->from, $relation, $columnLabel, "alias", "join", "label" );
        $columnLabel = str_replace( '::', '->', $columnLabel);

        $transform = isset( $cl->column->transform ) ? $cl->column->transform : null;
        $transformLabel = isset( $cl->column->transform_label ) ? $cl->column->transform_label : "";

        $dataType = "";
        $op = "";
        $opLabel = "";
        $fieldDoc = "";
        $aggregate = "";

        foreach ( $jsonData->filter_cols as $fc ) {
                if ( $fc->name === $colName ) {
                    $dataType = $fc->datatype;
                    $op = $fc->operator->op;
                    $opLabel = $fc->operator->label;
                    isset( $fc->doc_text ) ? $fieldDoc = $fc->doc_text : false;
                    if ( isset( $fc->transform->aggregate ) ) {
                        if ( $fc->transform->aggregate != "undefined" ) {
                            $aggregate = $fc->transform->aggregate;
                        }
                    }
                    break;
                }
        }

        $tableName = "";
        pullLabel( $jsonData->from, $relation, $tableName, "alias", "join", "table" );
        $P = isset( $cl->condition ) ? $cl->condition : null;

        if ( isset( $P ) ) {
                list( $key, $opValue ) = each( $P );  //get the first (and only) value
        }

        if ( isset( $opValue ) ) {
                if ( is_array( $opValue ) ) $opValue = implode( ',', $opValue );      //if array convert it back to a string
        } else {
                $opValue = "";
        }

        return array(
                'column' => $columnLabel
                ,'transform' => $transform
                ,'transformLabel' => $transformLabel
                ,'op' => $op
                ,'opLabel' => $opLabel
                ,'param'=> $opValue
                ,'fieldDoc' => $fieldDoc
                ,'aggregate' => $aggregate
                ,'dataType' => $dataType
                ,'table' => $tableName
        );
    }
?>
