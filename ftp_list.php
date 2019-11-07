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
$field_create_date     = $cfg['field_create_date'];
$field_bytes_in_used  = $cfg['field_bytes_in_used'];
$field_bytes_out_used = $cfg['field_bytes_out_used'];
$field_files_in_used  = $cfg['field_files_in_used'];
$field_files_out_used = $cfg['field_files_out_used'];

$all_users = $ac->get_users();
$users = array();

/* return FTP name and password */
$update_ftpname = $_GET["update_ftpname"];
$update_password = $_GET["update_password"];

$create_ftpname = $_GET["create_ftpname"];
$create_password = $_GET["create_password"];

if (!isset($_GET["create_ftpname"]) && !isset($_GET["create_password"])) {
  $infomsg = 'The FTP has been created. <br /> FTP name: ' . $create_ftpname . ' <br /> Password: ' . $create_password;
}
if (!isset($_GET["update_ftpname"]) && !isset($_GET["update_password"])) {
  $infomsg = 'The FTP password has been updated. <br /> FTP name: ' . $update_ftpname . ' <br /> Password: ' . $update_password;
}

/* parse filter  */
$userfilter = array();
$ufilter="";
// see config_example.php on howto activate
if ($cfg['ftpname_filter_separator'] != "") {
  $ufilter = isset($_REQUEST["uf"]) ? $_REQUEST["uf"] : "";
  foreach ($all_users as $user) {
    $pos = strpos($user[$field_ftpname], $cfg['ftpname_filter_separator']);
    // ftpname's should not start with a - !
    if ($pos != FALSE) {
      $prefix = substr($user[$field_ftpname], 0, $pos);
      if(@$userfilter[$prefix] == "") {
        $userfilter[$prefix] = $prefix;
      }
    }
  }
}

/* filter users */
if (!empty($all_users)) {
  foreach ($all_users as $user) { 
    if ($ufilter != "") {
      if ($ufilter == "None" && strpos($user[$field_ftpname], $cfg['ftpname_filter_separator'])) {
        // filter is None and user has a prefix
        continue;
      }
      if ($ufilter != "None" && strncmp($user[$field_ftpname], $ufilter, strlen($ufilter)) != 0) {
        // filter is something else and user does not have a prefix
      	continue;
      }
    }
    $users[] = $user;
  }
}

include ("includes/header.php");
?>
<?php include ("includes/messages.php"); ?>

<?php if(!is_array($all_users)) { ?>
<div class="col-sm-12">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Users</h3>
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <p>Currently there are no registered users.</p>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <a class="btn btn-primary pull-right" href="create_ftp.php" role="button">Create FTP &raquo;</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } else { ?>
<div class="col-sm-12">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Users</h3>
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-12">
          <?php if (count($userfilter) > 0) { ?>
          <!-- Filter toolbar -->
          <div class="form-group">
            <label>Prefix filter:</label>
            <div class="btn-group" role="group">
              <a type="button" class="btn btn-default" href="ftp_list.php">All users</a>
              <a type="button" class="btn btn-default" href="ftp_list.php?uf=None">No prefix</a>
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" id="idPrefix" data-toggle="dropdown" aria-expanded="false">Prefix <span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu" aria-labelledby="idPrefix">
                <?php foreach ($userfilter as $uf) { ?>
                  <li role="presentation"><a role="menuitem" tabindex="-1" href="ftp_list.php?uf=<?php echo $uf; ?>"><?php echo $uf; ?></a></li>
                <?php } ?>
                </ul>
              </div>
            </div>
          </div>
          <?php } ?>
          <!-- User table -->
          <div class="form-group">
            <table class="table table-striped table-condensed sortable">
              <thead>
                <th class="hidden-xs hidden-sm" data-defaultsort="disabled"><span class="glyphicon glyphicon-tags" aria-hidden="true" title="FTP Name"></th>
                <th>UID</th>
                <th class="hidden-xs hidden-sm"><span class="glyphicon glyphicon-list-alt" aria-hidden="true" title="Login count"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-signal" aria-hidden="true" title="Uploaded MBs"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true" title="Uploaded MBs"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-signal" aria-hidden="true" title="Downloaded MBs"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true" title="Downloaded MBs"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-file" aria-hidden="true" title="Uploaded files"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true" title="Uploaded files"></th>
                <th class="hidden-xs"><span class="glyphicon glyphicon-file" aria-hidden="true" title="Downloaded files"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true" title="Downloaded files"></th>
                <th><span class="glyphicon glyphicon-user" aria-hidden="true" title="Login"></th>
                <th class="hidden-xs hidden-sm hidden-md"><span class="glyphicon glyphicon-time" aria-hidden="true" title="Create date"></th>
                <th data-defaultsort="disabled"></th>
              </thead>
              <tbody>
                <?php foreach ($users as $user) { ?>
                  <tr>

                    <td class="pull-middle"><a href="edit_ftp.php?action=show&<?php echo $field_id; ?>=<?php echo $user[$field_id]; ?>"><?php echo $user[$field_ftpname]; ?></a/></td>
                    <td class="pull-middle"><?php echo $user[$field_uid]; ?></td>
                    <td class="pull-middle hidden-xs hidden-sm"><?php echo $user[$field_login_count]; ?></td>
                    <td class="pull-middle hidden-xs"><?php echo sprintf("%2.1f", $user[$field_bytes_in_used] / 1048576); ?></td>
                    <td class="pull-middle hidden-xs"><?php echo sprintf("%2.1f", $user[$field_bytes_out_used] / 1048576); ?></td>
                    <td class="pull-middle hidden-xs"><?php echo $user[$field_files_in_used]; ?></td>
                    <td class="pull-middle hidden-xs"><?php echo $user[$field_files_out_used]; ?></td>
                    <td class="pull-middle"><?php echo $user[$field_login]; ?></td>
                    <td class="pull-middle hidden-xs hidden-sm hidden-md"><?php echo $user[$field_create_date]; ?></td>
                    <td class="pull-middle">
                      <div class="btn-toolbar pull-right" role="toolbar">
                        <a class="btn-group" role="group" href="edit_ftp.php?action=show&<?php echo $field_id; ?>=<?php echo $user[$field_id]; ?>"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
                        <a class="btn-group" role="group" href="remove_ftp.php?action=remove&<?php echo $field_id; ?>=<?php echo $user[$field_id]; ?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <!-- Actions -->
          <div class="form-group">
            <a class="btn btn-primary pull-right" href="create_ftp.php" role="button">Create FTP &raquo;</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<?php include ("includes/footer.php"); ?>
