<?php

class eZTimeTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $result = array();
        if( (bool) $attribute->attribute( eZTimeType::USE_SECONDS_FIELD ) === TRUE )
        {
            $result['use_seconds'] = (bool) $attribute->attribute( eZTimeType::DEFAULT_FIELD );
        }
        if( (bool) $attribute->attribute( eZDateType::DEFAULT_FIELD ) === TRUE )
        {
            $result['set_with_current_time'] = (bool) $attribute->attribute( eZTimeType::DEFAULT_FIELD );
        }
        return $result;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        if( array_key_exists( 'use_seconds', $options ) )
        {
            $attribute->setAttribute( eZTimeType::USE_SECONDS_FIELD, $options['use_seconds'] );
        }
        if( array_key_exists( 'set_with_current_time', $options ) )
        {
            $attribute->setAttribute( eZTimeType::DEFAULT_FIELD, $options['set_with_current_time'] );
        }
    }

}
