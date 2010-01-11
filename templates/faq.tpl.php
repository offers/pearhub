<p class="question">
  What is PEAR?
</p>
<p class="answer">
  PEAR is two things; It's a public repository of libraries, and it's a distribution system for publishing php libraries. Pearhub has nothing to do with the PEAR repository - it just uses the protocols of the distribution system. You can read more about PEAR on <a href="http://pear.php.net/manual/en/guide.users.concepts.php">the pear website</a>.
</p>

<p class="question">
  Is there a tutorial somewhere?
</p>
<p class="answer">
  Glad you asked; Rob Morgan has put together <a href="http://robmorgan.id.au/post/322528178/howto-publish-a-pear-package-on-pearhub">a nice little tutorial</a>, showing how to use pearhub with a project hosted on github. Give it a try.
</p>

<p class="question">
  How do automatic releases work?
</p>
<p class="answer">
  Automatic releases are the preferred mode of publishing packages. When this is selected, pearhub will routinely check the repository for new tags, following the naming convention of <code>X.X.X</code>. If a new tag is found, a package will be generated. This means that all you have to do to roll a new release is to tag it in your repository. Nothing more. (Note that it may take a few minutes for the system to recognise it)
</p>

<p class="question">
  How do manual releases work?
</p>
<p class="answer">
  While I recommend automatic releases, it isn't always possible to change the naming convension. In these cases, you can use manual releases. A manual release is initiated by going to a projects releases page and select the link "Create a new release". Enter the new version number and press the button. Then wait for the crontab to come by. This can take up a few minutes.
</p>

<p class="question">
  What's this naming convension?
</p>
<p class="answer">
  For pearhub to pick up a tagged release, you need to follow a naming convension called <a href="http://semver.org/">Semantic Versioning</a>. If you use subversion, you should tag your releases in <code>tags/vX.X.X</code>, where the X's are replaced with numbers. If you use git, tag your releases as <code>vX.X.X</code>. You may leave out the leading <strong>v</strong>.
</p>

<p class="question">
  How do I tag a release in subversion?
</p>
<p class="answer">
  From the console, assuming that you stand in the repository root, type:
</p>
<pre>$ svn cp trunk tags/X.X.X
$ svn commit -m "Tagged release X.X.X" tags/X.X.X</pre>

<p class="question">
  How do I tag a release in git?
</p>
<p class="answer">
  From the console, type:
</p>
<pre>$ git tag -a vX.X.X -m "Tagged release X.X.X"
$ git push origin : vX.X.X</pre>

<p class="question">
  What's the meaning of those diferent numbers?
</p>
<p class="answer">
  The meaning of these are <em>major</em>, <em>minor</em> and <em>patch</em>. The first publicly available release should be <code>1.0.0</code>. When you add new features, bump the minor number a notch. If you make major changes, breaking backwards compatibility, bump the major number. For smaller bug-fixes and additions, use the patch number. Note that numbers are not restricted to be a single digit - it's perfectly valid to have version numbers like <code>1.23.0</code>.
  <br/>
  See: <a href="http://semver.org/">Semantic Versioning</a> for a more detailed explanation.
</p>

<p class="question">
  How do I create a profile?
</p>
<p class="answer">
  You don't - there's no need. Just login with an <a href="http://openid.net/">OpenID</a> service, and we'll use that for controlling access rights.
</p>

<p class="question">
  Can I manage someone else's project?
</p>
<p class="answer">
  Yes. If you need a pear package for a project, you should try to contact the maintainer first and have them setup the project here. If that's not possible, you can set it up your self. Unless they follow the required tagging convention, you'll have to run manual releases.
</p>

<p class="question">
  Someone <em>stole</em> my project. The bastard!
</p>
<p class="answer">
  Chill. It's probably a fan who just wanted an easy way to install your awesome project. If you want to take over, please contact me and we'll sort the transfer out.
</p>

<p class="question">
  Is pearhub affiliated with github?
</p>
<p class="answer">
  Nope. I'm just really lame at making up names.
</p>

<p class="question">
  I have a not-so-frequent question. Where should I direct it?
</p>
<p class="answer">
  You really should use the feedback forum at <a href="http://pearhub.uservoice.com/">uservoice</a>, but if you can also contact me directly at <a href="mailto:troelskn@gmail.com?subject=Question%20about%20pearhub">mailing me</a>.
</p>

<p class="question">
  Who's behind this?
</p>
<p class="answer">
  Me. I made it in my spare time, because noone else did. I'm Troels Knak-Nielsen. The channel publishing is driven by <a href="http://www.pirum-project.org/">pirum</a>. The frontend is driven by <a href="http://www.konstrukt.dk/">Konstrukt</a>. The OpenID integration comes from <a href="http://framework.zend.com/manual/en/zend.openid.html">Zend Framework</a>.
</p>

<p class="question">
  Is pearhub one of a kind or are there any similar projects?
</p>
<p class="answer">
  I'm currently aware of <a href="http://pearfarm.org/">pearfarm</a> and <a href="http://openpear.org/">openpear</a>. They both offer a slightly different experience.
</p>
