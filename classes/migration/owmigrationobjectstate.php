<?php

class OWMigrationObjectState extends OWMigrationBase {

    protected $objectStateGroupIdentifier;
    protected $objectStateGroup;

    public function startMigrationOn( $param ) {
        $this->objectStateGroupIdentifier = $param;
    }

    public function end( ) {
        $this->objectStateGroupIdentifier = NULL;
        $this->objectStateGroup = NULL;
    }

    public function createIfNotExists( ) {
    }

    public function addState( $identifier, $params ) {

    }

    public function updateState( $identifier, $params ) {

    }

    public function removeState( $identifier ) {

    }

    public function removeObjectStateGroup( ) {

    }

}
