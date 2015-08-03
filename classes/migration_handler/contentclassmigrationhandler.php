<?php

class ContentClassMigrationHandler
{

    static public function toArray( eZContentClass $contentClass )
    {
        $contentClassArray = array();
        foreach( $contentClass->attributes() as $attributeIdentifier )
        {
            $attributeValue = $contentClass->attribute( $attributeIdentifier );
            switch( $attributeIdentifier )
            {
                case 'name' :
                case 'description' :
                    $nameList = $contentClass->attribute( $attributeIdentifier . 'List' );
                    $nameListValue = OWMigrationTools::cleanupNameList( $nameList );
                    if( !empty( $nameListValue ) )
                    {
                        $contentClassArray[$attributeIdentifier] = $nameListValue;
                    }
                    break;
                case 'contentobject_name' :
                case 'url_alias_name' :
                    if( $attributeValue != '' )
                    {
                        $contentClassArray[$attributeIdentifier] = $attributeValue;
                    }
                    break;
                case 'is_container' :
                case 'always_available' :
                    if( $attributeValue == TRUE )
                    {
                        $contentClassArray[$attributeIdentifier] = TRUE;
                    }
                    break;
                case 'sort_field' :
                    if( $attributeValue != 1 )
                    {
                        $contentClassArray[$attributeIdentifier] = $attributeValue;
                    }
                    break;
                case 'sort_order' :
                    if( $attributeValue == FALSE )
                    {
                        $contentClassArray[$attributeIdentifier] = FALSE;
                    }
                    break;
                default :
                    break;
            }
        }
        return $contentClassArray;
    }

    function fromArray( eZContentClass $contentClass, array $options )
    {
        
    }

}
