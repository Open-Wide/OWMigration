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
            OWScriptLogger::logNotice( "State group '$this->stateGroupIdentifier' exists, nothing to do.", __FUNCTION__ );
            return;
        }
        $this->stateGroup = new eZContentObjectStateGroup( );
        $this->stateGroup->setAttribute( 'identifier', $this->stateGroupIdentifier );
        $this->stateGroup->setAttribute( 'default_language_id', $this->topPriorityLanguage->attribute( 'id' ) );
        $translation = $this->stateGroup->translationByLocale( $this->topPriorityLanguage->attribute( 'locale' ) );
        $translation->setAttribute( 'name', $trans->transformByGroup( $this->stateGroupIdentifier, 'humanize' ) );
        $this->stateGroup->store( );
        $translation->store( );
        OWScriptLogger::logNotice( "State group '$this->stateGroupIdentifier' created.", __FUNCTION__ );
    }

    public function update( $params ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            OWScriptLogger::logError( "State group '$this->stateGroupIdentifier' not found.", __FUNCTION__ );
            return;
        }
        foreach( $params as $key => $value ) {
            if( $this->stateGroup->hasAttribute( $key ) ) {
                if( $key == 'default_language_id' && !is_numeric( $value ) ) {
                    $language = eZContentLanguage::fetchByLocale( $value );
                    $value = $language->attribute( 'id' );
                }
                $this->stateGroup->setAttribute( $key, $value );
                $this->stateGroup->store( );
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
                    $this->stateGroup->store( );
                    $translation->store( );
                } else {
                    OWScriptLogger::logError( "Attribute or translation '$key' not found.", __FUNCTION__ );
                }
            }
        }
        OWScriptLogger::logNotice( "State group '$this->stateGroupIdentifier' updated.", __FUNCTION__ );
    }

    public function addState( $identifier, $params = array() ) {
        $trans = eZCharTransform::instance( );
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            OWScriptLogger::logNotice( "State group '$this->stateGroupIdentifier' nou found.", __FUNCTION__ );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            OWScriptLogger::logWarning( "State '$identifier' already exists.", __FUNCTION__ );
            return;
        } else {
            $state = $this->stateGroup->newState( );
            $state->setAttribute( 'identifier', $identifier );
            $state->setAttribute( 'default_language_id', $this->topPriorityLanguage->attribute( 'id' ) );
            $translation = $state->translationByLocale( $this->topPriorityLanguage->attribute( 'locale' ) );
            $translation->setAttribute( 'name', $trans->transformByGroup( $identifier, 'humanize' ) );
            $state->store( );
            $translation->store( );
            if( !empty( $params ) ) {
                $this->fillStateWithParams( $state, $params );
            }
            $state->store( );
            OWScriptLogger::logNotice( "State $identifier added.", __FUNCTION__ );
        }
    }

    public function updateState( $identifier, $params ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            OWScriptLogger::logError( "State group '$this->stateGroupIdentifier' not found.", __FUNCTION__ );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $this->fillStateWithParams( $state, $params );
            $state->store( );
            OWScriptLogger::logNotice( "State '$identifier' updated.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "State '$identifier' not found.", __FUNCTION__ );
        }
    }

    public function removeState( $identifier ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            OWScriptLogger::logError( "State group '$this->stateGroupIdentifier' not found.", __FUNCTION__ );
            return;
        }
        $state = $this->stateGroup->stateByIdentifier( $identifier );
        if( $state instanceof eZContentObjectState ) {
            $state->remove( );
            OWScriptLogger::logNotice( "State '$identifier' removed.", __FUNCTION__ );
        } else {
            OWScriptLogger::logWarning( "State '$identifier' not found.", __FUNCTION__ );
            return;
        }
    }

    public function removeStateGroup( ) {
        if( !$this->stateGroup instanceof eZContentObjectStateGroup ) {
            OWScriptLogger::logError( "State group '$this->stateGroupIdentifier' not found.", __FUNCTION__ );
            return;
        }
        foreach( $this->stateGroup->states() as $objectState ) {
            foreach( $objectState->allTranslations() as $translation ) {
                $translation->remove( );
            }
            $objectState->remove( );
        }
        foreach( $this->stateGroup->allTranslations() as $translation ) {
            $translation->remove( );
        }
        $this->stateGroup->remove( );
        OWScriptLogger::logNotice( "State group '$this->stateGroupIdentifier' removed.", __FUNCTION__ );
    }

    protected function fillStateWithParams( $state, $params ) {
        foreach( $params as $key => $value ) {
            if( $key == 'default_language' ) {
                $language = eZContentLanguage::fetchByLocale( $value );
                $state->setAttribute( 'default_language_id', $language->attribute( 'id' ) );
            } elseif( $state->hasAttribute( $key ) ) {
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
                    OWScriptLogger::logWarning( "Attribute or translation '$key' not found.", __FUNCTION__ );
                }
            }
        }
    }

}
