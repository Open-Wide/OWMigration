<?php

class eZMultiplexerTypeMigrationHandler extends DefaultEventTypeMigrationHandler {

    const SELECTED_SECTIONS = "data_text1";
    const SELECTED_USERGROUPS = "data_text2";
    const SELECTED_CLASSES = "data_text5";
    const SELECTED_WORKFLOW = "data_int1";
    const LANGUAGE_LIST = "data_int2";
    const VERSION_OPTION = "data_int3";

    static public function toArray( eZWorkflowEvent $event ) {
        $eventType = $event->attribute( 'workflow_type' );
        if( !$eventType instanceof eZMultiplexerType ) {
            throw new InvalidArgumentException( );
        }
        $attributesArray = array( );
        foreach( $eventType->typeFunctionalAttributes( ) as $attributeIdentifier ) {
            $attribute = $eventType->attributeDecoder( $event, $attributeIdentifier );
            unset( $IDList );
            switch ($attributeIdentifier) {
                case 'selected_sections' :
                    foreach( $attribute as $ID ) {
                        if( $ID != '-1' ) {
                            $object = eZSection::fetch( $ID );
                            if( $object instanceof eZSection ) {
                                if( $object->hasAttribute( 'identifier' ) ) {
                                    $IDList[] = $object->attribute( 'identifier' );
                                } else {
                                    $IDList[] = $object->attribute( 'name' );
                                }
                            }
                        }
                    }
                    break;
                case 'selected_classes' :
                    foreach( $attribute as $ID ) {
                        if( $ID != '-1' ) {
                            $object = eZContentClass::fetch( $ID );
                            if( $object instanceof eZContentClass ) {
                                $IDList[] = $object->attribute( 'identifier' );
                            }
                        }
                    }
                    break;
                case 'selected_usergroups' :
                    foreach( $attribute as $ID ) {
                        if( $ID != '-1' ) {
                            $object = eZContentObject::fetch( $ID );
                            if( $object instanceof eZContentObject ) {
                                $object = $object->attribute( 'main_node' );
                                if( $object instanceof eZContentObjectTreeNode ) {
                                    $IDList[] = $object->attribute( 'path_identification_string' );
                                }
                            }
                        }
                    }
                    break;
                case 'selected_workflow' :
                    $object = eZWorkflow::fetch( $attribute );
                    if( $object instanceof eZWorkflow ) {
                        $IDList = $object->attribute( 'name' );
                    }
                    break;
                case 'language_list' :
                    $attributeValue = $event->attribute( self::LANGUAGE_LIST );
                    if( $attributeValue != 0 ) {
                        $IDList = array( );
                        $languages = eZContentLanguage::languagesByMask( $attributeValue );
                        foreach( $languages as $language ) {
                            $IDList[$language->attribute( 'id' )] = $language->attribute( 'locale' );
                        }
                    }
                    break;
                case 'version_option' :
                    switch ($attribute) {
                        case eZMultiplexerType::VERSION_OPTION_FIRST_ONLY :
                            $IDList = "first_only";
                            break;
                        case eZMultiplexerType::VERSION_OPTION_EXCEPT_FIRST :
                            $IDList = "first_only";
                            break;
                        case eZMultiplexerType::VERSION_OPTION_ALL :
                            $IDList = "all";
                            break;
                        default :
                            break;
                    }
                    break;

                default :
                    if( !empty( $attribute ) ) {
                        $IDList = $attribute;
                    }
                    break;
            }
            if( isset( $IDList ) ) {
                $attributesArray[$attributeIdentifier] = $IDList;
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZWorkflowEvent $event, array $options ) {
        $eventType = $event->attribute( 'workflow_type' );
        if( !$eventType instanceof eZMultiplexerType ) {
            throw new InvalidArgumentException( );
        }
        foreach( $options as $optionsIdentifier => $optionsValue ) {
            if( $event->hasAttribute( $optionsIdentifier ) ) {
                switch ($optionsIdentifier) {
                    case 'selected_sections' :
                        if( is_array( $optionsValue ) ) {
                            foreach( $optionsValue as $index => $option ) {
                                $object = OWMigrationTools::findOrCreateSection( $option );
                                if( $object instanceof eZSection ) {
                                    $optionsValue[$index] = $object->attribute( 'id' );
                                }
                            }
                            $optionsValue = implode( ',', $optionsValue );
                        } else {
                            $optionsValue = '';
                        }
                        $event->setAttribute( self::SELECTED_SECTIONS, $optionsValue );
                        break;
                    case 'selected_classes' :
                        if( is_array( $optionsValue ) ) {
                            foreach( $optionsValue as $index => $option ) {
                                $object = eZContentClass::fetchByIdentifier( $option );
                                if( $object instanceof eZContentClass ) {
                                    $optionsValue[$index] = $object->attribute( 'id' );
                                }
                            }
                            $optionsValue = implode( ',', $optionsValue );
                        } else {
                            $optionsValue = '';
                        }
                        $event->setAttribute( self::SELECTED_CLASSES, $optionsValue );
                        break;
                    case 'selected_usergroups' :
                        if( is_array( $optionsValue ) ) {
                            foreach( $optionsValue as $index => $option ) {
                                $object = OWMigrationTools::findNode( $option );
                                if( $object instanceof eZContentObjectTreeNode ) {
                                    $optionsValue[$index] = $object->attribute( 'contentobject_id' );
                                }
                            }
                            $optionsValue = implode( ',', $optionsValue );
                        } else {
                            $optionsValue = '';
                        }
                        $event->setAttribute( self::SELECTED_USERGROUPS, $optionsValue );
                        break;
                    case 'selected_workflow' :
                        $object = OWMigrationTools::findOrCreateWorkflow( $optionsValue );
                        if( $object instanceof eZWorkflow ) {
                            $optionsValue = $object->attribute( 'id' );
                        }
                        $event->setAttribute( self::SELECTED_WORKFLOW, $optionsValue );
                        break;
                    case 'language_list' :
                        if( is_array( $optionsValue ) ) {
                            $optionsValue = eZContentLanguage::maskByLocale( $optionsValue );
                        } else {
                            $optionsValue = '';
                        }
                        $event->setAttribute( self::LANGUAGE_LIST, $optionsValue );
                        break;
                    case 'version_option' :
                        switch ($optionsValue) {
                            case "first_only" :
                                $optionsValue = eZMultiplexerType::VERSION_OPTION_FIRST_ONLY;
                                break;
                            case "first_only" :
                                $optionsValue = eZMultiplexerType::VERSION_OPTION_EXCEPT_FIRST;
                                break;
                            case "all" :
                                $optionsValue = eZMultiplexerType::VERSION_OPTION_ALL;
                                break;
                            default :
                                $optionsValue = eZMultiplexerType::VERSION_OPTION_ALL;
                                break;
                        }
                        $event->setAttribute( self::VERSION_OPTION, $optionsValue );
                        break;
                    default :
                        $event->setAttribute( $optionsIdentifier, $optionsValue );
                }
            }
        }
    }

}
?>