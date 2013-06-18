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
            $returnValue[$limitation['name']] = $limitationValueArray;
        }
        return $returnValue;
    }

}
?>