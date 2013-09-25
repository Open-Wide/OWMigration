{ezcss_require( 'owmigration.css' )}
{ezscript_require( 'owmigration.js' )}
<div class="context-block">
    <div class="box-header">
        <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
            <h1 class="context-title">{'Migration dashboard'|i18n('owmigration/dashboard' )}</h1>
            <div class="header-mainline"></div>
        </div></div></div></div></div>
    </div>
    <div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
        <div class="box-content">
           <div class="context-toolbar"></div>
           <div class="block">
               {if $extension_list|count()}
                   {def $tmp_version = 0}
                   <div class="yui-dt">
                        <table class="list">
                            <thead>
                                <tr class="yui-dt-first yui-dt-last">
                                    <th><div class="yui-dt-liner">{'Extension'|i18n('owmigration/dashboard' )}</div></th>
                                    <th><div class="yui-dt-liner">{'Current version'|i18n('owmigration/dashboard' )}</div></th>
                                    <th><div class="yui-dt-liner">{'Latest version'|i18n('owmigration/dashboard' )}</div></th>
                                    <th><div class="yui-dt-liner"></div></th>
                                </tr>
                            </thead>
                            <tbody class="yui-dt-data">
                                {foreach $extension_list as $index => $extension sequence array( 'yui-dt-even', 'yui-dt-odd' ) as $style}
                                    {set $tmp_version = 0}
                                    <tr class="{if $index|eq(0)}yui-dt-first{/if} {$style}">
                                        <td><div class="yui-dt-liner">{$extension.name}</div></td>
                                        <td><div class="yui-dt-liner">{$extension.current_version}</div></td>
                                        <td><div class="yui-dt-liner">{$extension.latest_version}</div></td>
                                        <td><div class="yui-dt-liner">
                                            {if $extension.latest_version|gt(0)}
                                                <a class="display_log_control" href="#" ref="row_{$index}" show_title="{'View all versions'|i18n('owmigration/dashboard' )}" hide_title="{'Hide all versions'|i18n('owmigration/dashboard' )}">{'View all versions'|i18n('owmigration/dashboard' )}</a>
                                            {else}
                                                {'No version data'|i18n('owmigration/dashboard' )}
                                            {/if}
                                        </div></td>
                                    </tr>
                                    <tr class="log_row {if $index|eq(0)}yui-dt-first{/if} {$style}" id="row_{$index}">
                                        <td colspan="4"><div class="yui-dt-liner">
                                            {foreach $extension.all_versions as $version}
                                                <p>{$version.version} : {$version.status|i18n('owmigration/dashboard' )}</p>
                                                {set $tmp_version = $version.version}
                                            {/foreach}
                                            {if $tmp_version|lt($extension.latest_version)}
                                                {for $tmp_version|inc() to $extension.latest_version as $next_version}
                                                    {if $next_version|lt(10)}
                                                        {set $next_version = concat('00', $next_version)}
                                                    {elseif $next_version|lt(100)}
                                                        {set $next_version = concat('0', $next_version)}
                                                    {/if}
                                                    <p>{$next_version} : {'never-installed'|i18n('owmigration/dashboard' )}</p>
                                               {/for}
                                            {/if}
                                        </div></td>
                                    <tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    {'No version data'|i18n('owmigration/dashboard' )}
                {/if}
            </div>
        </div>
    </div></div></div></div></div></div>
</div>