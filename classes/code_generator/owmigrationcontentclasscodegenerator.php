<?php

class OWMigrationContentClassCodeGenerator extends OWMigrationCodeGenerator {

    static function getMigrationClassFile( $classIdentifier, $dir ) {
        $filename = self::generateSafeFileName( $classIdentifier . 'contentclassmigration.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, OWMigrationRoleCodeGenerator::getMigrationClass( $roleIdentifier ) );
        return $filepath;
    }

    static function getMigrationClass( $classIdentifier ) {
        $trans = eZCharTransform::instance( );
        $contentClass = eZContentClass::fetchByIdentifier( $classIdentifier );
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class %sContentClassMigration extends OWMigration {" . PHP_EOL . PHP_EOL, $trans->transformByGroup( $contentClass->attribute( 'identifier' ), 'camelize' ) );
        $code .= self::getUpMethod( $contentClass );
        $code .= self::getDownMethod( $contentClass );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "?>";
        return $code;
    }

    static function getUpMethod( $contentClass ) {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $contentClass->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;
        //'name'
        if( $contentClass->attribute( 'name' ) ) {
            $code .= "\t\t\$migration->name = array(" . PHP_EOL;
            foreach( $contentClass->attribute( 'nameList' ) as $key => $value ) {
                $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $key ), self::escapeString( $value ) );
            }
            $code .= "\t\t);" . PHP_EOL;
        }
        //'description'
        if( $contentClass->attribute( 'description' ) != '' ) {
            $code .= "\t\t\$migration->description = array(" . PHP_EOL;
            foreach( $contentClass->attribute( 'descriptionList' ) as $key => $value ) {
                $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $key ), self::escapeString( $value ) );
            }
            $code .= "\t\t);" . PHP_EOL;
        }
        //'contentobject_name'
        if( $contentClass->attribute( 'contentobject_name' ) != '' ) {
            $code .= sprintf( "\t\t\$migration->contentobject_name = '%s';" . PHP_EOL, self::escapeString( $contentClass->attribute( 'contentobject_name' ) ) );
        }
        //'url_alias_name'
        if( $contentClass->attribute( 'url_alias_name' ) != '' ) {
            $code .= sprintf( "\t\t\$migration->url_alias_name = '%s';" . PHP_EOL, self::escapeString( $contentClass->attribute( 'url_alias_name' ) ) );
        }
        //'is_container'
        if( $contentClass->attribute( 'is_container' ) == TRUE ) {
            $code .= "\t\t\$migration->is_container = TRUE;" . PHP_EOL;
        }
        //'always_available'
        if( $contentClass->attribute( 'always_available' ) == TRUE ) {
            $code .= "\t\t\$migration->always_available = TRUE;" . PHP_EOL;
        }
        //'sort_field'
        if( $contentClass->attribute( 'sort_field' ) != 1 ) {
            $code .= sprintf( "\t\t\$migration->sort_field = %s;" . PHP_EOL, self::escapeString( $contentClass->attribute( 'sort_field' ) ) );
        }
        //'always_available'
        if( $contentClass->attribute( 'sort_order' ) == FALSE ) {
            $code .= "\t\t\$migration->sort_order = FALSE;" . PHP_EOL;
        }
        $code .= PHP_EOL;
        $attributesList = $contentClass->fetchAttributes( );
        foreach( $attributesList as $attribute ) {
            $code .= sprintf( "\t\t\$migration->addAttribute( '%s', array( " . PHP_EOL, self::escapeString( $attribute->attribute( 'identifier' ) ) );
            //'sort_field'
            if( $attribute->attribute( 'data_type_string' ) != 'ezstring' ) {
                $code .= sprintf( "\t\t\t'data_type_string' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_type_string' ) ) );
            }
            //'name'
            if( $attribute->attribute( 'name' ) != '' ) {
                $code .= "\t\t\t'name' => array(" . PHP_EOL;
                foreach( $attribute->attribute( 'nameList' ) as $key => $value ) {
                    $code .= sprintf( "\t\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $key ), self::escapeString( $value ) );
                }
                $code .= "\t\t\t)," . PHP_EOL;
            }
            //'description'
            if( $attribute->attribute( 'description' ) != '' ) {
                $code .= "\t\t\t'description' => array(" . PHP_EOL;
                foreach( $attribute->attribute( 'descriptionList' ) as $key => $value ) {
                    $code .= sprintf( "\t\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $key ), self::escapeString( $value ) );
                }
                $code .= "\t\t\t)," . PHP_EOL;
            }
            //'is_searchable'
            if( $attribute->attribute( 'is_searchable' ) == FALSE ) {
                $code .= "\t\t\t'is_searchable' => FALSE," . PHP_EOL;
            }
            //'is_required'
            if( $attribute->attribute( 'is_required' ) == TRUE ) {
                $code .= "\t\t\t'is_required' => TRUE," . PHP_EOL;
            }
            //'can_translate'
            if( $attribute->attribute( 'can_translate' ) == FALSE ) {
                $code .= "\t\t\t'can_translate' => FALSE," . PHP_EOL;
            }
            //'is_information_collector'
            if( $attribute->attribute( 'is_information_collector' ) == TRUE ) {
                $code .= "\t\t\t'is_information_collector' => TRUE," . PHP_EOL;
            }
            //'data_int1'
            if( $attribute->attribute( 'data_int1' ) ) {
                $code .= sprintf( "\t\t\t'data_int1' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_int1' ) ) );
            }
            //'data_int2'
            if( $attribute->attribute( 'data_int2' ) ) {
                $code .= sprintf( "\t\t\t'data_int2' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_int2' ) ) );
            }
            //'data_int3'
            if( $attribute->attribute( 'data_int3' ) ) {
                $code .= sprintf( "\t\t\t'data_int3' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_int3' ) ) );
            }
            //'data_int4'
            if( $attribute->attribute( 'data_int4' ) ) {
                $code .= sprintf( "\t\t\t'data_int4' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_int4' ) ) );
            }
            //'data_float1'
            if( $attribute->attribute( 'data_float1' ) ) {
                $code .= sprintf( "\t\t\t'data_float1' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_float1' ) ) );
            }
            //'data_float2'
            if( $attribute->attribute( 'data_float2' ) ) {
                $code .= sprintf( "\t\t\t'data_float2' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_float2' ) ) );
            }
            //'data_float3'
            if( $attribute->attribute( 'data_float3' ) ) {
                $code .= sprintf( "\t\t\t'data_float3' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_float3' ) ) );
            }
            //'data_float4'
            if( $attribute->attribute( 'data_float4' ) ) {
                $code .= sprintf( "\t\t\t'data_float4' => %s," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_float4' ) ) );
            }
            //'data_text1'
            if( $attribute->attribute( 'data_text1' ) ) {
                $code .= sprintf( "\t\t\t'data_text1' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_text1' ) ) );
            }
            //'data_text2'
            if( $attribute->attribute( 'data_text2' ) ) {
                $code .= sprintf( "\t\t\t'data_text2' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_text2' ) ) );
            }
            //'data_text3'
            if( $attribute->attribute( 'data_text3' ) ) {
                $code .= sprintf( "\t\t\t'data_text3' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_text3' ) ) );
            }
            //'data_text4'
            if( $attribute->attribute( 'data_text4' ) ) {
                $code .= sprintf( "\t\t\t'data_text4' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_text4' ) ) );
            }
            //'data_text5'
            if( $attribute->attribute( 'data_text5' ) ) {
                $code .= sprintf( "\t\t\t'data_text5' => '%s'," . PHP_EOL, self::escapeString( $attribute->attribute( 'data_text5' ) ) );
            }
            //'category'
            if( $attribute->attribute( 'category' ) ) {
                $code .= sprintf( "\t\t\t'category' => '%s'" . PHP_EOL, self::escapeString( $attribute->attribute( 'category' ) ) );
            }
            $code .= "\t\t) );" . PHP_EOL;
        }
        $code .= PHP_EOL;
        foreach( $contentClass->attribute( 'ingroup_list' ) as $classGroup ) {
            $code .= sprintf( "\t\t\$migration->addToContentClassGroup( '%s' );" . PHP_EOL, self::escapeString( $classGroup->attribute( 'group_name' ) ) );
        }
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $contentClass ) {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $contentClass->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->removeClass( );" . PHP_EOL;
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}
?>