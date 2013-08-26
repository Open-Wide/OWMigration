<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
$tpl = templateInit( );
$tpl->setVariable( 'migration_list', OWMigration::fetchList( ) );
$Result['content'] = $tpl->fetch( 'design:owmigration/history.tpl' );
$Result['left_menu'] = 'design:owmigration/menu.tpl';
if( function_exists( 'ezi18n' ) ) {
    $Result['path'] = array( array(
            'url' => 'owmigration/history',
            'text' => ezi18n( 'owmigration/history', 'Migration history' )
        ) );

} else {
    $Result['path'] = array( array(
            'url' => 'owmigration/roles',
            'text' => ezpI18n::tr( 'owmigration/history', 'Migration history' )
        ) );

}
