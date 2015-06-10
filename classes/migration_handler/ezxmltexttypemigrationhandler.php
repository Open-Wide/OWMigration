<?php

class eZXMLTextTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $result = array();
        if( $attribute->attribute( eZXMLTextType::COLS_FIELD ) != 10 )
        {
            $result['cols'] = $attribute->attribute( eZXMLTextType::COLS_FIELD );
        }
        if( $attribute->attribute( eZXMLTextType::TAG_PRESET_FIELD ) != '' )
        {
            $result['tagpreset'] = $attribute->attribute( eZXMLTextType::TAG_PRESET_FIELD );
        }
        return $result;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        if( array_key_exists( 'cols', $options ) )
        {
            $attribute->setAttribute( eZXMLTextType::COLS_FIELD, $options['cols'] );
        }
        if( array_key_exists( 'tagpreset', $options ) )
        {
            $attribute->setAttribute( eZXMLTextType::TAG_PRESET_FIELD, $options['tagpreset'] );
        }
    }

}
