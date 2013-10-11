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
        'ActionExportCode' => 'ExportCode',
        'ActionExportAllClassCode' => 'ExportAllClassCode'
    ),
    'post_action_parameters' => array(
        'GenerateCode' => array( 'ContentClassIdentifier' => 'ContentClassIdentifier' ),
        'ExportCode' => array( 'ContentClassIdentifier' => 'ContentClassIdentifier' )
    )
);

$ViewList['roles'] = array(
    'script' => 'roles.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
    'params' => array( 'RoleID' ),
    'single_post_actions' => array(
        'ActionGenerateCode' => 'GenerateCode',
        'ActionExportCode' => 'ExportCode',
        'ActionExportAllClassCode' => 'ExportAllClassCode'
    ),
    'post_action_parameters' => array(
        'GenerateCode' => array( 'RoleID' => 'RoleID' ),
        'ExportCode' => array( 'RoleID' => 'RoleID' )
    )
);

$ViewList['workflows'] = array(
    'script' => 'workflows.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
    'params' => array( 'WorkflowID' ),
    'single_post_actions' => array(
        'ActionGenerateCode' => 'GenerateCode',
        'ActionExportCode' => 'ExportCode',
        'ActionExportAllClassCode' => 'ExportAllClassCode'
    ),
    'post_action_parameters' => array(
        'GenerateCode' => array( 'WorkflowID' => 'WorkflowID' ),
        'ExportCode' => array( 'WorkflowID' => 'WorkflowID' )
    )
);

$ViewList['dashboard'] = array(
    'script' => 'dashboard.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$FunctionList['read'] = array( );
?>
