<?php

class eZFloatTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $return = array( );
        if( $attribute->attribute( eZFloatType::MIN_FIELD ) != 0 ) {
            $return['min_value'] = $attribute->attribute( eZFloatType::MIN_FIELD );
        }
        if( $attribute->attribute( eZFloatType::MAX_FIELD ) != 0 ) {
            $return['max_value'] = $attribute->attribute( eZFloatType::MAX_FIELD );
        }
        if( $attribute->attribute( eZFloatType::DEFAULT_FIELD ) != 0 ) {
            $return['default_value'] = $attribute->attribute( eZFloatType::DEFAULT_FIELD );
        }
        return $return;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        $hasMin = FALSE;
        $hasMax = FALSE;
        foreach( $options as $key => $value ) {
            switch ($key) {
                case 'min_value' :
                    $attribute->setAttribute( eZFloatType::MIN_FIELD, $value );
                    $hasMin = TRUE;
                    break;
                case 'max_value' :
                    $attribute->setAttribute( eZFloatType::MAX_FIELD, $value );
                    $hasMax = TRUE;
                    break;
                case 'default_value' :
                    $attribute->setAttribute( eZFloatType::DEFAULT_FIELD, $value );
                    break;
            }
        }
        if( $hasMin === TRUE && $hasMax === TRUE ) {
            $attribute->setAttribute( eZFloatType::INPUT_STATE_FIELD, eZFloatType::HAS_MIN_MAX_VALUE );
        } elseif( $hasMin === FALSE && $hasMax === FALSE ) {
            $attribute->setAttribute( eZFloatType::INPUT_STATE_FIELD, eZFloatType::NO_MIN_MAX_VALUE );
        } elseif( $hasMin === TRUE ) {
            $attribute->setAttribute( eZFloatType::INPUT_STATE_FIELD, eZFloatType::HAS_MIN_VALUE );
        } elseif( $hasMax === TRUE ) {
            $attribute->setAttribute( eZFloatType::INPUT_STATE_FIELD, eZFloatType::HAS_MAX_VALUE );
        }
    }

}
