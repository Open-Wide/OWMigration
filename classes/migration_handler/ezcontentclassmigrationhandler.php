<?php

class eZContentClassMigrationHandler
{

    static public function toArray( eZContentClass $attribute )
    {
        $attributesArray = array();
        foreach( $attribute->attributes() as $attributeIdentifier )
        {
            $attributeValue = $attribute->attribute( $attributeIdentifier );
            switch( $attributeIdentifier )
            {
                case 'name' :
                case 'description' :
                    $nameList = $attribute->attribute( $attributeIdentifier . 'List' );
                    $nameListValue = OWMigrationTools::cleanupNameList( $nameList );
                    if( !empty( $nameListValue ) )
                    {
                        $attributesArray[$attributeIdentifier] = $nameListValue;
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
                    if( $attributeValue == FALSE )
                    {
                        $attributesArray[$attributeIdentifier] = FALSE;
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
            }
        }
        ksort( $attributesArray );
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        
    }

}

