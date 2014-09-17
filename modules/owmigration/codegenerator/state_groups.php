<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
if( is_callable( 'eZTemplate::factory' ) ) {
    $tpl = eZTemplate::factory( );
} else {
    $tpl = templateInit( );
}

$objectStateGroupID = FALSE;
$objectState = FALSE;
if( $Module->hasActionParameter( 'ObjectStateGroupID' ) ) {
    $objectStateGroupID = $Module->actionParameter( 'ObjectStateGroupID' );
} elseif( isset( $Params['ObjectStateGroupID'] ) ) {
    $objectStateGroupID = $Params['ObjectStateGroupID'];
}
if( $objectStateGroupID && is_numeric( $objectStateGroupID ) ) {
    $objectState = eZContentObjectStateGroup::fetchById( $objectStateGroupID );
}
if( ($objectState instanceof eZContentObjectStateGroup && ($Module->isCurrentAction( 'ExportCode' )) || $Module->isCurrentAction( 'ExportAllClassCode' )) ) {
    $mainTmpDir = eZSys::cacheDirectory( ) . '/owmigration/';
    $tmpDir = $mainTmpDir . time( ) . '/';
    OWMigrationObjectStateCodeGenerator::createDirectory( $tmpDir );
}
if( $Module->isCurrentAction( 'ExportCode' ) ) {
    $filepath = OWMigrationObjectStateCodeGenerator::getMigrationClassFile( $objectState, $tmpDir );
    $file = pathinfo( $filepath, PATHINFO_BASENAME );
    eZFile::download( $filepath, true, $file );
    OWMigrationObjectStateCodeGenerator::removeDirectory( $tmpDir );
} elseif( $Module->isCurrentAction( 'ExportAllClassCode' ) ) {
    $objectStateGroupCount = eZPersistentObject::count( eZContentObjectStateGroup::definition( ) );
    $objectStateGroupList = eZContentObjectStateGroup::fetchByOffset( $objectStateGroupCount, 0 );
    $archiveFile = 'object_states.zip';
    $archiveFilepath = $tmpDir . $archiveFile;
    eZFile::create( $archiveFile, $tmpDir );
    @unlink( $archiveFilepath );
    $zip = new ZipArchive;
    if( $zip->open( $archiveFilepath, ZIPARCHIVE::CREATE ) === TRUE ) {
        foreach( $objectStateGroupList as $objectState ) {
            $filepath = OWMigrationObjectStateCodeGenerator::getMigrationClassFile( $objectState, $tmpDir );
            $file = pathinfo( $filepath, PATHINFO_BASENAME );
            $zip->addFile( $filepath, $file );
        }
        $zip->close( );
        eZFile::download( $archiveFilepath, true, $archiveFile );
        OWMigrationObjectStateCodeGenerator::removeDirectory( $tmpDir );
    }
} else {
    $objectStateGroupCount = eZPersistentObject::count( eZContentObjectStateGroup::definition( ) );
    $tpl->setVariable( 'object_state_group_list', eZContentObjectStateGroup::fetchByOffset( $objectStateGroupCount, 0 ) );
    $tpl->setVariable( 'object_state_group_id', $objectStateGroupID );
    $Result['content'] = $tpl->fetch( 'design:owmigration/codegenerator/state_groups.tpl' );
    $Result['left_menu'] = 'design:owmigration/menu.tpl';
    if( function_exists( 'ezi18n' ) ) {
        $Result['path'] = array(
            array(
                'url' => 'owmigration/dashboard',
                'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Migrations' )
            ),
            array( 'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Code generator' ) ),
            array(
                'url' => 'owmigration/state_groups',
                'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'State group' )
            )
        );

    } else {
        $Result['path'] = array(
            array(
                'url' => 'owmigration/dashboard',
                'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Migrations' )
            ),
            array( 'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Code generator' ) ),
            array(
                'url' => 'owmigration/state_groups',
                'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'State group' )
            )
        );
    }
}
?>
