<?php

class OWMigrationContentClass extends OWMigrationBase {

    protected $classIdentifier;
    protected $contentClassObject;
    protected $contentClassAttributes = array();

    public function startMigrationOn( $param ) {
        $this->classIdentifier = $param;
        $this->contentClassObject = eZContentClass::fetchByIdentifier( $this->classIdentifier );
        if ( $this->contentClassObject instanceof eZContentClass ) {
            $this->contentClassAttributes = $this->contentClassObject->fetchAttributes();
        }
        OWScriptLogger::logNotice( "Content class '$this->classIdentifier'.", 'start_migration', true );
    }

    public function end() {
        if ( $this->contentClassObject instanceof eZContentClass ) {
            $this->db->begin();
            $this->contentClassObject->store();
            $this->contentClassObject->sync();
            $this->db->commit();
            $currentClassGroup = $this->contentClassObject->attribute( 'ingroup_list' );
            if ( empty( $currentClassGroup ) ) {
                $this->addToContentClassGroup( 'Content' );
            }
            $this->adjustPlacementsAndStoreAttributes();
        }
        $this->classIdentifier = NULL;
        $this->contentClassObject = NULL;
        $this->contentClassAttributes = array();
    }

    public function createIfNotExists() {
        $trans = eZCharTransform::instance();
        if ( $this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $user = eZUser::currentUser();
        $userID = $user->attribute( 'contentobject_id' );
        $this->isNew = FALSE;
        $languageLocale = eZContentLanguage::topPriorityLanguage() !== false ? eZContentLanguage::topPriorityLanguage()->attribute( 'locale' ) : false;
        $this->contentClassObject = eZContentClass::create( $userID, array(
                'version' => eZContentClass::VERSION_STATUS_DEFINED,
                'create_lang_if_not_exist' => TRUE,
                'identifier' => $this->classIdentifier,
                'name' => $trans->transformByGroup( $this->classIdentifier, 'humanize' )
                ), $languageLocale );
        $this->db->begin();
        $this->contentClassObject->store();
        $this->db->commit();
        OWScriptLogger::logNotice( "Content class '$this->classIdentifier' created.", __FUNCTION__ );
    }

    public function createFrom( $classIdentifier ) {
        $trans = eZCharTransform::instance();
        if ( $this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, 0 );
        if ( !$class ) {
            OWScriptLogger::logError( "Content class '$classIdentifier' not found.", __FUNCTION__ );
            return;
        }
        $this->contentClassObject = clone $class;
        $this->contentClassObject->initializeCopy( $class );
        $this->contentClassObject->setAttribute( 'version', eZContentClass::VERSION_STATUS_DEFINED );
        $this->contentClassObject->setAttribute( 'identifier', $this->classIdentifier );

        $nameList = $this->contentClassObject->languages();
        foreach ( $nameList as $language => $value ) {
            $nameList[$language] = $trans->transformByGroup( $this->classIdentifier, 'humanize' );
        }
        $classAttributeNameList = new eZContentClassNameList( serialize( $nameList ) );
        $classAttributeNameList->validate();
        $this->contentClassObject->NameList = $classAttributeNameList;
        $this->contentClassObject->store();
        $classAttributeCopies = array();
        $classAttributes = $class->fetchAttributes();
        foreach ( array_keys( $classAttributes ) as $classAttributeKey ) {
            $classAttribute = &$classAttributes[$classAttributeKey];
            $classAttributeCopy = clone $classAttribute;

            if ( $datatype = $classAttributeCopy->dataType() ) {//avoiding fatal error if datatype not exist (was removed).
                $datatype->cloneClassAttribute( $classAttribute, $classAttributeCopy );
            } else {
                continue;
            }

            $classAttributeCopy->setAttribute( 'contentclass_id', $this->contentClassObject->attribute( 'id' ) );
            $classAttributeCopy->setAttribute( 'version', eZContentClass::VERSION_STATUS_DEFINED );
            $classAttributeCopy->store();
            $classAttributeCopies[] = &$classAttributeCopy;
            unset( $classAttributeCopy );
        }
        $this->contentClassAttributes = $this->contentClassObject->fetchAttributes();
    }

    public function addToContentClassGroup( $classGroupName ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if ( !$classGroup ) {
            $classGroup = eZContentClassGroup::create();
            $classGroup->setAttribute( 'name', $classGroupName );
            $this->db->begin();
            $classGroup->store();
            $this->db->commit();
            OWScriptLogger::logNotice( "Group '$classGroupName' not found, create group.", __FUNCTION__ );
        }
        $this->db->begin();
        $classGroup->appendClass( $this->contentClassObject );
        $this->db->commit();
        OWScriptLogger::logNotice( "Class added in '$classGroupName' group.", __FUNCTION__ );
    }

    public function removeFromContentClassGroup( $classGroupName ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classGroup = eZContentClassGroup::fetchByName( $classGroupName );
        if ( $classGroup ) {
            $this->db->begin();
            eZContentClassClassGroup::removeGroup( $this->contentClassObject->attribute( 'id' ), null, $classGroup->attribute( 'id' ) );
            $this->db->commit();
            OWScriptLogger::logNotice( "Class removed from group '$classGroupName'.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "Group '$classGroupName' not found.", __FUNCTION__ );
        }
    }

    public function getAttributes() {
        return $this->contentClassAttributes;
        OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
    }

    public function hasAttribute( $identifier ) {
        if ( $this->getAttribute( $identifier ) ) {
            return TRUE;
        }
        return FALSE;
    }

    public function getAttribute( $identifier ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        foreach ( $this->contentClassAttributes as $attribute ) {
            if ( $attribute->attribute( 'identifier' ) == $identifier ) {
                return $attribute;
            }
        }
        return;
    }

    public function addAttribute( $classAttributeIdentifier, $params = array() ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return false;
        }
        if ( $this->hasAttribute( $classAttributeIdentifier ) ) {
            OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' already exists.", __FUNCTION__ );
            return false;
        }

        $classID = $this->contentClassObject->attribute( 'id' );

        $datatype = isset( $params['data_type_string'] ) ? $params['data_type_string'] : 'ezstring';
        $this->db->begin();
        $newAttribute = eZContentClassAttribute::create( $classID, $datatype, array(
                'identifier' => $classAttributeIdentifier,
                'version' => eZContentClass::VERSION_STATUS_DEFINED,
                'placement' => count( $this->contentClassAttributes ) + 1
            ) );
        $this->db->commit();
        $dataType = $newAttribute->dataType();
        if ( !$dataType ) {
            OWScriptLogger::logError( "Unknown datatype: '$datatype'", __FUNCTION__ );
            return false;
        }
        $this->db->begin();
        $dataType->initializeClassAttribute( $newAttribute );
        $newAttribute->store();
        $this->db->commit();

        $contentClassAttributeHandlerClass = get_class( $newAttribute ) . 'MigrationHandler';
        call_user_func( "$contentClassAttributeHandlerClass::fromArray", $newAttribute, $params );

        $datatypeHandlerClass = get_class( $dataType ) . 'MigrationHandler';
        if ( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::fromArray' ) ) {
            $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
        }
        call_user_func( "$datatypeHandlerClass::fromArray", $newAttribute, $params );
        $this->contentClassAttributes[] = $newAttribute;
        $this->adjustPlacementsAndStoreAttributes();

        $this->db->begin();
        $newAttribute->storeDefined();
        $this->db->commit();
        OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' added.", __FUNCTION__ );
        $newAttribute->initializeObjectAttributes();

        return $newAttribute;
    }

    public function updateAttribute( $classAttributeIdentifier, $params = array() ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classAttribute = $this->getAttribute( $classAttributeIdentifier );

        if ( isset( $classAttribute ) ) {
            $contentClassAttributeHandlerClass = get_class( $classAttribute ) . 'MigrationHandler';
            call_user_func( "$contentClassAttributeHandlerClass::fromArray", $classAttribute, $params );
            $dataType = $classAttribute->dataType();
            $datatypeHandlerClass = get_class( $dataType ) . 'MigrationHandler';
            if ( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::toArray' ) ) {
                $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
            }
            call_user_func( "$datatypeHandlerClass::fromArray", $classAttribute, $params );

            $this->db->begin();
            $classAttribute->sync();
            $classAttribute->store();
            $this->db->commit();

            OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' updated.", __FUNCTION__ );
            return $classAttribute;
        } else {
            OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' not found.", __FUNCTION__ );
            return;
        }
    }

    public function removeAttribute( $classAttributeIdentifier, $removableDataTypeString = null ) {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $classAttribute = $this->getAttribute( $classAttributeIdentifier );
        if ( $classAttribute ) {
            if ( $removableDataTypeString && $classAttribute->attribute( 'data_type_string' ) != $removableDataTypeString ) {
                OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' not a $removableDataTypeString.", __FUNCTION__ );
                return;
            }
            foreach ( eZContentObjectAttribute::fetchSameClassAttributeIDList( $classAttribute->attribute( 'id' ) ) as $objectAttribute ) {
                $objectAttribute->removeThis( $objectAttribute->attribute( 'id' ) );
            }
            $classAttribute->datatype()->deleteNotVersionedStoredClassAttribute( $classAttribute );

            if ( !is_array( $classAttribute ) ) {
                $classAttribute = array( $classAttribute );
            }
            $this->db->begin();
            $this->contentClassObject->removeAttributes( $classAttribute );
            $this->db->commit();
            $this->contentClassAttributes = $this->contentClassObject->fetchAttributes();
            $this->adjustPlacementsAndStoreAttributes();
            OWScriptLogger::logNotice( "Attribute '$classAttributeIdentifier' removed.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "Attribute '$classAttributeIdentifier' not found.", __FUNCTION__ );
        }
        return;
    }

    protected function adjustPlacementsAndStoreAttributes() {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logError( "Content class object not found.", __FUNCTION__ );
            return;
        }
        $this->contentClassObject->adjustAttributePlacements( $this->contentClassAttributes );
        foreach ( $this->contentClassAttributes as $attribute ) {
            $this->db->begin();
            $attribute->store();
            $this->db->commit();
        }
        return;
    }

    public function removeClass() {
        if ( !$this->contentClassObject instanceof eZContentClass ) {
            OWScriptLogger::logWarning( "Content class '$this->classIdentifier' not found.", __FUNCTION__ );
            return;
        }
        $ClassName = $this->contentClassObject->attribute( 'name' );
        $ClassID = $this->contentClassObject->attribute( 'id' );

        if ( !$this->contentClassObject->isRemovable() ) {
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' cannot be removed.", __FUNCTION__ );
        } else {
            $classObjects = eZContentObject::fetchSameClassList( $ClassID );
            $ClassObjectsCount = count( $classObjects );
            if ( $ClassObjectsCount == 0 ) {
                $ClassObjectsCount .= " object";
            } else {
                $ClassObjectsCount .= " objects";
            }
            eZContentClassOperations::remove( $ClassID );
            OWScriptLogger::logNotice( "$ClassObjectsCount objects removed.", __FUNCTION__ );
            OWScriptLogger::logNotice( "Content class '$this->classIdentifier' removed.", __FUNCTION__ );
            $this->classIdentifier = NULL;
            $this->contentClassObject = NULL;
            $this->contentClassAttributes = array();
        }
    }

    public function __set( $name, $value ) {
        if ( $this->contentClassObject instanceof eZContentClass ) {
            if ( $this->contentClassObject->hasAttribute( $name ) ) {
                switch ( $name ) {
                    case 'name' :
                        if ( is_string( $value ) ) {
                            $this->contentClassObject->setAttribute( 'name', $value );
                        } elseif ( is_array( $value ) ) {
                            $classAttributeNameList = new eZContentClassNameList( serialize( $value ) );
                            $classAttributeNameList->validate();
                            $this->contentClassObject->NameList = $classAttributeNameList;
                        }
                        break;
                    case 'description' :
                        if ( is_string( $value ) ) {
                            $this->contentClassObject->setAttribute( 'description', $value );
                        } elseif ( is_array( $value ) ) {
                            $classAttributeDescriptionList = new eZContentClassNameList( serialize( $value ) );
                            $classAttributeDescriptionList->validate();
                            $this->contentClassObject->DescriptionList = $classAttributeDescriptionList;
                        }
                        break;
                    case 'identifier' :
                        if ( $this->contentClassObject->attribute( 'identifier' ) != $value ) {
                            $duplicateContentClass = eZContentClass::fetchByIdentifier( $value );
                            if ( $duplicateContentClass instanceof eZContentClass ) {
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
        if ( $this->contentClassObject instanceof eZContentClass ) {
            if ( $this->contentClassObject->hasAttribute( $name ) ) {
                return $this->contentClassObject->attribute( $name );
            } else {
                throw new OWMigrationContentClassException( "Attribute $name not found." );
            }
        }
    }

    public function __isset( $name ) {
        if ( $this->contentClassObject instanceof eZContentClass ) {
            if ( $this->contentClassObject->hasAttribute( $name ) ) {
                TRUE;
            } else {
                FALSE;
            }
        }
    }

}
