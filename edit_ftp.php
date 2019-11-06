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

$field_id      = $cfg['field_id'];
$field_uid      = $cfg['field_uid'];
$field_login    = $cfg['field_login'];
$field_ftpname  = $cfg['field_ftpname'];
$field_passwd   = $cfg['field_passwd'];
$field_path     = $cfg['field_path'];
$field_shell    = $cfg['field_shell'];

$field_login_count    = $cfg['field_login_count'];
$field_last_login     = $cfg['field_last_login'];
$field_create_date  = $cfg['field_create_date'];
$field_bytes_in_used  = $cfg['field_bytes_in_used'];
$field_bytes_out_used = $cfg['field_bytes_out_used'];
$field_files_in_used  = $cfg['field_files_in_used'];
$field_files_out_used = $cfg['field_files_out_used'];

$passwd = $ac->generate_random_string((int) $cfg['default_passwd_length']);

if (empty($_REQUEST[$field_id])) {
  header("Location: ftp_list.php");
  die();
}


$id = $_REQUEST[$field_id];
if (!$ac->is_valid_id($id)) {
  $errormsg = 'Invalid ID; must be a positive integer.';
} else {
  $user = $ac->get_user_by_id($id);
  if (!is_array($user)) {
    $errormsg = 'User does not exist; cannot find ID '.$id.' in the database.';
  }
}

if (empty($errormsg) && !empty($_REQUEST["action"]) && $_REQUEST["action"] == "update") {
  $errors = array();
  /* user id validation */
  if (empty($_REQUEST[$field_ftpname])
      || !preg_match($cfg['ftpname_regex'], $_REQUEST[$field_ftpname])
      || strlen($_REQUEST[$field_ftpname]) > $cfg['max_ftpname_length']) {
    array_push($errors, 'Invalid user name; user name must contain only letters, numbers, hyphens, and underscores with a maximum of '.$cfg['max_ftpname_length'].' characters.');
  }
  /* uid validation */
  if (empty($user[$field_uid]) || !$ac->is_valid_id($user[$field_uid])) {
    array_push($errors, 'Invalid UID; must be a positive integer.');
  }
  if ($cfg['max_uid'] != -1 && $cfg['min_uid'] != -1) {
    if ($user[$field_uid] > $cfg['max_uid'] || $user[$field_uid] < $cfg['min_uid']) {
      array_push($errors, 'Invalid UID; UID must be between ' . $cfg['min_uid'] . ' and ' . $cfg['max_uid'] . '.');
    }
  } else if ($cfg['max_uid'] != -1 && $user[$field_uid] > $cfg['max_uid']) {
    array_push($errors, 'Invalid UID; UID must be at most ' . $cfg['max_uid'] . '.');
  } else if ($cfg['min_uid'] != -1 && $user[$field_uid] < $cfg['min_uid']) {
    array_push($errors, 'Invalid UID; UID must be at least ' . $cfg['min_uid'] . '.');
  }
  /* shell validation */
  if (strlen($user[$field_shell]) <= 1) {
    array_push($errors, 'Invalid shell; shell cannot be empty.');
  }
  if (count($errors) == 0) {
    /* update user */
    $userdata = array($field_id       => $_REQUEST[$field_id],
                      $field_passwd   => $passwd);
    if (!$ac->update_user($userdata)) {
      $errormsg = 'User "'.$_REQUEST[$field_ftpname].'" update failed; check log files.';
    } else {
      /* update user data */
      $user = $ac->get_user_by_id($id);
      header('Location: ftp_list.php?password='.$_REQUEST[$field_passwd]);
    }
  } else {
    $errormsg = implode($errors, "<br />\n");
  }
  if (empty($errormsg)) {
    /* update additional groups */
    $infomsg = 'User "'.$_REQUEST[$field_ftpname].'" updated successfully.';
  }
}

/* Form values */
if (empty($errormsg)) {
  /* Default values */
  $uid      = $user[$field_uid];
  $ftpname      = $user[$field_ftpname];
  $passwd   = $passwd;
}

include ("includes/header.php");
?>
<?php include ("includes/messages.php"); ?>

<?php if (is_array($user)) { ?>
<!-- FTP metadata panel -->
<div class="col-xs-12 col-sm-6">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">
        <a data-toggle="collapse" href="#userstats" aria-expanded="true" aria-controls="userstats">FTP statistics</a>
      </h3>
    </div>
    <div class="panel-body collapse in" id="userstats" aria-expanded="true">
      <div class="col-sm-12">
        <form role="form" class="form-horizontal" method="post" data-toggle="validator">
          <!-- Login count (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_login_count; ?>" class="col-sm-4 control-label">Login count</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_login_count; ?>" name="<?php echo $field_login_count; ?>" value="<?php echo $user[$field_login_count]; ?>" readonly />
            </div>
          </div>
          <!-- Last login (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_last_login; ?>" class="col-sm-4 control-label">Last login</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_last_login; ?>" name="<?php echo $field_last_login; ?>" value="<?php echo $user[$field_last_login]; ?>" readonly />
            </div>
          </div>
          <!-- Create date (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_create_date; ?>" class="col-sm-4 control-label">Create date</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_create_date; ?>" name="<?php echo $field_create_date; ?>" value="<?php echo $user[$field_create_date]; ?>" readonly />
            </div>
          </div>
          <!-- Bytes in (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_bytes_in_used; ?>" class="col-sm-4 control-label">Bytes uploaded</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_bytes_in_used; ?>" name="<?php echo $field_bytes_in_used; ?>" value="<?php echo sprintf("%2.1f", $user[$field_bytes_in_used] / 1048576); ?> MB" readonly />
            </div>
          </div>
          <!-- Bytes out (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_bytes_out_used; ?>" class="col-sm-4 control-label">Bytes downloaded</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_bytes_out_used; ?>" name="<?php echo $field_bytes_out_used; ?>" value="<?php echo sprintf("%2.1f", $user[$field_bytes_out_used] / 1048576); ?> MB" readonly />
            </div>
          </div>
          <!-- Files in (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_files_in_used; ?>" class="col-sm-4 control-label">Files uploaded</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_files_in_used; ?>" name="<?php echo $field_files_in_used; ?>" value="<?php echo $user[$field_files_in_used]; ?>" readonly />
            </div>
          </div>
          <!-- Files out (readonly) -->
          <div class="form-group">
            <label for="<?php echo $field_files_out_used; ?>" class="col-sm-4 control-label">Files downloaded</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_files_out_used; ?>" name="<?php echo $field_files_out_used; ?>" value="<?php echo $user[$field_files_out_used]; ?>" readonly />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Edit panel -->
<div class="col-xs-12 col-sm-6">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">
        <a data-toggle="collapse" href="#userprops" aria-expanded="true" aria-controls="userprops">FTP properties</a>
      </h3>
    </div>
    <div class="panel-body collapse in" id="userprops" aria-expanded="true">
      <div class="col-sm-12">
        <form role="form" class="form-horizontal" method="post" data-toggle="validator">
          <!-- FTP Name -->
          <div class="form-group">
            <label for="<?php echo $field_ftpname; ?>" class="col-sm-4 control-label">FTP Name</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_ftpname; ?>" name="<?php echo $field_ftpname; ?>" value="<?php echo $ftpname; ?>" readonly />
            </div>
          </div>
          <!-- Password -->
          <div class="form-group">
            <label for="<?php echo $field_passwd; ?>" class="col-sm-4 control-label">Password</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_passwd; ?>" name="<?php echo $field_passwd; ?>" value="<?php echo $passwd; ?>" readonly />
            </div>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <div class="col-sm-12">
              <input type="hidden" name="<?php echo $field_id; ?>" value="<?php echo $id; ?>" />
              <a class="btn btn-danger" href="remove_ftp.php?action=remove&<?php echo $field_id; ?>=<?php echo $id; ?>">Remove FTP</a>
              <button type="submit" class="btn btn-primary pull-right" name="action" value="update">Change password</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<?php include ("includes/footer.php"); ?>
