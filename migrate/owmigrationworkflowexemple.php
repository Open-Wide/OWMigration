<?php

class OWMigrationWorkflowExemple extends OWMigration {

    public function up( ) {
        $workflow = new OWMigrationWorkflow( 'Mon workflow 1' );
        $workflow->updateEvent( 'Evénement 1', 'event_ezwaituntildate', array( 'data_int1' => 1 ) );
        $workflow->addEvent( 'Evénement 2', 'event_ezwaituntildate' );
        $workflow->assignToTrigger( 'content', 'publish', 'after' );
        $workflow->save( );
        
        
        $workflow = new OWMigrationWorkflow( 'Mon workflow 2' );
        $workflow->addEvent( 'Evénement 3', 'event_ezwaituntildate' );
        $workflow->save( );
    }

    public function down( ) {
        $workflow = new OWMigrationWorkflow( 'Mon workflow 1' );
        $workflow->removeEvent( 'Evénement 2', 'event_ezwaituntildate' );
        
        OWMigrationWorkflow::unassignTrigger( 'content', 'publish', 'after' );
        
        OWMigrationWorkflow::removeWorkflow( 'Mon workflow 2' );
    }

}
