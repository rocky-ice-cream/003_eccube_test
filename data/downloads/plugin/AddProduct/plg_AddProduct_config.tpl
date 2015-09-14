<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_header.tpl"}-->
<script type="text/javascript">
</script>

<h2><!--{$tpl_subtitle}--></h2>
<form name="form1" id="form1" method="post" action="<!--{$smarty.server.REQUEST_URI|h}-->">
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="edit" />
<p>商品登録アプリ連携の詳細な設定が行えます。<br/>
    <br/>
</p>

<table border="0" cellspacing="1" cellpadding="8" summary=" ">
    <tr>
        <td colspan="2" width="90" bgcolor="#f3f3f3">▼アプリに設定するサイトURL</td>
    </tr>
    <tr>
        <td colspan="2"><!--{$smarty.const.HTTPS_URL}--><!--{$smarty.const.ADMIN_DIR}--></td>
    </tr>
    <tr>
        <td colspan="2" width="90" bgcolor="#f3f3f3">▼設定</td>
    </tr>
    <tr>
        <td bgcolor="#f3f3f3">SSL<span class="red">※</span></td>
        <td style="<!--{$arrErr.ssl|sfGetErrorColor}-->">
        <!--{assign var=key value="ssl"}-->
        <span class="red"><!--{$arrErr[$key]}--></span>
        <input type="radio" name="ssl" id="ssl_any" value="Any" <!--{if $arrForm.ssl == "Any"}-->checked="checked"<!--{/if}--> /><label for="ssl_any">HTTP(非SSL)でのAPIアクセスを許可する</label><br />
        <input type="radio" name="ssl" id="ssl_only" value="Only" <!--{if $arrForm.ssl == "Only"}-->checked="checked"<!--{/if}--> /><label for="ssl_only">非SSLでのAPIアクセスを許可しない</label>
        </td>
    </tr>
</table>

<div class="btn-area">
    <ul>
        <li>
            <a class="btn-action" href="javascript:;" onclick="document.form1.submit();return false;"><span class="btn-next">この内容で登録する</span></a>
        </li>
    </ul>
</div>

</form>
<!--{include file="`$smarty.const.TEMPLATE_ADMIN_REALDIR`admin_popup_footer.tpl"}-->
