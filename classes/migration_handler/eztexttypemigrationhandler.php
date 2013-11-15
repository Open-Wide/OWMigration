<?php

class eZTextTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        if( $attribute->attribute( eZTextType::COLS_FIELD ) != 10 ) {
            return array( 'cols' => $attribute->attribute( eZTextType::COLS_FIELD ) );
        }
        return array( );
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        if( array_key_exists( 'cols', $options ) ) {
            $attribute->setAttribute( eZTextType::COLS_FIELD, $options['cols'] );
        }
    }

}
