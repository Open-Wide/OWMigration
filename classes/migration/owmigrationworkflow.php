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
            OWScriptLogger::logNotice( "Workflow '$this->workflowName' not found -> create new workflow.", __FUNCTION__ );
            $this->addToGroup( 'Standard' );
        }
        $this->eventList = $this->workflow->fetchEvents( );
    }

    public function end( ) {
        if( $this->workflow instanceof eZWorkflow ) {
            $currentGroupList = $this->workflow->attribute( 'ingroup_list' );
            if( empty( $currentGroupList ) ) {
                $this->addToGroup( 'Standard' );
                OWScriptLogger::logNotice( "Adding in the standard group", __FUNCTION__ );
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
            OWScriptLogger::logNotice( "Workflow '$this->workflowName' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $currentUser = eZUser::currentUser( );
        $this->workflow = eZWorkflow::create( $currentUser->attribute( 'contentobject_id' ) );
        $this->workflow->setAttribute( 'name', $this->workflowName );
        $this->db->begin( );
        $this->workflow->store( );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Workflow '$this->workflowName' created.", __FUNCTION__ );
        $this->addToGroup( 'Standard' );
    }

    public function addToGroup( $groupName ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
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
            OWScriptLogger::logNotice( "Workflow group '$groupName' created.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        if( $this->hasEvent( $description, $workflowTypeString ) ) {
            OWScriptLogger::logError( "Event '$description' ($workflowTypeString) already exists.", __FUNCTION__ );
            return;
        }
        $this->db->begin( );
        $event = eZWorkflowEvent::create( $this->workflow->attribute( 'id' ), $workflowTypeString );
        $event->setAttribute( 'description', $description );
        $eventType = $event->eventType( );
        if( !$eventType ) {
            OWScriptLogger::logError( "Event type '$workflowTypeString' unknown.", __FUNCTION__ );
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
                    OWScriptLogger::logWarning( "Event '$description' ($workflowTypeString) has no attribute '$attributeName'.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            OWScriptLogger::logWarning( "Event '$description' ($workflowTypeString) not found.", __FUNCTION__ );
        }
        foreach( $params as $attributeName => $attributeValue ) {
            if( $attributeName != 'placement' ) {
                if( $event->hasAttribute( $attributeName ) ) {
                    $data = $this->parseAndReplaceStringReferences( $attributeValue );
                    $event->setAttribute( $attributeName, $data );
                } else {
                    OWScriptLogger::logWarning( "Event '$description' ($workflowTypeString) has no attribute '$attributeName'.", __FUNCTION__ );
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
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            OWScriptLogger::logWarning( "Event '$description' ($workflowTypeString) not found.", __FUNCTION__ );
            return;
        }
        $this->db->begin( );
        $event->remove( );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Event '$description' ($workflowTypeString) removed.", __FUNCTION__ );
    }

    public function assignToTrigger( $module, $operation, $connectType ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
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
                OWScriptLogger::logWarning( "Fail to save trigger.", __FUNCTION__ );
            }
        }
        $connectType = $connectType == 'a' ? 'after' : ($triggerOperationType == 'b' ? 'before' : $connectType);
        OWScriptLogger::logNotice( "Workflow assigned to trigger '$module, $operation, $connectType'.", __FUNCTION__ );
    }

    public function unassignFromTrigger( $module = NULL, $operation = NULL, $connectType = NULL ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
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
                    OWScriptLogger::logNotice( "Trigger '$triggerModule, $triggerOperation, $triggerOperationType' unassigned.", __FUNCTION__ );
                }
            }

        }
    }

    public function removeWorkflow( ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        $workflowID = $this->workflow->attribute( 'id' );
        $this->db->begin( );
        eZTrigger::removeTriggerForWorkflow( $workflowID );
        eZWorkflow::setIsEnabled( false, $workflowID );
        $this->db->commit( );
        OWScriptLogger::logNotice( "Workflow '$this->workflowName' removed.", __FUNCTION__ );
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
