<form method="post" action={'owmigration/codegenerator_roles'|ezurl()}>
    <div class="context-block">
        <div class="box-header">
            <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
                                <h1 class="context-title">{'Migrate role'|i18n('owmigration/roles' )}</h1>
                                <div class="header-mainline"></div>
                            </div></div></div></div></div>
        </div>
        <div class="box-ml"><div class="box-mr">
                <div class="box-content">
                    <div class="context-toolbar"></div>
                    <div class="context-attributes">
                        <div class="block">
                            <label for="RoleID">{'Roles list'|i18n( 'owmigration/roles' )}
                                <select name="RoleID" id="RoleID">
                                    {foreach $rolelist as $role}
                                        <option value="{$role.id|wash()}"{cond( $role_id|eq( $role.id ), ' selected="selected"' , '' )}>{$role.name|wash()}</option>
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
{if $role_id}
    {$role_id|display_role_migration_class()}
{/if}
