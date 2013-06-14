<?php

class OWMigrationStateGroup extends OWMigrationBase {

    protected $stateGroupIdentifier;
    protected $stateGroup;
    protected $topPriorityLanguage;

    public function startMigrationOn( $param ) {
        $this->stateGroupIdentifier = $param;
        $objectStateGroup = eZContentObjectStateGroup::fetchByIdentifier( $param );
        if( $objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->stateGroup = $objectStateGroup;
        }
        $this->topPriorityLanguage = eZContentLanguage::topPriorityLanguage( );
    }

    public function end( ) {
        $this->stateGroupIdentifier = NULL;
        $this->stateGroup = NULL;
    }

    public function createIfNotExists( ) {
        $trans = eZCharTransform::instance( );
        if( $this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->notice( "Create if not exists : state group '$this->stateGroupIdentifier' exists, nothing to do." );
            return;
        }
        $this->db->begin( );
        $this->stateGroup = new eZContentObjectStateGroup( );
        $this->stateGroup->setAttribute( 'identifier', $this->stateGroupIdentifier );
        $this->stateGroup->setCurrentLanguage( $this->topPriorityLanguage->attribute( 'locale' ) );
        $translations = $this->stateGroup->allTranslations( );
        foreach( $translations as $translation ) {
            $translation->setAttribute( 'name', $trans->transformByGroup( $this->stateGroupIdentifier, 'humanize' ) );
        }
        $this->stateGroup->store( );
        $this->db->commit( );
        $this->output->notice( "Create if not exists : state group '$this->stateGroupIdentifier' created." );
    }

    public function update( $params ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Update : state group '$this->stateGroupIdentifier' not found." );
            return;
        }
        foreach( $params as $key => $value ) {
            if( $this->stateGroup->hasAttribute( $key ) ) {
                $this->stateGroup->setAttribute( $key, $value );
            } else {
                $translation = $this->stateGroup->translationByLocale( $key );
                if( $translation instanceof eZContentObjectStateGroupLanguage ) {
                    if( is_array( $value ) ) {
                        if( isset( $value['name'] ) ) {
                            $translation->setAttribute( 'name', $value['name'] );
                        }
                        if( isset( $value['description'] ) ) {
                            $translation->setAttribute( 'description', $value['description'] );
                        }
                    } else {
                        $translation->setAttribute( 'name', $value );
                    }
                } else {
                    $this->output->error( "update : attribute or translation '$key' not found." );
                }
            }
        }
        $this->stateGroup->store( );
        $this->output->notice( "update : state group '$this->stateGroupIdentifier' updated." );
    }

    public function addState( $identifier, $params = array() ) {
        $trans = eZCharTransform::instance( );
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->notice( "Add state : state group '$this->stateGroupIdentifier' nou found." );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $this->output->warning( "Add state : state '$identifier' already exists." );
            return;
        } else {
            $state = $this->stateGroup->newState( );
            $state->setAttribute( 'identifier', $identifier );
            $state->setCurrentLanguage( $this->topPriorityLanguage->attribute( 'locale' ) );
            $translations = $state->allTranslations( );
            foreach( $translations as $translation ) {
                $locale = $translation->language( )->attribute( 'locale' );
                $translation->setAttribute( 'name', $trans->transformByGroup( $identifier, 'humanize' ) );
            }
            $state->store( );
            if( !empty( $params ) ) {
                $state = $this->fillStateWithParams( $state, $params );
            }
            $state->store( );
            $this->output->notice( "Add state : state $identifier added." );
        }
    }

    public function updateState( $identifier, $params ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Update state : state group '$this->stateGroupIdentifier' not found." );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $state = $this->fillStateWithParams( $state, $params );
            $state->store( );
            $this->output->notice( "Update state : state '$identifier' upadted." );
        } else {
            $this->output->warning( "Update state : state '$identifier' not found." );
        }
    }

    public function removeState( $identifier ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Remove state : state group '$this->stateGroupIdentifier' not found." );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $state->remove( );
            $this->output->notice( "Remove state : state '$identifier' removed." );
        } else {
            $this->output->warning( "Remove state : state '$identifier' not found." );
            return;
        }
    }

    public function removeStateGroup( ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Remove state : state group '$this->stateGroupIdentifier' not found." );
            return;
        }
        $this->stateGroup->remove( );
        $this->output->notice( "Remove state group : state group '$this->stateGroupIdentifier' removed." );
    }

    protected function fillStateWithParams( $state, $params ) {
        foreach( $params as $key => $value ) {
            if( $state->hasAttribute( $key ) ) {
                $state->setAttribute( $key, $value );
            } else {
                $translation = $state->translationByLocale( $key );
                if( $translation instanceof eZContentObjectStateLanguage ) {
                    if( is_array( $value ) ) {
                        if( isset( $value['name'] ) ) {
                            $translation->setAttribute( 'name', $value['name'] );
                        }
                        if( isset( $value['description'] ) ) {
                            $translation->setAttribute( 'description', $value['description'] );
                        }
                    } else {
                        $translation->setAttribute( 'name', $value );
                    }
                } else {
                    $this->output->warning( "fill state with params : attribute or translation '$key' not found." );
                }
            }
        }
        return $state;
    }

}
