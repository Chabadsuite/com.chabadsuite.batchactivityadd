{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<table>
  <tr>
    {foreach from=$elementNames item=elementName}
      {if strpos($elementName, '_0') != false}
        <th>
          <div class="label">{$form.$elementName.label}</div>
        </th>
      {/if}
    {/foreach}
  </tr>
  {section name=batchActivity loop=25}
    {assign var='form_number' value="_`$smarty.section.batchActivity.index`_activity"}
    <tr>
      {foreach from=$elementNames item=elementName name=totalLoop}
        {if strpos($elementName, $form_number) != false}
          <td>
            <div class="content">{$form.$elementName.html}</div>
            <div class="clear"></div>
          </td>
        {/if}
      {/foreach}
    </tr>
  {/section}
</table>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
