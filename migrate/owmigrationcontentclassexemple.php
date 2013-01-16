<?php

class OWMigrationContentClassExemple extends OWMigration {

    public function up( ) {
        $contentClass = new OWMigrationContentClass( 'my_class' );
        $contentClass->contentobject_name = '<name>';
        $contentClass->name = array(
                'fre-FR' => 'Ma class',
                'eng-GB' => 'My class'
        );
        $contentClass->addAttribute( 'name' );
        $contentClass->addAttribute( 'description', array( 'data_type_string' => 'eztext' ) );
        $contentClass->updateAttribute( 'name', array(
                'name' => array(
                        'fre-FR' => 'Nom',
                        'eng-GB' => 'Name'
                ),
                eZStringType::MAX_LEN_FIELD => 100
        ) );
        $contentClass->addAttribute( 'body', array(
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
        $contentClass->addToContentClassGroup( 'Migration classes' );
        $contentClass->save( );
    }

    public function down( ) {
        $contentClass = new OWMigrationContentClass( 'my_class' );
        $contentClass->removeAttribute( 'body' );
        $contentClass->removeFromContentClassGroup( 'Migration classes' );
        $contentClass->addToContentClassGroup( 'Content' );
    }

}
