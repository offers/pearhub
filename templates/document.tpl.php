<html>
  <head>
    <title><?php e($title); ?> | pearhub.org</title>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
    <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
  </head>
  <body>

    <div id="header">
      <div id="profile">
<?php if (!$context->identity()->anonymous()): ?>
        <form method="post" action="<?php e(url('/logout')) ?>">
          <p>
            Hello <strong><?php e(preg_replace('~^(http|https)://(.*)/$~', '$2', $context->identity()->user())) ?></strong>
            | <input type="submit" value="log out" id="logout" />
          </p>
        </form>
<?php endif; ?>
      </div>

      <div id="crumbtrail">
<?php
$tmp = array();
foreach ($crumbtrail as $crumb) {
  $tmp[] = html_link($crumb['url'], $crumb['title']);
}
echo implode(' / ', $tmp);
?>
      </div>
    </div>

    <div id="content">
<h1><?php e($title); ?></h1>
<?php echo $content; ?>
    </div>

  </body>
<?php foreach ($onload as $javascript): ?>
    <script type="text/javascript">
      <?php echo $javascript; ?>
    </script>
<?php endforeach; ?>
</html>
