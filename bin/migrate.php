<?php

require 'autoload.php';

$cli = eZCLI::instance( );
$script = eZScript::instance( array(
    'description' => ("eZ Publish Migration Handler\n" . "Launch migration\n" . "\n" . ".extension/OWMigration/bin/php/migrate.php --extension=my_extension"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true
) );

$script->startup( );

$options = $script->getOptions( "[list][extension:][version:][force:]", "", array(
    'list' => 'List the version for each extension',
    'extension' => 'Name of the extension to migrate',
    'version' => 'Version to migrate',
    'force' => 'Force up() ou down() method call',
) );
$sys = eZSys::instance( );

$script->initialize( );

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

// test si owscriptlogger est installée

if( isset( $options['list'] ) && $options['list'] === TRUE ) {
    // TODO :: display extension version
    /*
     $migrationList = OWMigration::fetchList( );
     if( $migrationList ) {
     $separationFormat = "-%'-21s-%'-42s-%'-12s-";
     $lineFormat = "| %-19s | %-40s | %-10s |";
     $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
     $cli->notice( sprintf( $lineFormat, 'Date', 'Class', 'Method' ) );
     $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
     foreach( $migrationList as $migration ) {
     $cli->notice( sprintf( $lineFormat, $migration->attribute( 'date' ), $migration->attribute( 'class' ), $migration->attribute( 'method' ) ) );
     }
     $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
     }
     $script->shutdown( 0 );
     */
}

$validOptions = TRUE;
$migration = new OWMigration( );

if( isset( $options['force'] ) ) {
    $force = $options['force'];
    if( $force != 'up' && $force != 'down' ) {
        $cli->error( 'Authorized values for force option are up or down.' );
        $validOptions = FALSE;
    }
}
if( isset( $options['version'] ) ) {
    $version = $options['version'];
}
if( isset( $options['extension'] ) ) {
    $extension = $options['extension'];
    $migration->startMigrationOnExtension( $extension );
}

if( isset( $force ) && !isset( $version ) ) {
    $cli->error( 'version option is required with force' );
    $validOptions = FALSE;
}
if( isset( $version ) && !isset( $extension ) ) {
    $cli->error( 'extension option is required with version' );
    $validOptions = FALSE;
}
if( !$validOptions ) {
    $script->shutdown( 0 );
}

try {
    if( isset( $force ) ) {
        $migration->migrate( $version, $force );
    } elseif( isset( $version ) ) {
        $migration->migrate( $version );
    } elseif( isset( $extension ) ) {
        $migration->migrate( );
    } else {
        $ini = eZINI::instance( );
        $migrationExtensions = $ini->variable( 'MigrationSettings', 'MigrationExtensions' );
        foreach( $migrationExtensions as $extension ) {
            $migration->startMigrationOnExtension( $extension );
            $migration->migrate( );
        }
    }
} catch(Exception $e) {
    if( $e->getCode( ) == 0 ) {
        $cli->error( $e->getMessage( ) );
    } else {
        $cli->notice( $e->getMessage( ) );
    }
}

$script->shutdown( 0 );
