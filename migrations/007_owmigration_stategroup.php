<?php

class OWMigration_007_TestStateGroupObjectState {
    public function up( ) {
        $migration = new OWMigrationObjectState( );
        $migration->startMigrationOn( 'test_state_group_2' );
        $migration->createIfNotExists( );
        $migration->update( array(
            'fre-FR' => array( 'name' => 'Groupe de test' ),
            'default_language_id' => 'fre-FR',
        ) );

        $migration->addState( 'test_etat_1', array( 'fre-FR' => array( 'name' => 'Test état 1' ), ) );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationObjectState( );
        $migration->startMigrationOn( 'test_state_group' );
        $migration->removeObjectState( );
    }

}
?>