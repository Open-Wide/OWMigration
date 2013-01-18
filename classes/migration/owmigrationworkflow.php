<?php

class OWMigrationWorkflow extends OWMigrationBase {

    protected $workflowName;
    protected $workflow;
    protected $eventList = array( );

    public function startMigrationOn( $param ) {
        $this->workflowName = $param;
        $workflow = $this->fetchWorkflow( );
        if( $workflow instanceof eZWorkflow ) {
            $this->workflow = $workflow;
        } else {
            $currentUser = eZUser::currentUser( );
            $this->workflow = eZWorkflow::create( $currentUser->attribute( 'contentobject_id' ) );
            $this->workflow->setAttribute( 'name', $this->workflowName );
            $this->db->begin( );            $this->workflow->store( );
            $this->db->commit( );
            $this->output->notice( "Role '$this->workflowName' not found -> create new workflow.", TRUE );
            $this->addToGroup( 'Standard' );
        }
        $this->eventList = $this->workflow->fetchEvents( );
    }

    public function end( ) {
        if( $this->workflow instanceof eZWorkflow ) {
            $currentGroupList = $this->workflow->attribute( 'ingroup_list' );
            if( empty( $currentGroupList ) ) {
                $this->addToGroup( 'Standard' );
                $this->output->notice( "Ajout dans le groupe standard" );
            }
            $this->db->begin( );
            $this->workflow->store( $this->eventList );
            $this->db->commit( );
            $WorkflowID = $this->workflow->attribute( 'id' );

            $workflowgroups = eZWorkflowGroupLink::fetchGroupList( $WorkflowID, 1 );
            foreach( $workflowgroups as $workflowgroup ) {
                $workflowgroup->setAttribute( "workflow_version", 0 );
                $this->db->begin( );
                $workflowgroup->store( );
                $this->db->commit( );
            }
            $this->db->begin( );
            eZWorkflowGroupLink::removeWorkflowMembers( $WorkflowID, 1 );
            eZWorkflow::removeEvents( false, $WorkflowID, 0 );
            $this->workflow->removeThis( true );
            $this->workflow->setVersion( 0, $this->eventList );
            $this->workflow->adjustEventPlacements( $this->eventList );
            $this->workflow->storeDefined( $this->eventList );
            $this->workflow->cleanupWorkFlowProcess( );
            eZWorkflow::removeEvents( false, $WorkflowID, 1 );
            $this->db->commit( );
        }
        $this->workflowName = NULL;
        $this->workflow = NULL;
        $this->eventList = array( );
    }

    public function createIfNotExists( ) {
        if( $this->workflow instanceof eZWorkflow ) {
            $this->output->notice( "Create if not exists : workflow '$this->workflowName' exists, nothing to do." );
            return;
        }
        $currentUser = eZUser::currentUser( );
        $this->workflow = eZWorkflow::create( $currentUser->attribute( 'contentobject_id' ) );
        $this->workflow->setAttribute( 'name', $this->workflowName );
        $this->db->begin( );
        $this->workflow->store( );
        $this->db->commit( );
        $this->output->notice( "Create if not exists : workflow '$this->workflowName' created." );
        $this->addToGroup( 'Standard' );
    }

    public function addToGroup( $groupName ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Add to group : workflow object not found." );
            return;
        }
        $workflowGroupList = eZWorkflowGroup::fetchList( );
        foreach( $workflowGroupList as $workflowGroupItem ) {
            if( $workflowGroupItem->attribute( 'name' ) == $groupName ) {
                $workflowGroup = $workflowGroupItem;
                break;
            }
        }
        if( !$workflowGroup ) {
            $this->output->notice( "Add to group : workflow group '$groupName' created." );
            $user = eZUser::currentUser( );
            $this->db->begin( );
            $workflowGroup = eZWorkflowGroup::create( $user->attribute( 'contentobject_id' ) );
            $workflowGroup->setAttribute( "name", $groupName );
            $workflowGroup->store( );
            $this->db->commit( );
        }
        $this->db->begin( );
        $ingroup = eZWorkflowGroupLink::create( $this->workflow->attribute( 'id' ), $this->workflow->attribute( "version" ), $workflowGroup->attribute( 'id' ), $groupName );
        $ingroup->store( );
        $this->db->commit( );
    }

    public function getEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Get event : workflow object not found." );
            return;
        }
        $cond = array(
            'workflow_id' => $this->workflow->attribute( 'id' ),
            'description' => $description,
            'workflow_type_string' => $workflowTypeString
        );
        $event = eZWorkflowEvent::fetchFilteredList( $cond );
        if( count( $event ) > 0 ) {
            return $event[0];
        }
        return NULL;
    }

    public function hasEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Has event : workflow object not found." );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( $event ) {
            return TRUE;
        }
        return FALSE;
    }

    public function addEvent( $description, $workflowTypeString, $params = array() ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Add event : workflow object not found." );
            return;
        }
        if( $this->hasEvent( $description, $workflowTypeString ) ) {
            $this->output->warning( "Add event : event '$description' ($workflowTypeString) already exists." );
            return;
        }
        $this->db->begin( );
        $event = eZWorkflowEvent::create( $this->workflow->attribute( 'id' ), $workflowTypeString );
        $event->setAttribute( 'description', $description );
        $eventType = $event->eventType( );
        if( !$eventType ) {
            $this->output->error( "Add event : event type '$workflowTypeString' unknown." );
            $event->remove( );
            return;
        }
        $this->workflow->store( $this->eventList );
        $eventType->initializeEvent( $event );
        $this->db->commit( );
        if( isset( $params['placement'] ) && is_numeric( $params['placement'] ) ) {
            $eventType->setAttribute( 'placement', (int)$params['placement'] );
        } else {
            $eventType->setAttribute( 'placement', $this->getNewEventPlacement( ) );
        }

        foreach( $params as $attributeName => $attributeValue ) {
            if( $attributeName != 'placement' ) {
                if( $event->hasAttribute( $attributeName ) ) {
                    $data = $this->parseAndReplaceStringReferences( $attributeValue );
                    $event->setAttribute( $attributeName, $data );
                } else {
                    $this->output->warning( "Add event : event '$description' ($workflowTypeString) has no attribute '$attributeName'." );
                }
            }
        }
        $this->db->begin( );
        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        $this->db->commit( );
        return $event;
    }

    public function updateEvent( $description, $workflowTypeString, $params = array() ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Update event : workflow object not found." );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            $this->output->warning( "Update event : event '$description' ($workflowTypeString) not found." );
        }
        foreach( $params as $attributeName => $attributeValue ) {
            if( $attributeName != 'placement' ) {
                if( $event->hasAttribute( $attributeName ) ) {
                    $data = $this->parseAndReplaceStringReferences( $attributeValue );
                    $event->setAttribute( $attributeName, $data );
                } else {
                    $this->output->warning( "Update event : event '$description' ($workflowTypeString) has no attribute '$attributeName'." );
                }
            }
        }
        $this->db->begin( );
        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        $this->db->commit( );
        return $event;
    }

    public function removeEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Remove event : workflow object not found." );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            $this->output->warning( "Remove event : event '$description' ($workflowTypeString) not found." );
            return;
        }
        $this->db->begin( );
        $event->remove( );
        $this->db->commit( );
        $this->output->notice( "Remove event : event '$description' ($workflowTypeString) removed." );
    }

    public function assignToTrigger( $module, $operation, $connectType ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Assign to trigger : workflow object not found." );
            return;
        }
        $connectType = $connectType[0];
        $parameters = array( );
        $parameters['module'] = $module;
        $parameters['function'] = $operation;
        $parameters['connectType'] = $connectType;

        $triggerList = eZTrigger::fetchList( $parameters );

        if( count( $triggerList ) ) {
            $trigger = $triggerList[0];
            $trigger->setAttribute( 'workflow_id', $this->workflow->attribute( 'id' ) );
            $trigger->store( );
        } else {
            try {
                $db = eZDB::instance( );
                $db->begin( );
                $newTrigger = eZTrigger::createNew( $module, $operation, $connectType, $this->workflow->attribute( 'id' ) );
                $db->commit( );
            } catch (Exception $e) {
                $this->output->warning( "Assign to trigger : fail to save trigger." );
            }
        }
        $connectType = $connectType == 'a' ? 'after' : ($triggerOperationType == 'b' ? 'before' : $connectType);
        $this->output->notice( "Assign to trigger : workflow assigned to trigger '$module, $operation, $connectType'." );
    }

    public function unassignFromTrigger( $module = NULL, $operation = NULL, $connectType = NULL ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "unassign to trigger : workflow object not found." );
            return;
        }

        $connectType = $connectType[0];
        $parameters = array( );
        if( $module ) {
            $parameters['module'] = $module;
        }
        if( $operation ) {
            $parameters['function'] = $operation;
        }
        if( $connectType ) {
            $parameters['connectType'] = $connectType;
        }

        $triggerList = eZTrigger::fetchList( $parameters );

        if( count( $triggerList ) ) {
            foreach( $triggerList as $trigger ) {
                if( $trigger->attribute( 'workflow_id' ) == $this->workflow->attribute( 'id' ) ) {
                    $triggerModule = $trigger->attribute( 'module_name' );
                    $triggerOperation = $trigger->attribute( 'function_name' );
                    $triggerOperationType = $trigger->attribute( 'connect_type' );
                    $triggerOperationType = $triggerOperationType == 'a' ? 'after' : ($triggerOperationType == 'b' ? 'before' : $triggerOperationType);
                    $this->db->begin( );
                    $trigger->remove( );
                    $this->db->commit( );
                    $this->output->notice( "Unassign to trigger : trigger '$triggerModule, $triggerOperation, $triggerOperationType' unassigned." );
                }
            }

        }
    }

    public function removeWorkflow( ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            $this->output->error( "Remove workflow : workflow object not found." );
            return;
        }
        $workflowID = $this->workflow->attribute( 'id' );
        $this->db->begin( );
        eZTrigger::removeTriggerForWorkflow( $workflowID );
        eZWorkflow::setIsEnabled( false, $workflowID );
        $this->db->commit( );
        $this->output->notice( "Remove workflow : workflow '$this->workflowName' removed." );
    }

    protected function fetchWorkflow( ) {
        return eZPersistentObject::fetchObject( eZWorkflow::definition( ), null, array( "name" => $this->workflowName ), TRUE );
    }

    protected function getNewEventPlacement( ) {
        $maxPlacement = -1;
        foreach( $this->eventList as $event ) {
            if( $event->attribute( 'placement' ) > $maxPlacement ) {
                $maxPlacement = $event->attribute( 'placement' );
            }
        }
        $maxPlacement++;
        return $maxPlacement;
    }

    protected function parseAndReplaceStringReferences( $string ) {
        $result = array( );
        $count = preg_match_all( '|\[([^\]\[]*)\]|', $string, $result );
        if( count( $result ) > 1 ) {
            foreach( $result[1] as $i => $refInfo ) {
                $id = $this->getReferenceID( $refInfo );
                $string = str_replace( $result[0][$i], $id, $string );
            }
        }
        $string = str_replace( '&#93;', ']', $string );
        $string = str_replace( '&#91;', '[', $string );
        return $string;
    }

}
