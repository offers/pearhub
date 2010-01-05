<h4>Install</h4>
<p>
  Pearhub is a pear channel. This means that you can install packages through the pear installer, with a single line of code.
</p>
<p>
  First initialize the pearhub channel. You only need to do this once:
</p>
<pre>$ pear channel-discover pearhub.org</pre>
<p>
  Then go and <?php echo html_link(url('projects'), "find a project"); ?> to install. It's as easy as typing:
</p>
<pre>$ pear install pearhub/PackageName</pre>

<h4>Publish</h4>

<p>Already have a project on github, google code or another public repository? Want to provide an easy way to install and update it?</p>
<p><?php echo html_link(url('projects', array('new')), "Register your project on pearhub"); ?> and have it appear in the channel in minutes. Promise!</p>

<h4>¿Confused?</h4>

<p>Try the <?php echo html_link(url('/faq'), 'FAQ'); ?> - full of made-up slightly-condenscending questions and answers.</p>
