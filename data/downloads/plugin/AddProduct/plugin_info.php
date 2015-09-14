<?php
/**
 * プラグイン の情報クラス.
 *
 * @package AddProduct
 * @author SystemFriend Inc
 * @version $Id: plugin_info.php 240 2012-09-28 02:58:22Z habu $
 */
class plugin_info{
    static $PLUGIN_CODE       = "AddProduct";
    static $PLUGIN_NAME       = "商品登録iPhoneアプリ「ECCUBE工房」連携";
    static $CLASS_NAME        = "AddProduct";
    static $PLUGIN_VERSION    = "1.0";
    static $COMPLIANT_VERSION = "2.12.2";
    static $AUTHOR            = "株式会社システムフレンド";
    static $DESCRIPTION       = "iPhoneアプリから商品登録を行なえる様にします。撮った写真をアプリ内でリサイズして手軽に商品登録できます。";
    static $PLUGIN_SITE_URL   = "http://ec-cube.systemfriend.co.jp/";
    static $AUTHOR_SITE_URL   = "http://systemfriend.co.jp/";
    static $HOOK_POINTS       = array("prefilterTransform");
    static $LICENSE           = "LGPL";
}
?>