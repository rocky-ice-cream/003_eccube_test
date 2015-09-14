<script>
    function ajaxLogin() {
        var postData = new Object;
        postData['<!--{$smarty.const.TRANSACTION_ID_NAME}-->'] = "<!--{$transactionid}-->";
        postData['mode'] = 'login';
        postData['login_id'] = $('input[type=text]').val();
        postData['password'] = $('input[type=password]').val();
        postData['apid'] = $('input[name=apid]').val();
        postData['url'] = $('input[name=url]').val();

        $.ajax({
            type: "POST",
            url: "?",
            data: postData,
            cache: false,
            dataType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown){
                alert(errorThrown);
                alert(textStatus);
            },
            success: function(result){
                if (result.success) {
                    location.href = result.success;
                } else {
                    alert(result.login_error);
                }
            }
        });
    }
</script>

<section>
    <h2 class="title">管理者権限認証</h2>
    <form name="login_form" id="login_form" method="post" action="javascript:;" onsubmit="return ajaxLogin();">
        <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
        <input type="hidden" name="mode" value="login" />
        <input type="hidden" name="url" value="<!--{$smarty.server.REQUEST_URI|h}-->" />
        <input type="hidden" name="apid" value="<!--{$smarty.get.apid|h}-->" />
        <div class="login_area">
            <div class="loginareaBox">
                <!--{assign var=key value="login_id"}-->
                <span class="attention"><!--{$arrErr[$key]}--></span>
                <input type="text" name="<!--{$key}-->" value="<!--{$tpl_login_id|h}-->" maxlength="<!--{$arrForm[$key].length}-->" style="<!--{$arrErr[$key]|sfGetErrorColor}-->" class="idtextBox data-role-none" placeholder="管理者ID" />
                <!--{assign var=key value="password"}-->
                <span class="attention"><!--{$arrErr[$key]}--></span>
                <input type="password" name="<!--{$key}-->" maxlength="<!--{$arrForm[$key].length}-->" style="<!--{$arrErr[$key]|sfGetErrorColor}-->" class="passtextBox data-role-none" placeholder="パスワード" />
            </div>
            <div class="btn_area">
                <input type="submit" value="認証" class="btn data-role-none" name="log" id="log" />
            </div>
        </div>
    </form>
</section>
