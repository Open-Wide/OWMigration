<?php

class DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = array( );
        foreach( $attribute->attributes() as $attributeIdentifier ) {
            if( (preg_match( '/^data_int[0-9]{1}/', $attributeIdentifier ) > 0 && $attribute->attribute( $attributeIdentifier ) !== '0') || (preg_match( '/^data_text[0-9]{1}/', $attributeIdentifier ) > 0 && $attribute->attribute( $attributeIdentifier ) !== '') ) {
                $attributesArray[$attributeIdentifier] = $attribute->attribute( $attributeIdentifier );
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        foreach( $options as $optionsIdentifier => $optionsValue ) {
            if( $attribute->hasAttribute( $optionsIdentifier ) && preg_match( '/^data_(int|text){1}[0-9]{1}/', $optionsIdentifier ) > 0 ) {
                $attribute->setAttribute( $optionsIdentifier, $optionsValue );
            }
        }
    }

}
?>