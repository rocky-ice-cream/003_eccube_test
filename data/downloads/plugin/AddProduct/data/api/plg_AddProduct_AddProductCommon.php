<?php

/**
 * 商品登録アプリ連携API共通関数
 *
 * @package Api
 * @author SystemFriend Inc.
 * @version $Id: plg_AddProduct_AddProductCommon.php 190 2012-09-14 08:15:01Z habu $
 */
require_once CLASS_EX_REALDIR . 'api_extends/SC_Api_Abstract_Ex.php';

class API_plg_AddProduct_AddProductCommon extends SC_Api_Abstract_Ex {

    protected $operation_name = 'AddProductCommon';
    protected $operation_description = 'AddProductCommon Operation';
    protected $default_auth_types = '99';
    protected $default_enable = '1';
    protected $default_is_log = '1';
    protected $default_sub_data = '';

    public function doAction($arrParam) {
        $this->arrResponse = array('DefaultEmpty' => array());
        return true;
    }

    public function getRequestValidate() {
        return array('DefaultResponse' => array());
    }

    public function getResponseGroupName() {
        return 'DefaultResponse';
    }

    protected function lfInitParam(&$objFormParam) {
    }

    /**
     * トークンIDをチェックする．
     *
     * @access private
     * @param string $token_id トークンID
     * @return boolean true:チェックOK、false:チェックNG(無効なトークンID)
     */
    function isValidToken($token_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        if ($objQuery->exists('plg_AddProduct_tokens P, dtb_member M', 'P.member_id = M.member_id AND P.token_id = ? AND P.del_flg = 0 AND P.allow_flg = 1 AND M.work = 1', array($token_id)) ) {
            $this->updateLastAccess($token_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * トークンIDを指定して、最終アクセス日時を更新する.
     *
     * @access private
     * @param array 更新用データの配列
     * @param string トークンID
     * @return 更新結果
     */
    function updateLastAccess($token_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->update('plg_AddProduct_tokens', array('last_access_date' => 'CURRENT_TIMESTAMP'), 'token_id = ?', array($token_id));
    }
}
