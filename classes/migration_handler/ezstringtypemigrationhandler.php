<?php

class eZStringTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $result = array( );
        if( $attribute->attribute( eZStringType::MAX_LEN_FIELD ) > 0 ) {
            $result['max_length'] = $attribute->attribute( eZStringType::MAX_LEN_FIELD );
        }
        if( $attribute->attribute( eZStringType::DEFAULT_STRING_FIELD )  != '' ) {
            $result['default_value'] = $attribute->attribute( eZStringType::DEFAULT_STRING_FIELD );
        }
        return $result;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        if( array_key_exists( 'max_length', $options ) ) {
            $attribute->setAttribute( eZStringType::MAX_LEN_FIELD, $options['max_length'] );
        }
        if( array_key_exists( 'default_value', $options ) ) {
            $attribute->setAttribute( eZStringType::DEFAULT_STRING_FIELD, $options['default_value'] );
        }
    }

}
