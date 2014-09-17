<?php

$Module = array( 'name' => 'Migrations' );

$ViewList = array();

$ViewList['dashboard'] = array(
    'script' => 'dashboard.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$ViewList['codegenerator_classes'] = array(
    'script' => 'codegenerator/classes.php',
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

$ViewList['codegenerator_roles'] = array(
    'script' => 'codegenerator/roles.php',
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

$ViewList['codegenerator_workflows'] = array(
    'script' => 'codegenerator/workflows.php',
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

$ViewList['state_groups'] = array(
    'script' => 'codegenerator/state_groups.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
    'params' => array( 'ObjectStateGroupID' ),
    'single_post_actions' => array(
        'ActionGenerateCode' => 'GenerateCode',
        'ActionExportCode' => 'ExportCode',
        'ActionExportAllClassCode' => 'ExportAllClassCode'
    ),
    'post_action_parameters' => array(
        'GenerateCode' => array( 'ObjectStateGroupID' => 'ObjectStateGroupID' ),
        'ExportCode' => array( 'ObjectStateGroupID' => 'ObjectStateGroupID' )
    )
);

$ViewList['description_classes'] = array(
    'script' => 'description/classes.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$ViewList['description_roles'] = array(
    'script' => 'description/roles.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$ViewList['description_workflows'] = array(
    'script' => 'description/workflows.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$ViewList['description_state'] = array(
    'script' => 'description/state.php',
    'functions' => array( 'read' ),
    'default_navigation_part' => 'owmigration',
    'ui_context' => 'view',
);

$FunctionList['read'] = array();

