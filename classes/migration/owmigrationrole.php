<?php

class OWMigrationRole extends OWMigrationBase {

    protected $roleName;
    protected $role;

    public function startMigrationOn( $param ) {
        $this->roleName = $param;
        $role = eZRole::fetchByName( $this->roleName );
        if( $role instanceof eZRole ) {
            $this->role = $role;
        } else {

        }
    }

    public function end( ) {
        $this->roleName = NULL;
        $this->role = NULL;
    }

    public function createIfNotExists( ) {
        if( $this->role instanceof eZRole ) {
            $this->output->notice( "Create if not exists : role '$this->roleName' exists, nothing to do." );
            return;
        }
        $this->db->begin( );
        $this->role = eZRole::create( $this->roleName );
        $this->role->store( );
        $this->db->commit( );
        $this->output->notice( "Create if not exists : role '$this->roleName' created." );
    }

    public function hasPolicy( $module = '*', $function = '*', $limitation = array() ) {
        $limitation = OWMigrationTools::correctLimitationArray( $limitation );
        if( !$this->role instanceof eZRole ) {
            $this->output->error( "Has policy : role object not found." );
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
            $this->output->error( "Add policy : role object not found." );
            return FALSE;
        }
        $messagePart = empty( $limitation ) ? 'without' : 'with';

        if( !$this->hasPolicy( $module, $function, $limitation ) ) {
            $limitation = $this->correctLimitationArray( $limitation );
            $this->db->begin( );
            $this->role->appendPolicy( $module, $function, $limitation );
            $this->role->store( );
            $this->db->commit( );
            $this->output->notice( "Policy on $module::$function $messagePart limitation added.", TRUE );
        } else {
            $this->output->notice( "Policy on $module::$function $messagePart limitation already exists.", TRUE );
        }
    }

    public function removePolicies( $module = FALSE, $function = FALSE, $limitation = FALSE ) {
        if( !$this->role instanceof eZRole ) {
            $this->output->error( "Remove policy : role object not found." );
            return;
        }
        $this->db->begin( );
        if( $module === FALSE ) {
            $this->db->begin( );
            $this->role->removePolicies( TRUE );

            $this->output->notice( "All policies deleted.", TRUE );
        } elseif( $limitation === FALSE ) {
            $this->role->removePolicy( $module, $function );
            $this->output->notice( "Policies on $module::$function deleted.", TRUE );
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
                                $this->output->notice( "Policies on $module::$function with limitation deleted.", TRUE );
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
            $this->output->error( "Assign to $messageType : role object not found." );
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
                $this->output->error( "Assign to $messageType : $messageType '$object' not found." );
                return;
            }
        } elseif( is_array( $object ) ) {
            foreach( $object as $item ) {
                $this->assignToUser( $item, $limitIdent, $limitValue );
            }
        } else {
            $this->output->error( "Assign to $messageType : $messageType param must be an integer, a string or an array." );
        }

        if( !is_null( $limitIdent ) ) {
            switch( strtolower( $limitIdent ) ) {
                case 'subtree' :
                    /*
                     if( !is_numeric( $limitValue ) ) {
                     $this->output->error( "Assign to $messageType : limit value must be a nodeID." );
                     return;
                     }
                     */
                    break;
                case 'section' :
                    if( is_string( $limitValue ) ) {
                        $section = eZPersistentObject::fetchObject( eZSection::definition( ), null, array( "identifier" => $limitValue ) );
                        if( !$section ) {
                            $section = new eZSection( array(
                                'name' => $limitValue,
                                'identifier' => $limitValue
                            ) );
                            $section->store( );
                            $limitValue = $section->attribute( 'id' );
                            $this->output->notice( "Assign to $messageType : section '$limitValue' not found => create new section." );
                        }
                        $limitValue = $section->attribute( 'id' );
                    } elseif( !is_numeric( $limitValue ) ) {
                        $this->output->error( "Assign to $messageType : limit value must be a section ID or a section identifer." );
                        return;
                    }
                    break;
                default :
                    $this->output->error( "Assign to user : $messageType identifier must be equal to 'subtree' or 'section'." );
                    return;
            }
        }

        if( isset( $objectID ) ) {
            $this->db->begin( );
            $this->role->assignToUser( $objectID, $limitIdent, $limitValue );
            $this->db->commit( );
            $this->output->notice( "Assign to $messageType : role assigned to user $object ($objectID)." );
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
            $this->output->error( "Assign to $messageType : role object not found." );
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
                $this->output->error( "Unassign to $messageType : $messageType '$object' not found." );
                return;
            }
        } elseif( is_array( $messageType ) ) {
            foreach( $messageType as $item ) {
                $this->unassignToUser( $item, $limitIdent, $limitValue );
            }
        } else {
            $this->output->error( "Unassign to $messageType : $messageType param must be an integer, a string or an array." );
        }

        if( !is_null( $limitIdent ) ) {
            switch( $limitIdent ) {
                case 'subtree' :
                    if( !is_numeric( $limitValue ) ) {
                        $this->output->error( "Assign to $messageType : limit value must be a nodeID." );
                        return;
                    } else {
                        $node = eZContentObjectTreeNode::fetch( $limitValue, false, false );
                        if( $node ) {
                            $limitValue = $node['path_string'];
                        } else {
                            $this->output->notice( "Unassign to $messageType : node not found." );
                            return;
                        }
                    }
                    break;
                case 'section' :
                    if( is_string( $limitValue ) ) {
                        $section = eZSection::fetchByIdentifier( $limitValue );
                        if( $section ) {
                            $limitValue = $section->attribute( 'id' );
                        } else {
                            $this->output->notice( "Unassign to $messageType : section not found." );
                            return;
                        }

                    } elseif( !is_numeric( $limitValue ) ) {
                        $this->output->error( "Unassign to $messageType : limit value must be a section ID or a section identifer." );
                        return;
                    }
                    break;
                default :
                    $this->output->error( "Unassign to $messageType : limit identifier must be equal to 'subtree' or 'section'." );
                    return;
            }
        } else {
            $limitValue = NULL;
        }
        if( isset( $objectID ) ) {
            foreach( $this->role->fetchUserByRole( ) as $userRole ) {
                if( $userRole['user_object']->attribute( 'id' ) == $objectID && strtolower( $userRole['limit_ident'] ) == $limitIdent && strtolower( $userRole['limit_value'] ) == $limitValue ) {
                    $this->db->begin( );
                    $this->role->removeUserAssignmentByID( $userRole['user_role_id'] );
                    $this->db->commit( );
                    $this->output->notice( "Assign to $messageType : role unassigned to user $object ($objectID)." );
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
            }
        }
        return $limitationArray;
    }

    public function removeRole( ) {
        $this->role->removeThis( );
        $this->output->notice( "Remove role : role '$this->roleName' removed." );
        $this->roleName = NULL;
        $this->role = NULL;
    }

}
