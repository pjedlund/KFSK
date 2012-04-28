<aside role="complementary">
<ul class="wrapper"><!-- start widget ul-wrapper -->

<?php if (is_home()) : ?>
<?php dynamic_sidebar('Frontpage'); ?>
<?php endif; //end is_home ?>

<?php //second sidbar
dynamic_sidebar('Sidebar'); ?>

</ul><!-- end widget ul-wrapper -->
</aside><!-- end complementary -->