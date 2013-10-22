<?php

class eZContentClassAttributeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = array( );
        foreach( $attribute->attributes() as $attributeIdentifier ) {
            if( preg_match( '/^data_(int|text){1}[0-9]{1}/', $attributeIdentifier ) == 0 ) {
                $attributeValue = $attribute->attribute( $attributeIdentifier );
                if( !empty( $attributeValue ) ) {
                    $attributesArray[$attributeIdentifier] = $attribute->attribute( $attributeIdentifier );
                }
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        $classAttributeIdentifier = $attribute->attribute( 'identifier' );
        if( !isset( $options['name'] ) ) {
            $trans = eZCharTransform::instance( );
            $attribute->setName( $trans->transformByGroup( $classAttributeIdentifier, 'humanize' ) );
        }
        foreach( $options as $optionsIdentifier => $optionsValue ) {
            if( $attribute->hasAttribute( $optionsIdentifier ) && preg_match( '/^data_(int|text){1}[0-9]{1}/', $optionsIdentifier ) == 0 ) {
                switch($optionsIdentifier ) {
                    case 'content' :
                        $content = $attribute->content( );
                        if( is_array( $content ) ) {
                            $optionsValue = array_merge( $content, $optionsValue );
                        }
                        $attribute->setContent( $optionsValue );
                        break;
                    case 'name' :
                        if( is_string( $optionsValue ) ) {
                            $attribute->setName( $optionsValue );
                        } elseif( is_array( $optionsValue ) ) {
                            $nameList = new eZContentClassAttributeNameList( serialize( $optionsValue ) );
                            $nameList->validate( );
                            $attribute->NameList = $nameList;
                        }
                        break;
                    case 'description' :
                        if( is_string( $optionsValue ) ) {
                            $attribute->setDescription( $optionsValue );
                        } elseif( is_array( $optionsValue ) ) {
                            $nameList = new eZContentClassAttributeNameList( serialize( $optionsValue ) );
                            $nameList->validate( );
                            $attribute->DescriptionList = $nameList;
                        }
                        break;
                    case 'data_type_string' :
                        if( $attribute->attribute( 'data_type_string' ) != $optionsValue ) {
                            OWScriptLogger::logError( "Datatype conversion not possible: '" . $params['data_type_string'] . "'", __FUNCTION__ );
                        }
                        break;
                    default :
                        $attribute->setAttribute( $optionsIdentifier, $optionsValue );
                        break;
                }

            }
        }
    }

}
?>