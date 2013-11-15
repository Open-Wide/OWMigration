<?php

class SckEnhancedSelectionTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        return $attribute->content( );
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        if( array_key_exists( 'options', $options ) ) {
            foreach( $options['options'] as $key => $value ) {
                if( !array_key_exists( 'id', $value ) ) {
                    $options['options'][$key]['id'] = $key + 1;
                }
                if( !array_key_exists( 'priority', $value ) ) {
                    $options['options'][$key]['priority'] = 1;
                }
            }
        }
        $content = $attribute->content( );
        $content = array_merge( $content, $options );
        $attribute->setContent( $content );
    }

}
