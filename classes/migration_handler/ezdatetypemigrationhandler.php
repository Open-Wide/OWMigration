<?php

class eZDateTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        if( (bool) $attribute->attribute( eZDateType::DEFAULT_FIELD ) === TRUE )
        {
            return array( 'set_with_current_date' => (bool) $attribute->attribute( eZDateType::DEFAULT_FIELD ) );
        }
        return array();
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        if( array_key_exists( 'set_with_current_date', $options ) )
        {
            $attribute->setAttribute( eZDateType::DEFAULT_FIELD, $options['set_with_current_date'] );
        }
    }

}
