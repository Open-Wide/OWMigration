<?php

class eZObjectRelationTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = array( );
        foreach( $attribute->content() as $attributeIdentifier => $attributeValue ) {
            switch ($attributeIdentifier) {
                case 'selection_type' :
                    $selectionMethods = self::getSelectionMethods( );
                    $attributesArray['selection_method'] = $selectionMethods[$attributeValue];
                    break;
                case 'default_selection_node' :
                    $attributesArray[$attributeIdentifier] = $attributeValue;
                    break;
                case 'fuzzy_match' :
                    $attributesArray[$attributeIdentifier] = (bool)$attributeValue;
                    break;
                default :
                    break;
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        parent::fromArray( $attribute, $options );
        $content = $attribute->content( );
        foreach( $options as $optionIdentifier => $optionValue ) {
            switch ($optionIdentifier) {
                case 'selection_method' :
                    $reverseSelectionMethods = self::getReverseSelectionMethods( );
                    $content['selection_type'] = $reverseSelectionMethods[$optionValue];
                    break;
                default :
                    $content[$optionIdentifier] = $optionValue;
                    break;
            }
        }
        $attribute->setContent( $content );
    }

    protected static function getSelectionMethods( ) {
        return array(
            0 => 'Browse',
            1 => 'Drop-down list',
        );
    }

    protected static function getReverseSelectionMethods( ) {
        return array_flip( self::getSelectionMethods( ) );
    }

}
