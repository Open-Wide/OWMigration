<div class="context-block">
    <div class="box-header">
        <div class="box-tc">
            <div class="box-ml">
                <div class="box-mr">
                    <div class="box-tl">
                        <div class="box-tr">
                            <h1 class="context-title">{'Describe content class'|i18n('owmigration/classes' )}</h1>
                            <div class="header-mainline"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-ml"><div class="box-mr">
            <div class="box-content">
                <div class="context-toolbar"></div>
                <div class="context-attributes">
                    <div class="block">
                        {foreach $class_list as $key_level_1 => $value_level_1}
                            <h2>{$key_level_1} :</h2>
                            {if $value_level_1|is_array()}
                                <ul>
                                    {foreach $value_level_1 as $key_level_2 => $value_level_2}
                                        <li>{$key_level_2} :
                                            {if $value_level_2|is_array()}
                                                <ul>
                                                    {foreach $value_level_2 as $key_level_3 => $value_level_3}
                                                        <li>{$key_level_3} :
                                                            {if $value_level_3|is_array()}
                                                                <ul>
                                                                    {foreach $value_level_3 as $key_level_4 => $value_level_4}
                                                                        <li>{$key_level_4} :
                                                                            {if $value_level_4|is_array()}
                                                                                <ul>
                                                                                    {foreach $value_level_4 as $key_level_5 => $value_level_5}
                                                                                        <li>{$key_level_5} :
                                                                                            {if $value_level_5|is_array()}
                                                                                                <ul>
                                                                                                    {foreach $value_level_5 as $key_level_6 => $value_level_6}
                                                                                                        <li>{$key_level_6} :
                                                                                                            {if $value_level_6|is_array()}
                                                                                                                <ul>
                                                                                                                    {foreach $value_level_6 as $key_level_7 => $value_level_7}
                                                                                                                        <li>{$key_level_7} :
                                                                                                                            {if $value_level_7|is_array()}
                                                                                                                                <ul>
                                                                                                                                    {foreach $value_level_7 as $key_level_8 => $value_level_8}
                                                                                                                                        <li>{$key_level_8} :
                                                                                                                                            {if $value_level_8|is_array()}
                                                                                                                                                {$value_level_8|dump()}
                                                                                                                                            {else}
                                                                                                                                                {$value_level_8|dump()|wash()}
                                                                                                                                            {/if}
                                                                                                                                        </li>
                                                                                                                                    {/foreach}
                                                                                                                                </ul>
                                                                                                                            {else}
                                                                                                                                {$value_level_7|dump()|wash()}
                                                                                                                            {/if}
                                                                                                                        </li>
                                                                                                                    {/foreach}
                                                                                                                </ul>
                                                                                                            {else}
                                                                                                                {$value_level_6|dump()|wash()}
                                                                                                            {/if}
                                                                                                        </li>
                                                                                                    {/foreach}
                                                                                                </ul>
                                                                                            {else}
                                                                                                {$value_level_5|dump()|wash()}
                                                                                            {/if}
                                                                                        </li>
                                                                                    {/foreach}
                                                                                </ul>
                                                                            {else}
                                                                                {$value_level_4|dump()|wash()}
                                                                            {/if}
                                                                        </li>
                                                                    {/foreach}
                                                                </ul>
                                                            {else}
                                                                {$value_level_3|dump()|wash()}
                                                            {/if}
                                                        </li>
                                                    {/foreach}
                                                </ul>
                                            {else}
                                                {$value_level_2|dump()|wash()}
                                            {/if}
                                        </li>
                                    {/foreach}
                                </ul>
                            {else}
                                {$value_level_1|dump()|wash()}
                            {/if}
                            </li>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="controlbar">
        <div class="box-bc">
            <div class="box-ml">
                <div class="box-mr">
                    <div class="box-tc">
                        <div class="box-bl">
                            <div class="box-br">
                                <div class="block">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>