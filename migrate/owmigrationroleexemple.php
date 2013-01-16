<?php

class OWMigrationRoleExemple extends OWMigration {

    public function up( ) {
        $role = new OWMigrationRole( 'Mon rôle 1' );
        $role->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'article'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $role->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'image'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $role->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $role->addPolicy( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'toto' )
        ) );
        $role->addPolicy( 'content', 'read' );
        $role->addPolicy( 'content', 'bookmark' );
        $role->addPolicy( 'ezoe' );

        $role->assignToUser( 'To To', 'section', 'toto' );
        $role->assignToUser( 'Ti Ti', 'section', 1 );
        $role->assignToUser( 'Ti Ti' );
        $role->assignToUser( array(
            'Tu Tu',
            'To To'
        ), 'subtree', 2 );
    }

    public function down( ) {
        $role = new OWMigrationRole( 'Mon rôle 1' );
        $role->removePolicies( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'article'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
        $role->removePolicies( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'file'
            ) ),
            'ParentClass' => self::getContentClassId( 'toto' )
        ) );
        $role->removePolicies( 'content', 'read' );
        $role->removePolicies( 'ezoe' );

        $role->unassignToUser( 'To To', 'section', 'toto' );
        $role->unassignToUser( 'Ti Ti', 'section', 1 );
        $role->unassignToUser( 'Ti Ti' );
        $role->unassignToUser( 'Tu Tu', 'subtree', 2 );
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
            $this->output->warning( "Class $classIdentifer not found.", TRUE );
            return NULL;
        }
    }

}
