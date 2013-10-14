<?php

class OWMigrationWorkflowCodeGenerator extends OWMigrationCodeGenerator {

    static function getMigrationClassFile( $workflow, $dir ) {
        if( is_numeric( $workflow ) ) {
            $workflow = eZWorkflow::fetch( $workflow );
        }
        if( !$workflow instanceof eZWorkflow ) {
            return FALSE;
        }
        self::createDirectory( $dir );
        $filename = self::generateSafeFileName( $workflow->attribute( 'name' ) . '_workflow.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, OWMigrationWorkflowCodeGenerator::getMigrationClass( $workflow ) );
        return $filepath;
    }

    static function getMigrationClass( $workflow ) {
        if( is_numeric( $workflow ) ) {
            $workflow = eZWorkflow::fetch( $workflow );
        }
        if( !$workflow instanceof eZWorkflow ) {
            return FALSE;
        }
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class myExtension_xxx_%sWorkflow {" . PHP_EOL, self::generateClassName( $workflow->attribute( 'name' ) ) );
        $code .= self::getUpMethod( $workflow );
        $code .= self::getDownMethod( $workflow );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "?>";
        return $code;
    }

    static function getUpMethod( $workflow ) {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationWorkflow( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $workflow->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;
        $eventCount = 1;
        foreach( $workflow->fetchEvents() as $event ) {
            $description = $event->attribute( 'description' );
            if( empty( $description ) ) {
                $description = "Event #$eventCount";
                $code .= "\t\t/* The description of events is used to identify when running migration scripts. Please fill to ensure the proper functioning of the script. */" . PHP_EOL;
            }
            $code .= sprintf( "\t\t\$migration->addEvent( '%s'", self::escapeString( $description ) );
            $workflowTypeClass = get_class( $event->attribute( 'workflow_type' ) );
            var_dump( $workflowTypeClass );
            $eventAttributes = $event->attributes( );
            $notEmptyEventAttributes = array( );
            unset( $eventAttributes['id'], $eventAttributes['version'], $eventAttributes['workflow_id'], $eventAttributes['placement'] );
            foreach( $eventAttributes as $attribute ) {
                $attributeValue = $event->attribute( $attribute );
                switch ($attribute) {
                    case 'id' :
                    case 'version' :
                    case 'workflow_id' :
                    case 'placement' :
                    case 'content' :
                    case 'workflow_type' :
                        break;
                    case 'data_int1' :
                    case 'data_int2' :
                    case 'data_int3' :
                    case 'data_int4' :
                        if( $attributeValue !== "0" ) {
                            $notEmptyEventAttributes[$attribute] = $attributeValue;
                        }
                        break;
                    case 'data_text1' :
                    case 'data_text2' :
                    case 'data_text3' :
                    case 'data_text4' :
                    case 'data_text5' :
                        if( $attributeValue !== "" ) {
                            $notEmptyEventAttributes[$attribute] = $attributeValue;
                        }
                        break;
                    default :
                        if( !empty( $attributeValue ) ) {
                            $notEmptyEventAttributes[$attribute] = $attributeValue;
                        }
                        break;
                }
            }
            if( count( $notEmptyEventAttributes ) > 0 ) {
                $code .= ", array(" . PHP_EOL;
                foreach( $notEmptyEventAttributes as $attribute => $value ) {
                    /*
                     if( is_array( $value ) ) {
                     $limitationValue = array_map( "self::escapeString", $limitationValue );
                     $arrayString = "array(\n\t\t\t\t'" . implode( "',\n\t\t\t\t'", $limitationValue ) . "'\n\t\t\t )";
                     $code .= sprintf( "\t\t\t'%s' => %s," . PHP_EOL, self::escapeString( $limitationKey ), $arrayString );
                     } else {
                     $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $limitationKey ), $limitationValue );
                     }
                     */
                    if( is_string( $value ) ) {
                        $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $attribute ), $value );
                    }
                }
                $code .= "\t\t) );" . PHP_EOL;
            } else {
                $code .= " );" . PHP_EOL;
            }
            $eventCount++;
        }
        /*
         foreach( $workflow->fetchUserByWorkflow() as $workflowAssignationArray ) {
         $workflowAssignation = $workflowAssignationArray['user_object'];
         if( $workflowAssignation->attribute( 'class_identifier' ) == 'user_group' ) {
         $code .= sprintf( "\t\t\$migration->assignToUserGroup( '%s'", self::escapeString( $workflowAssignation->attribute( 'name' ) ) );
         } else {
         $code .= sprintf( "\t\t\$migration->assignToUser( '%s'", self::escapeString( $workflowAssignation->attribute( 'name' ) ) );
         }
         if( !empty( $workflowAssignationArray['limit_ident'] ) ) {
         $code .= sprintf( ", '%s', '%s'", self::escapeString( $workflowAssignationArray['limit_ident'] ), self::escapeString( $workflowAssignationArray['limit_value'] ) );
         }
         $code .= " );" . PHP_EOL;
         }
         */
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $workflow ) {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationWorkflow( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $workflow->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->removeWorkflow( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}
?>