<p class="question">
  How do automatic releases work?
</p>
<p class="answer">
  Automatic releases are the preferred mode of publishing packages. When this is selected, pearhub will routinely check the repository for new tags, following the naming convention of <code>X.X.X</code>. If a new tag is found, a package will be generated. This means that all you have to do to roll a new release is to tag it in your repository. Nothing more.
</p>

<p class="question">
  How do manual releases work?
</p>
<p class="answer">
  While we recommend automatic releases, it isn't always possible to change the naming convension. In these cases, you can use manual releases. A manual release is initiated by going to a projects releases page and select the link "Create a new release". Enter the new version number and press the button. Then wait for the crontab to come by. This can take up to 15 minutes.
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
  I have a not-so-frequesnt question. Where should I direct it?
</p>
<p class="answer">
  Try <a href="mailto:troelskn@gmail.com?subject=Question%20about%20pearhub">mailing me</a>.
</p>

<p class="question">
  Who's behind this?
</p>
<p class="answer">
  Me. I made it in my spare time, because noone else did. I'm Troels Knak-Nielsen. The channel publishing is driven by <a href="http://www.pirum-project.org/">pirum</a>. The frontend is driven by <a href="http://www.konstrukt.dk/">Konstrukt</a>. The open-id integration comes from <a href="http://framework.zend.com/manual/en/zend.openid.html">Zend Framework</a>.
</p>
