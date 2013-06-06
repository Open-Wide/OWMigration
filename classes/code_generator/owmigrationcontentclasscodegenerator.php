<?php

class OWMigrationContentClassCodeGenerator {

    static function getMigrationClass( $classIdentifier ) {
        $contentClass = eZContentClass::fetchByIdentifier( $classIdentifier );
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= "class " . sfInflector::camelize( $contentClass->attribute( 'identifier' ) ) . "ContentClassMigration extends OWMigration {" . PHP_EOL . PHP_EOL;
        $code .= self::getUpMethod( $contentClass );
        $code .= self::getDownMethod( $contentClass );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "?>";
        return $code;
    }

    static function getUpMethod( $contentClass ) {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= "\t\t\$migration->startMigrationOn( '" . $contentClass->attribute( 'identifier' ) . "' );" . PHP_EOL;
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;
        //'name'
        if( $contentClass->attribute( 'name' ) ) {
            $code .= "\t\t\$migration->name = array(" . PHP_EOL;
            foreach( $contentClass->attribute( 'nameList' ) as $key => $value ) {
                $code .= "\t\t\t'" . $key . "' => '" . $value . "'," . PHP_EOL;
            }
            $code .= "\t\t);" . PHP_EOL;
        }
        //'description'
        if( $contentClass->attribute( 'description' ) != '' ) {
            $code .= "\t\t\$migration->description = array(" . PHP_EOL;
            foreach( $contentClass->attribute( 'descriptionList' ) as $key => $value ) {
                $code .= "\t\t\t'" . $key . "' => '" . $value . "'," . PHP_EOL;
            }
            $code .= "\t\t);" . PHP_EOL;
        }
        //'contentobject_name'
        if( $contentClass->attribute( 'contentobject_name' ) != '' ) {
            $code .= "\t\t\$migration->contentobject_name = '" . $contentClass->attribute( 'contentobject_name' ) . "';" . PHP_EOL;
        }
        //'url_alias_name'
        if( $contentClass->attribute( 'url_alias_name' ) != '' ) {
            $code .= "\t\t\$migration->url_alias_name = '" . $contentClass->attribute( 'url_alias_name' ) . "';" . PHP_EOL;
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
            $code .= "\t\t\$migration->sort_field = " . $contentClass->attribute( 'sort_field' ) . ";" . PHP_EOL;
        }
        //'always_available'
        if( $contentClass->attribute( 'sort_order' ) == FALSE ) {
            $code .= "\t\t\$migration->sort_order = FALSE;" . PHP_EOL;
        }
        $code .= PHP_EOL;
        $attributesList = $contentClass->fetchAttributes( );
        foreach( $attributesList as $attribute ) {
            $code .= "\t\t\$migration->addAttribute( '" . $attribute->attribute( 'identifier' ) . "', array( " . PHP_EOL;
            //'sort_field'
            if( $attribute->attribute( 'data_type_string' ) != 'ezstring' ) {
                $code .= "\t\t\t'data_type_string' => '" . $attribute->attribute( 'data_type_string' ) . "'," . PHP_EOL;
            }
            //'name'
            if( $attribute->attribute( 'name' ) != '' ) {
                $code .= "\t\t\t'name' => array(" . PHP_EOL;
                foreach( $attribute->attribute( 'nameList' ) as $key => $value ) {
                    $code .= "\t\t\t\t'" . $key . "' => '" . $value . "'," . PHP_EOL;
                }
                $code .= "\t\t\t)," . PHP_EOL;
            }
            //'description'
            if( $attribute->attribute( 'description' ) != '' ) {
                $code .= "\t\t\t'description' => array(" . PHP_EOL;
                foreach( $attribute->attribute( 'descriptionList' ) as $key => $value ) {
                    $code .= "\t\t\t\t'" . $key . "' => '" . $value . "'," . PHP_EOL;
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
                $code .= "\t\t\t'data_int1' => " . $attribute->attribute( 'data_int1' ) . "," . PHP_EOL;
            }
            //'data_int2'
            if( $attribute->attribute( 'data_int2' ) ) {
                $code .= "\t\t\t'data_int2' => " . $attribute->attribute( 'data_int2' ) . "," . PHP_EOL;
            }
            //'data_int3'
            if( $attribute->attribute( 'data_int3' ) ) {
                $code .= "\t\t\t'data_int3' => " . $attribute->attribute( 'data_int3' ) . "," . PHP_EOL;
            }
            //'data_int4'
            if( $attribute->attribute( 'data_int4' ) ) {
                $code .= "\t\t\t'data_int4' => " . $attribute->attribute( 'data_int4' ) . "," . PHP_EOL;
            }
            //'data_float1'
            if( $attribute->attribute( 'data_float1' ) ) {
                $code .= "\t\t\t'data_float1' => " . $attribute->attribute( 'data_float1' ) . "," . PHP_EOL;
            }
            //'data_float2'
            if( $attribute->attribute( 'data_float2' ) ) {
                $code .= "\t\t\t'data_float2' => " . $attribute->attribute( 'data_float2' ) . "," . PHP_EOL;
            }
            //'data_float3'
            if( $attribute->attribute( 'data_float3' ) ) {
                $code .= "\t\t\t'data_float3' => " . $attribute->attribute( 'data_float3' ) . "," . PHP_EOL;
            }
            //'data_float4'
            if( $attribute->attribute( 'data_float4' ) ) {
                $code .= "\t\t\t'data_float4' => " . $attribute->attribute( 'data_float4' ) . "," . PHP_EOL;
            }
            //'data_text1'
            if( $attribute->attribute( 'data_text1' ) ) {
                $code .= "\t\t\t'data_text1' => '" . $attribute->attribute( 'data_text1' ) . "'," . PHP_EOL;
            }
            //'data_text2'
            if( $attribute->attribute( 'data_text2' ) ) {
                $code .= "\t\t\t'data_text2' => '" . $attribute->attribute( 'data_text2' ) . "'," . PHP_EOL;
            }
            //'data_text3'
            if( $attribute->attribute( 'data_text3' ) ) {
                $code .= "\t\t\t'data_text3' => '" . $attribute->attribute( 'data_text3' ) . "'," . PHP_EOL;
            }
            //'data_text4'
            if( $attribute->attribute( 'data_text4' ) ) {
                $code .= "\t\t\t'data_text4' => '" . $attribute->attribute( 'data_text4' ) . "'," . PHP_EOL;
            }
            //'data_text5'
            if( $attribute->attribute( 'data_text5' ) ) {
                $code .= "\t\t\t'data_text5' => '" . $attribute->attribute( 'data_text5' ) . "'," . PHP_EOL;
            }
            //'category'
            if( $attribute->attribute( 'category' ) ) {
                $code .= "\t\t\t'category' => " . $attribute->attribute( 'category' ) . "," . PHP_EOL;
            }
            $code .= "\t\t) );" . PHP_EOL;
        }
        $code .= PHP_EOL;
        foreach( $contentClass->attribute( 'ingroup_list' ) as $classGroup ) {
            $code .= "\t\t\$migration->addToContentClassGroup( '" . $classGroup->attribute( 'group_name' ) . "' );" . PHP_EOL;
        }
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $contentClass ) {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= "\t\t\$migration->startMigrationOn( '" . $contentClass->attribute( 'identifier' ) . "' );" . PHP_EOL;
        $code .= "\t\t\$migration->removeClass( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}
?>