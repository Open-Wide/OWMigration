<?php

class eZIntegerTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $return = array();
        if( $attribute->attribute( eZIntegerType::MIN_VALUE_FIELD ) != 0 )
        {
            $return['min_value'] = $attribute->attribute( eZIntegerType::MIN_VALUE_FIELD );
        }
        if( $attribute->attribute( eZIntegerType::MAX_VALUE_FIELD ) != 0 )
        {
            $return['max_value'] = $attribute->attribute( eZIntegerType::MAX_VALUE_FIELD );
        }
        if( $attribute->attribute( eZIntegerType::DEFAULT_VALUE_FIELD ) != 0 )
        {
            $return['default_value'] = $attribute->attribute( eZIntegerType::DEFAULT_VALUE_FIELD );
        }
        return $return;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        $hasMin = FALSE;
        $hasMax = FALSE;
        foreach( $options as $key => $value )
        {
            switch( $key )
            {
                case 'min_value' :
                    $attribute->setAttribute( eZIntegerType::MIN_VALUE_FIELD, $value );
                    $hasMin = TRUE;
                    break;
                case 'max_value' :
                    $attribute->setAttribute( eZIntegerType::MAX_VALUE_FIELD, $value );
                    $hasMax = TRUE;
                    break;
                case 'default_value' :
                    $attribute->setAttribute( eZIntegerType::DEFAULT_VALUE_FIELD, $value );
                    break;
            }
        }
        if( $hasMin === TRUE && $hasMax === TRUE )
        {
            $attribute->setAttribute( eZIntegerType::INPUT_STATE_FIELD, eZIntegerType::HAS_MIN_MAX_VALUE );
        } elseif( $hasMin === FALSE && $hasMax === FALSE )
        {
            $attribute->setAttribute( eZIntegerType::INPUT_STATE_FIELD, eZIntegerType::NO_MIN_MAX_VALUE );
        } elseif( $hasMin === TRUE )
        {
            $attribute->setAttribute( eZIntegerType::INPUT_STATE_FIELD, eZIntegerType::HAS_MIN_VALUE );
        } elseif( $hasMax === TRUE )
        {
            $attribute->setAttribute( eZIntegerType::INPUT_STATE_FIELD, eZIntegerType::HAS_MAX_VALUE );
        }
    }

}
