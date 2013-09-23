<?php

class OWMigration_v006 {

    public function up( ) {
        $migration = new OWMigrationWorkflow( );
        $migration->startMigrationOn( 'Mon workflow 1' );
        $migration->createIfNotExists( );
        $migration->updateEvent( 'Evénement 1', 'event_ezwaituntildate', array( 'data_int1' => 1 ) );
        $migration->addEvent( 'Evénement 2', 'event_ezwaituntildate' );
        $migration->assignToTrigger( 'content', 'publish', 'after' );
        $migration->end( );

        $migration->startMigrationOn( 'Mon workflow 2' );
        $migration->createIfNotExists( );
        $migration->addEvent( 'Evénement 3', 'event_ezwaituntildate' );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationWorkflow( );
        $migration->startMigrationOn( 'Mon workflow 1' );
        $migration->removeEvent( 'Evénement 2', 'event_ezwaituntildate' );
        $migration->unassignFromTrigger( 'content', 'publish', 'after' );
        $migration->end( );

        $migration->startMigrationOn( 'Mon workflow 2' );
        $migration->removeWorkflow( );
    }

}
