<?php

class OWMigrationWorkflowCodeGenerator extends OWMigrationCodeGenerator
{

    static function getMigrationClassFile( $workflow, $dir )
    {
        if( is_numeric( $workflow ) )
        {
            $workflow = eZWorkflow::fetch( $workflow );
        }
        if( !$workflow instanceof eZWorkflow )
        {
            return FALSE;
        }
        self::createDirectory( $dir );
        $filename = self::generateSafeFileName( $workflow->attribute( 'name' ) . '_workflow.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, OWMigrationWorkflowCodeGenerator::getMigrationClass( $workflow ) );
        return $filepath;
    }

    static function getMigrationClass( $workflow )
    {
        if( is_numeric( $workflow ) )
        {
            $workflow = eZWorkflow::fetch( $workflow );
        }
        if( !$workflow instanceof eZWorkflow )
        {
            return FALSE;
        }
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class myExtension_xxx_%sWorkflow {" . PHP_EOL, self::generateClassName( $workflow->attribute( 'name' ) ) );
        $code .= self::getUpMethod( $workflow );
        $code .= self::getDownMethod( $workflow );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "";
        return $code;
    }

    static function getUpMethod( $workflow )
    {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationWorkflow( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $workflow->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;
        $eventCount = 1;
        foreach( $workflow->fetchEvents() as $event )
        {
            $description = $event->attribute( 'description' );
            if( empty( $description ) )
            {
                $description = "Event #$eventCount";
                $code .= "\t\t/* The description of events is used to identify when running migration scripts. Please fill to ensure the proper functioning of the script. */" . PHP_EOL;
            }
            $code .= sprintf( "\t\t\$migration->addEvent( '%s', '%s'", self::escapeString( $description ), self::escapeString( $event->attribute( 'workflow_type_string' ) ) );
            $workflowTypeHandlerClass = get_class( $event->attribute( 'workflow_type' ) ) . 'MigrationHandler';
            if( !class_exists( $workflowTypeHandlerClass ) || !is_callable( $workflowTypeHandlerClass . '::toArray' ) )
            {
                $workflowTypeHandlerClass = "DefaultEventTypeMigrationHandler";
            }
            $eventAttributes = call_user_func( "$workflowTypeHandlerClass::toArray", $event );
            if( count( $eventAttributes ) > 0 )
            {
                $code .= sprintf( ", %s", self::formatValue( $eventAttributes ) );
            }
            $code .= " );" . PHP_EOL;
            $eventCount++;
        }
        $triggerList = eZPersistentObject::fetchObjectList( eZTrigger::definition(), null, array( 'workflow_id' => $workflow->attribute( 'id' ) ) );
        foreach( $triggerList as $trigger )
        {
            $connectType = $trigger->attribute( 'connect_type' ) == 'a' ? 'after' : 'before';
            $code .= sprintf( "\t\t\$migration->assignToTrigger( '%s', '%s', '%s' );" . PHP_EOL, self::escapeString( $trigger->attribute( 'module_name' ) ), self::escapeString( $trigger->attribute( 'function_name' ) ), self::escapeString( $connectType ) );
        }
        foreach( $workflow->ingroupList() as $group )
        {
            $code .= sprintf( "\t\t\$migration->addToGroup( '%s' );" . PHP_EOL, self::escapeString( $group->attribute( 'group_name' ) ) );
        }
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $workflow )
    {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationWorkflow( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $workflow->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->removeWorkflow( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}

