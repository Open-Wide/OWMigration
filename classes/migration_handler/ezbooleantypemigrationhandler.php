<?php

class eZBooleanTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        if( (bool) $attribute->attribute( 'data_int3' ) === TRUE )
        {
            return array( 'default_value' => (bool) $attribute->attribute( 'data_int3' ) );
        }
        return array();
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        if( array_key_exists( 'default_value', $options ) )
        {
            $attribute->setAttribute( 'data_int3', $options['default_value'] );
        }
    }

}
