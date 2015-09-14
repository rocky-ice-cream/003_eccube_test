<?php
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

// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';

/**
 * 商品登録アプリ連携トークン管理 のページクラス.
 *
 * @package Page
 * @author SystemFriend Inc.
 * @version $Id: plg_AddProduct_LC_Page_Admin_System_AddProduct.php 190 2012-09-14 08:15:01Z habu $
 */
class plg_AddProduct_LC_Page_Admin_System_AddProduct extends LC_Page_Admin_Ex {

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();

        $this->list_data    = '';  // テーブルデータ取得用
        $this->tpl_disppage = '';  // 表示中のページ番号
        $this->tpl_strnavi  = '';
        $this->tpl_mainpage = 'system/plg_AddProduct_addproduct.tpl';
        $this->tpl_mainno   = 'system';
        $this->tpl_subno    = 'index';
        $this->tpl_onload   = 'fnGetRadioChecked();';
        $this->tpl_maintitle = 'システム設定';
        $this->tpl_subtitle = '商品登録アプリ連携トークン管理';

        $masterData = new SC_DB_MasterData_Ex();
//        $this->arrMember   = $this->getMemberData();
        $this->arrAllow[0]   = '不許可';
        $this->arrAllow[1]   = '許可';
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action() {
        // データの引き継ぎ
        $this->arrForm = $_POST;
        // エラーチェック
        $this->arrErr = $this->errorCheck($this->arrForm);

        switch ($this->getMode()) {
            case 'delete':
                // エラーの無い場合は 削除
                if (empty($this->arrErr)) {
                    $this->deleteTokenData($this->arrForm['token_id']);
                    $this->tpl_onload = "window.alert('削除しました。');";
                } else {
                    $this->tpl_onload = "window.alert('削除できませんでした。対象トークンが指定されていません');";
                }
                break;
            case 'deny':
                // エラーの無い場合は アクセス不許可にする
                if (empty($this->arrErr)) {
                    $this->denyTokenData($this->arrForm['token_id']);
                    $this->tpl_onload = "window.alert('アクセス不許可にしました。');";
                } else {
                    $this->tpl_onload = "window.alert('アクセス不許可にできませんでした。対象トークンが指定されていません');";
                }
                break;
            case 'allow':
                // エラーの無い場合は アクセス許可にする
                if (empty($this->arrErr)) {
                    $this->allowTokenData($this->arrForm['token_id']);
                    $this->tpl_onload = "window.alert('アクセスを許可しました。');";
                } else {
                    $this->tpl_onload = "window.alert('アクセスを許可できませんでした。対象トークンが指定されていません');";
                }
                break;
            default:
                break;
        }

        // トークンの件数を取得
        $linemax = $this->getTokenCount('del_flg <> 1');

        // アクセス許可中のトークン件数を取得
        $this->workmax
            = $this->getTokenCount('allow_flg = 1 AND del_flg <> 1');

        // ページ送りの処理 $_GET['pageno']が信頼しうる値かどうかチェックする。
        $pageno = $this->lfCheckPageNo($_GET['pageno']);

        $objNavi = new SC_PageNavi_Ex($pageno, $linemax, MEMBER_PMAX, 'fnMemberPage', NAVI_PMAX);
        $this->tpl_strnavi  = $objNavi->strnavi;
        $this->tpl_disppage = $objNavi->now_page;
        $this->tpl_pagemax  = $objNavi->max_page;

        // 取得範囲を指定(開始行番号、行数のセット)してトークンデータを取得
        $this->list_data = $this->getTokenData($objNavi->start_row);

    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }

    /**
     * plg_AddProduct_tokensからWHERE句に該当する件数を取得する.
     *
     * @access private
     * @param string $where WHERE句
     * @return integer 件数
     */
    function getTokenCount($where) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $table = 'plg_AddProduct_tokens';
        return $objQuery->count($table, $where);
    }

    /**
     * 開始行番号, 行数を指定してトークンデータを取得する.
     *
     * @access private
     * @param integer $startno 開始行番号
     * @return array トークンデータの連想配列
     */
    function getTokenData($startno) {
        $col = 'P.token_id, P.member_id, P.appli_id, P.allow_flg, P.last_access_date, P.create_date, M.name, M.work';
        $from = 'plg_AddProduct_tokens P, dtb_member M';
        $where = 'P.member_id = M.member_id AND P.del_flg <> 1';
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->setOrder('last_access_date DESC');
        $objQuery->setLimitOffset(MEMBER_PMAX, $startno);
        $arrTokenData = $objQuery->select($col, $from, $where, array());
        return $arrTokenData;
    }

    /**
     * ページ番号が信頼しうる値かチェックする.
     *
     * @access private
     * @param integer  $pageno ページの番号（$_GETから入ってきた値）
     * @return integer $clean_pageno チェック後のページの番号
     */
    function lfCheckPageNo($pageno) {
        $clean_pageno = '';

        // $pagenoが0以上の整数かチェック
        if (SC_Utils_Ex::sfIsInt($pageno) && $pageno > 0) {
            $clean_pageno = $pageno;
        } else {
            // 例外は全て1とする
            $clean_pageno = 1;
        }
        return $clean_pageno;
    }

    /**
     * エラーチェックを行う.
     *
     * @access private
     * @param array $arrForm $_POST 値
     * @return array チェック結果の配列
     */
    function errorCheck(&$arrForm) {
        $objErr = new SC_CheckError_Ex($arrForm);
        switch ($this->getMode()) {
            case 'delete':
            case 'deny':
            case 'allow':
                $objErr->doFunc(array('対象トークンID', 'token_id') ,array('EXIST_CHECK'));
                break;
            default:
                break;
        }
        return $objErr->arrErr;
    }

    /**
     * トークンIDを指定して、トークンデータを削除する.
     *
     * @access private
     * @param string トークンID
     * @return 更新結果
     */
    function deleteTokenData($token_id) {
        return $this->updateTokenData(array('del_flg' => 1), $token_id);
    }

    /**
     * トークンIDを指定して、トークンデータをアクセス不許可にする.
     *
     * @access private
     * @param string トークンID
     * @return 更新結果
     */
    function denyTokenData($token_id) {
        return $this->updateTokenData(array('allow_flg' => 0), $token_id);
    }

    /**
     * トークンIDを指定して、トークンデータをアクセス許可する.
     *
     * @access private
     * @param string トークンID
     * @return 更新結果
     */
    function allowTokenData($token_id) {
        return $this->updateTokenData(array('allow_flg' => 1), $token_id);
    }

    /**
     * トークンIDを指定して、トークンデータを更新する.
     *
     * @access private
     * @param array 更新用データの配列
     * @param string トークンID
     * @return 更新結果
     */
    function updateTokenData($sqlVal, $token_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->update('plg_AddProduct_tokens', $sqlVal, 'token_id = ?', array($token_id));
    }
}
