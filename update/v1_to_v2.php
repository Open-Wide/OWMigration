<?php

require 'autoload.php';

$script = eZScript::instance( array(
    'description' => ("Migrate owmigration V1 to V2"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true
) );
$script->startup( );
$sys = eZSys::instance( );
$script->initialize( );
$cli = eZCLI::instance( );

if( !class_exists( 'OWScriptLogger' ) ) {
    $cli->error( 'You need to install OWScriptLogger : https://github.com/Open-Wide/OWScriptLogger' );
    $script->shutdown( 0 );
}

$migrationList = OWMigration::fetchList( );
foreach( $migrationList as $migration ) {
    $date = $migration->attribute( 'date' );
    $logger = OWScriptLogger::fetchByIdentiferAndDate( 'owmigration', $date );
    if( !$logger ) {
        $row = array(
            'identifier' => 'owmigration',
            'date' => $date,
            'runtime' => NULL,
            'memory_usage' => NULL,
            'memory_usage_peak' => NULL,
            'status' => OWScriptLogger::FINISHED_STATUS,
        );
        $logger = new OWScriptLogger( $row );
        $logger->store( );
    }

    $row = array(
        'owscriptlogger_id' => $logger->attribute( 'id' ),
        'date' => $date,
        'level' => OWScriptLogger::NOTICELOG,
        'action' => 'call',
        'message' => $migration->attribute( 'class' ) . '::' . $migration->attribute( 'method' )
    );
    OWScriptLogger_Log::create( $row );
    $logArray = $migration->attribute( 'log_array' );
    if( $logArray ) {
        foreach( $migration->attribute('log_array') as $log ) {
            $level = $log['level'];
            $messageArray = explode( ' - ', $log['message'] );
            if( $messageArray[0] ) {
                $action = strtolower( preg_replace( '/([A-Z])/', '_$1', $messageArray[0] ) );
            }
            if( $messageArray[1] ) {
                $message = is_array( $messageArray[1] ) ? implode( ' - ', $messageArray[1] ) : $messageArray[1];
            }
            $row = array(
                'owscriptlogger_id' => $logger->attribute( 'id' ),
                'date' => $date,
                'level' => $level,
                'action' => $action,
                'message' => $message
            );
            OWScriptLogger_Log::create( $row );
        }
    } else {
        $row = array(
            'owscriptlogger_id' => $logger->attribute( 'id' ),
            'date' => $date,
            'level' => OWScriptLogger::ERRORLOG,
            'action' => 'import_owmigration',
            'message' => 'No log found'
        );
        OWScriptLogger_Log::create( $row );
    }
    $logger->setAttribute( 'notice_count', $logger->countNotice( ) );
    $logger->setAttribute( 'warning_count', $logger->countWarning( ) );
    $logger->setAttribute( 'error_count', $logger->countError( ) );
    $logger->store( );
}

$script->shutdown( );
