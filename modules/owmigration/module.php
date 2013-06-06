<?php

$Module = array( 'name' => 'OW Migration' );

$ViewList = array( );
$ViewList['classes'] = array(
    'script' => 'classes.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
    'params' => array( 'ContentClassIdentifier' ),
    'single_post_actions' => array(
        'ActionGenerateCode' => 'GenerateCode',
        'ActionExportCode' => 'ExportCode'
    ),
    'post_action_parameters' => array(
        'GenerateCode' => array( 'ContentClassIdentifier' => 'ContentClassIdentifier' ),
        'ExportCode' => array( 'ContentClassIdentifier' => 'ContentClassIdentifier' )
    )
);

$FunctionList['read'] = array( );
?>
