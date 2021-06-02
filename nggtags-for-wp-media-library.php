<?php
namespace NggTags_for_Media_Library;

/*  
    Copyright 2013-2016 b68c, Magenta Cuda

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

global $wpdb;

// Parameters

const MAX_TAXONOMIES = 8;

// NextGEN Gallery MySQL Table names defined here.

$ntfwml_ngg_pictures  = $wpdb->prefix . 'ngg_pictures';
$ntfwml_ngg_galleries = $wpdb->prefix . 'ngg_gallery';
$ntfwml_ngg_album     = $wpdb->prefix . 'ngg_album';

// TML post types

$tml_post_type = [
    'label'               => 'TML Galleries and Albums',
    'description'         => 'a container for images or galleries',
    'public'              => true,
    'exclude_from_search' => false,
    'public_queryable'    => true,
    'show_ui'             => true,
    'query_var'           => true,
    'show_in_nav_menus'   => true,
    'show_in_menu'        => true,
    'menu_position'       => 50,
    'capability_type'     => 'page',
    'supports'            => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments',
                               'revisions', 'post-formats' ]
];

// Are there any NextGEN Gallery pictures?

try {
    $tables = @$wpdb->get_col( "SHOW TABLES LIKE '$ntfwml_ngg_pictures'" );
} catch ( Exception $e ) {
    $tables = [];
}
$ntfwml_ngg_pictures_count = !$tables ? 0 : $wpdb->get_var( "SELECT COUNT(*) FROM $ntfwml_ngg_pictures" );

// What is the current state of the update?

$ntfwml_options = get_option( 'nggtags_for_wp_media_library', [ ] );
$version_1_1 = false;
if ( isset( $ntfwml_options['version'] ) ) {
    list( $major, $minor ) = sscanf( $ntfwml_options['version'], '%D.%D' );
    if ( $major > 1 || $major === 1 && $minor >= 1 ) {
        $version_1_1 = true;
    }
}

if ( $ntfwml_ngg_pictures_count && ( !$ntfwml_options || !isset( $ntfwml_options['status'] )
    || $ntfwml_options['status'] != 'update done' ) ) {
    # start or continue the upgrade to media library
    include( dirname( __FILE__ ) . '/update-to-wp-media-library.php' );
    return;
}   # if ( $ntfwml_ngg_pictures_count && ( !$ntfwml_options || !isset( $ntfwml_options['status'] )

// Update completed or no update needed so enable nggtags for Media Library.
// Allow the user to set WordPress Gallery options for the shortcode 'nggtags'

add_action( 'admin_init', function () use ( $version_1_1 ) {
    add_settings_section( 'nggtags_for_media_library_settings_section', 'Tags for Media Library Settings',
        function () use ( $version_1_1 ) {
?>
<div style="margin:10px 20px;padding:5px 10px;font-size:smaller;">
TML will work with its default configuration. However, you may want to configure TML to better suit your website.
Of course this can be done at anytime and you can use the default configuration until you better understand the configuration 
parameters.
Since the NextGEN Gallery shortcodes -
<a href="http://nggtagsforwpml.wordpress.com/#nggtags" target="_blank">nggtags</a>,
<a href="http://nggtagsforwpml.wordpress.com/#nggallery-singlepic" target="_blank">nggallery</a> and
<a href="http://nggtagsforwpml.wordpress.com/#nggallery-singlepic" target="_blank">singlepic</a> - are implemented
using WordPress's gallery shortcode the options are the same as
<a href="http://codex.wordpress.org/Gallery_Shortcode" target="_blank">the options of WordPress's gallery shortcode</a>.
These options will automatically be added to the corresponding shortcodes in your post content. Additionally, the option
<a href="http://nggtagsforwpml.wordpress.com/#tml_view" target="_blank">tml_view</a> can be used to specify the initial view 
of the gallery. The values of this option are &quot;standard&quot;, &quot;miro&quot;, &quot;titles&quot;, &quot;large&quot;
and &quot;slideshow&quot;. 
The option <a href="http://nggtagsforwpml.wordpress.com/#tml_view" target="_blank">tml_views</a> can be used to specify the 
list (space separated) of selectable views. Any of these settings can be overridden by a corresponding option specified with
 the shortcode, e.g., [nggallery id="148" tml_view=&quot;slideshow&quot; tml_views=&quot;slideshow standard&quot;].
To display only the standard WordPress gallery view set tml_view to standard and disable the alternate gallery views option.
<?php
if ( $version_1_1 ) {
?>
The NextGEN galleries and albums are now posts in
<a href="<?php echo admin_url( 'edit.php?post_type=tml_galleries_albums' ); ?>">
&quot;TML Galleries and Albums&quot;</a>.
<?php
}
?>
</div>
<script type="text/javascript">
jQuery(document).ready(function(){
    jQuery("input#submit[type='submit'][name='submit']").parent().append('&nbsp;\
<input type="button" name="nggml-clear" id="nggml-clear" class="button button-primary" \
value="Remove All Options from Database" /> \
<input type="hidden" name="nggml-clear-nonce" id="nggml-clear-nonce" value="<?php echo wp_create_nonce( "nggml-clear-options" ); ?>">');
    jQuery("input#nggml-clear[type='button'][name='nggml-clear']").click(function(){
        if(window.prompt("This will remove all settings including taxonomies and search widget parameters from the database. \
This is probably only useful if you will be removing this plugin or you really want to re-specify everything. \
Enter \"remove\" to continue")==="remove"){
            jQuery.post(ajaxurl,{action:"nggml_clear_options_from_database",nonce:jQuery("input#nggml-clear-nonce").val()},function(r){
                window.alert(r);
                document.location.reload(true);
            });
        }
    });
});
</script>
<?php
        }, 'nggtags_for_media_library_settings_page' );

    # settings field for gallery options for WordPress gallery shortcode
    add_settings_field( 'wp_gallery_for_media_library_gallery_options', 'WordPress gallery options',
        function( ) {
?>
<input id="wp_gallery_for_media_library_gallery_options" name="wp_gallery_for_media_library_gallery_options" type="text"
    size="50" value='<?php echo get_option( 'wp_gallery_for_media_library_gallery_options' ); ?>'
    placeholder='e.g. tml_view="large" tml_views="titles large" columns="6"'/>
<?php
        echo '&nbsp;&nbsp;adds TML options to WordPress gallery shortcodes';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for gallery options for nggtags shortcode
    add_settings_field( 'nggtags_for_media_library_gallery_options', 'nggtags options',
        function( ) {
?>
<input id="nggtags_for_media_library_gallery_options" name="nggtags_for_media_library_gallery_options" type="text"
    size="50" value='<?php echo get_option( 'nggtags_for_media_library_gallery_options' ); ?>'
    placeholder='e.g. tml_view="slideshow" tml_views="standard slideshow" columns="6"'/>
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for gallery options for nggallery shortcode
    add_settings_field( 'nggallery_for_media_library_gallery_options', 'nggallery options',
        function( ) {
?>
<input id="nggallery_for_media_library_gallery_options" name="nggallery_for_media_library_gallery_options" type="text"
    size="50" value='<?php echo get_option( 'nggallery_for_media_library_gallery_options' ); ?>'
    placeholder='e.g. tml_view="large" tml_views="titles large" columns="6"'/>
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for gallery options for slideshow shortcode
    add_settings_field( 'slideshow_for_media_library_gallery_options', 'slideshow options',
        function () {
?>
<input id="slideshow_for_media_library_gallery_options" name="slideshow_for_media_library_gallery_options" type="text"
    size="50" value='<?php echo get_option( 'slideshow_for_media_library_gallery_options' ); ?>'
    placeholder=''/>
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
        
    # settings field for gallery options for singlepic shortcode
    add_settings_field( 'singlepic_for_media_library_gallery_options', 'singlepic options',
        function () {
?>
<input id="singlepic_for_media_library_gallery_options" name="singlepic_for_media_library_gallery_options" type="text"
    size="50" value='<?php echo get_option( 'singlepic_for_media_library_gallery_options' ); ?>'
    placeholder='e.g. size="full" columns="1"'/>
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
        
    # settings field for gallery options for search widget
    add_settings_field( 'search_results_for_media_library_gallery_options', 'search results options',
        function () {
?>
<input id="search_results_for_media_library_gallery_options" name="search_results_for_media_library_gallery_options"
    type="text" size="50" value='<?php echo get_option( 'search_results_for_media_library_gallery_options' ); ?>'
    placeholder='e.g. tml_view="titles" tml_views="titles" columns="6"'/>
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
        
    # settings field for user CSS file
    add_settings_field( 'nggml_user_css_file_url', 'URL of user CSS file', function () {
?>
<input id="nggml_user_css_file_url" name="nggml_user_css_file_url" type="url" size="50"
    value="<?php echo get_option( 'nggml_user_css_file_url', '' ); ?>"/>
<?php
        echo '&nbsp;&nbsp;use to override the plugin\'s css file';
    }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
        
    # settings field for alt high density gallery view
    add_settings_field( 'nggml_alt_high_density_gallery_enable', 'enable alternate gallery views',
        function () {
?>
<input id="nggml_alt_high_density_gallery_enable" name="nggml_alt_high_density_gallery_enable"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_alt_high_density_gallery_enable', 'enabled' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp;for the user (not admin) alternate gallery views';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
        
    # settings field for icon images width
    add_settings_field( 'nggml_alt_high_density_gallery_image_width', 'alternate gallery view image width',
        function () {
?>
<input id="nggml_alt_high_density_gallery_image_width" name="nggml_alt_high_density_gallery_image_width"
    type="number" min="16" max="1024" size="20"
    value='<?php echo get_option( 'nggml_alt_high_density_gallery_image_width', '64' ); ?>' />
<?php
            echo '&nbsp;&nbsp;for the user (not admin) alternate gallery views';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for highlight color
    add_settings_field( 'nggml_alt_high_density_gallery_focus_color', 'alternate gallery view highlight color',
        function () {
?>
<input id="nggml_alt_high_density_gallery_focus_color" name="nggml_alt_high_density_gallery_focus_color"
    type="color" size="20"
    value='<?php echo get_option( 'nggml_alt_high_density_gallery_focus_color', '#ffff00' ); ?>' />
<?php
            echo '&nbsp;&nbsp;for the user (not admin) alternate gallery views';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

/*
    # settings field for include sidebar with search results
    add_settings_field( 'nggml_include_sidebar_with_search_results', 'include sidebar with search results',
        function () {
?>
<input id="nggml_include_sidebar_with_search_results" name="nggml_include_sidebar_with_search_results"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_include_sidebar_with_search_results', '' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp;the search results is by default shown without a sidebar';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );
*/

    # settings field for preserving thumbnail size
    add_settings_field( 'nggml_preserve_thumbnail_size', 'preserve thumbnail aspect ratio',
        function () {
?>
<input id="nggml_preserve_thumbnail_size" name="nggml_preserve_thumbnail_size"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_preserve_thumbnail_size', 'enabled' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp; i.e., do not stretch the thumbnails into square tiles';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for stretch image to fit option
    add_settings_field( 'nggml_stretch_to_fit_enable', 'enable stretch image to fit view',
        function () {
?>
<input id="nggml_stretch_to_fit_enable" name="nggml_stretch_to_fit_enable"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_stretch_to_fit_enable', '' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp;for the large image and slideshow views';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for preserve aspect ratio on stretch image to fit
    add_settings_field( 'nggml_preserve_aspect_ratio_enable', 'enable preserve aspect ratio',
        function () {
?>
<input id="nggml_preserve_aspect_ratio_enable" name="nggml_preserve_aspect_ratio_enable"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_preserve_aspect_ratio_enable', 'enabled' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp;when stretching image to fit view';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for Miro's preferred row height
    add_settings_field( 'nggml_miro_row_height', 'Miro\'s gallery row height', function() {
?>
<input id="nggml_miro_row_height" name="nggml_miro_row_height" type="number" min="32" max="512" size="20" value='<?php echo get_option( 'nggml_miro_row_height', '128' ); ?>' />
<?php
            echo '&nbsp;&nbsp;the preferred height of rows in Miro\'s gallery in pixels';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for use full screen slideshow
    add_settings_field( 'nggml_use_full_screen_slideshow_enable', 'enable full browser window slide show',
        function () {
?>
<input id="nggml_use_full_screen_slideshow_enable" name="nggml_use_full_screen_slideshow_enable"
    type="checkbox" value="enabled"
    <?php echo ( get_option( 'nggml_use_full_screen_slideshow_enable', '' ) === 'enabled' ? ' checked' : '' ); ?> />
<?php
            echo '&nbsp;&nbsp;otherwise use post content view, i.e., do not overlay sidebar';
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for slide show interval
    add_settings_field( 'nggml_slideshow_interval', 'slide show time interval in milliseconds',
        function () {
?>
<input id="nggml_slideshow_interval" name="nggml_slideshow_interval"
    type="number" min="2500" max="100000" size="40"
    value='<?php echo get_option( 'nggml_slideshow_interval', '5000' ); ?>' />
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    # settings field for css transition for slide show
    add_settings_field( 'nggml_transition', 'slide show transition',
        function () {
            $transition = get_option( 'nggml_transition', 'flip' );
?>
<input id="nggml_transition" name="nggml_transition" type="radio" value="slide-left"
    <?php echo $transition === 'slide-left' ? 'checked' : ''; ?>>Slide Left&nbsp;&nbsp;&nbsp;&nbsp;
<input id="nggml_transition" name="nggml_transition" type="radio" value="fade"
    <?php echo $transition === 'fade' ? 'checked' : ''; ?>>Fade In&nbsp;&nbsp;&nbsp;&nbsp;
<input id="nggml_transition" name="nggml_transition" type="radio" value="explode"
    <?php echo $transition === 'explode' ? 'checked' : ''; ?>>Explode&nbsp;&nbsp;&nbsp;&nbsp;
<input id="nggml_transition" name="nggml_transition" type="radio" value="flip"
    <?php echo $transition === 'flip' ? 'checked' : ''; ?>>Flip&nbsp;&nbsp;&nbsp;&nbsp;
<input id="nggml_transition" name="nggml_transition" type="radio" value="rotation"
    <?php echo $transition === 'rotation' ? 'checked' : ''; ?>>Rotate&nbsp;&nbsp;&nbsp;&nbsp;
<input id="nggml_transition" name="nggml_transition" type="radio" value="reveal-left"
    <?php echo $transition === 'reveal-left' ? 'checked' : ''; ?>>Reveal Left&nbsp;&nbsp;&nbsp;&nbsp;
<?php
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_settings_section' );

    register_setting( 'nggtags_for_media_library_settings', 'wp_gallery_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'nggtags_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'nggallery_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'slideshow_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'singlepic_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'search_results_for_media_library_gallery_options' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_user_css_file_url' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_alt_high_density_gallery_enable' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_alt_high_density_gallery_image_width' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_alt_high_density_gallery_focus_color' );
    #register_setting( 'nggtags_for_media_library_settings', 'nggml_include_sidebar_with_search_results' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_preserve_thumbnail_size' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_stretch_to_fit_enable' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_preserve_aspect_ratio_enable' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_miro_row_height' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_use_full_screen_slideshow_enable' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_slideshow_interval' );
    register_setting( 'nggtags_for_media_library_settings', 'nggml_transition' );
    
    add_settings_section( 'nggtags_for_media_library_taxonomy_section', 'Taxonomies for Media Library',
        function () {
?>
<div style="margin:10px 20px;padding:5px 10px;font-size:smaller;">
In addition to Ngg Tags you can <a href="http://nggtagsforwpml.wordpress.com/#additional-taxonomies" target="_blank">create
your own tag taxonomies</a> for Media Library images.
</div>
<?php
        }, 'nggtags_for_media_library_settings_page' );
    $taxonomy_count = MAX_TAXONOMIES + 1; 
    for ( $i = 1; $i <= $taxonomy_count; $i++ ) {
        if ( $i === $taxonomy_count ) {
            if ( !empty( $first_empty_i ) ) {
                $use_i = $first_empty_i;
                $taxonomy_slug = "nggtags_for_media_library_taxonomy_slug_$use_i";
                $taxonomy_name = "nggtags_for_media_library_taxonomy_name_$use_i";
            } else {
                break;
            }
        } else {
            $use_i = $i;
            $taxonomy_slug = "nggtags_for_media_library_taxonomy_slug_$i";
            $taxonomy_name = "nggtags_for_media_library_taxonomy_name_$i";
        }
        $taxonomy_name_value = get_option( $taxonomy_name );
        if ( $i === 1 ) {
            $taxonomy_slug_value = 'ngg_tag';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'NGG Tags'; }
        } else if ( $i === 2 ) {
            $taxonomy_slug_value = 'priority';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'Priority'; }
        } else if ( $i === 3 ) {
            $taxonomy_slug_value = 'exclude';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'Exclude'; }
        } else {  
            $taxonomy_slug_value = get_option( $taxonomy_slug );
        }
        if ( empty( $taxonomy_slug_value ) ) {
            if ( $i !== $taxonomy_count ) {
                if ( empty( $first_empty_i) ) { $first_empty_i = $i; }
                continue;
            }
            $taxonomy_name_value = '';
        }
        add_settings_field( $taxonomy_name, "Taxonomy Name $use_i",
            function () use ( $taxonomy_name, $taxonomy_name_value, $use_i ) {
?>
<input id="<?php echo $taxonomy_name; ?>" name="<?php echo $taxonomy_name; ?>" type="text"
    class="nggtags_for_media_library_taxonomy_name"
    size="40" value="<?php echo $taxonomy_name_value; ?>" placeholder="enter new taxonomy name" />
<?php
            if ( $use_i === 2 ) {
                echo '&nbsp;&nbsp;this number sets the order in a TML gallery - smaller is earlier';
            } else if ( $use_i === 3 ) {
                echo '&nbsp;&nbsp;a yes value will exclude this image from a TML gallery';
            }
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_taxonomy_section' );
        add_settings_field( $taxonomy_slug, "Taxonomy Slug $use_i",
            function () use ( $taxonomy_slug, $taxonomy_slug_value, $use_i, $i, $taxonomy_count ) {
?>
<input id="<?php echo $taxonomy_slug; ?>" name="<?php echo $taxonomy_slug; ?>" type="text"
    size="40" value="<?php echo $taxonomy_slug_value; ?>" placeholder="enter new taxonomy slug"
    <?php if ( $use_i <= 3 ) { echo 'disabled'; } ?> />
<?php
            if ( !empty( $taxonomy_slug_value ) && $use_i > 3 ) {
                echo '&nbsp;&nbsp;an empty slug value will delete this taxonomy';
            }
            if ( $i === $taxonomy_count ) {
?>
<script type="text/javascript">
(function(){
    var list=document.querySelectorAll("input.nggtags_for_media_library_taxonomy_name");
    for(var i=0;i<list.length;i++){
        list.item(i).addEventListener("change",function(){
            document.getElementById(this.id.replace("name","slug")).value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,"-");
        });
    }
})();
</script>
<?php
            }
        }, 'nggtags_for_media_library_settings_page', 'nggtags_for_media_library_taxonomy_section' );
        register_setting( 'nggtags_for_media_library_settings', $taxonomy_slug );
        register_setting( 'nggtags_for_media_library_settings', $taxonomy_name );
    }
    add_filter( 'pre_update_option', function ( $new_value, $option, $old_value ) {
        if ( strpos( $option, 'nggtags_for_media_library_taxonomy_slug_' ) !== 0 ) { return $new_value; }
        // nggtags_for_media_library_taxonomy_slugs cannot be changed
        if ( $old_value && $new_value ) { return $old_value; }
        return $new_value;
    }, 10, 3 );
    add_action( 'updated_option', function ( $option, $old_value, $new_value ) {
        global $wpdb;
        if ( sscanf( $option, 'nggtags_for_media_library_taxonomy_slug_%d', $index ) !== 1 ) { return; }
        if ( $new_value ) { return; }
        // !$new_value means delete the taxonomy
        $ids = $wpdb->get_col( <<< EOD
SELECT r.object_id FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x
    WHERE r.term_taxonomy_id = x.term_taxonomy_id AND x.taxonomy = "$old_value"
EOD
        );
        foreach ( $ids as $id ) { wp_delete_object_term_relationships( $id, $old_value ); }
        $ids = $wpdb->get_col( "SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = '$old_value'" );
        foreach ( $ids as $id ) { wp_delete_term( $id, $old_value ); }
    }, 10, 3 );
} );

if ( strpos( $_SERVER['REQUEST_URI'], 'nggtags-for-wp-media-library/upload.php' ) !== false ) {
    add_action( 'wp_loaded', function() {
        global $pagenow;
        $pagenow = 'upload.php';
    } );
    add_action('admin_head', function() {
    ?>
      <base href="<?php echo admin_url(); ?>">
    <?php
    } );
}

add_action( 'admin_init', function () {
    // admin mode gallery test
    // error_log( 'do_shortcode("[gallery id=162]")=' . print_r( do_shortcode( '[gallery id="162"]' ), true ) );
    // TODO: is this dead code?
    // if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'nggtags-for-wp-media-library/upload.php' ) {
    //     wp_redirect( plugins_url( 'upload.php', __FILE__ ) );
    // }
    if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'tml_upload' ) {
        $_GET['noheader'] = $_REQUEST['noheader'] = '1';
        # patch URI to have selected POST variables
        $request_uri = &$_SERVER['REQUEST_URI'];
        foreach ( $_POST as $index => $post ) {
            if ( substr_compare( $index, 'filter-', 0, 7 ) === 0 ) {
                if ( is_array( $post ) ) {
                    foreach( $post as $value ) {
                        $request_uri = add_query_arg( $index . '[]', $value, $request_uri );
                    }
                } else {
                    $request_uri = add_query_arg( $index, $post, $request_uri );
                }
            }
        }
        if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
            $request_uri = add_query_arg( 's', $_POST['s'], $request_uri );
        }
        if ( isset( $_POST['paged'] ) && ! empty( $_POST['paged'] ) ) {
            $request_uri = add_query_arg( 'paged', $_POST['paged'], $request_uri );
        }
        # error_log( 'ACTION::admin_init():$_SERVER[REQUEST_URI]=' . $_SERVER['REQUEST_URI'] );
    }
}, 1 );

add_action( 'admin_menu', function () {
    add_options_page( 'Tags for Media Library Settings', 'Tags for Media Library',
        'manage_options', 'nggtags_for_media_library_settings_page', function () {
        echo( '<form method="post" action="options.php">' );
        settings_fields( 'nggtags_for_media_library_settings' ); 
        do_settings_sections( 'nggtags_for_media_library_settings_page' );
        submit_button();
        echo( '</form>' );
    } );
    add_media_page( 'Tags for Media Library', 'Tags for Media Library', 'edit_posts', 'tml_upload', function() {
        $_GET['noheader'] = $_REQUEST['noheader'] = '1';
        include( dirname( __FILE__ ) . '/upload.php' );
    });
}, 11 );

add_action( 'load-media_page_tml_upload', function() {
//    new \WP_Media_List_Table_for_Ngg_Tags( null );
});

add_filter( 'plugin_action_links', function ( $actions, $plugin_file, $plugin_data, $context ) {
    if ( dirname( $plugin_file ) === basename( dirname( __FILE__ ) ) ) {
        array_unshift( $actions, '<a href="' . admin_url( 'options-general.php?page=nggtags_for_media_library_settings_page' ) . '">'
            . __( 'Settings' ) . '</a>' );
    }
    return $actions;
}, 10, 4 );
        

add_action( 'init', function ( ) use ( $version_1_1, $tml_post_type ) {
    $taxonomy_count = MAX_TAXONOMIES; 
    # check for pre 0.8.2 taxonomy database format and upgrade to 0.8.2 format if necessary
    if ( get_option( 'nggtags_for_media_library_taxonomy_slug_3', '' ) ) {
        for ( $i = $taxonomy_count; $i >= 3; $i-- ) {
            if ( $taxonomy_slug_value = get_option( "nggtags_for_media_library_taxonomy_slug_$i", '' ) ) {
                $j = $i + 1;
                update_option( "nggtags_for_media_library_taxonomy_slug_$j", $taxonomy_slug_value );
                update_option( "nggtags_for_media_library_taxonomy_name_$j",
                    get_option( "nggtags_for_media_library_taxonomy_name_$i", '' ) );
            }
        }
        update_option( 'nggtags_for_media_library_taxonomy_slug_3', '' );
        update_option( 'nggtags_for_media_library_taxonomy_name_3', '' );
    }

    // from version 1.1 NextGEN galleries and albums are stored in its own post type - "tml_galleries_albums"

    if ( $version_1_1 ) {
        register_post_type( 'tml_galleries_albums', $tml_post_type );
    }

    // do taxonomies

    for ( $i = 1; $i <= $taxonomy_count; $i++ ) {
        $taxonomy_slug = "nggtags_for_media_library_taxonomy_slug_$i";
        $taxonomy_name = "nggtags_for_media_library_taxonomy_name_$i";
        $taxonomy_name_value = get_option( $taxonomy_name );
        if ( $i === 1 ) {
            // create ngg_tag taxonomy for WordPress attachments.
            $taxonomy_slug_value = 'ngg_tag';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'NGG Tags'; }
        } else if ( $i === 2 ) {
            // priority taxonomy will be used to replace NextGEN Gallery's sortorder
            $taxonomy_slug_value = 'priority';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'Priority'; }
        } else if ( $i === 3 ) {
            // exclude taxonomy holds the NextGEN Gallery exclude flag
            $taxonomy_slug_value = 'exclude';
            if ( empty( $taxonomy_name_value ) ) { $taxonomy_name_value = 'Exclude'; }
        } else {  
            $taxonomy_slug_value = get_option( $taxonomy_slug );
        }
        if ( empty( $taxonomy_slug_value ) ) { continue; }
        register_taxonomy( $taxonomy_slug_value, 'attachment', array(
            'label'              => __( $taxonomy_name_value ),
            'labels'             => array(
                                        'name'          => _x( $taxonomy_name_value, 'taxonomy general name' ),
                                        'singular_name' => _x( $taxonomy_name_value, 'taxonomy singular name' ),
                                    ),
            'show_ui'            => true,
            'show_admin_column'  => true,
            'rewrite'            => array( 'slug' => $taxonomy_slug_value )
        ) );
        register_taxonomy_for_object_type( $taxonomy_slug_value, 'attachment' );
    }
} );

add_action( 'wp_enqueue_scripts', function () { wp_enqueue_script( 'jquery' ); } );

// Now register the NextGEN Gallery shortcodes.

include_once( dirname( __FILE__ ) . '/magic-fields-2-post-filter.php' );

/*
 * class Nggtags_for_Media_Library is just a wrapper so we can use the trait Magic_Fields_2_Toolkit_Post_Filters
 * which is defined in the above include.
 */
 
class Nggtags_for_Media_Library {
    use \Magic_Fields_2_Toolkit_Post_Filters;
}

function sort_ids_by_priority( $ids ) {
    global $wpdb;
    if ( !$ids ) { return $ids; }
    # get the priorities which are tags in the priority taxonomy 
    $list_ids = implode( ',', $ids );
    $sort_order = $wpdb->get_results( <<<EOD
SELECT r.object_id id, t.name priority FROM $wpdb->terms t, $wpdb->term_taxonomy x, $wpdb->term_relationships r
    WHERE t.term_id = x.term_id AND x.term_taxonomy_id = r.term_taxonomy_id AND x.taxonomy = 'priority'
        AND r.object_id IN ( $list_ids )       
EOD
        , OBJECT_K );
    uasort( $sort_order, function( $a, $b ) {
        if ( $a->priority == $b->priority ) {
            return $a->id == $b->id ? 0 : $a->id < $b->id ? -1 : 1;
        }
        return $a->priority < $b->priority ? -1 : 1;
    } );
    $sorted_ids = array_keys( $sort_order );
    // now append those ids that don't have a priority
    $ids = array_merge( $sorted_ids, array_diff( $ids, $sorted_ids ) );
    return $ids;
}

function emit_meta_for_images( $ids ) {
    global $wpdb;
    // $uploads = wp_get_upload_dir();
    // $uploads = $uploads['error'] === FALSE ? $uploads['basedir'] : FALSE;
    $posts   = get_posts( [ 'include'        => implode( ',', $ids ),
                            'post_status'    => 'inherit',
                            'post_type'      => 'attachment',
                            'post_mime_type' => 'image',
                            'order'          => 'ASC',
                            'orderby'        => 'post__in'
                          ] );
    $images  = [];
    foreach ( $posts as $post ) {
        $images[ $post->ID ]                        = [];
        $image                                      =& $images[ $post->ID ];
        $image['ID']                                = $post->ID;
        $image['post_content']                      = apply_filters( 'tml_the_content', apply_filters( 'the_content', $post->post_content ) );
        $image['post_title']                        = $post->post_title;
        $image['post_excerpt']                      = $post->post_excerpt;
        $author                                     = get_userdata( $post->post_author );
        $image['post_author']                       = $author ? $author->display_name : '';
        $image['post_name']                         = $post->post_name;
        $image['guid']                              = $post->guid;
        $image['post_mime_type']                    = $post->post_mime_type;
        $image['attachment_url']                    = wp_get_attachment_url( $post->ID );
        $image['attachment_image_src_thumbnail']    = wp_get_attachment_image_src( $post->ID, 'thumbnail' );
        $image['attachment_image_src_medium']       = wp_get_attachment_image_src( $post->ID, 'medium' );
        $image['attachment_image_src_medium_large'] = wp_get_attachment_image_src( $post->ID, 'medium_large' );
        $image['attachment_image_src_large']        = wp_get_attachment_image_src( $post->ID, 'large' );
        $image['attachment_image_src_full']         = wp_get_attachment_image_src( $post->ID, 'full' );
        $image['srcset']                            = wp_get_attachment_image_srcset( $post->ID );
        $image['attachment_link']                   = get_attachment_link( $post->ID );
        $image['taxonomy']                          = [];
    }
    $results = $wpdb->get_results( "SELECT post_id, meta_key, meta_value from $wpdb->postmeta WHERE post_id IN ( " . implode( ', ', $ids ) . ' )' );
    foreach ( $results as $result ) {
        if ( empty( $images[ $result->post_id ] ) ) {
            continue;
        }
        $images[ $result->post_id ][ $result->meta_key ] = maybe_unserialize( $result->meta_value );
    }
    $taxonomies = get_object_taxonomies( 'attachment', 'objects' );
    $taxonomies = array_filter( $taxonomies, function( $taxonomy ) {
        return ! ( $taxonomy->name === 'priority' || $taxonomy->name === 'exclude' );
    } );
    $taxonomy_slugs = array_keys( $taxonomies );
    $terms = wp_get_object_terms( $ids, $taxonomy_slugs, [ 'fields' => 'all_with_object_id' ] );
    foreach ( $terms as $term ) {
        if ( empty( $images[ $term->object_id ] ) ) {
            continue;
        }
        $images[ $term->object_id ]['taxonomy'][ $taxonomies[ $term->taxonomy ]->label ][] = $term->name;
    }  
    ob_start();
?>
<script type="text/javascript">
  window.nggml = window.nggml || {};
  window.nggml.images = window.nggml.images || {};
  jQuery.extend( window.nggml.images, <?php echo wp_json_encode($images); ?> );
  window.nggml.preserveIconAspectRatio = <?php echo get_option( 'nggml_preserve_thumbnail_size', 'enabled' ) === 'enabled' ? 'true' : 'false'; ?>;
</script>
<?php
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
}

/*
 * nggtags_func() uses Nggtags_for_Media_Library::get_posts_with_spec() to find the images that matches the nggtags tags
 * then constructs a shortcode for WordPress's 'gallery' shortcode with an 'ids' parameter and calls do_shortcode on the 
 * construct and returns this result.
 */

add_shortcode( 'nggtags', function ( $atts, $content, $tag ) {
    static $count = 0;
    if ( !$tag ) { $tag = $atts[0]; }
    extract( $atts );
    // pass all parameters except 'gallery' and 'album' to the WordPress builtin 'gallery' shortcode
    unset( $atts['gallery'], $atts['album'] );
    $args = '';
    $classes = [ "tag-$tag" ];
    $gallery_options = get_option( 'nggtags_for_media_library_gallery_options', '' );
    if ( !empty( $gallery_options ) ) {
        if ( preg_match_all( '/(^|\s)(tml_views?)=("|\')?([\w\s]+)\3/', $gallery_options, $all_matches, PREG_SET_ORDER ) ) {
            foreach ( $all_matches as $matches ) {
                $classes[] = "$matches[2]-" . str_replace( ' ', '_', $matches[4] );
            }
        }
        $gallery_options = ' ' . trim( $gallery_options );
    }
    foreach ( $atts as $att => $att_value ) {
        $args .= " $att=\"$att_value\"";
        $classes[] = $att . '-' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $att_value );
    }
    if ( !empty( $gallery ) ) {
        // this is a gallery
        // rewrite the default taxonomy 'ngg_tag' entry to include the implied taxonomy specifier 'ngg_tag:'
        // the duplicate below is not a mistake - two passes are sometimes necessary
        $gallery = preg_replace( '#(^|;)(([a-z_-]+,)*[a-z_-]+)(;|$)#', '$1ngg_tag:$2$4', $gallery );
        $gallery = preg_replace( '#(^|;)(([a-z_-]+,)*[a-z_-]+)(;|$)#', '$1ngg_tag:$2$4', $gallery );
        $ids = Nggtags_for_Media_Library::get_posts_with_spec( 'attachment:' . $gallery . ';exclude:-yes' );
        // reorder $ids using priorities saved in taxonomy priority
        $ids = sort_ids_by_priority( $ids );
        // use WordPress's built in gallery to do NextGEN Gallery's nggtags shortcode
        $gallery = do_shortcode( '[gallery ids="' . implode( ',', $ids ) . "\"{$gallery_options}{$args} nggml=\"1\"]" );
        if ( $classes ) {
            $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'',
                $gallery );
        }   
        return $gallery;
    }
    if ( !empty( $album ) ) {
        // this is an album
        $count++;
        $tags = explode( ',', $album );
        $image_ids = array();
        $gallery_image_ids = array();
        $gallery_image_tags = array();
        foreach( $tags as $tag ) {
            $ids = Nggtags_for_Media_Library::get_posts_with_spec( 'attachment:ngg_tag:' . $tag . ';exclude:-yes' );
            if ( empty( $ids ) ) {
                continue;
            }
            // reorder $ids using priorities saved in taxonomy priority
            $ids = sort_ids_by_priority( $ids );
            $image_ids[] = $ids;
            $gallery_image_ids[] = $ids[0];
            // get the term name for $tag
            $term_name = $tag;
            if ( is_array( $terms = get_the_terms( $ids[0], 'ngg_tag' ) ) ) {
                foreach ( $terms as $term ) {
                    if ( $term->slug === $tag ) {
                        $term_name = $term->name;
                        break;
                    }
                }
            }
            // save the term name to be used as caption for gallery
            $gallery_image_tags[] = $term_name;
        }
        // for albums make sure link is not set since we want the permalink for preg_replace
        unset( $atts['link'] );
        $album_args = '';
        foreach ( $atts as $att => $att_value ) {
            if ( $att === 'link' || $att === 'tml_view' ) { continue; }
            $album_args = " $att=\"$att_value\"";
        }
        // create an album of galleries
        // for albums make sure link is not set since we want the permalink for preg_replace
        $album_gallery_options = preg_replace( '#(^|\s)link=("|\')?\w+\2#', '', $gallery_options );
        // also for albums the main view must be standard
        $album_gallery_options = preg_replace( '#(^|\s)tml_views?=("|\')?[\w\s]+\2#', '', $album_gallery_options );
        $album = do_shortcode( '[gallery ids="' . implode( ',', $gallery_image_ids ) . "\"{$album_gallery_options}{$album_args} tml_view=\"standard\" tml_views=\"standard\" nggml=\"1\"]" );
        // replace the <a> element with a <span> element since we do not want that link
        $album = preg_replace( array( '#<a\s+data-attachment-id="(\d+)".+?>#', '#</a>#' ),
            array( '<span id="album-gallery-$1" class="album-gallery-icon">', '</span>' ), $album ); 
        // replace the image captions with the tag name
        $callback_count = 0;
        // handle the new <figcaption> tag from WordPress 4.0
        $album = preg_replace_callback( '#<figure\s.+?</figure>#s', function( $m )
            use ( $gallery_image_tags, &$callback_count ) {
            $figure = preg_replace_callback( '#(<figcaption[^>]*>)[^<]*</figcaption>#',
                function ( $m ) use ( $gallery_image_tags, $callback_count ) {
                    return $m[1] . $gallery_image_tags[$callback_count] . '</figcaption>';
            }, $m[0] );
            $callback_count++;
            return $figure;
        }, $album );
        if ( !$callback_count ) {
            // handle pre WordPress 4.0
            $album = preg_replace_callback( '#(<(\w+)\s+class=(\'|")wp-caption-text[^\'"]*\3[^>]*>)([^<]*)(</\2>)#',
                function ( $m ) use ( $gallery_image_tags, &$callback_count ) {
                    return $m[1] . $gallery_image_tags[$callback_count++] . $m[5];
                }, $album );
        }
        // Since there may be multiple albums use $count to give everything a unique identity
        $content .= "<div id='div-album-$count'>$album</div>";
        // now do the corresponding galleries
        $galleries = '';
        foreach ( $image_ids as $ids ) {
            $galleries .= "<div id='hidden-gallery-$ids[0]' class='hidden-gallery' style='display:none;'>";
            $galleries .= '<div><button class="nggml-button-back" style="float:right">Go Back to Album View</button></div>';
            $galleries .= '<br style="clear:both">';
            $gallery = do_shortcode( '[gallery ids="' . implode( ',', $ids ) . "\"{$gallery_options}{$args} nggml=\"1\"]" );
            if ( $classes ) {
                $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'',
                    $gallery );
            }
            $galleries .= $gallery;
            $galleries .= '<div style="margin-top:20px;"><button class="nggml-button-back" style="float:right" >Go Back to Album View</button></div></div>';
            $galleries .= '<br style="clear:both">';
        }
        $content .= "<div id='div-galleries-$count'>$galleries</div>";
        $script = <<<EOT
<script type="text/javascript">
    // install a click handler to show the corresponding gallery
    jQuery( "div#div-album-$count span.album-gallery-icon" ).click( function(e) {
        jQuery( "div#div-album-$count" ).css( "display", "none" );        
        jQuery( "div#div-galleries-$count div.hidden-gallery" ).css( "display", "none" );
        var gallery=jQuery( "div#div-galleries-$count div#hidden-gallery-" + this.id.substr( 14 ) );
        gallery.css( "display", "block" );
        gallery.find("select.nggml-gallery-select-view").change();
        e.stopImmediatePropagation();
        e.stopPropagation();
        e.preventDefault();
        e.returnValue=false;
        return false;
    } );
    // install a buttun click handler to go back to album view
    jQuery( "div#div-galleries-$count button.nggml-button-back" ).click( function() {
        jQuery( "div#div-galleries-$count div.hidden-gallery" ).css( "display", "none" );
        jQuery( "div#div-album-$count" ).css( "display", "block" );        
        altGallery.metaLocked=false;
        jQuery("div.nggml-alt-gallery-meta").css("color","#555");
        jQuery("div#nggml-meta-overlay").css({borderColor:"black",display:"none"});
    } );    
</script>
EOT;
        $content .= $script;
        return $content;
    }
} );

/*
 * nggallery_func() constructs a WordPress 'gallery' shortcode from the 'nggallery' shortcode and calls do_shortcode on the 
 * construct and returns this result.
 */
 
function do_nggallery( $atts, $content, $tag ) {
    global $wpdb;
    if ( !$tag ) { $tag = $atts[0]; }
    extract( $atts );
    $classes = [ "tag-$tag" ];
    if ( $tag === 'slideshow' ) { $classes[] = 'tml_view-slideshow'; }
    // get the global nggallery options
    $gallery_options = get_option( $tag === 'slideshow' ? 'slideshow_for_media_library_gallery_options'
        : 'nggallery_for_media_library_gallery_options', '' );
    if ( !empty( $gallery_options ) ) {
        if ( preg_match_all( '/(^|\s)(tml_views?)=("|\')?([\w\s]+)\3/', $gallery_options, $all_matches, PREG_SET_ORDER ) ) {
            foreach ( $all_matches as $matches ) {
                $classes[] = "$matches[2]-" . str_replace( ' ', '_', $matches[4] );
            }
        }
        $gallery_options = ' ' . trim( $gallery_options );
    }
    if ( empty( $id ) ) {
        return '';
    }
    $ids = $wpdb->get_col( $wpdb->prepare( <<<EOD
SELECT ID FROM $wpdb->posts p WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%%' AND p.post_parent = %d
    AND NOT EXISTS ( SELECT * FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x, $wpdb->terms t
        WHERE r.term_taxonomy_id = x.term_taxonomy_id AND x.term_id = t.term_id AND x.taxonomy = 'exclude' AND t.slug = 'yes' AND r.object_id = p.ID )
EOD
        , (integer) $id ) );
    if ( empty( $ids ) ) {
        return '';
    }
    $ids  = sort_ids_by_priority( $ids );
    $ids  = implode( ',', $ids );
    $args = '';
    unset( $atts['id'] );
    foreach ( $atts as $att => $att_value ) {
        $args .= " $att=\"$att_value\"";
        $classes[] = $att . '-' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $att_value );
    }
    // use WordPress's built in gallery to do NextGEN Gallery's nggallery shortcode
    $gallery = do_shortcode( "[gallery ids=\"{$ids}\"{$gallery_options}{$args} nggml=\"1\"]" );
    if ( $classes ) {
        $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'',
            $gallery );
    }
    return $gallery;
}

add_shortcode( 'nggallery', 'NggTags_for_Media_Library\do_nggallery' );
add_shortcode( 'slideshow', 'NggTags_for_Media_Library\do_nggallery' );

/*
 * singlepic_func() constructs a WordPress 'gallery' shortcode from the 'singlepic' shortcode and calls do_shortcode on the 
 * construct and returns this result.
 */
 
add_shortcode( 'singlepic', function ( $atts, $content, $tag ) {
    if ( !$tag ) { $tag = $atts[0]; }
    // get the global singlepic options
	extract( $atts );
    $gallery_options = get_option( 'singlepic_for_media_library_gallery_options', '' );
    if ( !empty( $gallery_options ) ) {
        $gallery_options = ' ' . trim( $gallery_options );
    }
    if ( empty( $id ) ) { return ''; }
    $ids = " ids=\"$id\"";
    unset( $atts['id'] );
    $args = '';
    $classes = [ "tag-$tag" ];
    foreach ( $atts as $att => $att_value ) {
        $args .= " $att=\"$att_value\"";
        $classes[] = $att . '-' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $att_value );
    }
    // use WordPress's built in gallery shortcode to do NextGEN Gallery's singlepic shortcode
    $gallery = do_shortcode( "[gallery{$ids}{$gallery_options}{$args} nggml=\"1\"]" );
    if ( $classes ) {
        $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'',
            $gallery );
    }
    return $gallery;
} );

add_shortcode( 'album', function ( $atts, $content, $tag ) {
    global $wpdb;
    static $count = 0;
    $count++;
    $gallery_ids = $wpdb->get_col( "SELECT post_content FROM $wpdb->posts WHERE ID = $atts[id]" );
    #error_log( 'shortcode:album:$gallery_ids=' . print_r( $gallery_ids, true ) );
    if ( !preg_match_all( '/\[nggallery\s+id="(\d+)"/', $gallery_ids[0], $matches, PREG_PATTERN_ORDER ) ) {
        return "$atts[id] is an invalid album id";
    }
    $gallery_ids = $matches[1];
    #error_log( 'shortcode:album:$gallery_ids=' . print_r( $gallery_ids, true ) );
    $image_ids = [];
    $gallery_image_ids = [];
    $gallery_image_tags = [];
    foreach ( $gallery_ids as $gallery_id ) {
        #error_log( 'shortcode:album:$gallery_id=' . $gallery_id );
        #$ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_parent = $gallery_id" );
        $ids = $wpdb->get_col( <<<EOD
SELECT ID FROM $wpdb->posts p WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%'
    AND p.post_parent = $gallery_id
    AND NOT EXISTS ( SELECT * FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x, $wpdb->terms t
        WHERE r.term_taxonomy_id = x.term_taxonomy_id AND x.term_id = t.term_id
            AND x.taxonomy = 'exclude' AND t.slug = 'yes' AND r.object_id = p.ID )
EOD
        );
        #error_log( 'shortcode:album:$ids=' . print_r( $ids, true ) );
        if ( !$ids ) { continue; }
        // reorder $ids using priorities saved in taxonomy priority
        $ids = sort_ids_by_priority( $ids );
        $image_ids[] = $ids;
        $cols = $wpdb->get_col( "SELECT post_title FROM $wpdb->posts WHERE ID = $gallery_id" );
        $gallery_image_tags[] = $cols ? $cols[0] : null;
        $cols = $wpdb->get_col(
            "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $gallery_id AND meta_key = '_thumbnail_id'" );
        $gallery_image_ids[] = $cols ? $cols[0] : null;
    }
    $classes = [ "tag-$tag" ];
    $gallery_options = get_option( 'nggallery_for_media_library_gallery_options', '' );
    if ( !empty( $gallery_options ) ) {
        if ( preg_match_all( '/(^|\s)(tml_views?)=("|\')?([\w\s]+)\3/', $gallery_options, $all_matches, PREG_SET_ORDER ) ) {
            foreach ( $all_matches as $matches ) {
                $classes[] = "$matches[2]-" . str_replace( ' ', '_', $matches[4] );
            }
        }
        $gallery_options = ' ' . trim( $gallery_options );
    }
    $args = '';
    foreach ( $atts as $att => $att_value ) {
        $args .= " $att=\"$att_value\"";
        $classes[] = $att . '-' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $att_value );
    }
    // for albums make sure link is not set since we want the permalink for preg_replace
    unset( $atts['link'] );
    $album_args = '';
    foreach ( $atts as $att => $att_value ) {
        if ( $att === 'link' || $att === 'tml_view' ) { continue; }
        $album_args = " $att=\"$att_value\"";
    }
    // create an album of galleries
    // for albums make sure link is not set since we want the permalink for preg_replace
    $album_gallery_options = preg_replace( '#(^|\s)link=("|\')?\w+\2#', '', $gallery_options );
    // also for albums the main view must be standard
    $album_gallery_options = preg_replace( '#(^|\s)tml_views?=("|\')?[\w\s]+\2#', '', $album_gallery_options );
    $album = do_shortcode( '[gallery ids="' . implode( ',', $gallery_image_ids ) . "\"{$album_gallery_options}{$album_args} tml_view=\"standard\" tml_views=\"standard\" nggml=\"1\"]" );
    // replace the <a> element with a <span> element since we do not want that link
    $album = preg_replace( array( '#<a\s+data-attachment-id="(\d+)".+?>#', '#</a>#' ),
        array( '<span id="album-gallery-$1" class="album-gallery-icon">', '</span>' ), $album ); 
    // replace the image captions with the tag name
    $callback_count = 0;
    // handle the new <figcaption> tag from WordPress 4.0
    $album = preg_replace_callback( '#<figure\s.+?</figure>#s', function( $m )
        use ( $gallery_image_tags, &$callback_count ) {
        $figure = preg_replace_callback( '#(<figcaption[^>]*>)[^<]*</figcaption>#',
            function ( $m ) use ( $gallery_image_tags, $callback_count ) {
                return $m[1] . $gallery_image_tags[$callback_count] . '</figcaption>';
        }, $m[0] );
        $callback_count++;
        return $figure;
    }, $album );
    if ( !$callback_count ) {
        // handle pre WordPress 4.0
        $album = preg_replace_callback( '#(<(\w+)\s+class=(\'|")wp-caption-text[^\'"]*\3[^>]*>)([^<]*)(</\2>)#',
            function ( $m ) use ( $gallery_image_tags, &$callback_count ) {
                return $m[1] . $gallery_image_tags[$callback_count++] . $m[5];
            }, $album );
    }
    // Since there may be multiple albums use $count to give everything a unique identity
    $content .= "<div id='div-album-$count'>$album</div>";
    // now do the corresponding galleries
    $galleries = '';
    for ( $i = 0; $i < count( $image_ids ); $i++ ) {
        $ids = $image_ids[$i];
        $galleries .= "<div id='hidden-gallery-$gallery_image_ids[$i]' class='hidden-gallery' style='display:none;'>";
        $galleries .= '<div><button class="nggml-button-back" style="float:right">Go Back to Album View</button></div>';
        $galleries .= '<br style="clear:both">';
        $gallery = do_shortcode( '[gallery ids="' . implode( ',', $ids ) . "\"{$gallery_options}{$args} nggml=\"1\"]" );
        if ( $classes ) {
            $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'',
                $gallery );
        }
        $galleries .= $gallery;
        $galleries .= '<div style="margin-top:20px;"><button class="nggml-button-back" style="float:right" >Go Back to Album View</button></div></div>';
        $galleries .= '<br style="clear:both">';
    }
    $content .= "<div id='div-galleries-$count'>$galleries</div>";
    $script = <<<EOT
<script type="text/javascript">
    // install a click handler to show the corresponding gallery
    jQuery( "div#div-album-$count span.album-gallery-icon" ).click( function(e) {
        jQuery( "div#div-album-$count" ).css( "display", "none" );        
        jQuery( "div#div-galleries-$count div.hidden-gallery" ).css( "display", "none" );
        var gallery=jQuery( "div#div-galleries-$count div#hidden-gallery-" + this.id.substr( 14 ) );
        gallery.css( "display", "block" );
        gallery.find("select.nggml-gallery-select-view").change();
        e.stopImmediatePropagation();
        e.stopPropagation();
        e.preventDefault();
        e.returnValue=false;
        return false;
    } );
    // install a buttun click handler to go back to album view
    jQuery( "div#div-galleries-$count button.nggml-button-back" ).click( function() {
        jQuery( "div#div-galleries-$count div.hidden-gallery" ).css( "display", "none" );
        jQuery( "div#div-album-$count" ).css( "display", "block" );        
    } );    
</script>
EOT;
    $content .= $script;
    return $content;
} );

add_action( 'wp_loaded', function( ) {
    remove_shortcode( 'gallery' );
    add_shortcode( 'gallery', function( $atts, $content, $tag ) {
        $nggml = FALSE;
        if ( !empty( $atts['nggml'] ) ) {
            unset( $atts['nggml'] );
            $nggml = TRUE;
        }
        $gallery_options = get_option( 'wp_gallery_for_media_library_gallery_options', '' );
        if ( !empty( $gallery_options ) ) {
            if ( empty( $atts ) ) {
                $atts = [];
            }
            $atts = array_merge( shortcode_parse_atts( $gallery_options ), $atts );
        }
        $gallery = gallery_shortcode( $atts );
        preg_match_all('#data-nggml-image-id=("|\')(\d*)\1#',$gallery,$matches);
        $classes = [ "tag-$tag" ];
        if ( !$nggml ) {
            if ( !empty( $gallery_options ) ) {
                if ( preg_match_all( '/(^|\s)(tml_views?)=("|\')?([\w\s]+)\3/', $gallery_options, $all_matches, PREG_SET_ORDER ) ) {
                    foreach ( $all_matches as $matches ) {
                        $classes[ ] = "$matches[2]-" . str_replace( ' ', '_', $matches[4] );
                    }
                }
                $gallery_options = ' ' . trim( $gallery_options );
            }
        }
        if ( is_array( $atts ) ) {
            foreach ( $atts as $att => $att_value ) {
                $classes[ ] = $att . '-' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $att_value );
            }
        }
        if ( $classes ) {
            $gallery = preg_replace( '/class=\'gallery\s([^\']+)\'/', 'class=\'gallery $1 ' . implode( ' ', $classes ) . '\'', $gallery );
        }
        return $gallery . emit_meta_for_images( $matches[2] ) .
            \Search_Media_Library_by_Taxonomy_Widget::output_overlay_search_widget() . \Search_Media_Library_by_Taxonomy_Widget::output_overlay_settings();
    } );
} );

function is_nggtags_media_library_request() {
    if ( strpos( $_SERVER['SCRIPT_NAME'], '/upload.php' ) === false ) { return false; }
    if ( !$_REQUEST || ( array_key_exists( 'action', $_REQUEST ) && $_REQUEST['action'] != -1 )
        || ( array_key_exists( 'action2', $_REQUEST ) && $_REQUEST['action2'] != -1 ) ) { return false; }
    if ( !array_key_exists( 's', $_REQUEST ) ) { return false; }
    if ( !( strpos( $_REQUEST['s'], 'tags:' ) === 0 ) && !( strpos( $_REQUEST['s'], 'gallery:' ) === 0 ) ) {
        return false;
    }    
    return true;
}

/*
 * posts_where_ngg_tags_filter() modifies the search when either 'tags:' or 'gallery:' prefix is present in the search criteria
 */
 
add_filter( 'posts_where', function ( $where ) {
    global $wpdb;
    if ( !is_nggtags_media_library_request() ) { return $where; }
    if ( strpos( $_REQUEST['s'], 'tags:' ) === 0 ) {
        // ngg_tag search
        list( , $tags ) = explode( ':', $_REQUEST['s'], 2 );
        $tags = '"' . implode( '","', array_map( function ( $tag ) { return strtolower( str_replace( ' ', '-', $tag ) ); },
            explode( ',', $tags ) ) ) . '"';
        if ( strpbrk( $tags, " \n\r\t\f()" ) !== false ) { die; } 
        $where = <<<EOT
 AND {$wpdb->posts}.post_type = 'attachment' AND ( {$wpdb->posts}.post_status = 'inherit' OR {$wpdb->posts}.post_status = 'private' ) 
 AND EXISTS ( SELECT * FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x, $wpdb->terms t
    WHERE r.object_id = {$wpdb->posts}.ID AND r.term_taxonomy_id = x.term_taxonomy_id AND x.term_id = t.term_id
        AND t.slug IN ( $tags ) )
EOT;
    } else if ( strpos( $_REQUEST['s'], 'gallery:' ) === 0 ) {
        // gallery search
        list( , $galleries ) = explode( ':', $_REQUEST['s'], 2 );
        $galleries = '"' . implode( '","', array_map( function ( $tag ) { return strtolower( str_replace( ' ', '-', $tag ) ); },
            explode( ',', $galleries ) ) ) . '"';
        if ( strpbrk( $galleries, " \n\r\t\f()" ) !== false ) { die; } 
        $galleries = $wpdb->get_col( "SELECT ID from $wpdb->posts where post_name IN ( $galleries )" );
        $galleries = ' "' . implode( '",', $galleries ) . '" ';
        $where = <<<EOT
 AND {$wpdb->posts}.post_type = 'attachment' AND ( {$wpdb->posts}.post_status = 'inherit' OR {$wpdb->posts}.post_status = 'private' ) 
 AND {$wpdb->posts}.post_parent IN ( $galleries )
EOT;
    } else {
        return $where;
    }
    return $where;
} );

add_filter( 'post_limits_request', function ( $limits ) {
    global $wpdb;
    if ( is_nggtags_media_library_request() ) {
        $limits = 'LIMIT 0, 256';
    }
    return $limits;
} );

add_action( 'wp_ajax_nggml_update_screen_options', function( ) {
    #$nggml_screen_options = json_decode( $_POST['nggml_screen_options'] );
    # make sure numbers are stored as numbers not strings
    if ( !wp_verify_nonce( $_POST['nggml_screen_options']['nonce'], 'nggml-screen-options' ) ) {
        die( 'Invalid Request' );
    }
    unset( $_POST['nggml_screen_options']['nonce'] );
    $nggml_screen_options = array_map( function( $v ) { return is_numeric($v) ? intval($v) : $v; },
        $_POST['nggml_screen_options'] );
    update_option( 'nggml_screen_options', $nggml_screen_options );
    die();
});

add_action( 'wp_ajax_nggml_get_image', function( ) {
    $id = (integer) $_REQUEST['nggml_image_id'];
    $image = get_post( $id, OBJECT );
?>
<div class="nggml-overlay-right">
    <?php echo wp_get_attachment_image( $id, 'full' ); ?>
</div>
<div class="nggml-overlay-left">
    <h1><?php echo get_the_title( $id ); ?></h1>
    <div class="nggml-overlay-caption"><p><?php echo $image->post_excerpt; ?></p></div>
    <div class="nggml-overlay-description"><p><?php echo $image->post_content; ?></p></div>
</div>
<?php
    die();
});

add_action( 'wp_ajax_nggml_get_media_table_rows', function( ) {
    global $wp_query;
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    require_once( ABSPATH . 'wp-admin/includes/class-wp-media-list-table.php' );
    require_once( dirname( __FILE__ ) . '/class-wp-media-list-table-for-nggtags.php' );
    $GLOBALS['hook_suffix']="nggtags-for-wp-media-library/upload.php";
    $wp_list_table = new \WP_Media_List_Table_for_Ngg_Tags( null );
    $wp_list_table->is_trash = false;
    query_posts( [ 'post_type' => 'attachment', 'post__in' => $_REQUEST['data'], 'post_status' => 'inherit',
        'posts_per_page' => -1 ] );
    $wp_list_table->display_rows();
    die;
} );

require_once( dirname( __FILE__ ) . '/nggtags-search-widget.php' );

if ( is_admin() === true ) {
    add_action( 'wp_ajax_nggml_clear_options_from_database', function() {
        if( !wp_verify_nonce( $_REQUEST['nonce'], 'nggml-clear-options' ) ) {
            die( 'Invalid Request' );
        }
        $deleted = 0;
        foreach ( [ 'wp_gallery_for_media_library_gallery_options',
            'nggtags_for_media_library_gallery_options', 'nggallery_for_media_library_gallery_options',
            'slideshow_for_media_library_gallery_options', 'singlepic_for_media_library_gallery_options',
            'search_results_for_media_library_gallery_options', 'nggml_user_css_file_url',
            'nggml_alt_high_density_gallery_enable', 'nggml_alt_high_density_gallery_image_width',
            'nggml_preserve_aspect_ratio_enable', 'nggml_miro_row_height', 'nggml_use_full_screen_slideshow_enable',
            'nggml_alt_high_density_gallery_focus_color', 'nggml_include_sidebar_with_search_results',
            'nggml_preserve_thumbnail_size', 'nggml_stretch_to_fit_enable', 'nggml_slideshow_interval',
            'nggml_transition', 'nggml_screen_options', 'widget_search_media_library_by_taxonomy_widget',
            'nggtags_for_media_library_taxonomy_slug_1', 'nggtags_for_media_library_taxonomy_name_1',
            'nggtags_for_media_library_taxonomy_slug_2', 'nggtags_for_media_library_taxonomy_name_2',
            'nggtags_for_media_library_taxonomy_slug_3', 'nggtags_for_media_library_taxonomy_name_3',
            'nggtags_for_media_library_taxonomy_slug_4', 'nggtags_for_media_library_taxonomy_name_4',
            'nggtags_for_media_library_taxonomy_slug_5', 'nggtags_for_media_library_taxonomy_name_5',
            'nggtags_for_media_library_taxonomy_slug_6', 'nggtags_for_media_library_taxonomy_name_6',
            'nggtags_for_media_library_taxonomy_slug_7', 'nggtags_for_media_library_taxonomy_name_7',
            'nggtags_for_media_library_taxonomy_slug_8', 'nggtags_for_media_library_taxonomy_name_8',
            'nggtags_for_media_library_taxonomy_slug_9', 'nggtags_for_media_library_taxonomy_name_9' ] as $option ) {
            if ( delete_option( $option ) ) { ++$deleted; }
        }
        echo "$deleted options deleted.";
        wp_die();
    } );
}

if ( !is_admin() ) {
    add_action( 'wp_enqueue_scripts', function() {
        global $wp_scripts;
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'nggml_search', plugins_url( 'nggml_search.css', __FILE__ ) );
        $user_css_url = get_option( 'nggml_user_css_file_url', '' );
        if ( $user_css_url ) {
            wp_enqueue_style( 'nggml_user', $user_css_url, [ 'nggml_search' ] );
        }
        wp_enqueue_style( 'justified-gallery', plugins_url( 'justifiedGallery.css', __FILE__ ) );
        // wp_enqueue_script( 'nggml-search', plugins_url( 'nggml-search.js', __FILE__ ), [ 'jquery', 'wp-util' ] );
        wp_enqueue_script( 'nggml-search', plugins_url( 'nggml-search.js', __FILE__ ), [ 'jquery', 'underscore' ] );
        wp_enqueue_script( 'justified-gallery', plugins_url( 'jquery.justifiedGallery.js', __FILE__ ), [ 'jquery' ] );
        $wp_scripts->add_data( 'nggml-search', 'data', 'var nggmlAltGalleryImageWidth='
            . get_option( 'nggml_alt_high_density_gallery_image_width', '64' ) . ';'
            . 'var nggmlAltGalleryFocusColor="'
            . get_option( 'nggml_alt_high_density_gallery_focus_color', '#ffff00' ) . '";'
            . 'var nggmlAltGalleryEnabled='
            . ( get_option( 'nggml_alt_high_density_gallery_enable', 'enabled' ) === 'enabled' ? 'true;' : 'false;' )
            . 'var nggmlStretchToFit='
            . ( get_option( 'nggml_stretch_to_fit_enable', '' ) === 'enabled' ? 'true;' : 'false;' )
            . 'var nggmlPreserveAspectRatio='
            . ( get_option( 'nggml_preserve_aspect_ratio_enable', 'enabled' ) === 'enabled' ? 'true;' : 'false;' )
            . 'var nggmlMiroRowHeight='
            . get_option( 'nggml_miro_row_height', '128' ) . ';'            
            . 'var nggmlUseFullScreenSlideShow='
            . ( get_option( 'nggml_use_full_screen_slideshow_enable', '' ) === 'enabled' ? 'true;' : 'false;' )
            . 'var nggmlSlideShowInterval='
            . get_option( 'nggml_slideshow_interval', '5000' ) . ';'
            . 'var nggmlTransition="'
            . get_option( 'nggml_transition', 'flip' ) . '";'
            . 'var ajaxurl="' . admin_url( 'admin-ajax.php' ) . '";' );
    } );
}

// if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && array_key_exists( 'search_media_library_for_nggtags_form', $_REQUEST ) &&
//             $_REQUEST['search_media_library_for_nggtags_form'] === 'search_media_library_for_nggtags_form' ) ) {
add_filter('wp_get_attachment_image_attributes', function( $attr, $attachment,$size ) {
    $attr['data-nggml-image-id']=$attachment->ID;
    return $attr;
}, 10, 3 );
add_filter( 'wp_get_attachment_link', function( $link, $id, $size, $permalink, $icon, $text ) {
    return preg_replace( '/<a\s/', "<a data-attachment-id=\"$id\" ", $link );
}, 100, 6 );
// }
?>
