<?php

class OWMigrationContentClass extends OWMigrationBase {

    protected $classIdentifier;
    protected $contentClassObject;
    protected $adjustAttributesPlacement = FALSE;

    public function startMigrationOn( $param ) {
        $this->classIdentifier = $param;
        $this->contentClassObject = eZContentClass::fetchByIdentifier( $this->classIdentifier );
        $this->output->notice( "Start migration of content class '$this->classIdentifier'." );
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
        if( $this->contentClassObject instanceof eZContentClass ) {
            $this->output->notice( "Create if not exists : content class '$this->classIdentifier' exists, nothing to do." );
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
        $this->contentClassObject->setName( sfInflector::humanize( $this->classIdentifier ) );
        $this->db->begin( );
        $this->contentClassObject->store( );
        $this->db->commit( );
        $this->output->notice( "Create if not exists : content class '$this->classIdentifier' created." );

    }

    public function createFrom( $classIdentifier ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            $this->output->notice( "Create from : content class '$this->classIdentifier' exists, nothing to do." );
            return;
        }
        $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, 0 );
        if( !$class ) {
            $this->output->error( "Create from : content class '$classIdentifier' not found." );
            return;
        }
        $this->contentClassObject = clone $class;
        $this->contentClassObject->initializeCopy( $class );
        $this->contentClassObject->setAttribute( 'version', eZContentClass::VERSION_STATUS_DEFINED );
        $this->contentClassObject->setAttribute( 'identifier', $this->classIdentifier );

        $nameList = $this->contentClassObject->languages( );
        foreach( $nameList as $language => $value ) {
            $nameList[$language] = sfInflector::humanize( $this->classIdentifier );
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
            $this->output->error( "Add to content class group : content class object not found." );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup ) {
            $classGroup = eZContentClassGroup::create( );
            $classGroup->setAttribute( 'name', $classGroupName );
            $this->db->begin( );
            $classGroup->store( );
            $this->db->commit( );
            $this->output->notice( "Add to content class group : group '$classGroupName' not found, create group." );
        }
        $this->db->begin( );
        $classGroup->appendClass( $this->contentClassObject );
        $this->db->commit( );
        $this->output->notice( "Add to content class group : class added in '$classGroupName' group." );
    }

    public function removeFromContentClassGroup( $classGroupName ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Remove from content class : content class object not found." );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( $classGroup ) {
            $this->db->begin( );
            eZContentClassClassGroup::removeGroup( $this->contentClassObject->attribute( 'id' ), null, $classGroup->attribute( 'id' ) );
            $this->db->commit( );
            $this->output->notice( "Remove from content class : class removed from group '$classGroupName'.", TRUE );
        } else {
            $this->output->warning( "Remove from content class : group '$classGroupName' not found." );
        }
    }

    public function getAttributes( ) {
        if( $this->contentClassObject instanceof eZContentClass ) {
            return $this->contentClassObject->fetchAttributes( );
        }
        $this->output->error( "Get attributes : content class object not found." );
    }

    public function hasAttribute( $identifier ) {
        if( $this->getAttribute( $identifier ) ) {
            return TRUE;
        }
        return FALSE;
    }

    public function getAttribute( $identifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Get attribute : content class object not found." );
            return;
        }
        $attribute = $this->contentClassObject->fetchAttributeByIdentifier( $identifier );
        if( $attribute instanceof eZContentClassAttribute ) {
            return $attribute;
        }
        return;
    }

    public function addAttribute( $classAttributeIdentifier, $params = array() ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Add attribute : content class object not found." );
            return;
        }
        if( $this->hasAttribute( $classAttributeIdentifier ) ) {
            $this->output->error( "Add attribute : attribute '$classAttributeIdentifier' already exists." );
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
            $attrCreateInfo['name'] = sfInflector::humanize( $classAttributeIdentifier );
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
            $this->output->error( "Unknown datatype: '$datatype'" );
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

        $this->adjustAttributesPlacement = TRUE;
        $this->db->begin( );
        $newAttribute->storeDefined( );
        $this->db->commit( );
        $this->output->notice( "Add attribute : attribute '$classAttributeIdentifier' added." );
        $newAttribute->initializeObjectAttributes( );
        return $newAttribute;
    }

    public function updateAttribute( $classAttributeIdentifier, $params = array() ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Update attribute : content class object not found." );
            return;
        }
        $classAttribute = $this->contentClassObject->fetchAttributeByIdentifier( $classAttributeIdentifier );
        if( $classAttribute ) {
            foreach( $params as $field => $value ) {
                switch( $field ) {
                    case 'data_type_string' :
                        if( $classAttribute->attribute( 'data_type_string' ) != $params['data_type_string'] ) {
                            $this->output->error( "Datatype conversion not possible: '" . $params['data_type_string'] . "'" );
                            return;
                        }
                        break;
                    case 'name' :
                        if( is_string( $value ) ) {
                            $classAttribute->setAttribute( 'name', $value );
                        } elseif( is_array( $value ) ) {
                            $nameList = new eZContentClassAttributeNameList( serialize( $value ) );
                            $nameList->validate( );
                            $classAttribute->NameList = $nameList;
                        }
                        break;
                    case 'placement' :
                        $classAttribute->setAttribute( 'placement', $value );
                        $this->adjustAttributesPlacement = TRUE;
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
            $this->output->notice( "Update attribute : attribute '$classAttributeIdentifier' updated." );
            return $classAttribute;
        } else {
            $this->output->warning( "Update attribute : attribute '$classAttributeIdentifier' not found." );
            return;
        }
    }

    public function removeAttribute( $classAttributeIdentifier ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Remove attribute : content class object not found." );
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
            $this->output->notice( "Remove attribute : attribute '$classAttributeIdentifier' removed." );
        } else {
            $this->output->warning( "Remove attribute : attribute '$classAttributeIdentifier' not found." );
        }
        return;
    }

    protected function storeAttributesAndAdjustPlacements( ) {
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->error( "Remove attribute : content class object not found." );
            return;
        }
        $attributes = $this->contentClassObject->fetchAttributes( );
        if( $this->adjustAttributesPlacement ) {
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
            $this->output->warning( "Remove content class : content class '$this->classIdentifier' not found." );
            return;
        }
        $ClassName = $this->contentClassObject->attribute( 'name' );
        $ClassID = $this->contentClassObject->attribute( 'id' );
        $classObjects = eZContentObject::fetchSameClassList( $ClassID );
        $ClassObjectsCount = count( $classObjects );
        if( $ClassObjectsCount == 0 ) {
            $ClassObjectsCount .= " object";
        } else {
            $ClassObjectsCount .= " objects";
        }
        $this->db->begin( );
        $this->contentClassObject->remove( TRUE );
        eZContentClassClassGroup::removeClassMembers( $ClassID, 0 );
        $this->db->commit( );
        $this->output->notice( "Remove content class : $ClassObjectsCount removed." );
        $this->output->notice( "Remove content class : content class '$this->classIdentifier' removed." );

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
