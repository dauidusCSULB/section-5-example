<div class="wrap">
	<h1><?php echo $page_info['title']; ?></h1>

    <h2 class="nav-tab-wrapper kaui-settings">
        <?php foreach($tabs as $tab_array): ?>
        <a href="?page=<?=$default_slug?>&amp;tab=<?=$tab_array['tab']?>" class="nav-tab<?=($tab===$tab_array['tab']?' nav-tab-active':'')?>"><?=$tab_array['tab-title']?></a>
        <?php endforeach; ?>
    </h2>

    <?php // Setting Page
        if(in_array($slug, $setting_pages)):
        settings_errors();
    ?>

	<form method="post" action="options.php" class="kaui-settings">
        <input type="hidden" name="<?=$slug?>[options-page-identification]" value="<?=$slug?>">
        <?php
            settings_fields( $slug );
            do_settings_sections( $slug );
        ?>
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?=__( 'Save Settings', 'kodeo-admin-ui' )?>">
        </p>
	</form>

    <?php // Not a Setting Page
        else:
            include( PLUGIN_INC_PATH . 'templates/'.$slug.'.php' );
        endif;
    ?>
</div>
