<?php

// Operator autoloading

$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] = array(
    'script' => 'extension/owmigration/autoloads/owmigrationoperators.php',
    'class' => 'OWMigrationOperators',
    'operator_names' => array(
        'camelize',
        'display_content_migration_class',
        'display_role_migration_class',
        'display_workflow_migration_class',
        'display_state_group_migration_class'
    )
);