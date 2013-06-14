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
    $filepath = $dir . str_replace( '_', '', $classIdentifier ) . 'contentclassmigration.php';
    @unlink( $filepath );
    eZFile::create( $filepath, false, OWMigrationContentClassCodeGenerator::getMigrationClass( $classIdentifier ) );
    if( !eZFile::download( $filepath ) ) {
        $Module->redirectTo( 'owmigration/classes' );
    }
} elseif( $Module->isCurrentAction( 'ExportAllClassCode' ) ) {
    $classList = eZContentClass::fetchAllClasses( );
    $dir = eZSys::cacheDirectory( ) . '/owmigration/';
    $archiveFile = 'contentclassmigration.zip';
    $archiveFilepath = $dir . $archiveFile;
    eZFile::create( $archiveFile, $dir );
    @unlink( $archiveFilepath );
    $zip = new ZipArchive;
    if( $zip->open( $archiveFilepath, ZIPARCHIVE::CREATE ) === TRUE ) {
        foreach( $classList as $class ) {
            $file = str_replace( '_', '', $class->attribute( 'identifier' ) ) . 'contentclassmigration.php';
            $filepath = $dir . $file;
            @unlink( $filepath );
            eZFile::create( $filepath, false, OWMigrationContentClassCodeGenerator::getMigrationClass( $class->attribute( 'identifier' ) ) );
            $zip->addFile( $filepath, $file );
        }
        $zip->close( );
        if( !eZFile::download( $archiveFilepath, true, $archiveFile ) ) {
            $Module->redirectTo( 'owmigration/classes' );
        }
    }
} else {
    $tpl->setVariable( 'class_identifier', $classIdentifier );
    $Result['content'] = $tpl->fetch( 'design:owmigration/classes.tpl' );
    $Result['left_menu'] = 'design:owmigration/menu.tpl';
    if( function_exists( 'ezi18n' ) ) {
        $Result['path'] = array( array(
                'url' => 'owmigration/classes',
                'text' => ezi18n( 'owmigration/classes', 'Migrate content class' )
            ) );

    } else {
        $Result['path'] = array( array(
                'url' => 'owmigration/classes',
                'text' => ezpI18n::tr( 'owmigration/classes', 'Migrate content class' )
            ) );

    }
}
?>
