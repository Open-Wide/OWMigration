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
        OWScriptLogger::logNotice( "Start migration of role '$this->roleName'.", __FUNCTION__ );
    }

    public function end( ) {
        if( $this->role instanceof eZRole ) {
            $this->role->store( );
        }
        $this->roleName = NULL;
        $this->role = NULL;
    }

    public function createIfNotExists( ) {
        if( $this->role instanceof eZRole ) {
            OWScriptLogger::logNotice( "Role '$this->roleName' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $this->db->begin( );
        $this->role = eZRole::create( $this->roleName );
        $this->role->store( );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Role '$this->roleName' created.", __FUNCTION__ );
    }

    public function hasPolicy( $module = '*', $function = '*', $limitation = array() ) {
        $limitation = OWMigrationTools::correctLimitationArray( $limitation );
        if( !$this->role instanceof eZRole ) {
            OWScriptLogger::logError( "Role object not found.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Role object not found.", __FUNCTION__ );
            return FALSE;
        }
        $messagePart = empty( $limitation ) ? 'without' : 'with';

        if( !$this->hasPolicy( $module, $function, $limitation ) ) {
            $limitation = $this->correctLimitationArray( $limitation );
            $this->db->begin( );
            $this->role->appendPolicy( $module, $function, $limitation );
            $this->role->store( );
            $this->db->commit( );
            OWScriptLogger::logNotice( "Policy on $module::$function $messagePart limitation added.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "Policy on $module::$function $messagePart limitation already exists.", __FUNCTION__ );
        }
    }

    public function removePolicies( $module = FALSE, $function = FALSE, $limitation = FALSE ) {
        if( !$this->role instanceof eZRole ) {
            OWScriptLogger::logError( "Role object not found.", __FUNCTION__ );
            return;
        }
        $this->db->begin( );
        if( $module === FALSE ) {
            $this->role->removePolicies( TRUE );

            OWScriptLogger::logNotice( "All policies deleted.", __FUNCTION__ );
        } elseif( $limitation === FALSE ) {
            $this->role->removePolicy( $module, $function );
            OWScriptLogger::logNotice( "Policies on $module::$function deleted.", __FUNCTION__ );
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
                                OWScriptLogger::logNotice( "Policies on $module::$function with limitation deleted.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Role object not found.", __FUNCTION__ );
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
                OWScriptLogger::logError( "$messageType '$object' not found.", __FUNCTION__ );
                return;
            }
        } elseif( is_array( $object ) ) {
            foreach( $object as $item ) {
                $this->assignTo( $type, $item, $limitIdent, $limitValue );
            }
        } else {
            OWScriptLogger::logError( "Object parameter must be an object ID, a object name or an array or object ID and object name.", __FUNCTION__ );
        }

        if( !is_null( $limitIdent ) ) {
            switch( $limitIdent ) {
                case 'Subtree' :
                    if( is_numeric( $limitValue ) ) {
                        $node = eZContentObjectTreeNode::fetch( $limitValue, false, false );
                        if( $node ) {
                            $limitValue = $node['path_string'];
                        } else {
                            OWScriptLogger::logNotice( "Node $limitValue not found.", __FUNCTION__ );
                            return;
                        }
                    }
                    break;
                case 'Section' :
                    if( is_string( $limitValue ) ) {
                        $section = OWMigrationTools::findOrCreateSection( $limitValue );
                        $limitValue = $section->attribute( 'id' );
                    } elseif( !is_numeric( $limitValue ) ) {
                        OWScriptLogger::logError( "Limit value must be a section ID or a section identifer.", __FUNCTION__ );
                        return;
                    }
                    break;
                default :
                    OWScriptLogger::logError( "Limit identifier must be equal to 'Subtree' or 'Section'.", __FUNCTION__ );
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
            OWScriptLogger::logNotice( "Role assigned to $messageType $object ($objectID).", __FUNCTION__ );
        }
    }

    public function unassignToUser( $user, $limitIdent = NULL, $limitValue = NULL ) {
        $this->unassignTo( 'user', $user, $limitIdent, $limitValue );
    }

    public function unassignToUserGroup( $group, $limitIdent = NULL, $limitValue = NULL ) {
        $this->unassignTo( 'user_group', $group, $limitIdent, $limitValue );
    }

    protected function unassignTo( $type, $object, $limitIdent = NULL, $limitValue = NULL ) {
        $trans = eZCharTransform::instance( );
        $messageType = strtolower( $trans->transformByGroup( $type, 'humanize' ) );
        if( !$this->role instanceof eZRole ) {
            OWScriptLogger::logError( "Role object not found.", __FUNCTION__ );
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
                OWScriptLogger::logError( "$messageType '$object' not found.", __FUNCTION__ );
                return;
            }
        } elseif( is_array( $object ) ) {
            foreach( $object as $item ) {
                $this->unassignTo( $type, $item, $limitIdent, $limitValue );
            }
        } else {
            OWScriptLogger::logError( "Object parameter must be an object ID, a object name or an array or object ID and object name.", __FUNCTION__ );
        }

        if( !is_null( $limitIdent ) ) {
            switch( $limitIdent ) {
                case 'Subtree' :
                    if( is_numeric( $limitValue ) ) {
                        $node = eZContentObjectTreeNode::fetch( $limitValue, false, false );
                        if( $node ) {
                            $limitValue = $node['path_string'];
                        } else {
                            OWScriptLogger::logNotice( "Node $limitValue not found.", __FUNCTION__ );
                            return;
                        }
                    }
                    break;
                case 'Section' :
                    if( is_string( $limitValue ) ) {
                        $section = OWMigrationTools::findSection( $limitValue );
                        if( $section ) {
                            $limitValue = $section->attribute( 'id' );
                        } else {
                            OWScriptLogger::logNotice( "Section $limitValue not found.", __FUNCTION__ );
                            return;
                        }

                    } elseif( !is_numeric( $limitValue ) ) {
                        OWScriptLogger::logError( "Limit value must be a section ID or a section identifer.", __FUNCTION__ );
                        return;
                    }
                    break;
                default :
                    OWScriptLogger::logError( "Limit identifier must be equal to 'subtree' or 'section'.", __FUNCTION__ );
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
                    OWScriptLogger::logNotice( "Role unassigned to $messageType $object ($objectID).", __FUNCTION__ );
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
                            $section = OWMigrationTools::findOrCreateSection( $limitationItem );
                            $newLimitation[] = $section->attribute( 'id' );
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
        OWScriptLogger::logNotice( "Role '$this->roleName' removed.", __FUNCTION__ );
        $this->roleName = NULL;
        $this->role = NULL;
    }

    public function __set( $name, $value ) {
        if( $this->role instanceof eZRole ) {
            if( $this->role->hasAttribute( $name ) ) {
                $this->role->setAttribute( $name, $value );
            } else {
                throw new OWMigrationRoleException( "Attribute $name not found" );
            }
        }
    }

    public function __get( $name ) {
        if( $this->role instanceof eZRole ) {
            if( $this->role->hasAttribute( $name ) ) {
                return $this->role->attribute( $name );
            } else {
                throw new OWMigrationRoleException( "Attribute $name not found." );
            }
        }
    }

    public function __isset( $name ) {
        if( $this->role instanceof eZRole ) {
            if( $this->role->hasAttribute( $name ) ) {
                TRUE;
            } else {
                FALSE;
            }
        }
    }

}
