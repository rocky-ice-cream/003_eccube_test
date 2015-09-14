<?php

/**
 * 各種パラメータ一覧返却API
 *
 * @package Api
 * @author SystemFriend Inc.
 * @version $Id: plg_AddProduct_GetParamList.php 240 2012-09-28 02:58:22Z habu $
 */
require_once 'plg_AddProduct_AddProductCommon.php';

class API_plg_AddProduct_GetParamList extends API_plg_AddProduct_AddProductCommon {

    protected $operation_name = 'GetParamList';
    protected $operation_description = '各種パラメータ一覧を取得します';
    protected $default_auth_types = self::API_AUTH_TYPE_OPEN;    // 別途、アクセストークンでの認証を行なうため、この部分の認証はオープンにしておく
    protected $default_enable = '1';
    protected $default_is_log = '1';
    protected $default_sub_data = '';

    protected function checkErrorExtended($arrParam) {
        $arrErr = array();

        // プラグインが有効化されている事を確認
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $enable = $objQuery->getOne("SELECT enable FROM dtb_plugin WHERE plugin_code = 'AddProduct'");
        if( $enable != PLUGIN_ENABLE_TRUE ) {
            $arrErr['access_token'] = '※ プラグインが有効化されていません。';
            return $arrErr;
        }

        // アクセストークンをチェック
        if (!$this->isValidToken($arrParam['access_token'])) {
            $arrErr['access_token'] = '※ 有効なアクセストークンではありません。';
            return $arrErr;
        }

        // プラグイン設定(SSL)を取得
        $plugin = SC_Plugin_Util_Ex::getPluginByPluginCode("AddProduct");
        if ($plugin['free_field1'] === 'Only') {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                // SSLアクセス
            } else {
                $arrErr['not ssl'] = '※ 必ずSSLでアクセスしてください。';
                return $arrErr;
            }
        }

        if ($arrParam['target'] === 'classcategory') {
            $objErr = new SC_CheckError_Ex($arrParam);
            $objErr->doFunc(array('規格ID', 'class_id', INT_LEN), array('EXIST_CHECK', 'NUM_CHECK'));
            $arrErr[] = $objErr->arrErr;
        }
        return $arrErr;
    }

    public function doAction($arrParam) {
        $this->doInitParam($arrParam);
        if ($this->isParamError()) {
            return false;
        }

        $objDb = new SC_Helper_DB_Ex();
        $masterData = new SC_DB_MasterData_Ex();

        // 対象データ
        switch ($arrParam['target']) {
            // カテゴリー
            case 'category':
                list($arrCatVal, $arrCatOut) = $objDb->sfGetLevelCatList(false);
                for ($i = 0; $i < count($arrCatVal); $i++) {
                    $arrData[] = array(
                        'id' => $arrCatVal[$i],
                        'name' => $arrCatOut[$i],
                        'order' =>  $i,
                        'remarks1' => ''
                    );
                }
                break;

            // 商品ステータス
            case 'status':
                $arrStatus = $masterData->getMasterData('mtb_status');
                $i = 0;
                foreach( $arrStatus as $key => $val ) {
                    $arrData[] = array(
                        'id' => $key,
                        'name' => $val,
                        'order' =>  ++$i,
                        'remarks1' => ''
                    );
                }
                break;

            // メーカー
            case 'maker':
                $arrMaker = SC_Helper_DB_Ex::sfGetIDValueList('dtb_maker', 'maker_id', 'name');
                $i = 0;
                foreach( $arrMaker as $key => $val ) {
                    $arrData[] = array(
                        'id' => $key,
                        'name' => $val,
                        'order' =>  ++$i,
                        'remarks1' => ''
                    );
                }
                break;

            // 発送日目安
            case 'deliv_date':
                $arrDelivDate = $masterData->getMasterData('mtb_delivery_date');
                $i = 0;
                foreach( $arrDelivDate as $key => $val ) {
                    $arrData[] = array(
                        'id' => $key,
                        'name' => $val,
                        'order' =>  ++$i,
                        'remarks1' => ''
                    );
                }
                break;

            // サイトパラメータ
            case 'site_param':
                $arrComments = SC_Utils_Ex::getHash2Array($masterData->getDBMasterData('mtb_constants', array('id', 'remarks', 'rank')));
                $i = 0;
                foreach ($masterData->getDBMasterData('mtb_constants') as $key => $val) {
                    $arrData[] = array(
                        'id' => $key,
                        'name' => $val,
                        'order' => $i,
                        'remarks1' => $arrComments[$i++]
                    );
                }
                break;

            // 規格
            case 'class':
                $arrClass = $this->getAllClass();
                $i = 0;
                foreach ($arrClass as $key => $val) {
                    $arrData[] = array(
                        'id' => $key,
                        'name' => $val,
                        'order' => ++$i,
                        'remarks1' => ''
                    );
                }
                break;

            // 規格分類
            case 'classcategory':
                $arrClassCat = $this->lfGetClassCat($arrParam['class_id']);
                $i = 0;
                foreach ($arrClassCat as $key => $val) {
                    $arrData[] = array(
                        'id' => $val['classcategory_id'],
                        'name' => $val['name'],
                        'order' => ++$i,
                        'remarks1' => ''
                    );
                }
                break;

            // トランザクションID
            case 'transaction':
                $arrData[] = array(
                    'transactionid' => SC_Helper_Session_Ex::getToken(),
                    'sessionid' => session_id()
                );
                break;

            default:
                break;
        }
        $this->setResponse('Item', $arrData);
        $this->setResponse('StatusCode', '0');

        return true;
    }

    protected function lfInitParam(&$objFormParam) {
        $objFormParam->addParam('対象データ', 'target', INT_LEN, 'a', array('EXIST_CHECK'));
        $objFormParam->addParam('アクセストークン', 'access_token', INT_LEN, 'a', array('EXIST_CHECK'));
    }

    public function getResponseGroupName() {
        return 'Result';
    }

    /**
     * 規格分類の登録された, すべての規格を取得する.
     *
     * @access private
     * @return array 規格分類の登録された, すべての規格
     */
    function getAllClass() {
        $arrClass = SC_Helper_DB_Ex::sfGetIDValueList('dtb_class', 'class_id', 'name');

        // 規格分類が登録されていない規格は表示しないようにする。
        $arrClassCatCount = SC_Utils_Ex::sfGetClassCatCount();

        $results = array();
        if (!SC_Utils_Ex::isBlank($arrClass)) {
            foreach ($arrClass as $key => $val) {
                if ($arrClassCatCount[$key] > 0) {
                    $results[$key] = $arrClass[$key];
                }
            }
        }
        return $results;
    }

    /**
     * 有効な規格分類情報の取得
     *
     * @param integer $class_id 規格ID
     * @return array 規格分類情報
     */
    function lfGetClassCat($class_id) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $where = 'del_flg <> 1 AND class_id = ?';
        $objQuery->setOrder('rank DESC');
        $arrClassCat = $objQuery->select('name, classcategory_id', 'dtb_classcategory', $where, array($class_id));
        return $arrClassCat;
    }
}
