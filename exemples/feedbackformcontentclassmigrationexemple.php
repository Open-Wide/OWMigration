<?php

// This code is generated by OWMigration

class FeedbackFormContentClassMigrationExemple extends OWMigration {

    public function up( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'feedback_form' );
        $migration->createIfNotExists( );

        $migration->name = array(
            'eng-GB' => 'Feedback form',
            'always-available' => 'eng-GB',
        );
        $migration->contentobject_name = '<name>';
        $migration->is_container = TRUE;

        $migration->addAttribute( 'name', array( 
            'data_type_string' => 'ezstring',
            'name' => array(
                'eng-GB' => 'Name',
                'always-available' => 'eng-GB',
            ),
            'is_required' => TRUE,
        ) );
        $migration->addAttribute( 'description', array( 
            'data_type_string' => 'ezxmltext',
            'name' => array(
                'eng-GB' => 'Description',
                'always-available' => 'eng-GB',
            ),
            'data_int1' => 10,
        ) );
        $migration->addAttribute( 'sender_name', array( 
            'data_type_string' => 'ezstring',
            'name' => array(
                'eng-GB' => 'Sender name',
                'always-available' => 'eng-GB',
            ),
            'is_searchable' => FALSE,
            'is_required' => TRUE,
            'can_translate' => FALSE,
            'is_information_collector' => TRUE,
        ) );
        $migration->addAttribute( 'subject', array( 
            'data_type_string' => 'ezstring',
            'name' => array(
                'eng-GB' => 'Subject',
                'always-available' => 'eng-GB',
            ),
            'is_required' => TRUE,
            'is_information_collector' => TRUE,
        ) );
        $migration->addAttribute( 'message', array( 
            'data_type_string' => 'eztext',
            'name' => array(
                'eng-GB' => 'Message',
                'always-available' => 'eng-GB',
            ),
            'is_required' => TRUE,
            'is_information_collector' => TRUE,
            'data_int1' => 10,
        ) );
        $migration->addAttribute( 'email', array( 
            'data_type_string' => 'ezemail',
            'name' => array(
                'eng-GB' => 'Email',
                'always-available' => 'eng-GB',
            ),
            'is_searchable' => FALSE,
            'is_required' => TRUE,
            'can_translate' => FALSE,
            'is_information_collector' => TRUE,
        ) );
        $migration->addAttribute( 'recipient', array( 
            'data_type_string' => 'ezemail',
            'name' => array(
                'eng-GB' => 'Recipient',
                'always-available' => 'eng-GB',
            ),
            'is_searchable' => FALSE,
            'can_translate' => FALSE,
        ) );

        $migration->addToContentClassGroup( 'Formulaires' );
        $migration->end( );
    }

    public function down( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'feedback_form' );
        $migration->removeClass( );
    }
}

?>
