<form method="get" id="Searchform" action="<?php bloginfo('home'); ?>/">
    <label class="visuallyhidden" for="s">Sök hela webbplatsen</label>
    <?php if (is_search()) : ?>
    <input type="text" name="s" id="Searchbar" value="<?php echo wp_specialchars($s, 1); ?>" />
    <?php else : ?>
    <input type="text" name="s" id="Searchbar" value="Sök..." />
    <?php endif; ?>
    <input class="submit" type="submit" id="Searchbtn" value="Sök" />
</form>