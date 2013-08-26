<?php

require 'autoload.php';

$cli = eZCLI::instance( );
$script = eZScript::instance( array(
    'description' => ("eZ Publish Migration Handler\n" . "Permet le déploiement des modifications à effextuer au n iveau de la base de données\n" . "\n" . ".extension/OWMigration/bin/php/migrate.php --migration-class=MigrationClass"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true
) );

$script->startup( );

$options = $script->getOptions( "[list][up][down][migration-class:]", "", array(
    'list' => 'List the migration classes launched',
    'up' => 'Upgrade version',
    'down' => 'Downgrade version',
    'migration-class' => 'Migration class',
) );
$sys = eZSys::instance( );

$script->initialize( );

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

if( isset( $options['list'] ) && $options['list'] === TRUE ) {
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
}

if( !isset( $options['migration-class'] ) ) {
    $cli->error( "--migration-class parameter is required." );
    $script->shutdown( 1 );
}

if( !class_exists( $options['migration-class'] ) ) {
    $cli->error( "Class " . $options['migration-class'] . " not found." );
    $script->shutdown( 1 );
}
$migrationClass = $options['migration-class'];

$action = "";
if( isset( $options['down'] ) && $options['down'] === TRUE ) {
    $action .= 'down';
}
if( isset( $options['up'] ) && $options['up'] === TRUE ) {
    $action .= 'up';
}
$migration = new $migrationClass( array(
    'class' => $migrationClass,
    'method' => $action,
    'date' => date( 'Y-m-d H:i:s' )
) );

if( $action == 'up' ) {
    $migration->up( );
} elseif( $action == 'down' ) {
    $migration->down( );
} else {
    $cli->error( "Choose between upgrade or downgrade." );
    $script->shutdown( 1 );
}

$migration->setAttribute( 'log', OWMigrationLogger::serializeMessages( ) );
$migration->store( );

$script->shutdown( 0 );
