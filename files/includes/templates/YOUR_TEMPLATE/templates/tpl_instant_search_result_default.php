<?php

declare(strict_types=1);

/**
 * Adaptation of ZC 2.2.2 \includes\templates\template_default\templates\tpl_search_result_default.php
 * for the Instant Search result page.
 */

/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * @updated  29/08/2026 torvista
 * @link     https://github.com/torvista/zencart_instantsearch
 */

/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=search_result.
 * Displays results of search
 *
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2026 Feb 19 Modified in v2.2.1 $
 */
?>
<div class="centerColumn" id="searchResultsDefault">
    <h1 id="searchResultsDefaultHeading"><?= HEADING_TITLE ?></h1>

<?php
if ($messageStack->size('search') > 0) {
    echo $messageStack->output('search');
}
?>
<?php // instant-search edit ?>
    <div id="filter-wrapper" class="group instantSearchResults__sorterRow" style="display:none">
<?php // eof ?>
<?php
if ($do_filter_list || PRODUCT_LIST_ALPHA_SORTER === 'true') {
// instant-search edit
  //$form = zen_draw_form('filter', zen_href_link(FILENAME_SEARCH_RESULT), 'get');
    $form = zen_draw_form('filter', zen_href_link(FILENAME_INSTANT_SEARCH_RESULT), 'get');
// eof
    $form .= '<label class="inputLabel">' . TEXT_SHOW . '</label>';
    echo $form;
    
    // -----
    // Don't include 'disp_order' and 'sort' if defaulted.
    //
    if (empty($_GET['disp_order']) || $_GET['disp_order'] === '8') {
        unset($_GET['disp_order']);
    }
    if (!empty($_GET['sort']) && $_GET['sort'] === '20a') {
        unset($_GET['sort']);
    }

    /* Redisplay all $_GET variables, except currency and page */
    echo zen_post_all_get_params(['currency', 'page']);
    require DIR_WS_MODULES . zen_get_module_directory(FILENAME_PRODUCT_LISTING_ALPHA_SORTER);

    echo '</form>';
}

/**
* display the product display-order dropdown
*/
// instant-search edit
// this module not currently working with instant-search TODO
//require $template->get_template_dir('/tpl_modules_listing_display_order.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_listing_display_order.php';
// eof 
?>
    </div>
<?php
/**
 * Used to collate and display products from search results
 */
// instant-search edit
// this module not required with instant search
//require $template->get_template_dir('tpl_modules_product_listing.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_product_listing.php';
// eof
?>
<?php // instant-search edit
//listing is inserted by JavaScript ?>
    <div id="productListing" class="group"></div>
<?php // eof ?>
    <div class="buttonRow back">
        <a href="<?= zen_href_link(FILENAME_SEARCH, zen_get_all_get_params(['sort', 'page', 'x', 'y']), 'NONSSL', true, false) ?>">
            <?= zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) ?>
        </a>
    </div>

</div>
