<?php

class OWMigrationOperators {
    /*!
     Constructor
     */
    function OWMigrationOperators( ) {
        $this->Operators = array(
            'camelize',
            'display_content_migration_class'
        );
    }

    /*!
     Returns the operators in this class.
     */
    function & operatorList( ) {
        return $this->Operators;
    }

    /*!
     \return true to tell the template engine that the parameter list
     exists per operator type, this is needed for operator classes
     that have multiple operators.
     */
    function namedParameterPerOperator( ) {
        return true;
    }

    /*!
     Both operators have one parameter.
     See eZTemplateOperator::namedParameterList()
     */
    function namedParameterList( ) {

        return array(
            'camelize' => array( ),
            'display_content_migration_class' => array( )
        );
    }

    /*!
     \Executes the needed operator(s).
     \Checks operator names, and calls the appropriate functions.
     */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters ) {
        switch ( $operatorName ) {
            case 'camelize' :
                $operatorValue = $this->camelize( $operatorValue );
                break;
            case 'display_content_migration_class' :
                $operatorValue = $this->displayContentMigrationClass( $operatorValue );
                break;
        }
    }

    function camelize( $operatorValue ) {
        return sfInflector::camelize( $operatorValue );
    }

    function displayContentMigrationClass( $operatorValue ) {
        $geshi = new GeSHi(OWMigrationContentClassCodeGenerator::getMigrationClass( $operatorValue ), 'php');
        return $geshi->parse_code();
    }

    /// \privatesection
    var $Operators;
}
?>