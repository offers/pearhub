<form method="post" action="<?= e(url())?>" id="openid">
  <h1>Authentication required</h1>
  <h2>OpenID Login</h2>
  <p>
<?= implode("<br/>", array_map('htmlspecialchars', $errors)) ?>
  </p>
  <p>
    <label>
      open-id url:
      <input type="text" name="openid_identifier" value="" />
      <input type="submit" value="Login" />
    </label>
  </p>
  <h2>Don't have an OpenID login?</h2>
  <p>
    You can create a login for free at: <a href="https://www.myopenid.com/signup">myopenid.com</a>
  </p>
</form>
