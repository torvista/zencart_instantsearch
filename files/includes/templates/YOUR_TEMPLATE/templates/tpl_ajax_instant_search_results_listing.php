<?php

declare(strict_types=1);

/**
 * Adaptation of ZC 2.2.2 /templates/responsive_classic/tpl_modules_product_listing.php
 * for the Instant Search result page.
 */

/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * @updated  26/08/2026 torvista
 * @link     https://github.com/torvista/zencart_instantsearch
 */

/**
 * Module Template
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 Jan 27 Modified in v2.0.0-alpha1 $
 */

?>

<?php if ($show_top_submit_button) { // only show when there is something to submit and enabled ?>
    <div class="prod-list-wrap group">
        <div class="forward button-top">
            <?php echo zen_image_submit(BUTTON_IMAGE_ADD_PRODUCTS_TO_CART, BUTTON_ADD_PRODUCTS_TO_CART_ALT, 'id="submit1" name="submit1"'); ?>
        </div>
    </div>
<?php } // show top submit ?>

<?php if (in_array($product_listing_layout_style, ['columns', 'fluid'])) {
    require($template->get_template_dir('tpl_columnar_display.php', DIR_WS_TEMPLATE, $current_page_base, 'common') . '/tpl_columnar_display.php');
} else {
    require($template->get_template_dir('tpl_tabular_display.php', DIR_WS_TEMPLATE, $current_page_base, 'common') . '/tpl_tabular_display.php');
} ?>

<?php if ($show_bottom_submit_button) { // only show when there is something to submit and enabled ?>
    <div class="prod-list-wrap group">
        <div class="forward button-top">
            <?php echo zen_image_submit(BUTTON_IMAGE_ADD_PRODUCTS_TO_CART, BUTTON_ADD_PRODUCTS_TO_CART_ALT, 'id="submit2" name="submit2"'); ?>
        </div>
    </div>
<?php } // show_bottom_submit_button ?>

<?php if ($how_many > 0 && PRODUCT_LISTING_MULTIPLE_ADD_TO_CART != 0 && $show_submit && $listing_split->number_of_rows > 0) {
    echo '</form>';
} ?>
