<html>
  <head>
<?php if ($title): ?>
    <title><?php e($title); ?> | pearhub.org</title>
<?php else: ?>
    <title>pearhub.org</title>
<?php endif; ?>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
    <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
    <link href="/feed.xml" rel="alternate" title="Latest packages | pearhub.org" type="application/atom+xml" />
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
<?php if ($title): ?>
<h1><?php e($title); ?></h1>
<?php endif; ?>
<?php echo $content; ?>
    </div>

  </body>
<?php foreach ($onload as $javascript): ?>
    <script type="text/javascript">
      <?php echo $javascript; ?>
    </script>
<?php endforeach; ?>
<script type="text/javascript" src="http://include.reinvigorate.net/re_.js"></script>
<script type="text/javascript">
<?php if (!$context->identity()->anonymous()): ?>
var re_name_tag = "<?php e($context->identity()->user()) ?>";
<?php endif; ?>
re_("ls8sp-7n86etz791");
</script>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-2258159-8");
pageTracker._trackPageview();
} catch(err) {}</script>
</html>
