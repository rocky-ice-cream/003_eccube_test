<?php

// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';

/**
 * 商品登録アプリ連携トークン取得用管理者ログイン のページクラス.
 *
 * @package Page
 * @author SystemFriend Inc.
 * @version $Id: plg_AddProduct_LC_Page_Admin_AddProduct_Login.php 241 2012-09-28 04:51:28Z habu $
 */
class plg_AddProduct_LC_Page_Admin_AddProduct_Login extends LC_Page_Admin_Ex {

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $this->arrErr = array();
        $this->tpl_mainpage = 'addproduct/plg_AddProduct_login.tpl';
        $this->httpCacheControl('nocache');
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
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action() {
        // 管理者ログインテンプレートフレームの設定
        $this->setTemplate("addproduct/plg_AddProduct_addproduct_login_frame.tpl");

        // プラグインが有効化されている事を確認
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $enable = $objQuery->getOne("SELECT enable FROM dtb_plugin WHERE plugin_code = 'AddProduct'");
        if( $enable != PLUGIN_ENABLE_TRUE ) {
            if ($this->getMode() === 'login') {
                $this->arrErr['password'] = '※ プラグインが有効化されていません。';
                // JSONを出力
                echo SC_Utils_Ex::jsonEncode(array('login_error' => join("\r\n", $this->arrErr)));
                SC_Response_Ex::actionExit();
            } else {
                $this->arrErr['title'] = 'エラー';
                $this->arrErr['msg'] = 'プラグインが有効化されていません';
                $this->tpl_mainpage = 'addproduct/plg_AddProduct_error.tpl';
                return;
            }
        }

        // パラメーター管理クラス
        $objFormParam = new SC_FormParam_Ex();

        switch ($this->getMode()) {
            case 'login':
                //ログイン処理
                $this->lfInitParam($objFormParam);
                $objFormParam->setParam($_POST);
                $this->arrErr = $this->lfCheckError($objFormParam);
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    // アクセス用トークンを保存
                    $arrMember = $this->lfDoLogin($objFormParam->getValue('login_id'));
                    $arrToken['token_id']    = $arrMember['token_id'];
                    $arrToken['member_id']   = $arrMember['member_id'];
                    $arrToken['appli_id']    = $objFormParam->getValue('apid');
                    $arrToken['onetime_key'] = SC_Helper_Session_Ex::createToken();    // トランザクションIDは重複する場合があるので、こちらも新規生成する様にした(アクセストークン表示用のワンタイムキーとして)
                    $this->insertTokenData($arrToken);
                    // JSONを出力
                    echo SC_Utils_Ex::jsonEncode(array('success' => 'plg_AddProduct_login.php?mode=done&' . TRANSACTION_ID_NAME . '=' . $arrToken['onetime_key']));
                    SC_Response_Ex::actionExit();
                } else {
                    // ログイン失敗時に遅延させる(ブルートフォースアタック対策)
                    sleep(LOGIN_RETRY_INTERVAL);
                    // JSONを出力
                    echo SC_Utils_Ex::jsonEncode(array('login_error' => join("\r\n", $this->arrErr)));
                    SC_Response_Ex::actionExit();
                }
                break;
            case 'done':
                // トークン表示
                $this->lfInitParam($objFormParam);
                $objFormParam->setParam($_GET);
                if ($objFormParam->getValue(TRANSACTION_ID_NAME) === '') {
                    $this->arrErr['title'] = 'パラメーターエラー';
                    $this->arrErr['msg'] = 'パラメーターが正しく設定されていません';
                    $this->tpl_mainpage = 'addproduct/plg_AddProduct_error.tpl';
                } else {
                    $this->tpl_token_id = $this->getToken($objFormParam->getValue(TRANSACTION_ID_NAME));
                    $this->tpl_mainpage = 'addproduct/plg_AddProduct_done.tpl';
                }
                break;
            default:
                $this->lfInitParam($objFormParam);
                $objFormParam->setParam($_GET);
                if ($objFormParam->getValue('apid') === '') {
                    $this->arrErr['title'] = 'パラメーターエラー';
                    $this->arrErr['msg'] = 'パラメーターが正しく設定されていません';
                    $this->tpl_mainpage = 'addproduct/plg_AddProduct_error.tpl';
                }
                break;
        }
    }

    /**
     * パラメーター情報の初期化
     *
     * @param array $objFormParam フォームパラメータークラス
     * @return void
     */
    function lfInitParam(&$objFormParam) {
        $objFormParam->addParam('管理者ID', 'login_id', ID_MAX_LEN, '', array('EXIST_CHECK', 'ALNUM_CHECK' ,'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('パスワード', 'password', ID_MAX_LEN, '', array('EXIST_CHECK', 'ALNUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('トランザクションID', TRANSACTION_ID_NAME, ID_MAX_LEN, '', array('EXIST_CHECK'));
        $objFormParam->addParam('アプリID', 'apid', ID_MAX_LEN, '', array('EXIST_CHECK'));
    }

    /**
     * パラメーターのエラーチェック
     *
     * @param array $objFormParam フォームパラメータークラス
     * @return array $arrErr エラー配列
     */
    function lfCheckError(&$objFormParam) {
        // 書式チェック
        $arrErr = $objFormParam->checkError(false);
        if (SC_Utils_Ex::isBlank($arrErr)) {
            $arrForm = $objFormParam->getHashArray();
            // ログインチェック
            if (!$this->lfIsLoginMember($arrForm['login_id'], $arrForm['password'])) {
                $arrErr['password'] = '※ 管理者IDもしくはパスワードが正しくありません。';
                $this->lfSetIncorrectData($arrForm['login_id']);
            }
        }
        return $arrErr;
    }

    /**
     * 有効な管理者ID/PASSかどうかチェックする
     *
     * @param string $login_id ログインID文字列
     * @param string $pass ログインパスワード文字列
     * @return boolean ログイン情報が有効な場合 true
     */
    function lfIsLoginMember($login_id, $pass) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        //パスワード、saltの取得
        $cols = 'password, salt';
        $table = 'dtb_member';
        $where = 'login_id = ? AND del_flg <> 1 AND work = 1';
        $arrData = $objQuery->getRow($cols, $table, $where, array($login_id));
        if (SC_Utils_Ex::isBlank($arrData)) {
            return false;
        }
        // ユーザー入力パスワードの判定
        if (SC_Utils_Ex::sfIsMatchHashPassword($pass, $arrData['password'], $arrData['salt'])) {
            return true;
        }
        return false;
    }

    /**
     * 管理者ログイン処理
     *
     * @param string $login_id ログインID文字列
     * @return string $arrData メンバー情報(アクセス用トークンIDを含む)
     */
    function lfDoLogin($login_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        //メンバー情報取得
        $cols = 'member_id, authority, login_date, name';
        $table = 'dtb_member';
        $where = 'login_id = ?';
        $arrData = $objQuery->getRow($cols, $table, $where, array($login_id));
        // アクセス用トークンを取得
        $arrData['token_id'] = SC_Helper_Session_Ex::createToken();
        // ログイン情報記録
        $this->lfSetLoginData($token_id, $arrData['member_id'], $login_id, $arrData['authority'], $arrData['login_date']);
        return $arrData;
    }

    /**
     * ログイン情報の記録
     *
     * @param mixed $token_id トークンID
     * @param integer $member_id メンバーID
     * @param string $login_id ログインID文字列
     * @param integer $authority 権限ID
     * @param string $last_login 最終ログイン日時(YYYY/MM/DD HH:ii:ss形式) またはNULL
     * @return void
     */
    function lfSetLoginData($token_id, $member_id, $login_id, $authority, $last_login) {
        // ログイン記録ログ出力
        $str_log = "login(addproduct auth): user=$login_id($member_id) auth=$authority "
                    . "lastlogin=$last_login token_id=$token_id";
        GC_Utils_Ex::gfPrintLog($str_log);

        // 最終ログイン日時更新
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $sqlval = array();
        $sqlval['login_date'] = date('Y-m-d H:i:s');
        $table = 'dtb_member';
        $where = 'member_id = ?';
        $objQuery->update($table, $sqlval, $where, array($member_id));
    }

    /**
     * ログイン失敗情報の記録
     *
     * @param string $login_id ログイン失敗時に投入されたlogin_id文字列
     * @return void
     */
    function lfSetIncorrectData($error_login_id) {
        GC_Utils_Ex::gfPrintLog($error_login_id . ' password incorrect.');
    }

    /**
     * 発行されたアクセス用トークンIDをInsertする.
     *
     * @param array アクセス用トークンIDデータの連想配列
     * @return void
     */
    function insertTokenData($arrData) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();

        // INSERTする値を作成する.
        $sqlVal = array();
        $sqlVal['token_id']         = $arrData['token_id'];
        $sqlVal['onetime_key']      = $arrData['onetime_key'];
        $sqlVal['member_id']        = $arrData['member_id'];
        $sqlVal['appli_id']         = $arrData['appli_id'];
        $sqlVal['allow_flg']        = '1'; // アクセス許可フラグをONに設定
        $sqlVal['del_flg']          = '0'; // 削除フラグをOFFに設定
        $sqlVal['last_access_date'] = 'CURRENT_TIMESTAMP';
        $sqlVal['create_date']      = 'CURRENT_TIMESTAMP';
        $sqlVal['update_date']      = 'CURRENT_TIMESTAMP';

        // INSERTの実行
        $objQuery->insert('plg_AddProduct_tokens', $sqlVal);
    }

    /**
     * 生成時のトランザクションIDを指定して、トークンIDを取得する．
     *
     * @access private
     * @param string $onetime_key トークン表示用のワンタイムキー
     * @return string トークンID
     */
    function getToken($onetime_key) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->getOne('SELECT token_id FROM plg_AddProduct_tokens WHERE onetime_key = ?', array($onetime_key));
    }

}
