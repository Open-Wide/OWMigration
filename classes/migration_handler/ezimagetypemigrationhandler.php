<?php

class eZImageTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        if( $attribute->attribute( eZImageType::FILESIZE_FIELD ) > 0 ) {
            return array( 'max_filesize' => $attribute->attribute( eZImageType::FILESIZE_FIELD ) );
        }
        return array( );
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        if( array_key_exists( 'max_filesize', $options ) ) {
            $attribute->setAttribute( eZImageType::FILESIZE_FIELD, $options['max_filesize'] );
        }
    }

}
