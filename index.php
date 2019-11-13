<?php
/**
 * This file is part of ProFTPd Admin
 *
 * @package ProFTPd-Admin
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @copyright Lex Brugman <lex_brugman@users.sourceforge.net>
 * @copyright Christian Beer <djangofett@gmx.net>
 * @copyright Ricardo Padilha <ricardo@droboports.com>
 *
 */

include_once ("configs/config.php");
include_once ("includes/AdminClass.php");
global $cfg;

$ac = new AdminClass($cfg);

$field_uid      = $cfg['field_uid'];
$field_login	= $cfg['field_login'];
$field_ftpname	= $cfg['field_ftpname'];
$field_passwd   = $cfg['field_passwd'];
$field_homedir	= $cfg['field_homedir'];
$field_shell    = $cfg['field_shell'];

$passwd = $ac->generate_random_string((int) $cfg['default_passwd_length']);
/* Data validation */
if (empty($errormsg) && !empty($_REQUEST["action"]) && $_REQUEST["action"] == "create") {
  $errors = array();
  /* SFTP name validation */
  if (empty($_REQUEST[$field_ftpname])
      || !preg_match($cfg['ftpname_regex'], $_REQUEST[$field_ftpname])
      || strlen($_REQUEST[$field_ftpname]) > $cfg['max_ftpname_length']) {
    array_push($errors, 'Invalid SFTP name; SFTP name must contain only letters, numbers, hyphens, and underscores with a maximum of '.$cfg['max_ftpname_length'].' characters.');
  }
  /* uid validation */
  if (empty($cfg['default_uid']) || !$ac->is_valid_id($cfg['default_uid'])) {
    array_push($errors, 'Invalid UID; must be a positive integer.');
  }
  if ($cfg['max_uid'] != -1 && $cfg['min_uid'] != -1) {
    if ($cfg['default_uid'] > $cfg['max_uid'] || $cfg['default_uid'] < $cfg['min_uid']) {
      array_push($errors, 'Invalid UID; UID must be between ' . $cfg['min_uid'] . ' and ' . $cfg['max_uid'] . '.');
    }
  } else if ($cfg['max_uid'] != -1 && $cfg['default_uid'] > $cfg['max_uid']) {
    array_push($errors, 'Invalid UID; UID must be at most ' . $cfg['max_uid'] . '.');
  } else if ($cfg['min_uid'] != -1 && $cfg['default_uid'] < $cfg['min_uid']) {
    array_push($errors, 'Invalid UID; UID must be at least ' . $cfg['min_uid'] . '.');
  }
  /* shell validation */
  if (strlen($cfg['default_shell']) <= 1) {
    array_push($errors, 'Invalid shell; shell cannot be empty.');
  }
  /* SFTP name uniqueness validation */
  if ($ac->check_username($_REQUEST[$field_ftpname])) {
    array_push($errors, 'SFTP name already exists; name must be unique.');
  }
  /* data validation passed */
  if (count($errors) == 0) {
    $userdata = array($field_uid      => $cfg['default_uid'],
		      $field_login    => $_SERVER['PHP_AUTH_USER'], 
                      $field_ftpname   => $_REQUEST[$field_ftpname],
                      $field_passwd   => $passwd,
                      $field_homedir  => $cfg['default_homedir'] . '/' . $_REQUEST[$field_ftpname],
                      $field_shell    => $cfg['default_shell']);
    if ($ac->add_user($userdata)) {
      $infomsg = 'SFTP "'.$_REQUEST[$field_ftpname].'" created successfully.';
      header('Location: ftp_list.php?create_ftpname=' . $_REQUEST[$field_ftpname] . '&create_password='. $_REQUEST[$field_passwd]);
    } else {
      $errormsg = 'SFTP "'.$_REQUEST[$field_ftpname].'" creation failed; check log files.';
    }
  } else {
    $errormsg = implode($errors, "<br />\n");
  }
}

/* Form values */
if (isset($errormsg)) {
  /* This is a failed attempt */
  $uid      = $cfg['default_uid'];
  $gid  = $cfg['default_gid'];
  $login  = $_REQUEST[$field_login];
  $ftpname   = $_REQUEST[$field_ftpname];
  $passwd   = $passwd;
  $homedir  = $cfg['default_homedir'];
  $shell    = $cfg['default_shell'];
} else {
  /* Default values */
  $ftpname   = "";
  if (empty($cfg['default_uid'])) {
    $uid    = $ac->get_last_uid() + 1;
  } else {
    $uid    = $cfg['default_uid'];
  }
  if (empty($infomsg)) {
    $shell  = "/bin/false";
  } else {
    $shell  = $cfg['default_shell'];
  }
  $uid      = $cfg['default_uid'];
  $passwd   = $passwd;
  $homedir  = $cfg['default_homedir'];
}

include ("includes/header.php");
?>
<?php include ("includes/messages.php"); ?>

<div class="col-xs-12 col-sm-8 col-md-6 center">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Add SFTP</h3>
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-12">
          <form role="form" class="form-horizontal" method="post" data-toggle="validator">
            <!-- SFTP name -->
            <div class="form-group">
              <label for="<?php echo $field_ftpname; ?>" class="col-sm-4 control-label">SFTP name <font color="red">*</font></label>
              <div class="controls col-sm-8">
                <input type="text" class="form-control" id="<?php echo $field_ftpname; ?>" name="<?php echo $field_ftpname; ?>" value="<?php echo $ftpname; ?>" placeholder="Name of your SFTP" maxlength="<?php echo $cfg['max_ftpname_length']; ?>" pattern="<?php echo substr($cfg['ftpname_regex'], 2, -3); ?>" required />
                <p class="help-block"><small>Only letters, numbers, hyphens, and underscores. Maximum <?php echo $cfg['max_ftpname_length']; ?> characters.</small></p>
              </div>
            </div>
            <!-- Password -->
            <div class="form-group">
              <label for="<?php echo $field_passwd; ?>" class="col-sm-4 control-label">Password</label>
              <div class="controls col-sm-8">
                <input type="text" class="form-control" id="<?php echo $field_passwd; ?>" name="<?php echo $field_passwd; ?>" value="<?php echo $passwd ?>" required readonly />
              </div>
            </div>
            <!-- Actions -->
            <div class="form-group">
              <div class="col-sm-12">
                <a class="btn btn-default" href="ftp_list.php">&laquo; View SFTP</a>
                <button type="submit" class="btn btn-primary pull-right" name="action" value="create">Create SFTP</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include ("includes/footer.php"); ?>
