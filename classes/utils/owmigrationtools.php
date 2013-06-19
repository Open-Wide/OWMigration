<?php

class OWMigrationTools {

    static function getPolicyLimitationArray( $policy ) {
        $returnValue = array( );
        $names = array( );
        if( !$policy ) {
            return $returnValue;
        }

        $currentModule = $policy->attribute( 'module_name' );
        $mod = eZModule::exists( $currentModule );
        if( !is_object( $mod ) ) {
            eZDebug::writeError( 'Failed to fetch instance for module ' . $currentModule );
            return $returnValue;
        }
        $functions = $mod->attribute( 'available_functions' );
        $functionNames = array_keys( $functions );

        $currentFunction = $policy->attribute( 'function_name' );

        foreach( $policy->limitationList() as $limitation ) {
            $valueList = $limitation->attribute( 'values_as_array' );
            $limitation = $functions[$currentFunction][$limitation->attribute( 'identifier' )];
            $limitationValueArray = array( );
            switch( $limitation['name'] ) {
                case 'Class' :
                case 'ParentClass' :
                    foreach( $valueList as $value ) {
                        $contentClass = eZContentClass::fetch( $value, false );
                        if( $contentClass != null ) {
                            $limitationValueArray[] = $contentClass['identifier'];
                        }
                    }
                    break;
                case 'Node' :
                case 'Subtree' :
                    $limitationValueArray = $valueList;
                    break;
                default :
                    if( $limitation && isset( $limitation['class'] ) && count( $limitation['values'] ) == 0 ) {
                        $obj = new $limitation['class']( array( ) );
                        $limitationValueList = call_user_func_array( array(
                            $obj,
                            $limitation['function']
                        ), $limitation['parameter'] );
                        foreach( $limitationValueList as $limitationValue ) {
                            $limitationValueArray[] = $limitationValue['name'];
                        }
                    } else {
                        $limitationValueArray = $valueList;
                    }
                    break;
            }
            sort( $limitationValueArray );
            $returnValue[$limitation['name']] = $limitationValueArray;
        }
        return $returnValue;
    }

    static function correctLimitationArray( $limitationArray ) {
        $trans = eZCharTransform::instance( );
        foreach( $limitationArray as $limitationKey => $limitation ) {
            if( !is_array( $limitation ) ) {
                $limitationArray[$limitationKey] = array( $limitation );
            } else {
                sort( $limitationArray[$limitationKey] );
            }
        }
        return $limitationArray;
    }

    static function compareArray( $array1, $array2 ) {
        $isAssoc1 = array_keys( $array1 ) !== range( 0, count( $array1 ) - 1 );
        $isAssoc2 = array_keys( $array2 ) !== range( 0, count( $array2 ) - 1 );
        if( $isAssoc1 === $isAssoc2 && $isAssoc2 === TRUE ) {
            foreach( $array1 as $key1 => $value1 ) {
                if( array_key_exists( $key1, $array2 ) ) {
                    if( $array2[$key1] != $value1 ) {
                        return FALSE;
                    }
                } else {
                    return FALSE;
                }
            }
        } elseif( $isAssoc1 === $isAssoc2 && $isAssoc2 === FALSE ) {
            return $array1 == $array2;
        } else {
            return FALSE;
        }
        return TRUE;
    }

}
?>