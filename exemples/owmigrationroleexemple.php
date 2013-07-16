<?php

class OWMigrationRoleExemple extends OWMigration {

    public function up( ) {
        $migration = new OWMigrationRole( );
        $migration->startMigrationOn( 'Mon rôle 1' );
        $migration->createIfNotExists( );
        $migration->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'article'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $migration->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'image'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $migration->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $migration->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'toto' )
        ) );
        $migration->addPolicy( 'content', 'read' );
        $migration->addPolicy( 'content', 'bookmark' );
        $migration->addPolicy( 'ezoe' );

        $migration->assignToUser( 'To To', 'section', 'toto' );
        $migration->assignToUser( 'Ti Ti', 'section', 1 );
        $migration->assignToUser( 'Ti Ti' );
        $migration->assignToUser( 'Ta Ta' );
        $migration->assignToUser( array(
            'Tu Tu',
            'To To'
        ), 'subtree', 2 );
    }

    public function down( ) {
        $migration = new OWMigrationRole( 'Mon rôle 1' );
        $migration->removePolicies( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'article'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $migration->removePolicies( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'toto' )
        ) );
        $migration->removePolicies( 'content', 'read' );
        $migration->removePolicies( 'ezoe' );

        $migration->unassignToUser( 'To To', 'section', 'toto' );
        $migration->unassignToUser( 'Ti Ti', 'section', 1 );
        $migration->unassignToUser( 'Ti Ti' );
        $migration->unassignToUser( 'Tu Tu', 'subtree', 2 );
    }

    protected function getContentClassId( $classIdentifer ) {
        if( is_array( $classIdentifer ) ) {
            $result = array( );
            foreach( $classIdentifer as $identifier ) {
                $result[] = self::getContentClassId( $identifier );
            }
            return $result;
        } else {
            $class = eZContentClass::fetchByIdentifier( $classIdentifer );
            if( $class instanceof eZContentClass ) {
                return $class->attribute( 'id' );
            }
            OWMigrationLogger::logWarning( "Class $classIdentifer not found.", TRUE );
            return NULL;
        }
    }

}
