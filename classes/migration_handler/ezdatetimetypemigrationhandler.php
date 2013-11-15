<?php

class eZDateTimeTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $result = array( );
        if( (bool)$attribute->attribute( eZDateTimeType::USE_SECONDS_FIELD ) === TRUE ) {
            $result['use_seconds'] = (bool)$attribute->attribute( eZDateTimeType::DEFAULT_FIELD );
        }
        if( $attribute->attribute( eZDateTimeType::DEFAULT_FIELD ) == 1 ) {
            $result['set_with_current_date'] = TRUE;
        } elseif( $attribute->attribute( eZDateTimeType::DEFAULT_FIELD ) == 2 ) {
            $result['set_with_adjusted_current_date'] = TRUE;
        }
        foreach( $attribute->content() as $adjustmentKey => $adjustmentValue ) {
            if( $adjustmentValue != '' ) {
                $result['adjustment'][$adjustmentKey] = $adjustmentValue;
            }
        }
        return $result;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        if( array_key_exists( 'use_seconds', $options ) ) {
            $attribute->setAttribute( eZDateTimeType::USE_SECONDS_FIELD, $options['use_seconds'] );
        }
        if( array_key_exists( 'set_with_current_date', $options ) ) {
            $attribute->setAttribute( eZDateTimeType::DEFAULT_FIELD, 1 );
        }
        if( array_key_exists( 'set_with_adjusted_current_date', $options ) ) {
            $attribute->setAttribute( eZDateTimeType::DEFAULT_FIELD, 2 );
        }
        if( array_key_exists( 'adjustment', $options ) ) {
            $content = $attribute->content( );
            foreach( $options['adjustment'] as $adjustmentKey => $adjustmentValue ) {
                $content[$adjustmentKey] = $adjustmentValue;
            }
            $attribute->setContent( $content );
        }
    }

}
