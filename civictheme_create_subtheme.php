<?php

/**
 * @file
 * CivicTheme sub-theme scaffolding.
 *
 *  *
 * Environment variables:
 * - SCRIPT_QUIET: Set to '1' to suppress verbose messages.
 * - SCRIPT_RUN_SKIP: Set to '1' to skip running of the script. Useful when
 *   unit-testing or requiring this file from other files.
 *
 * Usage:
 * @code
 * php civictheme_create_subtheme.php new_machine_name "Human name" "Human description"
 *
 * php civictheme_create_subtheme.php new_machine_name "Human name" "Human description" path/to/theme/new_machine_name
 * @endcode
 *
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingBefore
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 */

/**
 * Defines exit codes.
 */
define('EXIT_SUCCESS', 0);
define('EXIT_ERROR', 1);

/**
 * Defines error level to be reported as an error.
 */
define('ERROR_LEVEL', E_USER_WARNING);

/**
 * Main functionality.
 */
function main(array $argv, $argc) {
  $default_new_theme_directory = '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'custom';

  if (in_array($argv[1] ?? NULL, ['--help', '-help', '-h', '-?'])) {
    print_help($default_new_theme_directory);

    return EXIT_SUCCESS;
  }

  // Show help if not enough or more than required arguments.
  if ($argc < 4 || $argc > 5) {
    print_help($default_new_theme_directory);

    return EXIT_ERROR;
  }

  // Collect and validate values from arguments.
  $new_theme_machine_name = trim($argv[1]);
  validate_theme_machine_name($new_theme_machine_name);
  $new_theme_name = trim($argv[2]);
  $new_theme_description = trim($argv[3]);
  $new_theme_directory = trim($argv[4] ?? $default_new_theme_directory . DIRECTORY_SEPARATOR . $new_theme_machine_name);

  // @todo Add check if path is absolute.
  $new_theme_directory = file_path_canonicalize(__DIR__ . DIRECTORY_SEPARATOR . $new_theme_directory);

  // Prepare theme stub.
  $stub_path = prepare_stub();

  // Process stub.
  process_stub($stub_path, [
    'machine_name' => $new_theme_machine_name,
    'name' => $new_theme_name,
    'description' => $new_theme_description,
    'path' => $new_theme_directory,
  ]);

  // Copy files from stub to the new theme path.
  file_copy_recursively($stub_path, $new_theme_directory);

  // Remove stub directory.
  @unlink($stub_path);

  // Print footer message.
  print_footer($new_theme_name, $new_theme_machine_name, $new_theme_directory);

  return EXIT_SUCCESS;
}

/**
 * Print help.
 */
function print_help($default_new_theme_dir) {
  $script_name = basename(__FILE__);
  print <<<EOF
CivicTheme Starter Kit scaffolding
----------------------------------

Arguments:
  machine_name           New theme machine name.
  name                   New theme human-readable name.
  description            New theme description.
  new_theme_directory    Optional new theme directory, including theme machine
                         name. Defaults to ${default_new_theme_dir}/machine_name.

Options:
  --help                 This help.

Examples:
  php ${script_name} civictheme_demo "CivicTheme Demo" "Demo sub-theme for a CivicTheme theme."

  php ${script_name} civictheme_demo "CivicTheme Demo" "Demo sub-theme for a CivicTheme theme." ../civictheme_demo

EOF;
  print PHP_EOL;
}

/**
 * Print footer.
 */
function print_footer($name, $machine_name, $path) {
  print <<<EOF

  $name ($machine_name) sub-theme was created successfully in "$path".

  NEXT STEPS
  ----------

  Insure that front-end assets can be built:

    cd $path
    npm ci
    npm run build
    npm run storybook

  Enable theme in Drupal:

    drush theme:enable civictheme -y
    drush config-set system.theme default civictheme
    drush theme:enable $machine_name -y
    drush config-set system.theme default $machine_name

EOF;
  print PHP_EOL;
}

/**
 * Validate theme machine name.
 */
function validate_theme_machine_name($name) {
  if (!preg_match('/^[a-z][a-z_0-9]*$/', $name)) {
    throw new \RuntimeException('Invalid machine name. Theme machine name can only start with lowercase letters and contain lowercase letters, numbers and underscores.');
  }
}

/**
 * Prepare theme stub files.
 *
 * @return string
 *   Path to stub directory.
 */
function prepare_stub() {
  $tmp_dir = file_tempdir();
  $starter_kit_dir = find_starter_kit_dir();
  file_copy_recursively($starter_kit_dir, $tmp_dir, file_ignore_paths());

  return $tmp_dir;
}

/**
 * Process stub directory.
 */
function process_stub($dir, $options) {
  $machine_name_hyphenated = str_replace('_', '-', $options['machine_name']);
  // @formatter:off
  // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
  // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
  file_replace_dir_content('civictheme_starter_kit',              $options['machine_name'], $dir);
  file_replace_dir_content('civictheme-starter-kit',              $machine_name_hyphenated, $dir);
  file_replace_dir_content('CivicTheme Starter Kit description.', $options['description'],  $dir);
  file_replace_dir_content('CivicTheme Starter Kit',              $options['name'],         $dir);

  file_replace_string_filename('civictheme_starter_kit',          $options['machine_name'], $dir);
  // @formatter:on
  // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
  // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

  // Resolve CivicTheme's location relative to the new theme.
  $current_dir = __DIR__;
  $relative_dir = file_get_relative_dir($options['path'], $current_dir);
  file_replace_file_content('../../contrib/civictheme/', $relative_dir, $dir . '/' . 'gulpfile.js');
  file_replace_file_content('../../contrib/civictheme/', $relative_dir, $dir . '/webpack/' . 'theme_js.js');
  file_replace_file_content('../../contrib/civictheme/', $relative_dir, $dir . '/' . 'package.json');
}

/**
 * Find starter kit directory.
 *
 * @return string
 *   Path to the Starter Kit directory.
 *
 * @throws \Exception
 *   If directory does not exist.
 */
function find_starter_kit_dir() {
  $dir = __DIR__ . DIRECTORY_SEPARATOR . 'civictheme_starter_kit';

  if (!file_exists($dir)) {
    throw new \Exception('Unable to find CivicTheme starter kit location.');
  }

  return $dir;
}

// ////////////////////////////////////////////////////////////////////////// //
//                        FILE MANIPULATORS                                   //
// ////////////////////////////////////////////////////////////////////////// //

/**
 * Recursively copy files and directories.
 *
 * The contents of $src will be copied as the contents of $dst.
 *
 * @param string $src
 *   Source directory to copy from.
 * @param string $dst
 *   Destination directory to copy to.
 * @param array $exclude
 *   Optional array of entries to exclude.
 * @param int $permissions
 *   Permissions to set on created directories. Defaults to 0755.
 * @param bool $copy_empty_dirs
 *   Flag to copy empty directories. Defaults to FALSE.
 *
 * @return bool
 *   TRUE if the result of copy was successful, FALSE otherwise.
 */
function file_copy_recursively($src, $dst, array $exclude = [], $permissions = 0755, $copy_empty_dirs = FALSE) {
  $parent = dirname($dst);

  if (!is_dir($parent)) {
    mkdir($parent, $permissions, TRUE);
  }

  // Note that symlink target must exist.
  if (is_link($src)) {
    // Changing dir symlink will be relevant to the current destination's file
    // directory.
    $cur_dir = getcwd();
    chdir($parent);
    $ret = TRUE;
    if (!is_readable(basename($dst))) {
      $ret = symlink(readlink($src), basename($dst));
    }
    chdir($cur_dir);

    return $ret;
  }

  if (is_file($src)) {
    $ret = copy($src, $dst);
    if ($ret) {
      chmod($dst, fileperms($src));
    }

    return $ret;
  }

  if (!is_dir($dst) && $copy_empty_dirs) {
    mkdir($dst, $permissions, TRUE);
  }

  $dir = dir($src);
  while (FALSE !== $entry = $dir->read()) {
    if ($entry == '.' || $entry == '..' || in_array($entry, $exclude)) {
      continue;
    }
    file_copy_recursively($src . DIRECTORY_SEPARATOR . $entry, $dst . DIRECTORY_SEPARATOR . $entry, $exclude, $permissions, $copy_empty_dirs);
  }

  $dir->close();

  return TRUE;
}

/**
 * Replace file content.
 */
function file_replace_file_content($needle, $replacement, $filename) {
  if (!is_readable($filename) || file_is_excluded_from_processing($filename)) {
    return FALSE;
  }

  $content = file_get_contents($filename);

  if (is_regex($needle)) {
    $replaced = preg_replace($needle, $replacement, $content);
  }
  else {
    $replaced = str_replace($needle, $replacement, $content);
  }
  if ($replaced != $content) {
    file_put_contents($filename, $replaced);
  }
}

/**
 * Replace directory content.
 */
function file_replace_dir_content($needle, $replacement, $dir) {
  $files = file_scandir_recursive($dir, file_ignore_paths());
  foreach ($files as $filename) {
    file_replace_file_content($needle, $replacement, $filename);
  }
}

/**
 * Replace a string in the file name.
 */
function file_replace_string_filename($search, $replace, $dir) {
  $files = file_scandir_recursive($dir, file_ignore_paths());
  foreach ($files as $filename) {
    $new_filename = str_replace($search, $replace, $filename);
    if ($filename != $new_filename) {
      $new_dir = dirname($new_filename);
      if (!is_dir($new_dir)) {
        mkdir($new_dir, 0777, TRUE);
      }
      rename($filename, $new_filename);
    }
  }
}

/**
 * Recursively scan directory for files.
 */
function file_scandir_recursive($dir, $ignore_paths = [], $include_dirs = FALSE) {
  $discovered = [];

  if (is_dir($dir)) {
    $paths = array_diff(scandir($dir), ['.', '..']);
    foreach ($paths as $path) {
      $path = $dir . '/' . $path;
      foreach ($ignore_paths as $ignore_path) {
        // Exclude based on sub-path match.
        if (strpos($path, $ignore_path) !== FALSE) {
          continue(2);
        }
      }
      if (is_dir($path)) {
        if ($include_dirs) {
          $discovered[] = $path;
        }
        $discovered = array_merge($discovered, file_scandir_recursive($path, $ignore_paths, $include_dirs));
      }
      else {
        $discovered[] = $path;
      }
    }
  }

  return $discovered;
}

/**
 * Ignore path.
 */
function file_ignore_paths() {
  return array_merge([
    '.git',
    '.idea',
    '.components-civictheme',
    '.data',
    'components_combined',
    'dist',
    'node_modules',
    'storybook-static',
    'vendor',
  ], file_internal_paths());
}

/**
 * Internal paths to ignore.
 */
function file_internal_paths() {
  return [];
}

/**
 * Check if the file ia excluded from the processing.
 */
function file_is_excluded_from_processing($filename) {
  $excluded_patterns = [
    '.+\.png',
    '.+\.jpg',
    '.+\.jpeg',
    '.+\.bpm',
    '.+\.tiff',
  ];

  return preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', $filename);
}

/**
 * Creates a random unique temporary directory.
 */
function file_tempdir($dir = NULL, $prefix = 'tmp_', $mode = 0700, $max_attempts = 1000) {
  if (is_null($dir)) {
    $dir = sys_get_temp_dir();
  }

  $dir = rtrim($dir, DIRECTORY_SEPARATOR);

  if (!is_dir($dir) || !is_writable($dir)) {
    return FALSE;
  }

  if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
    return FALSE;
  }
  $attempts = 0;

  do {
    $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
  } while (!mkdir($path, $mode) && $attempts++ < $max_attempts);

  if (!is_dir($path) || !is_writable($path)) {
    throw new \RuntimeException(sprintf('Unable to create temporary directory "%s".', $path));
  }

  return $path;
}

/**
 * Get relative directory path from 2 directory paths.
 *
 * This function does not rely on existence of paths.
 *
 * @param string $dir1
 *   First dir path to compare.
 * @param string $dir2
 *   Second dir path to compare.
 *
 * @return string
 *   Relative path between 2 directories.
 */
function file_get_relative_dir($dir1, $dir2) {
  $dir1 = rtrim($dir1, '/') . '/';
  $dir2 = rtrim($dir2, '/') . '/';

  if ($dir1 === $dir2) {
    return './';
  }

  $dir1 = explode('/', $dir1);
  $dir2 = explode('/', $dir2);
  $parts = $dir2;

  foreach ($dir1 as $depth => $dir) {
    if ($dir === $dir2[$depth]) {
      array_shift($parts);
    }
    else {
      $remaining = count($dir1) - $depth;
      if ($remaining > 1) {
        $parts = array_pad($parts, -1 * (count($parts) + $remaining - 1), '..');
        break;
      }
      else {
        $parts[0] = './' . $parts[0];
      }
    }
  }

  return implode('/', $parts);
}

/**
 * Canonicalize the path by removing './' and '../'.
 *
 * @param string $path
 *   Path that need to be canonicalized.
 *
 * @return string
 *   Path with all '.', '..', './' and '../' removed.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-5.2.4
 */
function file_path_canonicalize($path) {
  $output = '';

  while ($path !== '') {
    if (
      ($prefix = substr($path, 0, 3)) == '../' ||
      ($prefix = substr($path, 0, 2)) == './'
    ) {
      $path = substr($path, strlen($prefix));
    }
    elseif (
      ($prefix = substr($path, 0, 3)) == '/./' ||
      ($prefix = $path) == '/.'
    ) {
      $path = '/' . substr($path, strlen($prefix));
    }
    elseif (
      ($prefix = substr($path, 0, 4)) == '/../' ||
      ($prefix = $path) == '/..'
    ) {
      $path = '/' . substr($path, strlen($prefix));
      $output = substr($output, 0, strrpos($output, '/'));
    }
    else {
      if ($path == '.' || $path == '..') {
        $path = '';
      }
      else {
        $pos = strpos($path, '/');
        if ($pos === 0) {
          $pos = strpos($path, '/', $pos + 1);
        }
        if ($pos === FALSE) {
          $pos = strlen($path);
        }
        $output .= substr($path, 0, $pos);
        $path = substr($path, $pos);
      }
    }
  }

  return $output;
}

// ////////////////////////////////////////////////////////////////////////// //
//                                HELPERS                                     //
// ////////////////////////////////////////////////////////////////////////// //

/**
 * Check if the provided string is a regular expression.
 */
function is_regex($str) {
  if (preg_match('/^(.{3,}?)[imsxuADU]*$/', $str, $m)) {
    $start = substr($m[1], 0, 1);
    $end = substr($m[1], -1);

    if ($start === $end) {
      return !preg_match('/[*?[:alnum:] \\\\]/', $start);
    }

    foreach ([['{', '}'], ['(', ')'], ['[', ']'], ['<', '>']] as $delimiters) {
      if ($start === $delimiters[0] && $end === $delimiters[1]) {
        return TRUE;
      }
    }
  }

  return FALSE;
}

/**
 * Show a verbose message.
 */
function verbose() {
  if (getenv('SCRIPT_QUIET') != '1') {
    print call_user_func_array('sprintf', func_get_args()) . PHP_EOL;
  }
}

// ////////////////////////////////////////////////////////////////////////// //
//                                ENTRYPOINT                                  //
// ////////////////////////////////////////////////////////////////////////// //

ini_set('display_errors', 1);

if (PHP_SAPI != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

// Allow to skip the script run.
if (getenv('SCRIPT_RUN_SKIP') != 1) {
  // Custom error handler to catch errors based on set ERROR_LEVEL.
  set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
      // This error code is not included in error_reporting.
      return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
  });

  try {
    $code = main($argv, $argc);
    if (is_null($code)) {
      throw new \Exception('Script exited without providing an exit code.');
    }
    exit($code);
  }
  catch (\ErrorException $exception) {
    if ($exception->getSeverity() <= ERROR_LEVEL) {
      print PHP_EOL . 'RUNTIME ERROR: ' . $exception->getMessage() . PHP_EOL;
      exit($exception->getCode() == 0 ? EXIT_ERROR : $exception->getCode());
    }
  }
  catch (\Exception $exception) {
    print PHP_EOL . 'ERROR: ' . $exception->getMessage() . PHP_EOL;
    exit($exception->getCode() == 0 ? EXIT_ERROR : $exception->getCode());
  }
}
