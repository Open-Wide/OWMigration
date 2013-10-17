<?php

class eZApproveTypeMigrationHandler implements MigrationHandlerInterface {

    const SELECTED_SECTIONS = "data_text1";
    const SELECTED_USERGROUPS = "data_text2";
    const APPROVE_USERS = "data_text3";
    const APPROVE_GROUPS = "data_text4";
    const LANGUAGE_LIST = "data_int2";
    const VERSION_OPTION = "data_int3";

    static public function toArray( eZWorkflowEvent $event ) {
        $eventType = $event->attribute( 'workflow_type' );
        if( !$eventType instanceof eZApproveType ) {
            throw new InvalidArgumentException( );
        }
        $attributesArray = array( );
        foreach( $eventType->typeFunctionalAttributes( ) as $attributeIdentifier ) {
            $attribute = $eventType->attributeDecoder( $event, $attributeIdentifier );
            $IDList = array( );
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
                case 'approve_users' :
                case 'approve_groups' :
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
                case 'language_list' :
                    $attributeValue = $event->attribute( self::LANGUAGE_LIST );
                    if( $attributeValue != 0 ) {
                        $languages = eZContentLanguage::languagesByMask( $attributeValue );
                        foreach( $languages as $language ) {
                            $IDList[$language->attribute( 'id' )] = $language->attribute( 'locale' );
                        }
                    }
                    break;
                case 'version_option' :
                    switch ($attribute) {
                        case eZApproveType::VERSION_OPTION_FIRST_ONLY :
                            $IDList = "first_only";
                            break;
                        case eZApproveType::VERSION_OPTION_EXCEPT_FIRST :
                            $IDList = "first_only";
                            break;
                        case eZApproveType::VERSION_OPTION_ALL :
                            $IDList = "all";
                            break;
                        default :
                            break;
                    }
                    break;

                default :
                    $IDList = $attribute;
                    break;
            }
            $attributesArray[$attributeIdentifier] = $IDList;
        }
        return $attributesArray;
    }

    static public function fromArray( eZWorkflowEvent $event, array $options ) {
        $eventType = $event->attribute( 'workflow_type' );
        if( !$eventType instanceof eZApproveType ) {
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
                    case 'approve_users' :
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
                        $event->setAttribute( self::APPROVE_USERS, $optionsValue );
                        break;
                    case 'approve_groups' :
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
                        $event->setAttribute( self::APPROVE_GROUPS, $optionsValue );
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
                                $optionsValue = eZApproveType::VERSION_OPTION_FIRST_ONLY;
                                break;
                            case "first_only" :
                                $optionsValue = eZApproveType::VERSION_OPTION_EXCEPT_FIRST;
                                break;
                            case "all" :
                                $optionsValue = eZApproveType::VERSION_OPTION_ALL;
                                break;
                            default :
                                $optionsValue = eZApproveType::VERSION_OPTION_ALL;
                                break;
                        }
                        $event->setAttribute( self::VERSION_OPTION, $optionsValue );
                        break;
                }
            }
        }
    }

}
?>