<?php

class DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = array( );
        foreach( $attribute->attributes() as $attributeIdentifier ) {
            if( (strpos( $attributeIdentifier, "data_int" ) === 0 && $attribute->attribute( $attributeIdentifier ) !== '0') || (strpos( $attributeIdentifier, "data_text" ) === 0 && $attribute->attribute( $attributeIdentifier ) !== '') ) {
                $attributesArray[$attributeIdentifier] = $attribute->attribute( $attributeIdentifier );
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        foreach( $options as $optionsIdentifier => $optionsValue ) {
            if( $attribute->hasAttribute( $optionsIdentifier ) && (strpos( $optionsIdentifier, "data_int" ) === 0 || strpos( $optionsIdentifier, "data_text" ) === 0) ) {
                $attribute->setAttribute( $optionsIdentifier, $optionsValue );
            }
        }
    }

}
?>