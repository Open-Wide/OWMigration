<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
if ( is_callable( 'eZTemplate::factory' ) ) {
    $tpl = eZTemplate::factory();
} else {
    $tpl = templateInit();
}


$Result['content'] = $tpl->fetch( 'design:owmigration/description/workflows.tpl' );
$Result['left_menu'] = 'design:owmigration/menu.tpl';
if ( function_exists( 'ezi18n' ) ) {
    $Result['path'] = array(
        array(
            'url' => 'owmigration/dashboard',
            'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Migrations' )
        ),
        array( 'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Description' ) ),
        array(
            'url' => 'owmigration/classes',
            'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Workflow' )
        )
    );
} else {
    $Result['path'] = array(
        array(
            'url' => 'owmigration/dashboard',
            'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Migrations' )
        ),
        array( 'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Description' ) ),
        array(
            'url' => 'owmigration/classes',
            'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Workflow' )
        )
    );
}