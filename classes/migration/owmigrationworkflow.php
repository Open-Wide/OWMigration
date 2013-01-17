<?php

class OWMigrationWorkflow {

    protected $workflowName;
    protected $workflow;
    protected $output;
    protected $eventList;

    public function __construct( $workflowName ) {
        $this->output = eZCLI::instance( );
        $this->workflowName = $workflowName;
        $workflow = self::fetchWorkflow( );
        if( $workflow instanceof eZWorkflow ) {
            $this->workflow = $workflow;
            $this->output->notice( "Workflow '$workflowName' found -> create new version.", TRUE );
        } else {
            $currentUser = eZUser::currentUser( );
            $this->workflow = eZWorkflow::create( $currentUser->attribute( 'contentobject_id' ) );
            $this->workflow->setAttribute( 'name', $workflowName );            $this->workflow->store( );
            $this->output->notice( "Role '$workflowName' not found -> create new workflow.", TRUE );
            $this->addToGroup( 'Standard' );
        }
        $this->eventList = $this->workflow->fetchEvents( );
    }

    public function addToGroup( $groupName ) {
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
            $workflowGroup = eZWorkflowGroup::create( $user->attribute( 'contentobject_id' ) );
            $workflowGroup->setAttribute( "name", $groupName );
            $workflowGroup->store( );
        }
        $ingroup = eZWorkflowGroupLink::create( $this->workflow->attribute( 'id' ), $this->workflow->attribute( "version" ), $workflowGroup->attribute( 'id' ), $groupName );
        $ingroup->store( );
    }

    public function getEvent( $description, $workflowTypeString ) {
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
        $event = $this->getEvent( $description, $workflowTypeString );
        if( $event ) {
            return TRUE;
        }
        return FALSE;
    }

    public function addEvent( $description, $workflowTypeString, $params = array() ) {
        if( $this->hasEvent( $description, $workflowTypeString ) ) {
            $this->output->warning( "Add event : event '$description' ($workflowTypeString) already exists." );
            return;
        }
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
        if( isset( $params['placement'] ) && is_numeric( $params['placement'] ) ) {
            $eventType->setAttribute( 'placement', (int)$params['placement'] );
        } else {
            $eventType->setAttribute( 'placement', self::getNewEventPlacement( ) );
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
        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        return $event;
    }

    public function updateEvent( $description, $workflowTypeString, $params = array() ) {
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            $this->output->warning( "Update event : event '$description' ($workflowTypeString) not found." );
        }
        foreach( $params as $attributeName => $attributeValue ) {
            if( $attributeName != 'placement' ) {
                if( $event->hasAttribute( $attributeName ) ) {
                    $data = $this->parseAndReplaceStringReferences( $attributeValue );
                    $event->setAttribute( $attributeName, $data );
                    var_dump( $event->content( ) );
                } else {
                    $this->output->warning( "Update event : event '$description' ($workflowTypeString) has no attribute '$attributeName'." );
                }
            }
        }
        $event->store( );
        $this->eventList[] = $event;
        $this->workflow->store( $this->eventList );
        return $event;
    }

    public function removeEvent( $description, $workflowTypeString ) {
        $event = $this->getEvent( $description, $workflowTypeString );
        if( !$event ) {
            $this->output->warning( "Remove event : event '$description' ($workflowTypeString) not found." );
            return;
        }
        $event->remove( );
        $this->output->notice( "Remove event : event '$description' ($workflowTypeString) removed." );
    }

    public function assignToTrigger( $module, $operation, $connectType ) {
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
        $this->output->notice( "Assign to trigger : workflow assigned to trigger '$module, $operation, $connectType'." );
    }

    public function save( ) {
        $currentGroupList = $this->workflow->attribute( 'ingroup_list' );
        if( empty( $currentGroupList ) ) {
            $this->addToGroup( 'Standard' );
            $this->output->notice( "Ajout dans le groupe standard" );
        }
        $this->workflow->store( $this->eventList );
        $WorkflowID = $this->workflow->attribute( 'id' );

        // Remove old version 0 first
        //eZWorkflowGroupLink::removeWorkflowMembers( $WorkflowID, 0 );

        $workflowgroups = eZWorkflowGroupLink::fetchGroupList( $WorkflowID, 1 );
        foreach( $workflowgroups as $workflowgroup ) {
            $workflowgroup->setAttribute( "workflow_version", 0 );
            $workflowgroup->store( );
        }
        // Remove version 1
        eZWorkflowGroupLink::removeWorkflowMembers( $WorkflowID, 1 );

        eZWorkflow::removeEvents( false, $WorkflowID, 0 );
        $this->workflow->removeThis( true );
        $this->workflow->setVersion( 0, $this->eventList );
        $this->workflow->adjustEventPlacements( $this->eventList );
        $this->workflow->storeDefined( $this->eventList );
        $this->workflow->cleanupWorkFlowProcess( );
        eZWorkflow::removeEvents( false, $WorkflowID, 1 );
    }

    static function unassignTrigger( $module, $operation, $connectType ) {
        $output = eZCLI::instance( );

        $connectType = $connectType[0];
        $parameters = array( );
        $parameters['module'] = $module;
        $parameters['function'] = $operation;
        $parameters['connectType'] = $connectType;

        $triggerList = eZTrigger::fetchList( $parameters );

        if( count( $triggerList ) ) {
            $trigger = $triggerList[0];
            $trigger->remove( );
        }
        $output->notice( "Unassign to trigger : trigger '$module, $operation, $connectType' unassigned." );
    }

    static function removeWorkflow( $workflowName ) {
        $output = eZCLI::instance( );

        $workflow = self::fetchWorkflow( $workflowName );
        if( $workflow instanceof eZWorkflow ) {
            $workflowID = $workflow->attribute( 'id' );
            eZTrigger::removeTriggerForWorkflow( $workflowID );
            eZWorkflow::setIsEnabled( false, $workflowID );
            $output->notice( "Workflow '$workflowName' removed." );
        } else {
            $output->notice( "Workflow '$workflowName' nor found." );
        }
    }

    protected function fetchWorkflow( $workflowName = FALSE ) {
        if( !$workflowName ) {
            $workflowName = $this->workflowName;
        }
        return eZPersistentObject::fetchObject( eZWorkflow::definition( ), null, array( "name" => $workflowName ), TRUE );
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
