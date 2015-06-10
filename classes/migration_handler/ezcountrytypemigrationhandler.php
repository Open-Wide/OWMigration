<?php

class eZCountryTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $result = array();
        foreach( $attribute->content() as $attributeIdentifier => $attributeValue )
        {
            switch( $attributeIdentifier )
            {
                case 'multiple_choice' :
                    if( (bool) $attributeValue === TRUE )
                    {
                        $result[$attributeIdentifier] = (bool) $attributeValue;
                    }
                    break;
                case 'default_countries' :
                    $result[$attributeIdentifier] = implode( ',', array_keys( $attributeValue ) );
                    break;
            }
        }
        return $result;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        $content = $attribute->content();
        foreach( $options as $optionIdentifier => $optionValue )
        {
            switch( $optionIdentifier )
            {
                case 'multiple_choice' :
                    $content[$optionIdentifier] = $optionValue;
                    break;
                case 'default_countries' :
                    $countryList = explode( ',', $optionValue );
                    $defaultList = array();
                    foreach( $countryList as $country )
                    {
                        if( trim( $country ) == '' )
                            continue;
                        $eZCountry = eZCountryType::fetchCountry( $country, 'Alpha2' );
                        if( $eZCountry )
                            $defaultList[$country] = $eZCountry;
                    }
                    $content[$optionIdentifier] = $defaultList;
                    break;
            }
        }
        $attribute->setContent( $content );
    }

}
