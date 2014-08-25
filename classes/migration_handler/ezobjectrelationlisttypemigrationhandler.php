<?php

class eZObjectRelationListTypeMigrationHandler extends DefaultDatatypeMigrationHandler {

    static public function toArray( eZContentClassAttribute $attribute ) {
        $attributesArray = array();
        $advancedObjectRelationList = TRUE;
        $ini = eZINI::instance();
        if ( $ini->hasVariable( 'BackwardCompatibilitySettings', 'AdvancedObjectRelationList' ) && $ini->variable( 'BackwardCompatibilitySettings', 'AdvancedObjectRelationList' ) != 'enabled' ) {
            $advancedObjectRelationList = FALSE;
        }
        foreach ( $attribute->content() as $attributeIdentifier => $attributeValue ) {
            switch ( $attributeIdentifier ) {
                case 'object_class' :
                    if ( $advancedObjectRelationList && !empty( $attributeValue ) ) {
                        $class = eZContentClass::fetch( $attributeValue );
                        $attributesArray['new_object_class'] = $class->attribute( 'identifier' );
                    }
                    break;
                case 'selection_type' :
                    $selectionMethods = self::getSelectionMethods();
                    $attributesArray['selection_method'] = $selectionMethods[$attributeValue];
                    break;
                case 'type' :
                    if ( $advancedObjectRelationList ) {
                        $selectionTypes = self::getSelectionTypes();
                        $attributesArray['selection_type'] = $selectionTypes[$attributeValue];
                    }
                    break;
                case 'class_constraint_list' :
                    $attributesArray[$attributeIdentifier] = implode( ',', $attributeValue );
                    break;
                case 'default_placement' :
                    if ( is_array( $attributeValue ) ) {
                        $nodeID = current( $attributeValue );
                        $node = eZContentObjectTreeNode::fetch( $nodeID );
                        $attributesArray[$attributeIdentifier] = $node->attribute( 'path_identification_string' );
                    }
                    break;
                default :
                    break;
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options ) {
        parent::fromArray( $attribute, $options );
        $content = $attribute->content();
        foreach ( $options as $optionIdentifier => $optionValue ) {
            switch ( $optionIdentifier ) {
                case 'new_object_class' :
                    if ( !empty( $optionValue ) ) {
                        $class = eZContentClass::fetchByIdentifier( $optionValue );
                        $content['object_class'] = $class->attribute( 'id' );
                    }
                    break;
                case 'selection_method' :
                    $reverseSelectionMethods = self::getReverseSelectionMethods();
                    $content['selection_type'] = $reverseSelectionMethods[$optionValue];
                    break;
                case 'selection_type' :
                    $reverseSelectionTypes = self::getReverseSelectionTypes();
                    $content['type'] = $reverseSelectionTypes[$optionValue];
                    break;
                case 'class_constraint_list' :
                    $content[$optionIdentifier] = explode( ',', $optionValue );
                    break;
                case 'default_placement' :
                    if ( is_numeric( $optionValue ) ) {
                        $content[$optionIdentifier] = array( 'node_id' => $optionValue );
                    } elseif ( is_string( $optionValue ) ) {
                        $node = eZContentObjectTreeNode::fetchByURLPath( $optionValue );
                        if ( $node instanceof eZContentObjectTreeNode ) {
                            $content[$optionIdentifier] = array( 'node_id' => $node->attribute( 'node_id' ) );
                        } else {
                            $content[$optionIdentifier] = FALSE;
                        }
                    } else {
                        $content[$optionIdentifier] = FALSE;
                    }
                    break;
                default :
                    break;
            }
        }
        if ( !isset( $content['type'] ) ) {
            $content['type'] = 2;
        }
        $attribute->setContent( $content );
    }

    protected static function getSelectionMethods() {
        return array(
            0 => 'Browse',
            1 => 'Drop-down list',
            2 => 'List with radio buttons',
            3 => 'List with checkboxes',
            4 => 'Multiple selection list',
            5 => 'Template based, multi',
            6 => 'Template based, single',
            7 => 'Drop-down list display the parent folder'
        );
    }

    protected static function getReverseSelectionMethods() {
        return array_flip( self::getSelectionMethods() );
    }

    protected static function getSelectionTypes() {
        return array(
            0 => 'New and existing objects',
            1 => 'Only new objects',
            2 => 'Only existing objects'
        );
    }

    protected static function getReverseSelectionTypes() {
        return array_flip( self::getSelectionTypes() );
    }

}
