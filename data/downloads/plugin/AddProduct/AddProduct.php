<?php

/**
 * プラグインのメインクラス
 *
 * @package AddProduct
 * @author SystemFriend Inc
 * @version $Id: AddProduct.php 226 2012-09-24 11:15:59Z habu $
 */
class AddProduct extends SC_Plugin_Base {

    /**
     * コンストラクタ
     */
    public function __construct(array $arrSelfInfo) {
        parent::__construct($arrSelfInfo);
    }
    
    /**
     * インストール
     * installはプラグインのインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
     * @return void
     */
    function install($arrPlugin) {
        // 【データベースの設定】

        // ■プラグイン設定の初期化
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();
        // プラグイン独自の設定データを追加
        $sqlval = array();
        $sqlval['free_field1'] = "Any";
        $sqlval['free_field2'] = "";
        $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
        $where = "plugin_code = 'AddProduct'";
        // UPDATEの実行
        $objQuery->update('dtb_plugin', $sqlval, $where);

        // ■インストール用SQLを実行
        if (!AddProduct::lfExecuteSQL(PLUGIN_UPLOAD_REALDIR . "AddProduct/sql/plg_AddProduct_Install_" . DB_TYPE . '.sql', DEFAULT_DSN)) {
            AddProduct::lfTriggerError('インストール用のSQLの実行に失敗しました.');
        }
        $objQuery->commit();

        // ■マスタデータのキャッシュをクリア
        $masterData = new SC_DB_MasterData_Ex();
        $masterData->clearCache('mtb_auth_excludes');

        // 【必要なファイルをコピー】

        // ■ロゴ画像
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/logo.png", PLUGIN_HTML_REALDIR . 'AddProduct/logo.png');

        // ■アクセストークン取得画面
        // コールPHP
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/html/addproduct"
            , HTML_REALDIR . ADMIN_DIR, true);    // ディレクトリごとコピー
        // 拡張クラス
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/page_extends/addproduct"
            , CLASS_EX_REALDIR . 'page_extends/admin/', true);    // ディレクトリごとコピー
        // 基本クラス
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/pages/addproduct"
            , CLASS_REALDIR . 'pages/admin/', true);    // ディレクトリごとコピー
        // テンプレート
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/templates/addproduct"
            , SMARTY_TEMPLATES_REALDIR . 'admin/', true);    // ディレクトリごとコピー
        // CSS
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/html/plg_AddProduct_addproduct.css"
            , USER_TEMPLATE_REALDIR . 'admin/css/plg_AddProduct_addproduct.css');

        // ■API
        // API共通関数(継承元クラス)
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/api/plg_AddProduct_AddProductCommon.php"
            , CLASS_REALDIR . 'api/operations/plg_AddProduct_AddProductCommon.php');
        // 各種パラメータ取得API
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/api/plg_AddProduct_GetParamList.php"
            , CLASS_REALDIR . 'api/operations/plg_AddProduct_GetParamList.php');
        // 商品登録用API
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/api/plg_AddProduct_AddProduct.php"
            , CLASS_REALDIR . 'api/operations/plg_AddProduct_AddProduct.php');

        // ■トークン管理画面
        // コールPHP
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/html/plg_AddProduct_addproduct.php"
            , HTML_REALDIR . ADMIN_DIR . 'system/plg_AddProduct_addproduct.php');
        // 拡張クラス
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/plg_AddProduct_LC_Page_Admin_System_AddProduct_Ex.php"
            , CLASS_EX_REALDIR . 'page_extends/admin/system/plg_AddProduct_LC_Page_Admin_System_AddProduct_Ex.php');
        // 基本クラス
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/plg_AddProduct_LC_Page_Admin_System_AddProduct.php"
            , CLASS_REALDIR . 'pages/admin/system/plg_AddProduct_LC_Page_Admin_System_AddProduct.php');
        // テンプレート
        AddProduct::lfCopyFile(PLUGIN_UPLOAD_REALDIR . "AddProduct/data/plg_AddProduct_addproduct.tpl"
            , SMARTY_TEMPLATES_REALDIR . 'admin/system/plg_AddProduct_addproduct.tpl');
    }

    /**
     * アンインストール
     * uninstallはアンインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     * 
     * @param array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    function uninstall($arrPlugin) {
        // ■DBテーブルを削除
        if (!AddProduct::lfExecuteSQL(PLUGIN_UPLOAD_REALDIR . "AddProduct/sql/plg_AddProduct_Uninstall_" . DB_TYPE . '.sql', DEFAULT_DSN)) {
            AddProduct::lfTriggerError('アンインストール用のSQLの実行に失敗しました.');
        }
        
        // ■マスタデータのキャッシュをクリア
        $masterData = new SC_DB_MasterData_Ex();
        $masterData->clearCache('mtb_auth_excludes');

        // 【配備したファイルを削除】
        // ロゴ画像
        AddProduct::lfDeleteFile(PLUGIN_HTML_REALDIR . 'AddProduct/logo.png');

        // ■アクセストークン取得画面用の各モジュールを削除
        // コールPHP
        AddProduct::lfDeleteFile(HTML_REALDIR . ADMIN_DIR . 'addproduct');    // ディレクトリごと削除
        // 拡張クラス
        AddProduct::lfDeleteFile(CLASS_EX_REALDIR . 'page_extends/admin/addproduct');    // ディレクトリごと削除
        // 基本クラス
        AddProduct::lfDeleteFile(CLASS_REALDIR . 'pages/admin/addproduct');    // ディレクトリごと削除
        // テンプレート
        AddProduct::lfDeleteFile(SMARTY_TEMPLATES_REALDIR . 'admin/addproduct');    // ディレクトリごと削除
        // CSS
        AddProduct::lfDeleteFile(USER_TEMPLATE_REALDIR . 'admin/css/plg_AddProduct_addproduct.css');

        // ■APIファイルを削除
        // API共通関数(継承元クラス)
        AddProduct::lfDeleteFile(CLASS_REALDIR . 'api/operations/plg_AddProduct_AddProductCommon.php');
        // 各種パラメータ取得API
        AddProduct::lfDeleteFile(CLASS_REALDIR . 'api/operations/plg_AddProduct_GetParamList.php');
        // 商品登録用API
        AddProduct::lfDeleteFile(CLASS_REALDIR . 'api/operations/plg_AddProduct_AddProduct.php');

        // ■トークン管理画面用の各ファイルを削除
        // コールPHP
        AddProduct::lfDeleteFile(HTML_REALDIR . ADMIN_DIR . 'system/plg_AddProduct_addproduct.php');
        // 拡張クラス
        AddProduct::lfDeleteFile(CLASS_EX_REALDIR . 'page_extends/admin/system/plg_AddProduct_LC_Page_Admin_System_AddProduct_Ex.php');
        // 基本クラス
        AddProduct::lfDeleteFile(CLASS_REALDIR . 'pages/admin/system/plg_AddProduct_LC_Page_Admin_System_AddProduct.php');
        // テンプレート
        AddProduct::lfDeleteFile(SMARTY_TEMPLATES_REALDIR . 'admin/system/plg_AddProduct_addproduct.tpl');
    }
    
    /**
     * 稼働
     * enableはプラグインを有効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    function enable($arrPlugin) {
        // nop
    }

    /**
     * 停止
     * disableはプラグインを無効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    function disable($arrPlugin) {
    }

    /**
     * 処理の介入箇所とコールバック関数を設定
     * registerはプラグインインスタンス生成時に実行されます
     * 
     * @param SC_Helper_Plugin $objHelperPlugin 
     */
    function register(SC_Helper_Plugin $objHelperPlugin) {
        $objHelperPlugin->addAction('prefilterTransform', array(&$this, 'prefilterTransform'), 1);
    }

    /**
     * プレフィルタコールバック関数
     *
     * @param string &$source テンプレートのHTMLソース
     * @param LC_Page_Ex $objPage ページオブジェクト
     * @param string $filename テンプレートのファイル名
     * @return void
     */
    function prefilterTransform(&$source, LC_Page_Ex $objPage, $filename) {
        // 「システム設定」にメニューを追加
        $objTransform = new SC_Helper_Transform($source);
        $template_dir = PLUGIN_UPLOAD_REALDIR . 'AddProduct/data/templates/';
        if ($this->endsWith($filename, 'Smarty/templates/admin/system/subnavi.tpl') !== false) {
            $objTransform->select('li', 0)->insertAfter(file_get_contents($template_dir . 'plg_AddProduct_subnavi.tpl'));
        }
        $source = $objTransform->getHTML();
    }

    /**
     * ファイルコピー
     *
     * @param string $srcPath
     * @param string $dstPath
     * @param string $dirFlg
     * @return boolean
     */
    static function lfCopyFile($srcPath, $dstPath, $dirFlg = false){
        if($dirFlg){
            if(SC_Utils_Ex::sfCopyDir($srcPath, $dstPath) === false) {
                AddProduct::lfTriggerError("'" . $srcPath . "'から、'" . $dstPath . "へのディレクトリコピーに失敗しました");
            }
        } else {
            if(copy($srcPath, $dstPath) === false) {
                AddProduct::lfTriggerError("'" . $srcPath . "'から、'" . $dstPath . "'へのコピーに失敗しました");
            }
        }
        return true;
    }

    /**
     * ファイル削除
     *
     * @param string $targetPath
     * @return void
     */
    static function lfDeleteFile($targetPath){
        if(SC_Helper_FileManager_Ex::deleteFile($targetPath) === false) {
            AddProduct::lfTriggerError('[' . $targetPath . ']の削除に失敗しました');
        }
    }

    static function lfTriggerError($errMsg){
        GC_Utils_Ex::gfPrintLog($errMsg, ERROR_LOG_REALFILE, true);
        
        // エラー発生時に、プラグイン一覧画面がシステムエラーになり表示できなくなるため、エラーは発生させない
        // trigger_error($errMsg, E_USER_ERROR);
    }

    // SQL文の実行
    static function lfExecuteSQL($filepath, $dsn, $disp_err = true) {
        $result = true;

        if(!file_exists($filepath)) {
            AddProduct::lfTriggerError('SQLファイル[' . $filepath . ']が見つかりません');
            $result = false;
        } else {
            if($fp = fopen($filepath, "r")) {
                $sql = fread($fp, filesize($filepath));
                fclose($fp);
            }

            // Debugモード指定
            $options['debug'] = PEAR_DB_DEBUG;
            $objDB = MDB2::connect($dsn, $options);

            // 接続エラー
            if(!PEAR::isError($objDB)) {
                $sql_split = split(";",$sql);

                foreach($sql_split as $key => $val) {
                    SC_Utils::sfFlush();
                    if (trim($val) != "") {
                        $ret = $objDB->query($val);
                        if(PEAR::isError($ret) && $disp_err) {
                            AddProduct::lfTriggerError("DB query Error message = " . $ret->message);
                            $result = false;
                            break;
                        }
                    }
                }
            } else {
                AddProduct::lfTriggerError("DB connect Error message = " . $objDB->message);
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 文字列の後方一致確認
     *
     * $haystackが$needleで終わるか判定します。
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    function endsWith($haystack, $needle){
        $length = (strlen($haystack) - strlen($needle));
        // 文字列長が足りていない場合はFALSEを返します。
        if($length < 0) return FALSE;
        return strpos($haystack, $needle, $length) !== FALSE;
    }
}
?>
