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
        $role->addPolicy( 'ezoe' );
    }

    public function down( ) {
        $role = new OWMigrationRole( 'Mon rôle 1' );
        $role->removePolicies( 'content', 'create', array(
            'Class' => self::getContentClassId( array(
                'folder',
                'image'
            ) ),
            'ParentClass' => self::getContentClassId( 'folder' )
        ) );
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
