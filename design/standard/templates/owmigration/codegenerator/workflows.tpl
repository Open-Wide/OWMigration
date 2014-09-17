<form method="post" action={'owmigration/codegenerator_workflows'|ezurl()}>
    <div class="context-block">
        <div class="box-header">
            <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
	            <h1 class="context-title">{'Migrate workflow'|i18n('owmigration/workflows' )}</h1>
	            <div class="header-mainline"></div>
	        </div></div></div></div></div>
        </div>
        <div class="box-ml"><div class="box-mr">
            <div class="box-content">
                <div class="context-toolbar"></div>
                <div class="context-attributes">
                    <div class="block">
                        <label for="WorkflowID">{'Workflows list'|i18n( 'owmigration/workflows' )}
                        <select name="WorkflowID" id="WorkflowID">
                            {foreach $workflowlist as $workflow}
                                <option value="{$workflow.id|wash()}"{cond( $workflow_id|eq( $workflow.id ), ' selected="selected"' , '' )}>{$workflow.name|wash()}</option>
                            {/foreach}
                        </select></label>
                    </div>
                </div>
            </div>
        </div></div>
        <div class="controlbar">
            <div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
                <div class="block">
                    <input class="button" type="submit" name="ActionGenerateCode" value="{'Display code'|i18n( 'owmigration/all' )}" />
                    <input class="button" type="submit" name="ActionExportCode" value="{'Export code'|i18n( 'owmigration/all' )}" />
                    <input class="defaultbutton" type="submit" name="ActionExportAllClassCode" value="{'Export all classes code'|i18n( 'owmigration/all' )}" />
                </div>
            </div></div></div></div></div></div>
        </div>
    </div>
</form>
{if $workflow_id}
    {$workflow_id|display_workflow_migration_class()}
{/if}
