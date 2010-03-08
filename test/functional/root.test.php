<?php
// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once dirname(__FILE__) . '/../../config/global.inc.php';
require_once 'konstrukt/virtualbrowser.inc.php';
require_once('document.php');

class WebTestOfRoot extends WebTestCase {
  function createBrowser() {
    $this->container = create_container();
    return new k_VirtualSimpleBrowser('components_Root', new k_InjectorAdapter($this->container, new Document()));
  }
  function createInvoker() {
    return new SimpleInvoker($this);
  }
  function test_root_prompts_for_login() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertLink("login");
  }
  function test_mock_auth() {
    $user = new k_AuthenticatedUser('lorem.ipsum');
    $this->container->get('k_adapter_MockSessionAccess')->set('identity', $user);
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertLink("logout");
  }
}
