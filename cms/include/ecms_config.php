<?php
// kate: space-indent on; tab-width 4; indent-width 4; mixed-indent off; replace-tabs on; indent-mode cstyle;

class ECMS_CONFIG{

    const VERSION  = '0.1.0';
    const CODENAME = 'fluffy';

    /** \defgroup domain
     */

    public static $proto = 'http';
    public static $subdomain = 'www';
    public static $domain = 'sebastiany.net';


    /** \defgroup rest
     */
    public static $path_to_resources        ='/_resources';
    public static $path_to_ecms_resources   ='/_ecms_resources';

    public static $default_head_template = 'head.html';
    public static $default_main_template = 'main.html';

    public static $db_ini = _path_to_content_.'/config/db.ini';

    public static $default_css = 'main.css';
    public static $default_dynamic_css = 'dynamic.css';

    public static $default_lang = 'de';
    public static $default_locale = 'de_DE.utf8';
    /** run '$ locale -a' on server (shell) for list of available locales **/

    public static $allowed_languages = array('de,en');

    public static $mkdir_mode = 0755;
    public static $cookie_timeout = 600;


    public static $title_prefix;
    public static $title_postfix;
    public static $title_separator;

    /** deprecated
     *  use
     *  $breadcrumb-divider: quote(">");
     * in css
     *  */
    public static $breadcrumb_separator;

    /** \defgroup publish
     */

    /** local|ftp|ssh **/
    public static $publish_methode ='local';

    /** local config **/
    public static $local_path_to_publish_ ='/ecms_static';

    /** ftp config **/
    public static $ftp_force_ssl_off    = false;
    public static $ftp_force_active     = false;
    public static $ftp_path_to_publish_ = '/public_html/ecms_static';
    public static $ftp_path_to_publish_include  = false;
    public static $ftp_host             = 'ecoware.de';
    public static $ftp_user             = '';
    public static $ftp_passwd           = '';
    public static $ftp_port             = 21;

        /** ftp config **/
    public static $sftp_path_to_publish_ = '/public_html/ecms_static';
    public static $sftp_host             = 'ecoware.de';
    public static $sftp_user             = '';
    public static $sftp_passwd           = '';
    public static $sftp_port             = 222;

    public static $url                  = '';
    public static $htaccess_user        = '';
    public static $htaccess_passwd      = '';

     /** \defgroup publish-assets
     */
    public static $local_path_to_assets  = _path_to_content_.'/assets';

    /** ftp config **/
    public static $assets_ftp_path_to_publish_ = '/';

    public static $assets_ftp_host             = 'assets.sebastiany.net';
    public static $assets_ftp_user             = '';
    public static $assets_ftp_passwd           = '';

    public static $assets_url                  = 'https://assets.sebastiany.net';
    public static $assets_htaccess_user        = '';
    public static $assets_htaccess_passwd      = '';

    /** \defgroup sitemap
     */
    public static $use_index_for_dir    = false;

    /** llms-full.txt automatisch aus contenfiles generieren (respektiert not_in_sitemap) */
    public static $generate_llms_full   = true;

    /** Nach jedem Deploy geänderte Seiten live mit include/geo-scanner (Submodule) prüfen */
    public static $geo_check_on_deploy  = false;

    /** Nach jedem Deploy geänderte Seiten per IndexNow an Bing/Yandex/Naver/Seznam/Yep melden */
    public static $indexnow_enabled     = false;
    public static $indexnow_key         = '';

    /** \defgroup template **/

    public static  $minify_html_output   = false;

    public static $sass_create_map      = false;



    /** \defgroup menu
     */

    public static $max_depth = 10;

    public static $_css_surrounding_ul_         ='nav';
    public static $_css_submenu_surrounding_ul_ ='subnav';
    public static $_css_item_                   ='nav-item';
    public static $_css_hasChild_               ='hasChild';
    public static $_css_childIsCurrent_         ='childIsActive';
    public static $_css_noChild_                ='noChild';
    public static $_css_hasParent_              ='hasParent';
    public static $_css_ParentIsCurrent_        ='ParentIsActive';
    public static $_css_noParent_               ='noParent';
    public static $_css_current_                ='active';
    public static $_css_lvl_                    ='lvl_';

    public static $_css_a_class_                ='nav-link';
    public static $_css_a_class_current_        ='active';
    public static $_css_a_hasParent_            ='dropdown-item';
    public static $_css_a_hasChild_             ='dropdown-item dropdown-toggle';

    //Debugging
    public static $error_lvl;

    /** \defgroup Media
     */
    public static $large_max_width_p = 600;
    public static $large_max_height_p = 900;
    public static $large_fixed_width_p = false;
    public static $large_fixed_height_p = true;

    public static $large_max_height_l = 600;
    public static $large_max_width_l = 900;
    public static $large_fixed_width_l = true;
    public static $large_fixed_height_l = false;

    public static $medium_max_width_p = 5;
    public static $medium_max_height_p = 260;
    public static $medium_fixed_width_p = false;
    public static $medium_fixed_height_p = true;

    public static $medium_max_width_l = 250;
    public static $medium_max_height_l = 250;
    public static $medium_fixed_width_l = false;
    public static $medium_fixed_height_l = true;

    public static $thumb_max_width_p = 140;
    public static $thumb_max_height_p = 140;
    public static $thumb_fixed_width_p = false;
    public static $thumb_fixed_height_p = true;

    public static $thumb_max_width_l = 210;
    public static $thumb_max_height_l = 140;
    public static $thumb_fixed_width_l = false;
    public static $thumb_fixed_height_l = true;


    /** \defgroup content_type_news
     */
    public static $news_allowed_extensions_ = array('jpg','jpeg');
    public static $news_picture_path_ = _path_to_content_.'/static/_resources/pictures/news';
    public static $news_per_page_ = 10;
    public static $number_pagination_items_ = 5;

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }
}
