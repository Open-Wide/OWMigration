<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
if( is_callable( 'eZTemplate::factory' ) ) {
    $tpl = eZTemplate::factory( );
} else {
    $tpl = templateInit( );
}

$classIdentifier = FALSE;
if( $Module->hasActionParameter( 'ContentClassIdentifier' ) ) {
    $classIdentifier = $Module->actionParameter( 'ContentClassIdentifier' );
} elseif( isset( $Params['ContentClassIdentifier'] ) ) {
    $classIdentifier = $Params['ContentClassIdentifier'];
}

if( $classIdentifier && is_numeric( $classIdentifier ) ) {
    $classIdentifier = eZContentClass::classIdentifierByID( $classIdentifier );
}
$class = eZContentClass::fetchByIdentifier( $classIdentifier );

if( ($class instanceof eZContentClass && ($Module->isCurrentAction( 'ExportCode' )) || $Module->isCurrentAction( 'ExportAllClassCode' )) ) {
    $mainTmpDir = eZSys::cacheDirectory( ) . '/owmigration/';
    $tmpDir = $mainTmpDir . time( ) . '/';
    OWMigrationContentClassCodeGenerator::createDirectory( $tmpDir );
}
if( $Module->isCurrentAction( 'ExportCode' ) ) {
    $filepath = OWMigrationContentClassCodeGenerator::getMigrationClassFile( $classIdentifier, $tmpDir );
    $file = pathinfo( $filepath, PATHINFO_BASENAME );
    eZFile::download( $filepath, true, $file );
    OWMigrationRoleCodeGenerator::removeDirectory( $tmpDir );
} elseif( $Module->isCurrentAction( 'ExportAllClassCode' ) ) {
    $classList = eZContentClass::fetchAllClasses( );
    $archiveFile = 'contentclasses.zip';
    $archiveFilepath = $tmpDir . $archiveFile;
    eZFile::create( $archiveFile, $tmpDir );
    @unlink( $archiveFilepath );
    $zip = new ZipArchive;
    if( $zip->open( $archiveFilepath, ZIPARCHIVE::CREATE ) === TRUE ) {
        foreach( $classList as $class ) {
            $filepath = OWMigrationContentClassCodeGenerator::getMigrationClassFile( $class->attribute( 'identifier' ), $tmpDir );
            $file = pathinfo( $filepath, PATHINFO_BASENAME );
            $zip->addFile( $filepath, $file );
        }
        $zip->close( );
        eZFile::download( $archiveFilepath, true, $archiveFile );
        OWMigrationContentClassCodeGenerator::removeDirectory( $tmpDir );
    }
} else {
    $tpl->setVariable( 'class_identifier', $classIdentifier );
    $Result['content'] = $tpl->fetch( 'design:owmigration/classes.tpl' );
    $Result['left_menu'] = 'design:owmigration/menu.tpl';
    if( function_exists( 'ezi18n' ) ) {
        $Result['path'] = array(
            array(
                'url' => 'owmigration/dashboard',
                'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'OW Migration' )
            ),
            array( 'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Code generator' ) ),
            array(
                'url' => 'owmigration/classes',
                'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Content class' )
            )
        );

    } else {
        $Result['path'] = array(
            array(
                'url' => 'owmigration/dashboard',
                'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'OW Migration' )
            ),
            array( 'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Code generator' ) ),
            array(
                'url' => 'owmigration/classes',
                'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Content class' )
            )
        );
    }
}
?>
