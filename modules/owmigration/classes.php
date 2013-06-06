<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
$tpl = templateInit( );

$classIdentifier = FALSE;
if( $Module->isCurrentAction( 'GenerateCode' ) && $Module->hasActionParameter( 'ContentClassIdentifier' ) ) {
    $classIdentifier = $Module->actionParameter( 'ContentClassIdentifier' );
} elseif( isset( $Params['ContentClassIdentifier'] ) ) {
    $classIdentifier = $Params['ContentClassIdentifier'];
}

if( $classIdentifier && is_numeric( $classIdentifier ) ) {
    $classIdentifier = eZContentClass::classIdentifierByID( $classIdentifier );
}
$tpl->setVariable( 'class_identifier', $classIdentifier );
$Result['content'] = $tpl->fetch( 'design:owmigration/classes.tpl' );
$Result['left_menu'] = 'design:owmigration/menu.tpl';
$Result['path'] = array( array(
        'url' => 'owmigration/classes',
        'text' => ezi18n( 'owmigration/classes', 'Migrate content class' )
    ) );
?>
