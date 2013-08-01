<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
$tpl = templateInit( );

$roleID = FALSE;
$role = FALSE;
if( $Module->hasActionParameter( 'RoleID' ) ) {
    $roleID = $Module->actionParameter( 'RoleID' );
} elseif( isset( $Params['RoleID'] ) ) {
    $roleID = $Params['RoleID'];
}

if( $roleID && is_numeric( $roleID ) ) {
    $role = eZRole::fetch( $roleID );
}
if( $role instanceof eZRole && ($Module->isCurrentAction( 'ExportCode' ) || $Module->isCurrentAction( 'ExportAllClassCode' )) ) {
    $mainTmpDir = eZSys::cacheDirectory( ) . '/owmigration/';
    $tmpDir = $mainTmpDir . time( ) . '/';
    OWMigrationRoleCodeGenerator::createDirectory( $tmpDir );
}
if( $Module->isCurrentAction( 'ExportCode' ) ) {
    if( eZFile::download( OWMigrationRoleCodeGenerator::getMigrationClassFile( $role, $tmpDir ) ) ) {
        OWMigrationRoleCodeGenerator::removeDirectory( $tmpDir );
    }
} elseif( $Module->isCurrentAction( 'ExportAllClassCode' ) ) {
    $roleList = eZRole::fetchList( );
    $archiveFile = 'rolemigration.zip';
    $archiveFilepath = $tmpDir . $archiveFile;
    eZFile::create( $archiveFile, $tmpDir );
    @unlink( $archiveFilepath );
    $zip = new ZipArchive;
    if( $zip->open( $archiveFilepath, ZIPARCHIVE::CREATE ) === TRUE ) {
        foreach( $roleList as $role ) {
            $filepath = OWMigrationRoleCodeGenerator::getMigrationClassFile( $role, $tmpDir );
            $file = pathinfo( $filepath, PATHINFO_FILENAME );
            $zip->addFile( $filepath, $file );
        }
        $zip->close( );
        eZFile::download( $archiveFilepath, true, $archiveFile );
        OWMigrationRoleCodeGenerator::removeDirectory( $tmpDir );
    }
} else {
    $roleCount = eZRole::roleCount( );
    $tpl->setVariable( 'rolelist', eZRole::fetchByOffset( 0, $roleCount ) );
    $tpl->setVariable( 'role_id', $roleID );
    $Result['content'] = $tpl->fetch( 'design:owmigration/roles.tpl' );
    $Result['left_menu'] = 'design:owmigration/menu.tpl';
    if( function_exists( 'ezi18n' ) ) {
        $Result['path'] = array( array(
                'url' => 'owmigration/roles',
                'text' => ezi18n( 'owmigration/roles', 'Migrate user roles' )
            ) );

    } else {
        $Result['path'] = array( array(
                'url' => 'owmigration/roles',
                'text' => ezpI18n::tr( 'owmigration/roles', 'Migrate user roles' )
            ) );

    }
}
?>
