<?php

/**
 * 商品登録API
 *
 * @package Api
 * @author SystemFriend Inc.
 * @version $Id: plg_AddProduct_AddProduct.php 240 2012-09-28 02:58:22Z habu $
 */
require_once 'plg_AddProduct_AddProductCommon.php';

class API_plg_AddProduct_AddProduct extends API_plg_AddProduct_AddProductCommon {

    protected $operation_name = 'AddProduct';
    protected $operation_description = '商品データを登録します';
    protected $default_auth_types = self::API_AUTH_TYPE_OPEN;    // 別途、アクセストークンでの認証を行なうため、この部分の認証はオープンにしておく
    protected $default_enable = '1';
    protected $default_is_log = '1';
    protected $default_sub_data = '';
    protected $memberData = array();

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

        return $arrErr;
    }

    public function doAction($arrParam) {
        $this->doInitParam($arrParam);
        if ($this->isParamError()) {
            return false;
        }

        // メンバー情報を取得
        $this->memberData = $this->getMemberData($arrParam['access_token']);

        // アップロードファイル情報の初期化
        $objUpFile = new SC_UploadFile_Ex(IMAGE_TEMP_REALDIR, IMAGE_SAVE_REALDIR);
        $this->lfInitFile($objUpFile);
        $objUpFile->setHiddenFileList($_POST);

        // ファイルを一時ディレクトリにアップロード
        $arrImgKey = array('main_large_image');
        for ($cnt = 1; $cnt <= PRODUCTSUB_MAX; $cnt++) {
            $arrImgKey[] = 'sub_large_image' . $cnt;
        }
        foreach ($arrImgKey as $val) {
            $this->arrErr[$val] = $objUpFile->makeTempFile($val, IMAGE_RENAME);
            if ($this->arrErr[$val] == '') {
                // 縮小画像作成
                $this->lfSetScaleImage($objUpFile, $val);
            }
        }

        // DBへデータ登録
        $product_id = $this->lfRegistProduct($objUpFile, $arrParam);

        // 件数カウントバッチ実行
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objDb = new SC_Helper_DB_Ex();
        $objDb->sfCountCategory($objQuery);
        $objDb->sfCountMaker($objQuery);

        // 一時ファイルを本番ディレクトリに移動する
        $this->lfSaveUploadFiles($objUpFile, $product_id);

        $arrData[] = array(
            'result_id' => 1,    // "1":登録成功、"2":登録失敗
            'product_id' => $product_id,
            'error_msg' => ''
        );

        $this->setResponse('Item', $arrData);
        $this->setResponse('StatusCode', '0');

        return true;
    }

    protected function lfInitParam(&$objFormParam) {
        $objFormParam->addParam('アクセストークン', 'access_token', STEXT_LEN, 'a', array('EXIST_CHECK'));
        $objFormParam->addParam('商品名', 'name', STEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('商品カテゴリ', 'category_id', INT_LEN, 'n', array('EXIST_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('公開・非公開', 'status', INT_LEN, 'n', array('EXIST_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('商品ステータス', 'product_status', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('商品コード', 'product_code', STEXT_LEN, 'KVna', array('EXIST_CHECK', 'SPTAB_CHECK','MAX_LENGTH_CHECK'));
        $objFormParam->addParam(NORMAL_PRICE_TITLE, 'price01', PRICE_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam(SALE_PRICE_TITLE, 'price02', PRICE_LEN, 'n', array('EXIST_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('在庫数', 'stock', AMOUNT_LEN, 'n', array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('在庫無制限', 'stock_unlimited', INT_LEN, 'n', array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('商品送料', 'deliv_fee', PRICE_LEN, 'n', array('NUM_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('ポイント付与率', 'point_rate', PERCENTAGE_LEN, 'n', array('EXIST_CHECK', 'NUM_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('発送日目安', 'deliv_date_id', INT_LEN, 'n', array('NUM_CHECK'));
        $objFormParam->addParam('販売制限数', 'sale_limit', AMOUNT_LEN, 'n', array('SPTAB_CHECK', 'ZERO_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('メーカーID', 'maker_id', INT_LEN, 'n', array('NUM_CHECK'));
        $objFormParam->addParam('メーカーURL', 'comment1', URL_LEN, 'a', array('SPTAB_CHECK', 'URL_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('検索ワード', 'comment3', LLTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('備考欄(SHOP専用)', 'note', LLTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('一覧-メインコメント', 'main_list_comment', MTEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('詳細-メインコメント', 'main_comment', LLTEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('save_main_large_image', 'save_main_large_image', '', '', array());
        $objFormParam->addParam('temp_main_large_image', 'temp_main_large_image', '', '', array());

        for ($cnt = 1; $cnt <= PRODUCTSUB_MAX; $cnt++) {
            $objFormParam->addParam('詳細-サブタイトル' . $cnt, 'sub_title' . $cnt, STEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
            $objFormParam->addParam('詳細-サブコメント' . $cnt, 'sub_comment' . $cnt, LLTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
            $objFormParam->addParam('save_sub_large_image' . $cnt, 'save_sub_large_image' . $cnt, '', '', array());
            $objFormParam->addParam('temp_sub_large_image' . $cnt, 'temp_sub_large_image' . $cnt, '', '', array());
        }

        $objFormParam->addParam('has_product_class', 'has_product_class', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('product_class_id', 'product_class_id', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
    }

    public function getResponseGroupName() {
        return 'Result';
    }

    /**
     * DBに商品データを登録する
     *
     * @param object $objUpFile SC_UploadFileインスタンス
     * @param array $arrList フォーム入力パラメーター配列
     * @return integer 登録商品ID
     */
    function lfRegistProduct(&$objUpFile, $arrList) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objDb = new SC_Helper_DB_Ex();

        // 配列の添字を定義
        $checkArray = array('name', 'status',
                            'main_list_comment', 'main_comment',
                            'deliv_fee', 'comment1', 'comment2', 'comment3',
                            'comment4', 'comment5', 'comment6',
                            'sale_limit', 'deliv_date_id', 'maker_id', 'note');
        $arrList = SC_Utils_Ex::arrayDefineIndexes($arrList, $checkArray);

        // INSERTする値を作成する。
        $sqlval['name'] = $arrList['name'];
        $sqlval['status'] = $arrList['status'];
        $sqlval['main_list_comment'] = $arrList['main_list_comment'];
        $sqlval['main_comment'] = $arrList['main_comment'];
        $sqlval['comment1'] = $arrList['comment1'];
        $sqlval['comment2'] = $arrList['comment2'];
        $sqlval['comment3'] = $arrList['comment3'];
        $sqlval['comment4'] = $arrList['comment4'];
        $sqlval['comment5'] = $arrList['comment5'];
        $sqlval['comment6'] = $arrList['comment6'];
        $sqlval['deliv_date_id'] = $arrList['deliv_date_id'];
        $sqlval['maker_id'] = $arrList['maker_id'];
        $sqlval['note'] = $arrList['note'];
        $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
        $sqlval['creator_id'] = $this->memberData['member_id'];
        $arrRet = $objUpFile->getDBFileList();
        $sqlval = array_merge($sqlval, $arrRet);

        for ($cnt = 1; $cnt <= PRODUCTSUB_MAX; $cnt++) {
            $sqlval['sub_title'.$cnt] = $arrList['sub_title'.$cnt];
            $sqlval['sub_comment'.$cnt] = $arrList['sub_comment'.$cnt];
        }

        $objQuery->begin();

        // 新規登録
        $product_id = $objQuery->nextVal('dtb_products_product_id');
        $sqlval['product_id'] = $product_id;

        // INSERTの実行
        $sqlval['create_date'] = 'CURRENT_TIMESTAMP';
        $objQuery->insert('dtb_products', $sqlval);

        $arrList['product_id'] = $product_id;

        // カテゴリを更新
        $objDb->updateProductCategories($arrList['category_id'], $product_id);

        // 規格登録
        if ($objDb->sfHasProductClass($product_id)) {
            // 規格あり商品（商品規格テーブルのうち、商品登録フォームで設定するパラメーターのみ更新）
            $this->lfUpdateProductClass($arrList);
        } else {
            // 規格なし商品（商品規格テーブルの更新）
            $this->lfInsertDummyProductClass($arrList);
        }

        // 商品ステータス設定
        $objProduct = new SC_Product_Ex();
        $this->setProductStatus($product_id, $arrList['product_status']);

        $objQuery->commit();
        return $product_id;
    }

    /**
     * 規格を設定していない商品を商品規格テーブルに登録
     *
     * @param array $arrList
     * @return void
     */
    function lfInsertDummyProductClass($arrList) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objDb = new SC_Helper_DB_Ex();

        // 配列の添字を定義
        $checkArray = array('product_class_id', 'product_id', 'product_code', 'stock', 'stock_unlimited', 'price01', 'price02', 'sale_limit', 'deliv_fee', 'point_rate' ,'product_type_id', 'down_filename', 'down_realfilename');
        $sqlval = SC_Utils_Ex::sfArrayIntersectKeys($arrList, $checkArray);
        $sqlval = SC_Utils_Ex::arrayDefineIndexes($sqlval, $checkArray);

        $sqlval['stock_unlimited'] = $sqlval['stock_unlimited'] ? UNLIMITED_FLG_UNLIMITED : UNLIMITED_FLG_LIMITED;
        $sqlval['creator_id'] = $this->memberData['member_id'];

        if (strlen($sqlval['product_class_id']) == 0) {
            $sqlval['product_class_id'] = $objQuery->nextVal('dtb_products_class_product_class_id');
            $sqlval['product_type_id'] = PRODUCT_TYPE_NORMAL;    // 通常商品
            $sqlval['create_date'] = 'CURRENT_TIMESTAMP';
            $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
            // INSERTの実行
            $objQuery->insert('dtb_products_class', $sqlval);
        } else {
            $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
            // UPDATEの実行
            $objQuery->update('dtb_products_class', $sqlval, 'product_class_id = ?', array($sqlval['product_class_id']));

        }
    }

    /**
     * 規格を設定している商品の商品規格テーブルを更新
     * (deliv_fee, point_rate, sale_limit)
     *
     * @param array $arrList
     * @return void
     */
    function lfUpdateProductClass($arrList) {
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $sqlval = array();

        $sqlval['deliv_fee'] = $arrList['deliv_fee'];
        $sqlval['point_rate'] = $arrList['point_rate'];
        $sqlval['sale_limit'] = $arrList['sale_limit'];
        $where = 'product_id = ?';
        $objQuery->update('dtb_products_class', $sqlval, $where, array($arrList['product_id']));
    }

    /**
     * アップロードファイルパラメーター情報の初期化
     * - 画像ファイル用
     *
     * @param object $objUpFile SC_UploadFileインスタンス
     * @return void
     */
    function lfInitFile(&$objUpFile) {
        $objUpFile->addFile('一覧-メイン画像', 'main_list_image', array('jpg', 'gif', 'png'),IMAGE_SIZE, false, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT);
        $objUpFile->addFile('詳細-メイン画像', 'main_image', array('jpg', 'gif', 'png'), IMAGE_SIZE, false, NORMAL_IMAGE_WIDTH, NORMAL_IMAGE_HEIGHT);
        $objUpFile->addFile('詳細-メイン拡大画像', 'main_large_image', array('jpg', 'gif', 'png'), IMAGE_SIZE, false, LARGE_IMAGE_WIDTH, LARGE_IMAGE_HEIGHT);
        for ($cnt = 1; $cnt <= PRODUCTSUB_MAX; $cnt++) {
            $objUpFile->addFile("詳細-サブ画像$cnt", "sub_image$cnt", array('jpg', 'gif', 'png'), IMAGE_SIZE, false, NORMAL_SUBIMAGE_WIDTH, NORMAL_SUBIMAGE_HEIGHT);
            $objUpFile->addFile("詳細-サブ拡大画像$cnt", "sub_large_image$cnt", array('jpg', 'gif', 'png'), IMAGE_SIZE, false, LARGE_SUBIMAGE_WIDTH, LARGE_SUBIMAGE_HEIGHT);
        }
    }

    /**
     * 縮小した画像をセットする
     *
     * @param object $objUpFile SC_UploadFileインスタンス
     * @param string $image_key 画像ファイルキー
     * @return void
     */
    function lfSetScaleImage(&$objUpFile, $image_key) {
        $subno = str_replace('sub_large_image', '', $image_key);
        switch ($image_key) {
        case 'main_large_image':
            // 詳細メイン画像
            $this->lfMakeScaleImage($objUpFile, $image_key, 'main_image');
        case 'main_image':
            // 一覧メイン画像
            $this->lfMakeScaleImage($objUpFile, $image_key, 'main_list_image');
            break;
        case 'sub_large_image' . $subno:
            // サブメイン画像
            $this->lfMakeScaleImage($objUpFile, $image_key, 'sub_image' . $subno);
            break;
        default:
            break;
        }
    }

    /**
     * 縮小画像生成
     *
     * @param object $objUpFile SC_UploadFileインスタンス
     * @param string $from_key 元画像ファイルキー
     * @param string $to_key 縮小画像ファイルキー
     * @param boolean $forced
     * @return void
     */
    function lfMakeScaleImage(&$objUpFile, $from_key, $to_key, $forced = false) {
        $arrImageKey = array_flip($objUpFile->keyname);
        $from_path = '';

        if ($objUpFile->temp_file[$arrImageKey[$from_key]]) {
            $from_path = $objUpFile->temp_dir . $objUpFile->temp_file[$arrImageKey[$from_key]];
        } elseif ($objUpFile->save_file[$arrImageKey[$from_key]]) {
            $from_path = $objUpFile->save_dir . $objUpFile->save_file[$arrImageKey[$from_key]];
        }

        if (file_exists($from_path)) {
            // 生成先の画像サイズを取得
            $to_w = $objUpFile->width[$arrImageKey[$to_key]];
            $to_h = $objUpFile->height[$arrImageKey[$to_key]];

            if ($forced) {
                $objUpFile->save_file[$arrImageKey[$to_key]] = '';
            }

            if (empty($objUpFile->temp_file[$arrImageKey[$to_key]])
                && empty($objUpFile->save_file[$arrImageKey[$to_key]])
            ) {
                // リネームする際は、自動生成される画像名に一意となるように、Suffixを付ける
                $dst_file = $objUpFile->lfGetTmpImageName(IMAGE_RENAME, '', $objUpFile->temp_file[$arrImageKey[$from_key]]) . $this->lfGetAddSuffix($to_key);
                $path = $objUpFile->makeThumb($from_path, $to_w, $to_h, $dst_file);
                $objUpFile->temp_file[$arrImageKey[$to_key]] = basename($path);
            }
        }
    }

    /**
     * アップロードファイルを保存する
     *
     * @param object $objUpFile SC_UploadFileインスタンス
     * @param integer $product_id 商品ID
     * @return void
     */
    function lfSaveUploadFiles(&$objUpFile, $product_id) {
        // TODO: SC_UploadFile::moveTempFileの画像削除条件見直し要
        $objImage = new SC_Image_Ex($objUpFile->temp_dir);
        $arrKeyName = $objUpFile->keyname;
        $arrTempFile = $objUpFile->temp_file;
        $arrSaveFile = $objUpFile->save_file;
        $arrImageKey = array();
        foreach ($arrTempFile as $key => $temp_file) {
            if ($temp_file) {
                $objImage->moveTempImage($temp_file, $objUpFile->save_dir);
                $arrImageKey[] = $arrKeyName[$key];
                if (!empty($arrSaveFile[$key])
                    && !$this->lfHasSameProductImage($product_id, $arrImageKey, $arrSaveFile[$key])
                    && !in_array($temp_file, $arrSaveFile)
                ) {
                    $objImage->deleteImage($arrSaveFile[$key], $objUpFile->save_dir);
                }
            }
        }
    }

    /**
     * リネームする際は、自動生成される画像名に一意となるように、Suffixを付ける
     *
     * @param string $to_key
     * @return string
     */
    function lfGetAddSuffix($to_key) {
        if ( IMAGE_RENAME === true) return;

        // 自動生成される画像名
        $dist_name = '';
        switch ($to_key) {
        case 'main_list_image':
            $dist_name = '_s';
            break;
        case 'main_image':
            $dist_name = '_m';
            break;
        default:
            $arrRet = explode('sub_image', $to_key);
            $dist_name = '_sub' .$arrRet[1];
            break;
        }
        return $dist_name;
    }

    /**
     * DBからtoken_idに対応する管理者データを取得する
     *
     * @param string $token_id トークンID
     * @return array 管理者データの連想配列, 無い場合は空の配列を返す
     */
    function getMemberData($token_id) {
        $table   = 'dtb_member M, plg_AddProduct_tokens P';
        $columns = 'M.name,M.login_id,P.member_id';
        $where   = 'M.member_id = P.member_id AND P.token_id = ?';

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        return $objQuery->getRow($columns, $table, $where, array($token_id));
    }

    /**
     * 商品ステータスを設定する.
     *
     * TODO 現在は DELETE/INSERT だが, UPDATE を検討する.
     *
     * @param integer $productId 商品ID
     * @param array $productStatusIds ON にする商品ステータスIDの配列
     */
    function setProductStatus($productId, $productStatusIds) {

        $val['product_id'] = $productId;
        $val['creator_id'] = $this->memberData['member_id'];
        $val['create_date'] = 'CURRENT_TIMESTAMP';
        $val['update_date'] = 'CURRENT_TIMESTAMP';
        $val['del_flg'] = '0';

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->delete('dtb_product_status', 'product_id = ?', array($productId));
        foreach ($productStatusIds as $productStatusId) {
            if ($productStatusId == '') continue;
            $val['product_status_id'] = $productStatusId;
            $objQuery->insert('dtb_product_status', $val);
        }
    }

}
