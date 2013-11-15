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
    $extensionList = OWMigration::extensionList( );
    if( $extensionList ) {
        $separationFormat = "-%'-42s-%'-17s-%'-17s-";
        $lineFormat = "| %-40s | %-15s | %-15s |";
        $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
        $cli->notice( sprintf( $lineFormat, 'Extension', 'Current version', 'Latest version' ) );
        $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
        foreach( $extensionList as $extension ) {
            $cli->notice( sprintf( $lineFormat, $extension['name'], $extension['current_version'], $extension['latest_version'] ) );
        }
        $cli->notice( sprintf( $separationFormat, '-', '-', '-' ) );
    }
    $script->shutdown( 0 );
}

$validOptions = TRUE;
$migration = new OWMigration( );

$ini = eZINI::instance( );
$migrationExtensions = $ini->variable( 'MigrationSettings', 'MigrationExtensions' );

$passedOptions = array( );
if( isset( $options['force'] ) ) {
    $force = $options['force'];
    if( $force != 'up' && $force != 'down' ) {
        $cli->error( 'Authorized values for force option are up or down.' );
        $validOptions = FALSE;
    }
    $passedOptions[] = '--force=' . $force;
}
if( isset( $options['version'] ) ) {
    $version = $options['version'];
    $passedOptions[] = '--version=' . $version;
}
if( isset( $options['extension'] ) ) {
    $extension = $options['extension'];
    $passedOptions[] = '--extension=' . $extension;
    if( !in_array( $extension, $migrationExtensions ) ) {
        $cli->error( 'Migration is not enabled for this extension' );
        $validOptions = FALSE;
    } else {
        $migration->startMigrationOnExtension( $extension );
    }
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

if( empty( $passedOptions ) ) {
    $optionString = 'No option';
} else {
    $optionString = 'option(s) = ' . implode( ' ', array_reverse( $passedOptions ) );
}

OWScriptLogger::startLog( 'owmigration_migrate' );
OWScriptLogger::logNotice( $optionString, 'migrate' );

if( isset( $force ) ) {
    $migration->migrate( $version, $force );
} elseif( isset( $version ) ) {
    $migration->migrate( $version );
} elseif( isset( $extension ) ) {
    $migration->migrate( );
} else {
    if( $migrationExtensions ) {
        foreach( $migrationExtensions as $extension ) {
            $migration->startMigrationOnExtension( $extension );
            $migration->migrate( );
        }
    }
}

$script->shutdown( 0 );
