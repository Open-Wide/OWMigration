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
            $code .= sprintf( "\t\t\$migration->addEvent( '%s', '%s'", self::escapeString( $description ), self::escapeString( $event->attribute( 'workflow_type_string' ) ) );
            $workflowTypeHandlerClass = get_class( $event->attribute( 'workflow_type' ) ) . 'MigrationHandler';
            if( !class_exists( $workflowTypeHandlerClass ) || !is_callable( $workflowTypeHandlerClass . '::toArray' ) ) {
                $workflowTypeHandlerClass = "DefaultEventTypeMigrationHandler";
            }
            $eventAttributes = $workflowTypeHandlerClass::toArray( $event );
            if( count( $eventAttributes ) > 0 ) {
                $code .= ", array(" . PHP_EOL;
                foreach( $eventAttributes as $attribute => $value ) {
                    if( is_array( $value ) ) {
                        $value = array_map( "self::escapeString", $value );
                        if( empty( $value ) ) {
                            $arrayString = "array( )";
                        } else {
                            $arrayString = "array(\n\t\t\t\t'" . implode( "',\n\t\t\t\t'", $value ) . "'\n\t\t\t )";
                        }
                        $code .= sprintf( "\t\t\t'%s' => %s," . PHP_EOL, self::escapeString( $attribute ), $arrayString );
                    } else {
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