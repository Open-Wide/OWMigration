<?php

$Module = $Params["Module"];
include_once ('kernel/common/template.php');
if( is_callable( 'eZTemplate::factory' ) )
{
    $tpl = eZTemplate::factory();
} else
{
    $tpl = templateInit();
}

$contentClassList = eZContentClass::fetchList( eZContentClass::VERSION_STATUS_DEFINED, true, false, array( 'identifier' => 'asc' ) );
$classList = array();
foreach( $contentClassList as $contentClass )
{
    $contentClassInfos = call_user_func( 'ContentClassMigrationHandler::toArray', $contentClass );
    $attributesList = $contentClass->fetchAttributes();
    foreach( $attributesList as $attribute )
    {
        $contentClassAttributeHandlerClass = get_class( $attribute ) . 'MigrationHandler';
        $contentClassAttributeArray = call_user_func( "$contentClassAttributeHandlerClass::toArray", $attribute );
        $datatypeHandlerClass = get_class( $attribute->dataType() ) . 'MigrationHandler';
        if( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::toArray' ) )
        {
            $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
        }
        $attributeDatatypeArray = call_user_func( "$datatypeHandlerClass::toArray", $attribute );
        $attributesInfos = array_merge( $contentClassAttributeArray, $attributeDatatypeArray );
        $contentClassInfos['attributes'][$attribute->attribute( 'identifier' )] = $attributesInfos;
        $classGroupList = array();
        foreach( $contentClass->attribute( 'ingroup_list' ) as $classGroup )
        {
            $classGroupList[] = $classGroup->attribute( 'group_name' );
        }
        $contentClassInfos['class_groups'] = $classGroupList;
    }
    $classList[$contentClass->attribute( 'identifier' )] = $contentClassInfos;
}
$tpl->setVariable( 'class_list', $classList );

$Result['content'] = $tpl->fetch( 'design:owmigration/description/classes.tpl' );
$Result['left_menu'] = 'design:owmigration/menu.tpl';
if( function_exists( 'ezi18n' ) )
{
    $Result['path'] = array(
        array(
            'url' => 'owmigration/dashboard',
            'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Migrations' )
        ),
        array( 'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Description' ) ),
        array(
            'url' => 'owmigration/classes',
            'text' => ezi18n( 'design/admin/parts/owmigration/menu', 'Content class' )
        )
    );
} else
{
    $Result['path'] = array(
        array(
            'url' => 'owmigration/dashboard',
            'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Migrations' )
        ),
        array( 'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Description' ) ),
        array(
            'url' => 'owmigration/classes',
            'text' => ezpI18n::tr( 'design/admin/parts/owmigration/menu', 'Content class' )
        )
    );
}