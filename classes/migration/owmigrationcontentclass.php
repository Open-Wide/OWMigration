<?php

class OWMigrationContentClass extends OWMigrationBase {

    protected $classIdentifier;
    protected $contentClassObject;

    public function startMigrationOn( $param ) {
        $this->classIdentifier = $param;
        $this->contentClassObject = eZContentClass::fetchByIdentifier( $this->classIdentifier );
        OWScriptLogger::logNotice( "Content class '$this->classIdentifier'.", 'start_migration' );
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
        }
        $this->classIdentifier = NULL;
        $this->contentClassObject = NULL;
    }

    public function createIfNotExists( ) {
        $trans = eZCharTransform::instance( );
        if( $this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' exists, nothing to do.", __FUNCTION__ );
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
        OWScriptLogger::logNotice( "Content class '$this->classIdentifier' created.", __FUNCTION__ );

    }

    public function createFrom( $classIdentifier ) {
        $trans = eZCharTransform::instance( );
        if( $this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, 0 );
        if( !$class ) {
            OWScriptLogger::logError( "Content class '$classIdentifier' not found.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup ) {
            $classGroup = eZContentClassGroup::create( );
            $classGroup->setAttribute( 'name', $classGroupName );
            $this->db->begin( );
            $classGroup->store( );
            $this->db->commit( );
            OWScriptLogger::logNotice( "Group '$classGroupName' not found, create group.", __FUNCTION__ );
        }
        $this->db->begin( );
        $classGroup->appendClass( $this->contentClassObject );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Class added in '$classGroupName' group.", __FUNCTION__ );
    }

    public function removeFromContentClassGroup( $classGroupName ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( $classGroup ) {
            $this->db->begin( );
            eZContentClassClassGroup::removeGroup( $this->contentClassObject->attribute( 'id' ), null, $classGroup->attribute( 'id' ) );
            $this->db->commit( );
            OWScriptLogger::logNotice( "Class removed from group '$classGroupName'.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "Group '$classGroupName' not found.", __FUNCTION__ );
        }
    }

    public function getAttributes( ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            return $this->contentClassObject->fetchAttributes( );
        }
        OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
    }

    public function hasAttribute( $identifier ) {
        if( $this->getAttribute( $identifier ) ) {
            return TRUE;
        }
        return FALSE;
    }

    public function getAttribute( $identifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return false;
        }
        if( $this->hasAttribute( $classAttributeIdentifier ) ) {
            OWScriptLogger::logError( "Attribute '$classAttributeIdentifier' already exists.", __FUNCTION__ );
            return false;
        }

        $classID = $this->contentClassObject->attribute( 'id' );
        $attributes = $this->contentClassObject->fetchAttributes( );

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
            $attrCreateInfo['name'] = $trans->transformByGroup( $classAttributeIdentifier, 'humanize' );
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
            OWScriptLogger::logError( "Unknown datatype: '$datatype'", __FUNCTION__ );
            return false;
        }
        $this->db->begin( );
        $dataType->initializeClassAttribute( $newAttribute );
        $newAttribute->store( );
        $this->db->commit( );

        if( $attrContent ) {
            $newAttribute->setContent( $attrContent );
        }

        $attributes[] = $newAttribute;
        if( isset( $params['placement'] ) ) {
            $newAttribute->setAttribute( 'placement', $params['placement'] );
        } else {
            $newAttribute->setAttribute( 'placement', count( $attributes ) );
        }
        $this->adjustPlacementsAndStoreAttributes( $attributes );
        
        // remove temporary version
        if( $newAttribute->attribute( 'id' ) !== null ) {
            $this->db->begin( );
            $newAttribute->remove( );
            $this->db->commit( );
        }

        $newAttribute->setAttribute( 'version', $this->version );
        $this->db->begin( );
        $newAttribute->storeDefined( );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' added.", __FUNCTION__ );
        $newAttribute->initializeObjectAttributes( );

        return $newAttribute;
    }

    public function updateAttribute( $classAttributeIdentifier, $params = array() ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $attributes = $this->contentClassObject->fetchAttributes( );
        foreach( $attributes as $attribute ) {
            if( $attribute->attribute( 'identifier' ) == $classAttributeIdentifier ) {
                $classAttribute = $attribute;
                continue;
            }
        }
        if( isset( $classAttribute ) ) {
            foreach( $params as $field => $value ) {
                switch( $field ) {
                    case 'data_type_string' :
                        if( $classAttribute->attribute( 'data_type_string' ) != $params['data_type_string'] ) {
                            OWScriptLogger::logError( "Datatype conversion not possible: '" . $params['data_type_string'] . "'", __FUNCTION__ );
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
                        $this->adjustPlacementsAndStoreAttributes( $attributes );
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

            $this->db->begin( );
            $classAttribute->sync( );
            $classAttribute->store( );
            $this->db->commit( );
            OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' updated.", __FUNCTION__ );
            return $classAttribute;
        } else {
            OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' not found.", __FUNCTION__ );
            return;
        }
    }

    public function removeAttribute( $classAttributeIdentifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
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
            // TODO
            OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' removed.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' not found.", __FUNCTION__ );
        }
        return;
    }

    protected function adjustPlacementsAndStoreAttributes( $attributes ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $this->contentClassObject->adjustAttributePlacements( $attributes );
        foreach( $attributes as $attribute ) {
            $this->db->begin( );
            $attribute->store( );
            $this->db->commit( );
        }
        return;
    }

    public function removeClass( ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logWarning( "Content class '$this->classIdentifier' not found.", __FUNCTION__ );
            return;
        }
        $ClassName = $this->contentClassObject->attribute( 'name' );
        $ClassID = $this->contentClassObject->attribute( 'id' );

        if( !$this->contentClassObject->isRemovable( ) ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' cannot be removed.", __FUNCTION__ );
        } else {
            $classObjects = eZContentObject::fetchSameClassList( $ClassID );
            $ClassObjectsCount = count( $classObjects );
            if( $ClassObjectsCount == 0 ) {
                $ClassObjectsCount .= " object";
            } else {
                $ClassObjectsCount .= " objects";
            }
            eZContentClassOperations::remove( $ClassID );
            OWScriptLogger::logNotice( "$ClassObjectsCount objects removed.", __FUNCTION__ );
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' removed.", __FUNCTION__ );
            $this->classIdentifier = NULL;
            $this->contentClassObject = NULL;
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
                                OWScriptLogger::logError( "A content class with the idenfier '$value' already exists.", __FUNCTION__ );
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
                throw new OWMigrationContentClassException( "Attribute $name not found" );
            }
        }
    }

    public function __get( $name ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            if( $this->contentClassObject->hasAttribute( $name ) ) {
                return $this->contentClassObject->attribute( $name );
            } else {
                throw new OWMigrationContentClassException( "Attribute $name not found." );
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
