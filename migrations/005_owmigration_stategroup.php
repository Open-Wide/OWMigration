<?php

class OWMigration_v005 {

    public function up( ) {
        $migration = new OWMigrationStateGroup( );
        $migration->startMigrationOn( 'my_state' );
        $migration->createIfNotExists( );
        $migration->update( array(
            'fre-FR' => array( 'name' => 'Mon état' ),
            'eng-GB' => array( 'description' => 'My super state' ),
            'xxx-YY' => array( 'description' => 'My super state' )
        ) );
        $migration->addState( 'my_state_1' );
        $migration->addState( 'my_state_2', array(
            'fre-FR' => 'Toto',
            'eng-GB' => 'Titi'
        ) );
        $migration->updateState( 'my_state_1', array(
            'fre-FR' => 'Tutu',
            'eng-GB' => array(
                'name' => 'Tata',
                'description' => 'Super Tata'
            )
        ) );
        $migration->updateState( 'my_state_3', array(
            'fre-FR' => 'Tutu',
            'eng-GB' => array(
                'name' => 'Tata',
                'description' => 'Super Tata'
            )
        ) );
        $migration->removeState( 'my_state_1' );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationStateGroup( );
        $migration->startMigrationOn( 'my_state' );
        $migration->removeStateGroup( );
    }

}
