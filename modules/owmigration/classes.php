<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
$tpl = templateInit( );

$classIdentifier = FALSE;
if( $Module->hasActionParameter( 'ContentClassIdentifier' ) ) {
    $classIdentifier = $Module->actionParameter( 'ContentClassIdentifier' );
} elseif( isset( $Params['ContentClassIdentifier'] ) ) {
    $classIdentifier = $Params['ContentClassIdentifier'];
}

if( $classIdentifier && is_numeric( $classIdentifier ) ) {
    $classIdentifier = eZContentClass::classIdentifierByID( $classIdentifier );
}

if( $Module->isCurrentAction( 'ExportCode' ) ) {
    $dir = eZSys::cacheDirectory( ) . '/';
    $file = $dir . str_replace( '_', '', $classIdentifier ) . 'contentclassmigration.php';
    @unlink( $file );
    eZFile::create( $file, false, OWMigrationContentClassCodeGenerator::getMigrationClass( $classIdentifier ) );
    if( !eZFile::download( $file ) ) {
        $module->redirectTo( 'owmigration/classes' );
    }
} else {
    $tpl->setVariable( 'class_identifier', $classIdentifier );
    $Result['content'] = $tpl->fetch( 'design:owmigration/classes.tpl' );
    $Result['left_menu'] = 'design:owmigration/menu.tpl';
    $Result['path'] = array( array(
            'url' => 'owmigration/classes',
            'text' => ezi18n( 'owmigration/classes', 'Migrate content class' )
        ) );
}
?>
