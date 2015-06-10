<?php

class OWMigration_007_TestStateGroupObjectState
{

    public function up()
    {
        $migration = new OWMigrationStateGroup( );
        $migration->startMigrationOn( 'test_state_group_1' );
        $migration->createIfNotExists();
        $migration->update( array(
            'fre-FR' => array( 'name' => 'Groupe de test' ),
            'default_language_id' => 'fre-FR',
        ) );

        $migration->addState( 'test_etat_1', array( 'fre-FR' => array( 'name' => 'Test état 1' ) ) );
        $migration->addState( 'test_etat_2' );
        $migration->addState( 'test_etat_3' );
        $migration->updateState( 'test_etat_1', array( 'fre-FR' => array( 'name' => 'Test état 1 (bis)' ) ) );
        $migration->removeState( 'test_etat_2' );
        $migration->addState( 'test_etat_4', array( 'priority' => 1 ) );
        $migration->end();
    }

    public function down()
    {
        $migration = new OWMigrationStateGroup( );
        $migration->startMigrationOn( 'test_state_group_1' );
        $migration->removeStateGroup();
    }

}

