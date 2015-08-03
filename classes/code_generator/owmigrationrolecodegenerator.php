<?php

class OWMigrationRoleCodeGenerator extends OWMigrationCodeGenerator
{

    static function getMigrationClassFile( $role, $dir )
    {
        if( is_numeric( $role ) )
        {
            $role = eZRole::fetch( $role );
        }
        if( !$role instanceof eZRole )
        {
            return FALSE;
        }
        self::createDirectory( $dir );
        $filename = self::generateSafeFileName( $role->attribute( 'name' ) . '_role.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, OWMigrationRoleCodeGenerator::getMigrationClass( $role ) );
        return $filepath;
    }

    static function getMigrationClass( $role )
    {
        if( is_numeric( $role ) )
        {
            $role = eZRole::fetch( $role );
        }
        if( !$role instanceof eZRole )
        {
            return FALSE;
        }
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class myExtension_xxx_%sRole {" . PHP_EOL, self::generateClassName( $role->attribute( 'name' ) ) );
        $code .= self::getUpMethod( $role );
        $code .= self::getDownMethod( $role );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "";
        return $code;
    }

    static function getUpMethod( $role )
    {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationRole( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $role->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;
        foreach( $role->policyList() as $policy )
        {
            $code .= sprintf( "\t\t\$migration->addPolicy( '%s', '%s'", self::escapeString( $policy->attribute( 'module_name' ) ), self::escapeString( $policy->attribute( 'function_name' ) ) );
            $policyLimitationArray = OWMigrationTools::getPolicyLimitationArray( $policy );
            if( count( $policyLimitationArray ) > 0 )
            {
                $code .= ", array(" . PHP_EOL;
                foreach( $policyLimitationArray as $limitationKey => $limitationValue )
                {
                    if( is_array( $limitationValue ) )
                    {
                        $limitationValue = array_map( "self::escapeString", $limitationValue );
                        $arrayString = "array(\n\t\t\t\t'" . implode( "',\n\t\t\t\t'", $limitationValue ) . "'\n\t\t\t )";
                        $code .= sprintf( "\t\t\t'%s' => %s," . PHP_EOL, self::escapeString( $limitationKey ), $arrayString );
                    } else
                    {
                        $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $limitationKey ), $limitationValue );
                    }
                }
                $code .= "\t\t) );" . PHP_EOL;
            } else
            {
                $code .= " );" . PHP_EOL;
            }
        }
        foreach( $role->fetchUserByRole() as $roleAssignationArray )
        {
            $roleAssignation = $roleAssignationArray['user_object'];
            if( $roleAssignation->attribute( 'class_identifier' ) == 'user_group' )
            {
                $code .= sprintf( "\t\t\$migration->assignToUserGroup( '%s'", self::escapeString( $roleAssignation->attribute( 'name' ) ) );
            } else
            {
                $code .= sprintf( "\t\t\$migration->assignToUser( '%s'", self::escapeString( $roleAssignation->attribute( 'name' ) ) );
            }
            if( !empty( $roleAssignationArray['limit_ident'] ) )
            {
                if( $roleAssignationArray['limit_ident'] == 'Subtree' )
                {
                    $subtreeNode = eZContentObjectTreeNode::fetchByPath( $roleAssignationArray['limit_value'], false );
                    if( $subtreeNode )
                    {
                        $roleAssignationArray['limit_value'] = $subtreeNode['path_identification_string'];
                    }
                }
                $code .= sprintf( ", '%s', '%s'", self::escapeString( $roleAssignationArray['limit_ident'] ), self::escapeString( $roleAssignationArray['limit_value'] ) );
            }
            $code .= " );" . PHP_EOL;
        }
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $role )
    {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationRole( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $role->attribute( 'name' ) ) );
        $code .= "\t\t\$migration->removeRole( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}

