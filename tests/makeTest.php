<?php

/**
 * Make makefile tests.
 * @group make
 * @group slow
 */
class makeMakefileCase extends Drush_CommandTestCase {
  /**
   * Run a given makefile test.
   *
   * @param $test
   *   The test makefile to run, as defined by $this->getMakefile();
   */
  private function runMakefileTest($test) {
    $default_options = array(
      'test' => NULL,
      'md5' => 'print',
    );
    $makefile_path = dirname(__FILE__) . '/makefiles';
    $config = $this->getMakefile($test);
    $options = array_merge($config['options'], $default_options);
    $makefile = $makefile_path . '/' . $config['makefile'];
    $return = !empty($config['fail']) ? self::EXIT_ERROR : self::EXIT_SUCCESS;
    $this->drush('make', array($makefile), $options, NULL, NULL, $return);

    // Check the log for the build hash if this test should pass.
    if (empty($config['fail'])) {
      $output = $this->getOutput();
      $this->assertContains($config['md5'], $output, $config['name'] . ' - build md5 matches expected value: ' . $config['md5']);
    }
  }

  function testMakeGet() {
    $this->runMakefileTest('get');
  }

  function testMakeGit() {
    $this->runMakefileTest('git');
  }

  function testMakeGitSimple() {
    $this->runMakefileTest('git-simple');
  }

  function testMakeNoPatchTxt() {
    $this->runMakefileTest('no-patch-txt');
  }

  function testMakePatch() {
    $this->runMakefileTest('patch');
  }

  function testMakeInclude() {
    $this->runMakefileTest('include');
  }

  function testMakeRecursion() {
    $this->runMakefileTest('recursion');
  }

  function testMakeSvn() {
    // Silently skip svn test if svn is not installed.
    exec('which svn', $output, $whichSvnErrorCode);
    if (!$whichSvnErrorCode) {
      $this->runMakefileTest('svn');
    }
    else {
      $this->markTestSkipped('svn command not available.');
    }
  }

  function testMakeBzr() {
    // Silently skip bzr test if bzr is not installed.
    exec('which bzr', $output, $whichBzrErrorCode);
    if (!$whichBzrErrorCode) {
      $this->runMakefileTest('bzr');
    }
    else {
      $this->markTestSkipped('bzr command is not available.');
    }
  }

  function testMakeTranslations() {
    $this->runMakefileTest('translations');
  }

  function testMakeTranslationsInside() {
    $this->runMakefileTest('translations-inside');
  }

  function testMakeContribDestination() {
    $this->runMakefileTest('contrib-destination');
  }

  function testMakeFile() {
    $this->runMakefileTest('file');
  }

  function testMakeMd5Succeed() {
    $this->runMakefileTest('md5-succeed');
  }

  function testMakeMd5Fail() {
    $this->runMakefileTest('md5-fail');
  }

  function testMakeIgnoreChecksums() {
    $this->runMakefileTest('ignore-checksums');
  }

  /**
   * Test .info file writing and the use of a git reference cache for
   * git downloads.
   */
  function testInfoFileWritingGit() {
    // Use the git-simple.make file.
    $config = $this->getMakefile('git-simple');

    $makefile_path = dirname(__FILE__) . '/makefiles';
    $options = array('no-core' => NULL);
    $makefile = $makefile_path . '/' . $config['makefile'];
    $this->drush('make', array($makefile, UNISH_SANDBOX . '/test-build'), $options);

    // Test cck_signup.info file.
    $this->assertFileExists(UNISH_SANDBOX . '/test-build/sites/all/modules/cck_signup/cck_signup.info');
    $contents = file_get_contents(UNISH_SANDBOX . '/test-build/sites/all/modules/cck_signup/cck_signup.info');
    $this->assertContains('; Information added by drush on ' . date('Y-m-d'), $contents);
    $this->assertContains('version = "2fe932c"', $contents);
    $this->assertContains('project = "cck_signup"', $contents);

    // Verify that a reference cache was created.
    $cache_dir = UNISH_SANDBOX . '/home/.drush/cache';
    $this->assertFileExists($cache_dir . '/git/cck_signup-' . md5('git://git.drupal.org/project/cck_signup.git'));

    // Test context_admin.info file.
    $this->assertFileExists(UNISH_SANDBOX . '/test-build/sites/all/modules/context_admin/context_admin.info');
    $contents = file_get_contents(UNISH_SANDBOX . '/test-build/sites/all/modules/context_admin/context_admin.info');
    $this->assertContains('; Information added by drush on ' . date('Y-m-d'), $contents);
    $this->assertContains('version = "eb9f05e"', $contents);
    $this->assertContains('project = "context_admin"', $contents);

    // Verify git reference cache exists.
    $this->assertFileExists($cache_dir . '/git/context_admin-' . md5('git://git.drupal.org/project/context_admin.git'));
  }

  function testMakeFileExtract() {
    $this->runMakefileTest('file-extract');
  }

  function getMakefile($key) {
    static $tests = array(
      'get' => array(
        'name'     => 'Test GET retrieval of projects',
        'makefile' => 'get.make',
        'build'    => TRUE,
        'md5' => '4bf18507da89bed601548210c22a3bed',
        'options'  => array('no-core' => NULL),
      ),
      'git' => array(
        'name'     => 'GIT integration',
        'makefile' => 'git.make',
        'build'    => TRUE,
        'md5' => '4c80d78b50c89b5ba11a997bafec2b43',
        'options'  => array('no-core' => NULL, 'no-gitinfofile' => NULL),
      ),
      'git-simple' => array(
        'name' => 'Simple git integration',
        'makefile' => 'git-simple.make',
        'build' => TRUE,
        'md5' => '6754a6814d4213326513ea750e6d5b65',
        'options'  => array('no-core' => NULL, 'no-gitinfofile' => NULL),
      ),
      'no-patch-txt' => array(
        'name'     => 'Test --no-patch-txt option',
        'makefile' => 'patches.make',
        'build'    => TRUE,
        'md5' => 'e43b25505a5edfcdf25b4eaa064978b2',
        'options'  => array('no-core' => NULL, 'no-patch-txt' => NULL),
      ),
      'patch' => array(
        'name'     => 'Test patching and writing of PATCHES.txt file',
        'makefile' => 'patches.make',
        'build'    => TRUE,
        'md5' => '27403b34b599af1cbdb50417e6ea626f',
        'options'  => array('no-core' => NULL),
      ),
      'include' => array(
        'name'     => 'Including files and property overrides',
        'makefile' => 'include.make',
        'build'    => TRUE,
        'md5' => 'e2e230ec5eccaf5618050559ab11510d',
        'options'  => array(),
      ),
      'recursion' => array(
        'name'     => 'Recursion',
        'makefile' => 'recursion.make',
        'build'    => TRUE,
        'md5' => 'a0357e99e2506fbf8629ae89d2f44096',
        'options'  => array('no-core' => NULL),
      ),
      'svn' => array(
        'name'     => 'SVN',
        'makefile' => 'svn.make',
        'build'    => TRUE,
        'md5' => '0cb28a15958d7fc4bbf8bf6b00bc6514',
        'options'  => array('no-core' => NULL),
      ),
      'bzr' => array(
        'name'     => 'Bzr',
        'makefile' => 'bzr.make',
        'build'    => TRUE,
        'md5' => '272e2b9bb27794c54396f2f03c159725',
        'options'  => array(),
      ),
      'translations' => array(
        'name'     => 'Translation downloads',
        'makefile' => 'translations.make',
        'build'    => TRUE,
        'md5' => '9b209494006aecd7f68c228a61bb26f9',
        'options'  => array(
          'translations' => 'es,pt-br',
          'no-core' => NULL,
        ),
      ),
      'translations-inside' => array(
        'name'     => 'Translation downloads inside makefile',
        'makefile' => 'translations-inside.make',
        'build'    => TRUE,
        'md5' => '0566b12158e6fba7070b80714ea4019d',
        'options'  => array(),
      ),
      'contrib-destination' => array(
        'name'     => 'Contrib-destination attribute',
        'makefile' => 'contrib-destination.make',
        'build'    => TRUE,
        'md5' => 'd615d004adfa8ebfe44e91119b88389c',
        'options'  => array('no-core' => NULL, 'contrib-destination' => '.'),
      ),
      'file' => array(
        'name'     => 'File extraction',
        'makefile' => 'file.make',
        'build'    => TRUE,
        'md5' => 'f76ec174a775ce67f8e9edcb02336ef2',
        'options'  => array('no-core' => NULL),
      ),
      'md5-succeed' => array(
        'name'     => 'MD5 validation',
        'makefile' => 'md5-succeed.make',
        'build'    => TRUE,
        'md5' => 'f76ec174a775ce67f8e9edcb02336ef2',
        'options'  => array('no-core' => NULL),
      ),
      'md5-fail' => array(
        'name'     => 'Failed MD5 validation test',
        'makefile' => 'md5-fail.make',
        'build'    => FALSE,
        'md5' => FALSE,
        'options'  => array('no-core' => NULL),
        'fail' => TRUE,
      ),
      'ignore-checksums' => array(
        'name'     => 'Ignore invalid checksum/s',
        'makefile' => 'md5-fail.make',
        'build'    => TRUE,
        'md5' => 'f76ec174a775ce67f8e9edcb02336ef2',
        'options'  => array('no-core' => NULL, 'ignore-checksums' => NULL),
      ),
      'file-extract' => array(
        'name'     => 'Extract archives',
        'makefile' => 'file-extract.make',
        'build'    => TRUE,
        'md5' => 'a7d0c50e7fb166ab717507e3797f5cbf',
        // @todo This test often fails with concurrency set to more than one.
        'options'  => array('no-core' => NULL, 'concurrency' => 1),
      ),
    );
    return $tests[$key];
  }
}
