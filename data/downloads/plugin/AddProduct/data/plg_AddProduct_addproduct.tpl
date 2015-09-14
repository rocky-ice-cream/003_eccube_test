<!--{*
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2012 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
*}-->

<form name="form1" id="form1" method="post" action="">
<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
<input type="hidden" name="mode" value="" />
<input type="hidden" name="token_id" value="" />
<div id="system" class="contents-main">
    <p>※非稼動メンバーの場合、トークンがアクセス許可になっていても、アクセスできません。</p>
    <br />
    <div class="paging">
        <!--▼ページ送り-->
        <!--{$tpl_strnavi}-->
        <!--▲ページ送り-->
    </div>

    <!--▼メンバー一覧ここから-->
    <table class="list">
        <col width="15%" />
        <col width="15%" />
        <col width="25%" />
        <col width="20%" />
        <col width="15%" />
        <col width="10%" />
        <tr>
            <th>最終アクセス日時</th>
            <th>認証日時</th>
            <th>アプリID</th>
            <th>メンバー名</th>
            <th>アクセス許可</th>
            <th>削除</th>
        </tr>
        <!--{section name=data loop=$list_data}--><!--▼トークン<!--{$smarty.section.data.iteration}-->-->
        <tr>
            <td><!--{$list_data[data].last_access_date|date_format:"%Y-%m-%d %H:%M:%S"}--></td>
            <td><!--{$list_data[data].create_date|date_format:"%Y-%m-%d %H:%M:%S"}--></td>
            <td><!--{$list_data[data].appli_id|h}--></td>
            <td><!--{$list_data[data].name|h}--><!--{if $list_data[data].work != 1}--> (※非稼動)<!--{/if}--></td>
            <!--{assign var="allow_flg" value=$list_data[data].allow_flg}-->
            <td>
                <!--{$arrAllow[$allow_flg]|h}--><br />
                <!--{if $allow_flg == 1}-->
                    <a href="#" onClick="fnModeSubmit('deny', 'token_id', '<!--{$list_data[data].token_id}-->'); return false;">→不許可にする</a>
                <!--{else}-->
                    <a href="#" onClick="fnModeSubmit('allow', 'token_id', '<!--{$list_data[data].token_id}-->'); return false;">→許可する</a>
                <!--{/if}-->
            </td>
            <td class="menu"><a href="#" onClick="fnModeSubmit('delete', 'token_id', '<!--{$list_data[data].token_id}-->'); return false;">削除</a></td>
        </tr>
        <!--▲トークン<!--{$smarty.section.data.iteration}-->-->
        <!--{/section}-->
    </table>

    <div class="paging">
        <!--▼ページ送り-->
        <!--{$tpl_strnavi}-->
        <!--▲ページ送り-->
    </div>
</div>
</form>
