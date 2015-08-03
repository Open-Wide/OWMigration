<?php

class eZEnumTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $content = $attribute->content();
        $res = array();
        foreach( $content->Enumerations as $key => $enumeration )
        {
            $res['options'][$key]['name'] = $enumeration->EnumElement;
            $res['options'][$key]['value'] = $enumeration->EnumValue;
        }

        $res['is_multiple'] = $attribute->attribute( eZEnumType::IS_MULTIPLE_FIELD );
        $res['is_option'] = $attribute->attribute( eZEnumType::IS_OPTION_FIELD );
        return $res;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {

        $attribute->setAttribute( eZEnumType::IS_OPTION_FIELD, $options['is_option'] );
        $attribute->setAttribute( eZEnumType::IS_MULTIPLE_FIELD, $options['is_multiple'] );

        $enum = new eZEnum( $attribute->attribute( 'id' ), $attribute->attribute( 'version' ) );
        $elementList = $options['options'];
        if( is_array( $elementList ) )
        {
            foreach( $elementList as $element )
            {
                $elementName = $element['name'];
                $elementValue = $element['value'];
                $value = eZEnumValue::create( $attribute->attribute( 'id' ), $attribute->attribute( 'version' ), $elementName );
                $value->setAttribute( 'enumvalue', $elementValue );
                $value->store();
                $enum->addEnumerationValue( $value );
            }
        }
    }

}
