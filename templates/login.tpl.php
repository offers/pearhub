<?php echo html_form_tag('post', url(), array('id' => 'openid')); ?>
  <h2>OpenID Login</h2>
  <p>
<?php echo implode("<br/>", array_map('escape', $errors)) ?>
  </p>
  <div>
    <label>
      open-id url:
      <input type="text" name="openid_identifier" value="" />
      <input type="submit" value="Login" class="submit" />
    </label>
    <br/>
    <label><input type="checkbox" name="remember" /> Remember login for 30 days</label>
  </div>
  <h2>Don't have an OpenID login?</h2>
  <p>
    You can create a login for free at: <a href="https://www.myopenid.com/signup">myopenid.com</a>
  </p>
</form>
