<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
$tpl = templateInit( );
$tpl->setVariable( 'extension_list', OWMigration::extensionList( ) );
$Result['content'] = $tpl->fetch( 'design:owmigration/dashboard.tpl' );
$Result['left_menu'] = 'design:owmigration/menu.tpl';
if( function_exists( 'ezi18n' ) ) {
    $Result['path'] = array( array(
            'url' => 'owmigration/dashboard',
            'text' => ezi18n( 'owmigration/dashboard', 'Migration dashboard' )
        ) );

} else {
    $Result['path'] = array( array(
            'url' => 'owmigration/roles',
            'text' => ezpI18n::tr( 'owmigration/dashboard', 'Migration dashboard' )
        ) );

}
