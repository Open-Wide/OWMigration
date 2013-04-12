<?php

class OWMigrationObjectState extends OWMigrationBase {

    protected $objectStateGroupIdentifier;
    protected $objectStateGroup;
    protected $topPriorityLanguage;

    public function startMigrationOn( $param ) {
        $this->objectStateGroupIdentifier = $param;
        $objectStateGroup = eZContentObjectStateGroup::fetchByIdentifier( $param );
        if( $objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->objectStateGroup = $objectStateGroup;
        }
        $this->topPriorityLanguage = eZContentLanguage::topPriorityLanguage( );
    }

    public function end( ) {
        $this->objectStateGroupIdentifier = NULL;
        $this->objectStateGroup = NULL;
    }

    public function createIfNotExists( ) {
        if( $this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->notice( "Create if not exists : state group '$this->objectStateGroupIdentifier' exists, nothing to do." );
            return;
        }
        $this->db->begin( );
        $this->objectStateGroup = new eZContentObjectStateGroup( );
        $this->objectStateGroup->setAttribute( 'identifier', $this->objectStateGroupIdentifier );
        $this->objectStateGroup->setCurrentLanguage( $this->topPriorityLanguage->attribute( 'locale' ) );
        $translations = $this->objectStateGroup->allTranslations( );
        foreach( $translations as $translation ) {
            $translation->setAttribute( 'name', sfInflector::humanize( $this->objectStateGroupIdentifier ) );
        }
        $this->objectStateGroup->store( );
        $this->db->commit( );
        $this->output->notice( "Create if not exists : state group '$this->objectStateGroupIdentifier' created." );
    }

    public function update( $params ) {
        if( !$this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Update : state group '$this->objectStateGroupIdentifier' not found." );
            return;
        }
        foreach( $params as $key => $value ) {
            if( $this->objectStateGroup->hasAttribute( $key ) ) {
                $this->objectStateGroup->setAttribute( $key, $value );
            } else {
                $translation = $this->objectStateGroup->translationByLocale( $key );
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
        $this->objectStateGroup->store( );
        $this->output->notice( "update : state group '$this->objectStateGroupIdentifier' updated." );
    }

    public function addState( $identifier, $params = array() ) {
        if( !$this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->notice( "Add state : state group '$this->objectStateGroupIdentifier' nou found." );
            return;
        }
        $state = $this->objectStateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $this->output->warning( "Add state : state '$identifier' already exists." );
            return;
        } else {
            $state = $this->objectStateGroup->newState( );
            $state->setAttribute( 'identifier', $identifier );
            $state->setCurrentLanguage( $this->topPriorityLanguage->attribute( 'locale' ) );
            $translations = $state->allTranslations( );
            foreach( $translations as $translation ) {
                $locale = $translation->language( )->attribute( 'locale' );
                $translation->setAttribute( 'name', sfInflector::humanize( $identifier ) );
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
        if( !$this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Update state : state group '$this->objectStateGroupIdentifier' not found." );
            return;
        }
        $state = $this->objectStateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $state = $this->fillStateWithParams( $state, $params );
            $state->store( );
            $this->output->notice( "Update state : state '$identifier' upadted." );
        } else {
            $this->output->warning( "Update state : state '$identifier' not found." );
        }
    }

    public function removeState( $identifier ) {
        if( !$this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Remove state : state group '$this->objectStateGroupIdentifier' not found." );
            return;
        }
        $state = $this->objectStateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $state->remove( );
            $this->output->notice( "Remove state : state '$identifier' removed." );
        } else {
            $this->output->warning( "Remove state : state '$identifier' not found." );
            return;
        }
    }

    public function removeObjectStateGroup( ) {
        if( !$this->objectStateGroup instanceof eZContentObjectStateGroup ) {
            $this->output->error( "Remove state : state group '$this->objectStateGroupIdentifier' not found." );
            return;
        }
        $this->objectStateGroup->remove( );
        $this->output->notice( "Remove state group : state group '$this->objectStateGroupIdentifier' removed." );
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
