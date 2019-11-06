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

$field_userid   = $cfg['field_userid'];
$field_id       = $cfg['field_id'];
$field_uid      = $cfg['field_uid'];
$field_ugid     = $cfg['field_ugid'];
$field_ad_gid   = 'ad_gid';
$field_passwd   = $cfg['field_passwd'];
$field_homedir  = $cfg['field_homedir'];
$field_shell    = $cfg['field_shell'];
$field_name     = $cfg['field_name'];
$field_company  = $cfg['field_company'];
$field_email    = $cfg['field_email'];
$field_comment  = $cfg['field_comment'];
$field_disabled = $cfg['field_disabled'];

$field_login_count    = $cfg['field_login_count'];
$field_last_login     = $cfg['field_last_login'];
$field_create_date  = $cfg['field_create_date'];
$field_bytes_in_used  = $cfg['field_bytes_in_used'];
$field_bytes_out_used = $cfg['field_bytes_out_used'];
$field_files_in_used  = $cfg['field_files_in_used'];
$field_files_out_used = $cfg['field_files_out_used'];

if (empty($_REQUEST[$field_id])) {
  header("Location: ftp_list.php");
  die();
}

$groups = $ac->get_groups();

$id = $_REQUEST[$field_id];
if (!$ac->is_valid_id($id)) {
  $errormsg = 'Invalid ID; must be a positive integer.';
} else {
  $user = $ac->get_user_by_id($id);
  if (!is_array($user)) {
    $errormsg = 'User does not exist; cannot find ID '.$id.' in the database.';
  } else {
    $userid = $user[$field_userid];
    $ugid = $user[$field_ugid];
    $group = $ac->get_group_by_gid($ugid);
    if (!$group) {
      $warnmsg = 'Main group does not exist; cannot find GID '.$ugid.' in the database.';
    }
    $ad_gid = $ac->parse_groups($userid);
  }
}

if (empty($errormsg) && !empty($_REQUEST["action"]) && $_REQUEST["action"] == "update") {
  $errors = array();
  /* user id validation */
  if (empty($_REQUEST[$field_userid])
      || !preg_match($cfg['userid_regex'], $_REQUEST[$field_userid])
      || strlen($_REQUEST[$field_userid]) > $cfg['max_userid_length']) {
    array_push($errors, 'Invalid user name; user name must contain only letters, numbers, hyphens, and underscores with a maximum of '.$cfg['max_userid_length'].' characters.');
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
  /* gid validation */
  if (empty($_REQUEST[$field_ugid]) || !$ac->is_valid_id($_REQUEST[$field_ugid])) {
    array_push($errors, 'Invalid main group; GID must be a positive integer.');
  }
  /* password length validation */
  if (strlen($_REQUEST[$field_passwd]) > 0 && strlen($_REQUEST[$field_passwd]) < $cfg['min_passwd_length']) {
    array_push($errors, 'Password is too short; minimum length is '.$cfg['min_passwd_length'].' characters.');
  }
  /* shell validation */
  if (strlen($user[$field_shell]) <= 1) {
    array_push($errors, 'Invalid shell; shell cannot be empty.');
  }
  /* user name uniqueness validation */
  if ($userid != $_REQUEST[$field_userid] && $ac->check_username($_REQUEST[$field_userid])) {
    array_push($errors, 'User name already exists; name must be unique.');
  }
  /* gid existance validation */
  if (!$ac->check_gid($_REQUEST[$field_ugid])) {
    array_push($errors, 'Main group does not exist; GID '.$_REQUEST[$field_ugid].' cannot be found in the database.');
  }
  /* data validation passed */
  if (count($errors) == 0) {
    /* remove all groups */
    while (list($g_gid, $g_group) = each($groups)) {
      if (!$ac->remove_user_from_group($userid, $g_gid)) {
        array_push($errors, 'Cannot remove user "'.$userid.'" from group "'.$g_group.'"; see log files for more information.');
        break;
      }
      if($_REQUEST[$field_ugid] == $g_gid) {
        $name_group = $g_group;
      }
    }
  }
  if (count($errors) == 0) {
    /* update user */
    $disabled = isset($_REQUEST[$field_disabled]) ? '1':'0';
    $userdata = array($field_id       => $_REQUEST[$field_id],
                      $field_userid   => $user[$field_userid],
                      $field_uid      => $user[$field_uid],
                      $field_ugid     => $_REQUEST[$field_ugid],
                      $field_passwd   => $_REQUEST[$field_passwd],
                      $field_homedir  => $cfg['default_homedir'] . $name_group . "/" . $_REQUEST[$field_userid],
                      $field_shell    => $user[$field_shell],
                      $field_name     => $_REQUEST[$field_name],
                      $field_email    => $_REQUEST[$field_email],
                      $field_company  => $_REQUEST[$field_company],
                      $field_comment  => $_REQUEST[$field_comment],
                      $field_disabled => $disabled);
    if (!$ac->update_user($userdata)) {
      $errormsg = 'User "'.$_REQUEST[$field_userid].'" update failed; check log files.';
    } else {
      /* update user data */
      $user = $ac->get_user_by_id($id);
    }
  } else {
    $errormsg = implode($errors, "<br />\n");
  }
  if (empty($errormsg)) {
    /* update additional groups */
    $infomsg = 'User "'.$_REQUEST[$field_userid].'" updated successfully.';
  }
}

/* Form values */
if (empty($errormsg)) {
  /* Default values */
  $uid      = $user[$field_uid];
  $ugid     = $user[$field_ugid];
  $passwd   = '';
  $homedir  = substr($user[$field_homedir], strlen($cfg['default_homedir']));
  $shell    = $user[$field_shell];
  $name     = $user[$field_name];
  $email    = $user[$field_email];
  $company  = $user[$field_company];
  $comment  = $user[$field_comment];
  $disabled = $user[$field_disabled];
} else {
  /* This is a failed attempt */
  $userid   = $_REQUEST[$field_userid];
  $uid      = $_REQUEST[$field_uid];
  $ugid     = $_REQUEST[$field_ugid];
  $ad_gid   = $_REQUEST[$field_ad_gid];
  $passwd   = $_REQUEST[$field_passwd];
  $homedir  = $_REQUEST[$field_homedir];
  $shell    = $_REQUEST[$field_shell];
  $name     = $_REQUEST[$field_name];
  $email    = $_REQUEST[$field_email];
  $company  = $_REQUEST[$field_company];
  $comment  = $_REQUEST[$field_comment];
  $disabled = isset($_REQUEST[$field_disabled]) ? '1' : '0';
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
            <label for="<?php echo $field_ugid; ?>" class="col-sm-4 control-label">Main group</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_passwd; ?>" name="<?php echo $field_passwd; ?>" value="<?php echo $passwd; ?>" placeholder="Change password" readonly />
            </div>
          </div>
          <!-- Password -->
          <div class="form-group">
            <label for="<?php echo $field_passwd; ?>" class="col-sm-4 control-label">Password</label>
            <div class="controls col-sm-8">
              <input type="text" class="form-control" id="<?php echo $field_passwd; ?>" name="<?php echo $field_passwd; ?>" value="<?php echo $passwd; ?>" placeholder="Change password" readonly />
              <p class="help-block"><small>Minimum length <?php echo $cfg['min_passwd_length']; ?> characters.</small></p>
            </div>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <div class="col-sm-12">
              <input type="hidden" name="<?php echo $field_id; ?>" value="<?php echo $id; ?>" />
              <a class="btn btn-danger" href="remove_ftp.php?action=remove&<?php echo $field_id; ?>=<?php echo $id; ?>">Remove user</a>
              <button type="submit" class="btn btn-primary pull-right" name="action" value="update">Update user</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<?php include ("includes/footer.php"); ?>
