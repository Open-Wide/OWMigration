<?php

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array(
            'description' => ("eZ Publish Migration Handler\n" . "Launch migration\n" . "\n" . ".extension/OWMigration/bin/php/specify.php --extension=my_extension"),
            'use-session' => false,
            'use-modules' => true,
            'use-extensions' => true
        ) );

$script->startup();

$options = $script->getOptions( "[extension:][all][classes][roles][workflows][object_states]", "", array(
    'extension' => 'Name of the extension to migrate',
    'all' => 'Specify classes, roles, workflows and object_states',
    'classes' => 'Specify classes',
    'roles' => 'Specify roles',
    'workflows' => 'Specify workflows',
    'object_states' => 'Specify object_states',
        ) );
$sys = eZSys::instance();

$script->initialize();

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );



if( isset( $options['extension'] ) )
{
    $extention = $options['extension'];
    if( !is_dir( 'extension/' . $extention . '/migrations' ) )
    {
        $cli->error( 'Migration directory is missing in ' . $extention . ' folder.' );
        $script->shutdown( 1 );
    }
} else
{
    $cli->error( '--extension parameter is missing.' );
    $script->shutdown( 1 );
}

$specifyClasses = $options['all'] || $options['classes'] ? true : false;
$specifyRoles = $options['all'] || $options['roles'] ? true : false;
$specifyWorkflows = $options['all'] || $options['classes'] ? true : false;
$specifyObjectStates = $options['all'] || $options['object_states'] ? true : false;

if( $specifyClasses )
{
    $result = array();
    $classesList = eZContentClass::fetchAllClasses();
    foreach( $classesList as $class )
    {
        $result[$class->attribute( 'identifier' )] = call_user_func( "eZContentClassMigrationHandler::toArray", $class );
        $attributesList = $class->fetchAttributes();
        $result[$class->attribute( 'identifier' )]['attributes'] = array();
        foreach( $attributesList as $attribute )
        {
            $contentClassAttributeHandlerClass = get_class( $attribute ) . 'MigrationHandler';
            $contentClassAttributeArray = call_user_func( "$contentClassAttributeHandlerClass::toArray", $attribute );
            if( $attribute->dataType() )
            {
                $datatypeHandlerClass = get_class( $attribute->dataType() ) . 'MigrationHandler';
                if( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::toArray' ) )
                {
                    $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
                }
                $attributeDatatypeArray = call_user_func( "$datatypeHandlerClass::toArray", $attribute );
                $attributeArray = array_merge( $contentClassAttributeArray, $attributeDatatypeArray );
            } else
            {
                $attributeArray = $contentClassAttributeArray;
            }
            $result[$class->attribute( 'identifier' )]['attributes'][$attribute->attribute( 'identifier' )] = $attributeArray;
        }
    }
    if( version_compare( PHP_VERSION, '5.4.0' ) >= 0 )
    {
        $jsonEncodeOption = JSON_PRETTY_PRINT;
    } else
    {
        $jsonEncodeOption = 0;
    }
    file_put_contents( 'extension/' . $extention . '/migrations/spec_classes.json', json_encode( $result, $jsonEncodeOption ) );
}

$script->shutdown( 0 );
