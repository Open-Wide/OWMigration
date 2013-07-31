<?php

class OWMigrationRole extends OWMigrationBase {

    protected $roleName;
    protected $role;

    public function startMigrationOn( $param ) {
        $this->roleName = $param;
        $role = eZRole::fetchByName( $this->roleName );
        if( $role instanceof eZRole ) {
            $this->role = $role;
        }
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Start migration of role '$this->roleName'." );
    }

    public function end( ) {
        $this->roleName = NULL;
        $this->role = NULL;
    }

    public function createIfNotExists( ) {
        if( $this->role instanceof eZRole ) {
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Role '$this->roleName' exists, nothing to do." );
            return;
        }
        $this->db->begin( );
        $this->role = eZRole::create( $this->roleName );
        $this->role->store( );
        $this->db->commit( );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Role '$this->roleName' created." );
    }

    public function hasPolicy( $module = '*', $function = '*', $limitation = array() ) {
        $limitation = OWMigrationTools::correctLimitationArray( $limitation );
        if( !$this->role instanceof eZRole ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Role object not found." );
            return FALSE;
        }
        foreach( $this->role->policyList() as $policy ) {
            if( $policy->attribute( 'module_name' ) == $module && $policy->attribute( 'function_name' ) == $function ) {
                $policyLimitations = OWMigrationTools::getPolicyLimitationArray( $policy );
                if( OWMigrationTools::compareArray( $policyLimitations, $limitation ) ) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    public function addPolicy( $module = '*', $function = '*', $limitation = array() ) {
        if( !$this->role instanceof eZRole ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Role object not found." );
            return FALSE;
        }
        $messagePart = empty( $limitation ) ? 'without' : 'with';

        if( !$this->hasPolicy( $module, $function, $limitation ) ) {
            $limitation = $this->correctLimitationArray( $limitation );
            $this->db->begin( );
            $this->role->appendPolicy( $module, $function, $limitation );
            $this->role->store( );
            $this->db->commit( );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Policy on $module::$function $messagePart limitation added." );
        } else {
            OWMigrationLogger::logError( __FUNCTION__ . " - Policy on $module::$function $messagePart limitation already exists." );
        }
    }

    public function removePolicies( $module = FALSE, $function = FALSE, $limitation = FALSE ) {
        if( !$this->role instanceof eZRole ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Role object not found." );
            return;
        }
        $this->db->begin( );
        if( $module === FALSE ) {
            $this->role->removePolicies( TRUE );

            OWMigrationLogger::logNotice( __FUNCTION__ . " - All policies deleted." );
        } elseif( $limitation === FALSE ) {
            $this->role->removePolicy( $module, $function );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Policies on $module::$function deleted." );
        } else {
            $policyList = $this->role->policyList( );
            if( is_array( $policyList ) && count( $policyList ) > 0 ) {
                foreach( $policyList as $key => $policy ) {
                    if( is_object( $policy ) ) {
                        if( $policy->attribute( 'module_name' ) == $module && $policy->attribute( 'function_name' ) == $function ) {
                            $accessArray = $policy->accessArray( );
                            if( current( $accessArray[$module][$function] ) == $limitation ) {
                                $policy->removeThis( );
                                unset( $this->role->Policies[$key] );
                                OWMigrationLogger::logNotice( __FUNCTION__ . " - Policies on $module::$function with limitation deleted." );
                            }
                        }
                    }
                }
            }

        }
        $this->role->store( );
        $this->db->commit( );

    }

    public function assignToUser( $user, $limitIdent = NULL, $limitValue = NULL ) {
        $this->assignTo( 'user', $user, $limitIdent, $limitValue );
    }

    public function assignToUserGroup( $group, $limitIdent = NULL, $limitValue = NULL ) {
        $this->assignTo( 'user_group', $group, $limitIdent, $limitValue );
    }

    protected function assignTo( $type, $object, $limitIdent = NULL, $limitValue = NULL ) {
        $trans = eZCharTransform::instance( );
        $messageType = strtolower( $trans->transformByGroup( $type, 'humanize' ) );
        if( !$this->role instanceof eZRole ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Role object not found." );
            return;
        }
        if( is_numeric( $object ) ) {
            $objectID = $object;
        } elseif( is_string( $object ) ) {
            $contentClass = eZContentClass::fetchByIdentifier( $type );
            $contentObject = eZContentObject::fetchFilteredList( array(
                'name' => $object,
                'contentclass_id' => $contentClass->attribute( 'id' )
            ) );
            if( is_array( $contentObject ) && count( $contentObject ) > 0 ) {
                $objectID = $contentObject[0]->attribute( 'id' );
            } else {
                OWMigrationLogger::logError( __FUNCTION__ . " - $messageType '$object' not found." );
                return;
            }
        } elseif( is_array( $object ) ) {
            foreach( $object as $item ) {
                $this->assignTo( $type, $item, $limitIdent, $limitValue );
            }
        } else {
            OWMigrationLogger::logError( __FUNCTION__ . " - Object parameter must be an object ID, a object name or an array or object ID and object name." );
        }

        if( !is_null( $limitIdent ) ) {
            switch( $limitIdent ) {
                case 'Subtree' :
                    if( is_numeric( $limitValue ) ) {
                        $node = eZContentObjectTreeNode::fetch( $limitValue, false, false );
                        if( $node ) {
                            $limitValue = $node['path_string'];
                        } else {
                            OWMigrationLogger::logNotice( __FUNCTION__ . "Node $limitValue not found." );
                            return;
                        }
                    }
                    break;
                case 'Section' :
                    if( is_string( $limitValue ) ) {
                        $section = eZPersistentObject::fetchObject( eZSection::definition( ), null, array( "identifier" => $limitValue ) );
                        if( !$section ) {
                            $section = new eZSection( array(
                                'name' => $limitValue,
                                'identifier' => $limitValue
                            ) );
                            $section->store( );
                            $limitValue = $section->attribute( 'id' );
                            OWMigrationLogger::logNotice( __FUNCTION__ . " - Section '$limitValue' not found => create new section." );
                        }
                        $limitValue = $section->attribute( 'id' );
                    } elseif( !is_numeric( $limitValue ) ) {
                        OWMigrationLogger::logError( __FUNCTION__ . " - Limit value must be a section ID or a section identifer." );
                        return;
                    }
                    break;
                default :
                    OWMigrationLogger::logError( __FUNCTION__ . " - Limit identifier must be equal to 'Subtree' or 'Section'." );
                    return;
            }
        }

        if( isset( $objectID ) ) {
            $this->db->begin( );
            $this->role->assignToUser( $objectID, $limitIdent, $limitValue );
            /* Clean up policy cache */
            eZUser::cleanupCache( );
            // Clear role caches.
            eZRole::expireCache( );
            // Clear all content cache.
            eZContentCacheManager::clearAllContentCache( );
            $this->db->commit( );
            OWMigrationLogger::logNotice( __FUNCTION__ . " - Role assigned to $messageType $object ($objectID)." );
        }
    }

    public function unassignToUser( $user, $limitIdent = NULL, $limitValue = NULL ) {
        $this->unassignTo( 'user', $user, $limitIdent, $limitValue );
    }

    public function unassignToUserGroup( $group, $limitIdent = NULL, $limitValue = NULL ) {
        $this->unassignTo( 'user_group', $user, $limitIdent, $limitValue );
    }

    protected function unassignTo( $type, $object, $limitIdent = NULL, $limitValue = NULL ) {
        $trans = eZCharTransform::instance( );
        $messageType = strtolower( $trans->transformByGroup( $type, 'humanize' ) );
        if( !$this->role instanceof eZRole ) {
            OWMigrationLogger::logError( __FUNCTION__ . " - Role object not found." );
            return;
        }
        if( is_numeric( $object ) ) {
            $objectID = $object;
        } elseif( is_string( $object ) ) {
            $contentClass = eZContentClass::fetchByIdentifier( 'user' );
            $contentObject = eZContentObject::fetchFilteredList( array(
                'name' => $object,
                'contentclass_id' => $contentClass->attribute( 'id' )
            ) );
            if( is_array( $contentObject ) && count( $contentObject ) > 0 ) {
                $objectID = $contentObject[0]->attribute( 'id' );
            } else {
                OWMigrationLogger::logError( __FUNCTION__ . " - $messageType '$object' not found." );
                return;
            }
        } elseif( is_array( $object ) ) {
            foreach( $object as $item ) {
                $this->unassignTo( $type, $item, $limitIdent, $limitValue );
            }
        } else {
            OWMigrationLogger::logError( __FUNCTION__ . " - Object parameter must be an object ID, a object name or an array or object ID and object name." );
        }

        if( !is_null( $limitIdent ) ) {
            switch( $limitIdent ) {
                case 'Subtree' :
                    if( is_numeric( $limitValue ) ) {
                        $node = eZContentObjectTreeNode::fetch( $limitValue, false, false );
                        if( $node ) {
                            $limitValue = $node['path_string'];
                        } else {
                            OWMigrationLogger::logNotice( __FUNCTION__ . " - Node $limitValue not found." );
                            return;
                        }
                    }
                    break;
                case 'Section' :
                    if( is_string( $limitValue ) ) {
                        $section = eZSection::fetchByIdentifier( $limitValue );
                        if( $section ) {
                            $limitValue = $section->attribute( 'id' );
                        } else {
                            OWMigrationLogger::logNotice( __FUNCTION__ . " - Section $limitValue not found." );
                            return;
                        }

                    } elseif( !is_numeric( $limitValue ) ) {
                        OWMigrationLogger::logError( __FUNCTION__ . " - Limit value must be a section ID or a section identifer." );
                        return;
                    }
                    break;
                default :
                    OWMigrationLogger::logError( __FUNCTION__ . " - Limit identifier must be equal to 'subtree' or 'section'." );
                    return;
            }
        } else {
            $limitValue = NULL;
        }
        if( isset( $objectID ) ) {
            foreach( $this->role->fetchUserByRole( ) as $userRole ) {
                if( $userRole['user_object']->attribute( 'id' ) == $objectID && strcasecmp( $userRole['limit_ident'], $limitIdent ) == 0 && strcasecmp( $userRole['limit_value'], $limitValue ) == 0 ) {
                    $this->db->begin( );
                    $this->role->removeUserAssignmentByID( $userRole['user_role_id'] );
                    /* Clean up policy cache */
                    eZUser::cleanupCache( );
                    // Clear role caches.
                    eZRole::expireCache( );
                    // Clear all content cache.
                    eZContentCacheManager::clearAllContentCache( );
                    $this->db->commit( );
                    OWMigrationLogger::logNotice( __FUNCTION__ . " - Role unassigned to $messageType $object ($objectID)." );
                }
            }
        }
    }

    protected function correctLimitationArray( $limitationArray ) {
        $trans = eZCharTransform::instance( );
        foreach( $limitationArray as $limitationKey => $limitation ) {
            if( !is_array( $limitation ) ) {
                $limitationArray[$limitationKey] = array( $limitation );
                $limitation = array( $limitation );
            }
            switch( $limitationKey ) {
                case 'Class' :
                case 'ParentClass' :
                    $newLimitation = array( );
                    foreach( $limitation as $limitationItem ) {
                        if( !is_numeric( $limitationItem ) ) {
                            $contentClass = eZContentClass::fetchByIdentifier( $limitationItem );
                            if( $contentClass instanceof eZContentClass ) {
                                $newLimitation[] = $contentClass->attribute( 'id' );
                            }
                        } else {
                            $newLimitation[] = $limitationItem;
                        }
                        $limitationArray[$limitationKey] = $newLimitation;
                    }
                    break;
                case 'Section' :
                    $newLimitation = array( );
                    foreach( $limitation as $limitationItem ) {
                        if( !is_numeric( $limitationItem ) ) {
                            $sectionList = eZSection::fetchFilteredList( array( 'name' => $limitationItem ) );
                            if( count( $sectionList ) > 0 ) {
                                $newLimitation[] = $sectionList[0]->attribute( 'id' );
                            } elseif( $forceCreateSection ) {
                                $section = new eZSection( array(
                                    'name' => $limitationItem,
                                    'identifier' => $trans->transformByGroup( $limitationItem, 'identifier' )
                                ) );
                                $section->store( );
                                $limitValue = $section->attribute( 'id' );
                            }
                        } else {
                            $newLimitation[] = $limitationItem;
                        }
                        $limitationArray[$limitationKey] = $newLimitation;
                    }
                    break;
                case 'SiteAccess' :
                    $newLimitation = array( );
                    foreach( $limitation as $limitationItem ) {
                        if( !is_numeric( $limitationItem ) ) {
                            $newLimitation[] = eZSys::ezcrc32( $limitationItem );
                        } else {
                            $newLimitation[] = $limitationItem;
                        }
                        $limitationArray[$limitationKey] = $newLimitation;
                    }
                    break;
                case 'NewState' :
                    $newLimitation = array( );
                    foreach( $limitation as $limitationItem ) {
                        if( is_numeric( $limitationItem ) ) {
                            $newLimitation[] = $limitationItem;
                        } else {
                            list( $stateGroupIdentifier, $stateIdentifier ) = explode( '/', $limitationItem );
                            $stateGroup = eZContentObjectStateGroup::fetchByIdentifier( $stateGroupIdentifier );
                            if( $stateGroup instanceof eZContentObjectStateGroup ) {
                                $state = eZContentObjectState::fetchByIdentifier( $stateIdentifier, $stateGroup->attribute( 'id' ) );
                                if( $state instanceof eZContentObjectState ) {
                                    $newLimitation[] = $state->attribute( 'id' );
                                }
                            }
                        }
                        $limitationArray[$limitationKey] = $newLimitation;
                    }
                    break;
                default :
                    if( strncmp( $limitationKey, 'StateGroup_', strlen( 'StateGroup_' ) ) == 0 ) {
                        $newLimitation = array( );
                        foreach( $limitation as $limitationItem ) {
                            if( is_numeric( $limitationItem ) ) {
                                $newLimitation[] = $limitationItem;
                            } else {
                                $stateGroupIdentifier = substr( $limitationKey, strlen( 'StateGroup_' ) );
                                $stateGroup = eZContentObjectStateGroup::fetchByIdentifier( $stateGroupIdentifier );
                                if( $stateGroup instanceof eZContentObjectStateGroup ) {
                                    $state = eZContentObjectState::fetchByIdentifier( $limitationItem, $stateGroup->attribute( 'id' ) );
                                    if( $state instanceof eZContentObjectState ) {
                                        $newLimitation[] = $state->attribute( 'id' );
                                    }
                                }
                            }
                            $limitationArray[$limitationKey] = $newLimitation;
                        }
                    }
            }
        }
        return $limitationArray;
    }

    public function removeRole( ) {
        $this->role->removeThis( );
        OWMigrationLogger::logNotice( __FUNCTION__ . " - Role '$this->roleName' removed." );
        $this->roleName = NULL;
        $this->role = NULL;
    }

}
