<?php
if (defined('INSTANT_SEARCH_ENABLE') && INSTANT_SEARCH_ENABLE === 'true') { ?>
    <script>const searchInputWaitTime = <?php echo INSTANT_SEARCH_INPUT_WAIT_TIME; ?>; </script>
    <script src="<?php echo $template->get_template_dir('instant_search.js', DIR_WS_TEMPLATE, $current_page_base, 'jscript') . '/' . 'instant_search.js'; ?>" defer></script>
<?php }
