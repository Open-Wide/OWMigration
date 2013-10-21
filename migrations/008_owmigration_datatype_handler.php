<?php

class OWMigration_008_NouvelleClasse {

    public function up( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'nouvelle_classe' );
        $migration->createIfNotExists( );

        $migration->name = array(
            'fre-FR' => 'Nouvelle classe',
            'always-available' => 'fre-FR',
        );
        $migration->contentobject_name = '<test_relation_d_objets_1>';

        $migration->addAttribute( 'test_relation_d_objets_2', array(
            'data_type_string' => 'ezobjectrelationlist',
            'name' => array(
                'fre-FR' => 'Test Relation d\'objets 2',
                'always-available' => 'fre-FR',
            )
        ) );
        $migration->updateAttribute( 'test_relation_d_objets_2', array(
            'new_object_class' => 'nouvelle_classe',
            'selection_method' => 'Browse',
            'selection_type' => 'Only new objects',
            'class_constraint_list' => 'abonnement,article,article_mainpage,article_subpage',
            'default_placement' => false
        ) );

        $migration->addToContentClassGroup( 'Test MVE' );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'nouvelle_classe' );
        $migration->removeClass( );
    }

}
?>