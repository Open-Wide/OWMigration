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

$options = $script->getOptions( "[up][down][migration-class:]", "", array(
        'up' => 'Upgrade version',
        'down' => 'Downgrade version',
        'migration-class' => 'Migration class',
) );
$sys = eZSys::instance( );

$script->initialize( );

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user , $user->attribute( 'contentobject_id' ) );

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
$migration = new $migrationClass( );
if( $action == 'up' ) {
    $migration->up( );
}
elseif( $action == 'down' ) {
    $migration->down( );
}
else {
    $cli->error( "Choose between upgrade or downgrade." );
    $script->shutdown( 1 );
}

$script->shutdown( 0 );
