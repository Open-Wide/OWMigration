<?php

class OWMigrationContentClass {

    protected $classIdentifier;
    protected $contentClassObject;
    protected $isNew = TRUE;
    protected $userID;
    protected $output;
    protected $adjustAttributesPlacement = FALSE;

    public function __construct( $classIdentifier ) {
        $this->output = eZCLI::instance( );
        $this->classIdentifier = $classIdentifier;
        $user = eZUser::currentUser( );
        $this->userID = $user->attribute( 'contentobject_id' );
        $this->contentClassObject = eZContentClass::fetchByIdentifier( $classIdentifier );
        if( !$this->contentClassObject instanceof eZContentClass ) {
            $this->output->notice( "Content class '$classIdentifier' not found -> create new content class." );
            $this->isNew = FALSE;
            $this->contentClassObject = eZContentClass::create( $this->userID, array(
                'version' => eZContentClass::VERSION_STATUS_DEFINED,
                'create_lang_if_not_exist' => true,
                'identifier' => $classIdentifier
            ) );
            $this->contentClassObject->setName( sfInflector::humanize( $classIdentifier ) );
            $this->contentClassObject->store( );
        } else {
            $this->output->notice( "Content class '$classIdentifier' found -> create new version." );
        }
    }

    public function addToContentClassGroup( $classGroupName ) {
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( !$classGroup ) {
            $classGroup = eZContentClassGroup::create( );
            $classGroup->setAttribute( 'name', $classGroupName );
            $classGroup->store( );
        }
        $classGroup->appendClass( $this->contentClassObject );
        $this->output->notice( "Add to content class group : class added in '$classGroupName' group." );
    }

    public function removeFromContentClassGroup( $classGroupName ) {
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if( $classGroup ) {
            eZContentClassClassGroup::removeGroup( $this->contentClassObject->attribute( 'id' ), null, $classGroup->attribute( 'id' ) );
            $this->output->notice( "Remove from content class : class removed from group '$classGroupName'.", TRUE );
        } else {
            $this->output->warning( "Remove from content class : group '$classGroupName' not found." );
        }
    }

    public function getAttributes( ) {
        return $this->contentClassObject->fetchAttributes( );
    }

    public function hasAttribute( $identifier ) {
        try {
            $this->getAttribute( $identifier );
            return TRUE;
        } catch ( OWMigrationContentClassException $e ) {
            return FALSE;
        }
    }

    public function getAttribute( $identifier ) {
        $attribute = $this->contentClassObject->fetchAttributeByIdentifier( $identifier );
        if( !$attribute instanceof eZContentClassAttribute ) {
            throw new OWMigrationContentClassException( "Attribute $identifier not found." );
        }
        return $attribute;
    }

    public function addAttribute( $classAttributeIdentifier, $params = array() ) {
        if( $this->hasAttribute( $classAttributeIdentifier ) ) {
            $this->output->error( "Add attribute : attribute $classAttributeIdentifier already exists." );
            return false;
        }

        $classID = $this->contentClassObject->attribute( 'id' );

        $datatype = isset( $params['data_type_string'] ) ? $params['data_type_string'] : 'ezstring';
        $defaultValue = isset( $params['default_value'] ) ? $params['default_value'] : false;
        $canTranslate = isset( $params['can_translate'] ) ? $params['can_translate'] : 0;
        $isRequired = isset( $params['is_required'] ) ? $params['is_required'] : 0;
        $isSearchable = isset( $params['is_searchable'] ) ? $params['is_searchable'] : 0;
        $isCollector = isset( $params['is_information_collector'] ) ? $params['is_information_collector'] : false;
        $attrContent = isset( $params['content'] ) ? $params['content'] : false;
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

        $newAttribute = eZContentClassAttribute::create( $classID, $datatype, $attrCreateInfo );

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
        $dataType->initializeClassAttribute( $newAttribute );
        $newAttribute->store( );

        if( $attrContent )
            $newAttribute->setContent( $attrContent );

        // store attribute, update placement, etc...
        $attributes = $this->contentClassObject->fetchAttributes( );
        $attributes[] = $newAttribute;

        // remove temporary version
        if( $newAttribute->attribute( 'id' ) !== null ) {
            $newAttribute->remove( );
        }

        $newAttribute->setAttribute( 'version', $this->version );
        $placement = isset( $params['placement'] ) ? intval( $params['placement'] ) : count( $attributes );
        $newAttribute->setAttribute( 'placement', $placement );

        $this->adjustAttributesPlacement = true;

        $newAttribute->storeDefined( );
        $this->output->notice( "Add attribute : attribute '$classAttributeIdentifier' added." );
        return $newAttribute;
    }

    public function updateAttribute( $classAttributeIdentifier, $params = array() ) {
        $classAttribute = $this->contentClassObject->fetchAttributeByIdentifier( $classAttributeIdentifier );
        if( $classAttribute ) {
            foreach( $params as $field => $value ) {
                switch( $field ) {
                    case 'data_type_string' :
                        if( $classAttribute->attribute( 'data_type_string' ) != $params['data_type_string'] ) {
                            $this->output->warning( "\t\tDatatype conversion not possible: '" . $params['data_type_string'] . "'", 'error' );

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
                        $this->adjustAttributesPlacement = true;
                        break;
                    default :
                        $classAttribute->setAttribute( $field, $value );
                        break;
                }
            }

            $dataType = $classAttribute->dataType( );
            $classAttribute->store( );
            $this->output->notice( "Update attribute : attribute '$classAttributeIdentifier' updated." );
            return $classAttribute;
        } else {
            $this->output->warning( "Update attribute : attribute '$classAttributeIdentifier' not found." );
        }
    }

    public function removeAttribute( $classAttributeIdentifier ) {
        $classAttribute = $this->contentClassObject->fetchAttributeByIdentifier( $classAttributeIdentifier );
        if( $classAttribute ) {
            if( !is_array( $classAttribute ) ) {
                $classAttribute = array( $classAttribute );
            }
            $this->contentClassObject->removeAttributes( $classAttribute );
            $this->output->notice( "Remove attribute : attribute '$classAttributeIdentifier' removed." );
        } else {
            $this->output->warning( "Remove attribute : attribute '$classAttributeIdentifier' not found." );
        }

    }

    protected function storeAttributesAndAdjustPlacements( ) {
        $attributes = $this->contentClassObject->fetchAttributes( );
        if( $this->adjustAttributesPlacement ) {
            $this->contentClassObject->adjustAttributePlacements( $attributes );
        }
        foreach( $attributes as $attribute ) {
            $attribute->store( );
        }
    }

    public function save( ) {
        $this->contentClassObject->store( );
        $this->contentClassObject->sync( );
        $currentClassGroup = $this->contentClassObject->attribute( 'ingroup_list' );
        if( empty( $currentClassGroup ) ) {
            $this->addToContentClassGroup( 'Content' );
        }
        $this->storeAttributesAndAdjustPlacements( );

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
                            $classAttributeDescriptionList = new eZContentObjectNameList( serialize( $value ) );
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

    static function removeContentClass( $classIdentifier ) {
        $class = eZContentClass::fetchByIdentifier( $classIdentifier );
        $output = eZCLI::instance( );
        if( $class ) {
            $ClassName = $class->attribute( 'name' );
            $ClassID = $class->attribute( 'id' );
            $classObjects = eZContentObject::fetchSameClassList( $ClassID );
            $ClassObjectsCount = count( $classObjects );
            if( $ClassObjectsCount == 0 ) {
                $ClassObjectsCount .= " object";
            } else {
                $ClassObjectsCount .= " objects";
            }
            $class->remove( true );
            eZContentClassClassGroup::removeClassMembers( $ClassID, 0 );
            $output->notice( "Remove content class : $ClassObjectsCount removed.", TRUE );
            $output->notice( "Remove content class : content class '$classIdentifier' removed.", TRUE );
        } else {
            $output->warning( "Remove content class : content class '$classIdentifier' not found.", TRUE );
        }

    }

}
