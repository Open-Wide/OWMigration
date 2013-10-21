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
            $this->workflow->store( );
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
            $this->workflow->store( $this->eventList );
            $WorkflowID = $this->workflow->attribute( 'id' );

            $workflowgroups = eZWorkflowGroupLink::fetchGroupList( $WorkflowID, 1 );
            foreach( $workflowgroups as $workflowgroup ) {
                $workflowgroup->setAttribute( "workflow_version", 0 );
                $workflowgroup->store( );
            }
            $this->workflow->setVersion( 0, $this->eventList );
            $this->workflow->adjustEventPlacements( $this->eventList );
            $this->workflow->storeDefined( $this->eventList );
            $this->workflow->cleanupWorkFlowProcess( );
            eZWorkflow::removeEvents( false, $WorkflowID, 1 );
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
        $this->workflow->store( );
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
            $workflowGroup = eZWorkflowGroup::create( $user->attribute( 'contentobject_id' ) );
            $workflowGroup->setAttribute( "name", $groupName );
            $workflowGroup->store( );
        }
        $ingroup = eZWorkflowGroupLink::create( $this->workflow->attribute( 'id' ), $this->workflow->attribute( "version" ), $workflowGroup->attribute( 'id' ), $groupName );
        $ingroup->store( );
    }

    public function getEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        foreach( $this->eventList as $event ) {
            if( $event->attribute( 'description' ) == $description && $event->attribute( 'workflow_type_string' ) == $workflowTypeString ) {
                return $event;
            }
        }
        return NULL;
    }

    public function hasEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        $event = $this->getEvent( $description, $workflowTypeString );
        if( $event instanceof eZWorkflowEvent ) {
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
        $event = eZWorkflowEvent::create( $this->workflow->attribute( 'id' ), $workflowTypeString );
        $event->setAttribute( 'description', $description );
        $eventType = $event->eventType( );
        if( !$eventType ) {
            OWScriptLogger::logError( "Event type '$workflowTypeString' unknown.", __FUNCTION__ );
            return;
        }
        $this->workflow->store( $this->eventList );
        $eventType->initializeEvent( $event );
        $workflowTypeHandlerClass = get_class( $event->attribute( 'workflow_type' ) ) . 'MigrationHandler';
        if( !class_exists( $workflowTypeHandlerClass ) || !is_callable( $workflowTypeHandlerClass . '::toArray' ) ) {
            $workflowTypeHandlerClass = "DefaultEventTypeMigrationHandler";
        }
        $workflowTypeHandlerClass::fromArray( $event, $params );
        if( isset( $params['placement'] ) && is_numeric( $params['placement'] ) ) {
            $eventType->setAttribute( 'placement', (int)$params['placement'] );
        } else {
            $eventType->setAttribute( 'placement', $this->getNewEventPlacement( ) );
        }

        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        OWScriptLogger::logNotice( "Event '$description' ($workflowTypeString) added.", __FUNCTION__ );
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
            return FALSE;
        }
        $workflowTypeHandlerClass = get_class( $event->attribute( 'workflow_type' ) ) . 'MigrationHandler';
        if( !class_exists( $workflowTypeHandlerClass ) || !is_callable( $workflowTypeHandlerClass . '::toArray' ) ) {
            $workflowTypeHandlerClass = "DefaultEventTypeMigrationHandler";
        }
        $currentAttributeValues = $workflowTypeHandlerClass::toArray( $event );
        $finalAttributeValues = array_merge( $currentAttributeValues, $params );
        if( OWMigrationTools::compareArray( $currentAttributeValues, $finalAttributeValues ) ) {
            OWScriptLogger::logNotice( "Event '$description' ($workflowTypeString) did not need to be updated.", __FUNCTION__ );
            return $event;
        }
        $eventAttributes = $workflowTypeHandlerClass::fromArray( $event, $params );
        if( isset( $params['placement'] ) && is_numeric( $params['placement'] ) ) {
            $event->setAttribute( 'placement', (int)$params['placement'] );
        }
        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        OWScriptLogger::logNotice( "Event '$description' ($workflowTypeString) updated.", __FUNCTION__ );
        return $event;
    }

    public function removeEvent( $description, $workflowTypeString ) {
        if( !$this->workflow instanceof eZWorkflow ) {
            OWScriptLogger::logError( "Workflow object not found.", __FUNCTION__ );
            return;
        }
        foreach( $this->eventList as $index => $event ) {
            if( $event->attribute( 'description' ) == $description && $event->attribute( 'workflow_type_string' ) == $workflowTypeString ) {
                $event->remove( );
                unset( $this->eventList[$index] );
                OWScriptLogger::logNotice( "Event '$description' ($workflowTypeString) removed.", __FUNCTION__ );
                return TRUE;
            }
        }
        OWScriptLogger::logWarning( "Event '$description' ($workflowTypeString) not found.", __FUNCTION__ );
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
                $newTrigger = eZTrigger::createNew( $module, $operation, $connectType, $this->workflow->attribute( 'id' ) );
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
                    $trigger->remove( );
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
        eZTrigger::removeTriggerForWorkflow( $workflowID );
        $this->workflow->removeThis( );
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

}
