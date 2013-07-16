<?php

class OWMigrationContentClass extends OWMigrationBase {

    protected $classIdentifier;
    protected $contentClassObject;
    protected $adjustAttributesPlacement = FALSE;

    public function startMigrationOn( $param ) {
        $this->classIdentifier = $param;
        $this->contentClassObject = eZContentClass::fetchByIdentifier( $this->classIdentifier );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Start migration of content class '$this->classIdentifier'." );
    }

    public function end( ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            $this->db->begin( );
            $this->contentClassObject->store( );
            $this->contentClassObject->sync( );
            $this->db->commit( );
            $currentClassGroup = $this->contentClassObject->attribute( 'ingroup_list' );
            if( empty( $currentClassGroup ) ) {
                $this->addToContentClassGroup( 'Content' );
            }
            $this->storeAttributesAndAdjustPlacements( );
        }
        $this->classIdentifier = NULL;
        $this->contentClassObject = NULL;
        $this->adjustAttributesPlacement = FALSE;
    }

    public function createIfNotExists( ) {
        $trans = eZCharTransform::instance( );
        if( $this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Content class '$this->classIdentifier' exists, nothing to do." );
            return;
        }
        $user = eZUser::currentUser( );
        $userID = $user->attribute( 'contentobject_id' );
        $this->isNew = FALSE;
        $this->contentClassObject = eZContentClass::create( $userID, array(
            'version' => eZContentClass::VERSION_STATUS_DEFINED,
            'create_lang_if_not_exist' => TRUE,
            'identifier' => $this->classIdentifier
        ) );
        $this->contentClassObject->setName( $trans->transformByGroup( $this->classIdentifier, 'humanize' ) );
        $this->db->begin( );
        $this->contentClassObject->store( );
        $this->db->commit( );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Content class '$this->classIdentifier' created." );

    }

    public function createFrom( $classIdentifier ) {
        $trans = eZCharTransform::instance( );
        if( $this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Content class '$this->classIdentifier' exists, nothing to do." );
            return;
        }
        $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, 0 );
        if( !$class ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class '$classIdentifier' not found." );
            return;
        }
        $this->contentClassObject = clone $class;
        $this->contentClassObject->initializeCopy( $class );
        $this->contentClassObject->setAttribute( 'version', eZContentClass::VERSION_STATUS_DEFINED );
        $this->contentClassObject->setAttribute( 'identifier', $this->classIdentifier );

        $nameList = $this->contentClassObject->languages( );
        foreach( $nameList as $language => $value ) {
            $nameList[$language] = $trans->transformByGroup( $this->classIdentifier, 'humanize' );
        }
        $classAttributeNameList = new eZContentClassNameList( serialize( $nameList ) );
        $classAttributeNameList->validate( );
        $this->contentClassObject->NameList = $classAttributeNameList;
        $this->contentClassObject->store( );
        $classAttributeCopies = array( );
        $classAttributes = $class->fetchAttributes( );
        foreach( array_keys( $classAttributes ) as $classAttributeKey ) {
            $classAttribute = &$classAttributes[$classAttributeKey];
            $classAttributeCopy = clone $classAttribute;

            if( $datatype = $classAttributeCopy->dataType( ) )//avoiding fatal error if datatype not exist (was removed).
            {
                $datatype->cloneClassAttribute( $classAttribute, $classAttributeCopy );
            } else {
                continue;
            }

            $classAttributeCopy->setAttribute( 'contentclass_id', $this->contentClassObject->attribute( 'id' ) );
            $classAttributeCopy->setAttribute( 'version', eZContentClass::VERSION_STATUS_DEFINED );
            $classAttributeCopy->store( );
            $classAttributeCopies[] = &$classAttributeCopy;
            unset( $classAttributeCopy );
        }
    }

    public function addToContentClassGroup( $classGroupName ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup ) {
            $classGroup = eZContentClassGroup::create( );
            $classGroup->setAttribute( 'name', $classGroupName );
            $this->db->begin( );
            $classGroup->store( );
            $this->db->commit( );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Group '$classGroupName' not found, create group." );
        }
        $this->db->begin( );
        $classGroup->appendClass( $this->contentClassObject );
        $this->db->commit( );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Class added in '$classGroupName' group." );
    }

    public function removeFromContentClassGroup( $classGroupName ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( $classGroup ) {
            $this->db->begin( );
            eZContentClassClassGroup::removeGroup( $this->contentClassObject->attribute( 'id' ), null, $classGroup->attribute( 'id' ) );
            $this->db->commit( );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Class removed from group '$classGroupName'." );
        } else {
            OWMigrationLogger::logWarning( __FUNCTION__ . " - Group '$classGroupName' not found." );
        }
    }

    public function getAttributes( ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            return $this->contentClassObject->fetchAttributes( );
        }
        OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
    }

    public function hasAttribute( $identifier ) {
        if( $this->getAttribute( $identifier ) ) {
            return TRUE;
        }
        return FALSE;
    }

    public function getAttribute( $identifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $attribute = $this->contentClassObject->fetchAttributeByIdentifier( $identifier );
        if( $attribute instanceof eZContentClassAttribute ) {
            return $attribute;
        }
        return;
    }

    public function addAttribute( $classAttributeIdentifier, $params = array() ) {
        $trans = eZCharTransform::instance( );
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        if( $this->hasAttribute( $classAttributeIdentifier ) ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' already exists." );
            return false;
        }

        $classID = $this->contentClassObject->attribute( 'id' );

        $datatype = isset( $params['data_type_string'] ) ? $params['data_type_string'] : 'ezstring';
        $defaultValue = isset( $params['default_value'] ) ? $params['default_value'] : FALSE;
        $canTranslate = isset( $params['can_translate'] ) ? $params['can_translate'] : TRUE;
        $isRequired = isset( $params['is_required'] ) ? $params['is_required'] : FALSE;
        $isSearchable = isset( $params['is_searchable'] ) ? $params['is_searchable'] : TRUE;
        $isCollector = isset( $params['is_information_collector'] ) ? $params['is_information_collector'] : FALSE;
        $attrContent = isset( $params['content'] ) ? $params['content'] : FALSE;
        $attrNode = isset( $params['attribute-node'] ) ? $params['attribute-node'] : array( );
        $datatypeParameter = isset( $params['datatype-parameter'] ) ? $params['datatype-parameter'] : array( );

        $attrCreateInfo = array(
            'identifier' => $classAttributeIdentifier,
            'can_translate' => $canTranslate,
            'is_required' => $isRequired,
            'is_searchable' => $isSearchable,
            'is_information_collector' => $isCollector
        );
        if( !isset( $params['name'] ) ) {
            $attrCreateInfo['name'] = $trans->transformByGroup( $this->classIdentifier, 'humanize' );
        } elseif( is_string( $params['name'] ) ) {
            $attrCreateInfo['name'] = $params['name'];
        } elseif( is_array( $params['name'] ) ) {
            $classAttributeNameNameList = new eZContentClassAttributeNameList( serialize( $params['name'] ) );
            $classAttributeNameNameList->validate( );
        }

        if( isset( $params['description'] ) ) {
            if( is_string( $params['description'] ) ) {
                $attrCreateInfo['description'] = $params['description'];
            } elseif( is_array( $params['description'] ) ) {
                $classAttributeDescriptionNameList = new eZContentClassAttributeNameList( serialize( $params['description'] ) );
                $classAttributeDescriptionNameList->validate( );
            }
        }
        $this->db->begin( );
        $newAttribute = eZContentClassAttribute::create( $classID, $datatype, $attrCreateInfo );
        $this->db->commit( );

        if( isset( $classAttributeNameNameList ) ) {
            $newAttribute->NameList = $classAttributeNameNameList;
        }

        if( isset( $classAttributeDescriptionNameList ) ) {
            $newAttribute->DescriptionList = $classAttributeDescriptionNameList;
        }

        foreach( $params as $field => $value ) {
            if( !in_array( $field, array_keys( $attrCreateInfo ) ) && $field != 'name' && $field != 'description' ) {
                $newAttribute->setAttribute( $field, $value );
            }
        }

        $dataType = $newAttribute->dataType( );
        if( !$dataType ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Unknown datatype: '$datatype'" );
            return false;
        }
        $this->db->begin( );
        $dataType->initializeClassAttribute( $newAttribute );
        $newAttribute->store( );
        $this->db->commit( );

        if( $attrContent )
            $newAttribute->setContent( $attrContent );

        // store attribute, update placement, etc...
        $attributes = $this->contentClassObject->fetchAttributes( );
        $attributes[] = $newAttribute;

        // remove temporary version
        if( $newAttribute->attribute( 'id' ) !== null ) {
            $this->db->begin( );
            $newAttribute->remove( );
            $this->db->commit( );
        }

        $newAttribute->setAttribute( 'version', $this->version );
        $placement = isset( $params['placement'] ) ? intval( $params['placement'] ) : count( $attributes );
        $newAttribute->setAttribute( 'placement', $placement );

        $this->db->begin( );
        $newAttribute->storeDefined( );
        $this->db->commit( );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' added." );
        $newAttribute->initializeObjectAttributes( );
        if( isset( $params['placement'] ) ) {
            $this->storeAttributesAndAdjustPlacements( TRUE );
            $this->adjustAttributesPlacement = TRUE;
        }
        return $newAttribute;
    }

    public function updateAttribute( $classAttributeIdentifier, $params = array() ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $classAttribute = $this->contentClassObject->fetchAttributeByIdentifier( $classAttributeIdentifier );
        if( $classAttribute ) {
            foreach( $params as $field => $value ) {
                switch( $field ) {
                    case 'data_type_string' :
                        if( $classAttribute->attribute( 'data_type_string' ) != $params['data_type_string'] ) {
                            OWMigrationLogger::logError( __FUNCTION__ . " - Datatype conversion not possible: '" . $params['data_type_string'] . "'" );
                            return;
                        }
                        break;
                    case 'name' :
                        if( is_string( $value ) ) {
                            $classAttribute->setName( $value );
                        } elseif( is_array( $value ) ) {
                            $nameList = new eZContentClassAttributeNameList( serialize( $value ) );
                            $nameList->validate( );
                            $classAttribute->NameList = $nameList;
                        }
                        break;
                    case 'description' :
                        if( is_string( $value ) ) {
                            $classAttribute->setDescription( $value );
                        } elseif( is_array( $value ) ) {
                            $nameList = new eZContentClassAttributeNameList( serialize( $value ) );
                            $nameList->validate( );
                            $classAttribute->DescriptionList = $nameList;
                        }
                        break;
                    case 'placement' :
                        $classAttribute->setAttribute( 'placement', $value );
                        break;
                    case 'content' :
                        $content = $classAttribute->content( );
                        $classAttribute->setContent( array_merge( $content, $value ) );
                        break;
                    default :
                        $classAttribute->setAttribute( $field, $value );
                        break;
                }
            }

            $dataType = $classAttribute->dataType( );
            $this->db->begin( );
            $classAttribute->store( );
            $this->db->commit( );
            if( isset( $params['placement'] ) ) {
                $this->storeAttributesAndAdjustPlacements( TRUE );
                $this->adjustAttributesPlacement = TRUE;
            }
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' updated." );
            return $classAttribute;
        } else {
            OWMigrationLogger::logWarning( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' not found." );
            return;
        }
    }

    public function removeAttribute( $classAttributeIdentifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $classAttribute = $this->contentClassObject->fetchAttributeByIdentifier( $classAttributeIdentifier );
        if( $classAttribute ) {
            if( !is_array( $classAttribute ) ) {
                $classAttribute = array( $classAttribute );
            }
            $this->db->begin( );
            $this->contentClassObject->removeAttributes( $classAttribute );
            $this->db->commit( );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' removed." );
        } else {
            OWMigrationLogger::logWarning( __FUNCTION__ . " - Attribute '$classAttributeIdentifier' not found." );
        }
        return;
    }

    protected function storeAttributesAndAdjustPlacements( $force = FALSE ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Content class object not found." );
            return;
        }
        $attributes = $this->contentClassObject->fetchAttributes( );
        if( $force || $this->adjustAttributesPlacement ) {
            $this->contentClassObject->adjustAttributePlacements( $attributes );
        }
        foreach( $attributes as $attribute ) {
            $this->db->begin( );
            $attribute->store( );
            $this->db->commit( );
        }
        return;
    }

    public function removeClass( ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWMigrationLogger::logWarning( __FUNCTION__ . " - Content class '$this->classIdentifier' not found." );
            return;
        }
        $ClassName = $this->contentClassObject->attribute( 'name' );
        $ClassID = $this->contentClassObject->attribute( 'id' );

        if( !$this->contentClassObject->isRemovable( ) ) {
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Content class '$this->classIdentifier' cannot be removed." );
        } else {
            $classObjects = eZContentObject::fetchSameClassList( $ClassID );
            $ClassObjectsCount = count( $classObjects );
            if( $ClassObjectsCount == 0 ) {
                $ClassObjectsCount .= " object";
            } else {
                $ClassObjectsCount .= " objects";
            }
            eZContentClassOperations::remove( $ClassID );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - $ClassObjectsCount objects removed." );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Content class '$this->classIdentifier' removed." );
            $this->classIdentifier = NULL;
            $this->contentClassObject = NULL;
            $this->adjustAttributesPlacement = FALSE;
        }

    }

    public function __set( $name, $value ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            if( $this->contentClassObject->hasAttribute( $name ) ) {
                switch( $name) {
                    case 'name' :
                        if( is_string( $value ) ) {
                            $this->contentClassObject->setAttribute( 'name', $value );
                        } elseif( is_array( $value ) ) {
                            $classAttributeNameList = new eZContentClassNameList( serialize( $value ) );
                            $classAttributeNameList->validate( );
                            $this->contentClassObject->NameList = $classAttributeNameList;
                        }
                        break;
                    case 'description' :
                        if( is_string( $value ) ) {
                            $this->contentClassObject->setAttribute( 'description', $value );
                        } elseif( is_array( $value ) ) {
                            $classAttributeDescriptionList = new eZContentClassNameList( serialize( $value ) );
                            $classAttributeDescriptionList->validate( );
                            $this->contentClassObject->DescriptionList = $classAttributeDescriptionList;
                        }
                        break;
                    case 'identifier' :
                        if( $this->contentClassObject->attribute( 'identifier' ) != $value ) {
                            $duplicateContentClass = eZContentClass::fetchByIdentifier( $value );
                            if( $duplicateContentClass instanceof eZContentClass ) {
                                OWMigrationLogger::logError( __FUNCTION__ . " - A content class with the idenfier '$value' already exists." );
                            } else {
                                $this->contentClassObject->setAttribute( $name, $value );
                            }
                        }
                        break;
                    default :
                        $this->contentClassObject->setAttribute( $name, $value );
                        break;
                }
            } else {
                throw new OWMigrationContentClassException( __FUNCTION__ . " - Attribute $name not found" );
            }
        }
    }

    public function __get( $name ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            if( $this->contentClassObject->hasAttribute( $name ) ) {
                return $this->contentClassObject->attribute( $name );
            } else {
                throw new OWMigrationContentClassException( __FUNCTION__ . " - Attribute $name not found." );
            }
        }
    }

    public function __isset( $name ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            if( $this->contentClassObject->hasAttribute( $name ) ) {
                TRUE;
            } else {
                FALSE;
            }
        }
    }

}
