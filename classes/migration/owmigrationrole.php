<?php

class OWMigrationRole {

    protected $roleName;
    protected $role;
    protected $output;

    public function __construct( $roleName ) {
        $this->output = eZCLI::instance( );
        $this->roleName = $roleName;
        $role = eZRole::fetchByName( $roleName );
        if( $role instanceof eZRole ) {
            $this->role = $role;
            $this->output->notice( "Role '$roleName' found -> create new version.", TRUE );
        } else {
            $this->role = eZRole::create( $roleName );
            $this->role->store( );
            $this->output->notice( "Role '$roleName' not found -> create new content class.", TRUE );
        }
    }

    public function hasPolicy( $module = '*', $function = '*', $limitation = array() ) {
        $limitation = self::correctLimitationArray( $limitation );
        $currentPolicies = $this->role->accessArray( );
        if( !isset( $currentPolicies[$module][$function] ) ) {
            return FALSE;
        } else {
            if( empty( $limitation ) ) {
                return TRUE;
            } else {
                foreach( $currentPolicies[$module][$function] as $currentLimitation ) {
                    if( $currentLimitation == $limitation ) {
                        return TRUE;
                    }
                }
                return FALSE;
            }
        }
        return FALSE;
    }

    public function addPolicy( $module = '*', $function = '*', $limitation = array() ) {
        $messagePart = empty( $limitation ) ? 'without' : 'with';
        if( !$this->hasPolicy( $module, $function, $limitation ) ) {
            $this->role->appendPolicy( $module, $function, $limitation );
            $this->output->notice( "Policy on $module::$function $messagePart limitation added.", TRUE );
        } else {
            $this->output->notice( "Policy on $module::$function $messagePart limitation already exists.", TRUE );
        }
        $this->role->store( );
    }

    public function removePolicies( $module = FALSE, $function = FALSE, $limitation = FALSE ) {
        if( $module === FALSE ) {
            $this->role->removePolicies( TRUE );
            $this->output->notice( "All policies deleted.", TRUE );
        } elseif( $limitation === FALSE ) {
            $this->role->removePolicy( $module, $function );
            $this->output->notice( "Policies on $module::$function deleted.", TRUE );
        } else {
            $policyList = $this->role->policyList( );
            if( is_array( $policyList ) && count( $policyList ) > 0 ) {
                $db = eZDB::instance( );
                $db->begin( );
                foreach( $policyList as $key => $policy ) {
                    if( is_object( $policy ) ) {
                        if( $policy->attribute( 'module_name' ) == $module && $policy->attribute( 'function_name' ) == $function ) {
                            $accessArray = $policy->accessArray( );
                            if( current( $accessArray[$module][$function] ) == $limitation ) {
                                $policy->removeThis( );
                                unset( $this->role->Policies[$key] );
                                $this->output->notice( "Policies on $module::$function with limitation deleted.", TRUE );
                            }
                        }
                    }
                }

                $db->commit( );
            }

        }
        $this->role->store( );
    }

    protected function correctLimitationArray( $limitationArray ) {
        foreach( $limitationArray as $limitationKey => $limitation ) {
            if( !is_array( $limitation ) ) {
                $limitationArray[$limitationKey] = array( $limitation );
            }
        }
        return $limitationArray;
    }

}
