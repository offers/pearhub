<html>
  <head>
<title><?php e($title); ?></title>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
    <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
  </head>
  <body>

<?php if (!$context->identity()->anonymous()): ?>
<form method="post" action="<?php e(url('/logout')) ?>">
  <p>
    Hello <strong><?php e($context->identity()->user()) ?></strong>
    <input type="submit" value="Log out" />
  </p>
</form>
<?php endif; ?>
<?php echo $content; ?>
  </body>
<?php foreach ($onload as $javascript): ?>
    <script type="text/javascript">
      <?php echo $javascript; ?>
    </script>
<?php endforeach; ?>
</html>
