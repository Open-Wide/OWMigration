<?php

class OWMigrationContentClassExemple extends OWMigration {

    public function up( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'my_class' );
        $migration->createIfNotExists();
        $migration->contentobject_name = '<name>';
        $migration->name = array(
            'fre-FR' => 'Ma class',
            'eng-GB' => 'My class'
        );
        $migration->addAttribute( 'name' );
        $migration->addAttribute( 'description', array( 'data_type_string' => 'eztext' ) );
        $migration->updateAttribute( 'name', array(
            'name' => array(
                'fre-FR' => 'Nom',
                'eng-GB' => 'Name'
            ),
            eZStringType::MAX_LEN_FIELD => 100
        ) );
        $migration->addAttribute( 'body', array(
            'name' => array(
                'fre-FR' => 'Corps',
                'eng-GB' => 'Body'
            ),
            'description' => array(
                'fre-FR' => 'Corps de la classe',
                'eng-GB' => 'Body of class'
            ),
            eZStringType::DEFAULT_STRING_FIELD => 'Corps',
            eZStringType::MAX_LEN_FIELD => 100
        ) );
        $migration->addToContentClassGroup( 'Migration classes' );
        $migration->end( );

        $migration->startMigrationOn( 'my_class_2' );
        $migration->createIfNotExists();
        $migration->contentobject_name = '<name>';
        $migration->name = array(
            'fre-FR' => 'Ma class (bis)',
            'eng-GB' => 'My class (bis)'
        );
        $migration->addAttribute( 'name' );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'my_class' );
        $migration->removeAttribute( 'body' );
        $migration->removeFromContentClassGroup( 'Migration classes' );
        $migration->addToContentClassGroup( 'Content' );
        $migration->end( );

        $migration->startMigrationOn( 'my_class_2' );
        $migration->removeClass( );
    }

}
